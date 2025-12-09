<?php declare(strict_types=1);

namespace Learning\Bundle\Service\Gtm;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * GTM DataLayer Service
 * 
 * Builds Google Tag Manager dataLayer objects following GA4 Enhanced E-commerce schema.
 * This service handles all data formatting for tracking events.
 */
class GtmDataLayerService
{
    private SystemConfigService $systemConfigService;
    private LoggerInterface $logger;

    public function __construct(
        SystemConfigService $systemConfigService,
        LoggerInterface $logger
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
    }

    /**
     * Check if GTM tracking is enabled
     */
    public function isEnabled(?string $salesChannelId = null): bool
    {
        return (bool) $this->systemConfigService->get(
            'LearningBundle.config.gtmEnabled',
            $salesChannelId
        );
    }

    /**
     * Get the GTM Container ID
     */
    public function getContainerId(?string $salesChannelId = null): ?string
    {
        return $this->systemConfigService->get(
            'LearningBundle.config.gtmContainerId',
            $salesChannelId
        );
    }

    /**
     * Check if debug mode is enabled
     */
    public function isDebugMode(?string $salesChannelId = null): bool
    {
        return (bool) $this->systemConfigService->get(
            'LearningBundle.config.gtmDebugMode',
            $salesChannelId
        );
    }

    // =========================================================================
    // PAGE DATA BUILDERS
    // =========================================================================

    /**
     * Build base page data (included on every page)
     */
    public function buildPageData(
        string $pageType,
        SalesChannelContext $context,
        ?CustomerEntity $customer = null
    ): array {
        $salesChannelId = $context->getSalesChannelId();
        
        $data = [
            'page_type' => $pageType,
            'ecommerce' => [
                'currency' => $context->getCurrency()->getIsoCode(),
            ],
        ];

        // Add user data if logged in
        if ($customer !== null) {
            $data['user'] = $this->buildUserData($customer);
        } else {
            $data['user'] = [
                'logged_in' => false,
            ];
        }

        $this->logDebug('Built page data', ['page_type' => $pageType], $salesChannelId);

        return $data;
    }

    /**
     * Build user data (privacy-compliant)
     */
    public function buildUserData(CustomerEntity $customer): array
    {
        return [
            'user_id' => $this->hashUserId($customer->getId()),
            'customer_group' => $customer->getGroup()?->getName() ?? 'Guest',
            'logged_in' => true,
            // Hash email for privacy - useful for remarketing audiences
            'email_hash' => $this->hashEmail($customer->getEmail()),
        ];
    }

    // =========================================================================
    // PRODUCT DATA BUILDERS
    // =========================================================================

    /**
     * Build single product item data (for product detail page)
     * Note: Only extracts primitive values to avoid memory issues with lazy-loaded relations
     */
    public function buildProductItem(
        SalesChannelProductEntity|ProductEntity $product,
        int $index = 0,
        ?int $quantity = 1
    ): array {
        $item = [
            'item_id' => $product->getProductNumber(),
            'item_name' => $product->getTranslation('name') ?? $product->getName() ?? 'Unknown',
            'index' => $index,
            'quantity' => $quantity,
        ];

        // Add price - only access already loaded price data
        try {
            if ($product instanceof SalesChannelProductEntity && $product->getCalculatedPrice()) {
                $item['price'] = $product->getCalculatedPrice()->getUnitPrice();
            } elseif ($product->getPrice() && $product->getPrice()->first()) {
                $item['price'] = $product->getPrice()->first()->getGross();
            }
        } catch (\Throwable $e) {
            // Price not available
            $item['price'] = 0;
        }

        // Skip manufacturer, categories, and options to avoid lazy loading
        // These can cause memory issues - track them client-side if needed

        return $item;
    }

    /**
     * Build product list items (for category/listing pages)
     */
    public function buildProductListItems(
        EntitySearchResult $products,
        ?string $listId = null,
        ?string $listName = null,
        int $maxItems = 50
    ): array {
        $items = [];
        $index = 0;

        foreach ($products as $product) {
            if ($index >= $maxItems) {
                break;
            }

            // Type check to ensure we have a product entity
            if ($product instanceof SalesChannelProductEntity || $product instanceof ProductEntity) {
                $items[] = $this->buildProductItem($product, $index);
                $index++;
            }
        }

        $data = [
            'items' => $items,
        ];

        if ($listId) {
            $data['item_list_id'] = $listId;
        }

        if ($listName) {
            $data['item_list_name'] = $listName;
        }

        return $data;
    }

    /**
     * Build view_item event data
     */
    public function buildViewItemData(
        SalesChannelProductEntity|ProductEntity $product,
        string $currency
    ): array {
        $item = $this->buildProductItem($product);
        
        return [
            'event' => 'view_item',
            'ecommerce' => [
                'currency' => $currency,
                'value' => $item['price'] ?? 0,
                'items' => [$item],
            ],
        ];
    }

    /**
     * Build view_item_list event data
     */
    public function buildViewItemListData(
        EntitySearchResult $products,
        string $currency,
        ?CategoryEntity $category = null
    ): array {
        $listId = $category ? 'category_' . $category->getId() : 'product_list';
        $listName = $category 
            ? ($category->getTranslation('name') ?? $category->getName())
            : 'Product Listing';

        $listData = $this->buildProductListItems($products, $listId, $listName);
        
        return [
            'event' => 'view_item_list',
            'ecommerce' => array_merge(
                ['currency' => $currency],
                $listData
            ),
        ];
    }

    // =========================================================================
    // CART DATA BUILDERS
    // =========================================================================

    /**
     * Build cart items from Shopware cart
     */
    public function buildCartItems(Cart $cart): array
    {
        $items = [];
        $index = 0;

        foreach ($cart->getLineItems() as $lineItem) {
            if ($lineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                continue;
            }

            $items[] = $this->buildCartLineItem($lineItem, $index);
            $index++;
        }

        return $items;
    }

    /**
     * Build single cart line item
     */
    public function buildCartLineItem(LineItem $lineItem, int $index = 0): array
    {
        $item = [
            'item_id' => $lineItem->getPayload()['productNumber'] ?? $lineItem->getReferencedId(),
            'item_name' => $lineItem->getLabel(),
            'price' => $lineItem->getPrice()?->getUnitPrice() ?? 0,
            'quantity' => $lineItem->getQuantity(),
            'index' => $index,
        ];

        // Extract category from payload if available
        if (isset($lineItem->getPayload()['categoryIds']) && !empty($lineItem->getPayload()['categoryIds'])) {
            // Categories would need to be resolved separately for full names
            $item['item_category'] = 'Category'; // Placeholder
        }

        // Extract manufacturer/brand from payload
        if (isset($lineItem->getPayload()['manufacturerId'])) {
            // Manufacturer name would need to be resolved
            $item['item_brand'] = $lineItem->getPayload()['manufacturerName'] ?? '';
        }

        return $item;
    }

    /**
     * Build view_cart event data
     */
    public function buildViewCartData(Cart $cart, string $currency): array
    {
        $items = $this->buildCartItems($cart);
        $value = $cart->getPrice()->getTotalPrice();

        return [
            'event' => 'view_cart',
            'ecommerce' => [
                'currency' => $currency,
                'value' => $value,
                'items' => $items,
            ],
        ];
    }

    /**
     * Build add_to_cart event data
     */
    public function buildAddToCartData(
        LineItem $lineItem,
        string $currency,
        ?float $value = null
    ): array {
        $item = $this->buildCartLineItem($lineItem);
        
        return [
            'event' => 'add_to_cart',
            'ecommerce' => [
                'currency' => $currency,
                'value' => $value ?? ($item['price'] * $item['quantity']),
                'items' => [$item],
            ],
        ];
    }

    /**
     * Build remove_from_cart event data
     */
    public function buildRemoveFromCartData(
        LineItem $lineItem,
        string $currency,
        int $removedQuantity = 1
    ): array {
        $item = $this->buildCartLineItem($lineItem);
        $item['quantity'] = $removedQuantity;
        
        return [
            'event' => 'remove_from_cart',
            'ecommerce' => [
                'currency' => $currency,
                'value' => $item['price'] * $removedQuantity,
                'items' => [$item],
            ],
        ];
    }

    // =========================================================================
    // CHECKOUT DATA BUILDERS
    // =========================================================================

    /**
     * Build begin_checkout event data
     */
    public function buildBeginCheckoutData(
        Cart $cart,
        string $currency,
        ?string $coupon = null
    ): array {
        $items = $this->buildCartItems($cart);
        $value = $cart->getPrice()->getTotalPrice();

        $data = [
            'event' => 'begin_checkout',
            'ecommerce' => [
                'currency' => $currency,
                'value' => $value,
                'items' => $items,
            ],
        ];

        if ($coupon) {
            $data['ecommerce']['coupon'] = $coupon;
        }

        return $data;
    }

    /**
     * Build add_shipping_info event data
     */
    public function buildAddShippingInfoData(
        Cart $cart,
        string $currency,
        string $shippingTier
    ): array {
        $items = $this->buildCartItems($cart);
        
        return [
            'event' => 'add_shipping_info',
            'ecommerce' => [
                'currency' => $currency,
                'value' => $cart->getPrice()->getTotalPrice(),
                'shipping_tier' => $shippingTier,
                'items' => $items,
            ],
        ];
    }

    /**
     * Build add_payment_info event data
     */
    public function buildAddPaymentInfoData(
        Cart $cart,
        string $currency,
        string $paymentType
    ): array {
        $items = $this->buildCartItems($cart);
        
        return [
            'event' => 'add_payment_info',
            'ecommerce' => [
                'currency' => $currency,
                'value' => $cart->getPrice()->getTotalPrice(),
                'payment_type' => $paymentType,
                'items' => $items,
            ],
        ];
    }

    // =========================================================================
    // ORDER/PURCHASE DATA BUILDERS
    // =========================================================================

    /**
     * Build purchase event data from completed order
     */
    public function buildPurchaseData(OrderEntity $order): array
    {
        $items = $this->buildOrderLineItems($order->getLineItems());
        
        $data = [
            'event' => 'purchase',
            'ecommerce' => [
                'transaction_id' => $order->getOrderNumber(),
                'affiliation' => 'Online Store',
                'value' => $order->getAmountTotal(),
                'tax' => $order->getAmountTotal() - $order->getAmountNet(),
                'shipping' => $order->getShippingTotal(),
                'currency' => $order->getCurrency()?->getIsoCode() ?? 'EUR',
                'items' => $items,
            ],
        ];

        // Add coupon if present
        // Note: You'd need to extract this from order custom fields or promotions
        
        return $data;
    }

    /**
     * Build order line items
     */
    public function buildOrderLineItems(?OrderLineItemCollection $lineItems): array
    {
        if ($lineItems === null) {
            return [];
        }

        $items = [];
        $index = 0;

        foreach ($lineItems as $lineItem) {
            if ($lineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                continue;
            }

            $items[] = [
                'item_id' => $lineItem->getPayload()['productNumber'] ?? $lineItem->getIdentifier(),
                'item_name' => $lineItem->getLabel(),
                'price' => $lineItem->getUnitPrice(),
                'quantity' => $lineItem->getQuantity(),
                'index' => $index,
            ];
            $index++;
        }

        return $items;
    }

    // =========================================================================
    // SEARCH DATA BUILDERS
    // =========================================================================

    /**
     * Build search event data
     */
    public function buildSearchData(string $searchTerm, int $resultsCount): array
    {
        return [
            'event' => 'search',
            'search_term' => $searchTerm,
            'search_results_count' => $resultsCount,
        ];
    }

    // =========================================================================
    // AUTHENTICATION EVENT BUILDERS
    // =========================================================================

    /**
     * Build login event data
     */
    public function buildLoginData(string $method = 'email'): array
    {
        return [
            'event' => 'login',
            'method' => $method,
        ];
    }

    /**
     * Build sign_up event data
     */
    public function buildSignUpData(string $method = 'email'): array
    {
        return [
            'event' => 'sign_up',
            'method' => $method,
        ];
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Extract product categories as array of names
     */
    private function extractProductCategories(SalesChannelProductEntity|ProductEntity $product): array
    {
        $categories = [];

        if ($product->getCategories()) {
            foreach ($product->getCategories() as $category) {
                $name = $category->getTranslation('name') ?? $category->getName();
                if ($name) {
                    $categories[] = $name;
                }
            }
        }

        // If we have a main category in SEO URL, prefer that
        if ($product->getSeoCategory()) {
            $mainCategory = $product->getSeoCategory()->getTranslation('name') 
                ?? $product->getSeoCategory()->getName();
            
            // Put main category first
            array_unshift($categories, $mainCategory);
            $categories = array_unique($categories);
        }

        return array_slice($categories, 0, 5); // GA4 supports up to 5 category levels
    }

    /**
     * Hash user ID for privacy
     */
    private function hashUserId(string $userId): string
    {
        // Use a consistent but anonymized hash
        return substr(hash('sha256', 'gtm_user_' . $userId), 0, 16);
    }

    /**
     * Hash email for remarketing (privacy-compliant)
     */
    private function hashEmail(string $email): string
    {
        // Normalize and hash email per Google's requirements
        $normalizedEmail = strtolower(trim($email));
        return hash('sha256', $normalizedEmail);
    }

    /**
     * Log debug message if debug mode is enabled
     */
    private function logDebug(string $message, array $context = [], ?string $salesChannelId = null): void
    {
        if ($this->isDebugMode($salesChannelId)) {
            $this->logger->debug('[GTM] ' . $message, $context);
        }
    }

    /**
     * Convert dataLayer array to JSON for template
     */
    public function toJson(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
