<?php declare(strict_types=1);

namespace Learning\Bundle\Subscriber\Gtm;

use Learning\Bundle\Service\Gtm\GtmDataLayerService;
use Psr\Log\LoggerInterface;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Shopware\Storefront\Page\Navigation\NavigationPageLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Shopware\Storefront\Page\Search\SearchPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * GTM Page Subscriber
 * 
 * Listens to Shopware page events and attaches GTM dataLayer data
 * to page extensions for rendering in Twig templates.
 */
class GtmPageSubscriber implements EventSubscriberInterface
{
    private GtmDataLayerService $gtmService;
    private LoggerInterface $logger;

    public function __construct(
        GtmDataLayerService $gtmService,
        LoggerInterface $logger
    ) {
        $this->gtmService = $gtmService;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Product Detail Page
            ProductPageLoadedEvent::class => 'onProductPageLoaded',
            
            // Category/Navigation Page
            NavigationPageLoadedEvent::class => 'onNavigationPageLoaded',
            
            // Note: ProductListingResultEvent and ProductSearchResultEvent are disabled
            // to avoid memory issues with large product lists. The view_item_list event
            // can be tracked client-side via the GTM JS plugin instead.
            
            // Search Results Page
            SearchPageLoadedEvent::class => 'onSearchPageLoaded',
            
            // Cart Page
            CheckoutCartPageLoadedEvent::class => 'onCartPageLoaded',
            
            // Checkout Confirm Page
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirmPageLoaded',
            
            // Order Confirmation Page
            CheckoutFinishPageLoadedEvent::class => 'onCheckoutFinishPageLoaded',
        ];
    }

    /**
     * Product Detail Page - view_item event
     */
    public function onProductPageLoaded(ProductPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        
        if (!$this->gtmService->isEnabled($salesChannelId)) {
            return;
        }

        $page = $event->getPage();
        $product = $page->getProduct();
        $context = $event->getSalesChannelContext();
        $customer = $context->getCustomer();
        $currency = $context->getCurrency()->getIsoCode();

        // Build base page data
        $pageData = $this->gtmService->buildPageData('product', $context, $customer);

        // Build view_item event data
        $viewItemData = $this->gtmService->buildViewItemData($product, $currency);

        // Combine for the dataLayer
        $dataLayer = [
            'pageData' => $pageData,
            'events' => [$viewItemData],
        ];

        // Add to page extensions for Twig access
        $page->addExtension('gtmDataLayer', new GtmDataLayerExtension($dataLayer));

        $this->logger->debug('[GTM] Product page loaded', [
            'product_id' => $product->getId(),
            'product_number' => $product->getProductNumber(),
        ]);
    }

    /**
     * Navigation/Category Page
     */
    public function onNavigationPageLoaded(NavigationPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        
        if (!$this->gtmService->isEnabled($salesChannelId)) {
            return;
        }

        $page = $event->getPage();
        $context = $event->getSalesChannelContext();
        $customer = $context->getCustomer();

        // Determine page type
        $pageType = 'category';
        $cmsPage = $page->getCmsPage();
        if ($cmsPage && $cmsPage->getType() === 'landingpage') {
            $pageType = 'home';
        }

        // Build base page data
        $pageData = $this->gtmService->buildPageData($pageType, $context, $customer);

        $dataLayer = [
            'pageData' => $pageData,
            'events' => [],
        ];

        $page->addExtension('gtmDataLayer', new GtmDataLayerExtension($dataLayer));
    }

    /**
     * Product Listing Result - view_item_list event
     * This fires for category pages and other listing scenarios
     */
    public function onProductListingResult(ProductListingResultEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        
        if (!$this->gtmService->isEnabled($salesChannelId)) {
            return;
        }

        $result = $event->getResult();
        $context = $event->getSalesChannelContext();
        $currency = $context->getCurrency()->getIsoCode();

        // Get current category if available
        $category = null;
        if (method_exists($result, 'getCurrentFilter') && $result->getCurrentFilter('navigationId')) {
            // Category would need to be loaded separately if full data is needed
        }

        // Build view_item_list data
        $viewItemListData = $this->gtmService->buildViewItemListData(
            $result,
            $currency,
            $category
        );

        // Store in result extensions
        $result->addExtension('gtmViewItemList', new GtmDataLayerExtension([
            'events' => [$viewItemListData],
        ]));
    }

    /**
     * Search Page Loaded
     */
    public function onSearchPageLoaded(SearchPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        
        if (!$this->gtmService->isEnabled($salesChannelId)) {
            return;
        }

        $page = $event->getPage();
        $context = $event->getSalesChannelContext();
        $customer = $context->getCustomer();

        // Build base page data
        $pageData = $this->gtmService->buildPageData('search', $context, $customer);

        $dataLayer = [
            'pageData' => $pageData,
            'events' => [],
        ];

        $page->addExtension('gtmDataLayer', new GtmDataLayerExtension($dataLayer));
    }

    /**
     * Product Search Result - search event + view_item_list
     */
    public function onProductSearchResult(ProductSearchResultEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        
        if (!$this->gtmService->isEnabled($salesChannelId)) {
            return;
        }

        $result = $event->getResult();
        $context = $event->getSalesChannelContext();
        $currency = $context->getCurrency()->getIsoCode();
        $request = $event->getRequest();

        $searchTerm = $request->get('search', '');
        $resultsCount = $result->getTotal();

        // Build search event
        $searchData = $this->gtmService->buildSearchData($searchTerm, $resultsCount);

        // Build view_item_list for search results
        $listData = $this->gtmService->buildProductListItems(
            $result,
            'search_results',
            'Search: ' . $searchTerm
        );

        $viewItemListData = [
            'event' => 'view_item_list',
            'ecommerce' => array_merge(
                ['currency' => $currency],
                $listData
            ),
        ];

        // Store in result extensions
        $result->addExtension('gtmSearchData', new GtmDataLayerExtension([
            'events' => [$searchData, $viewItemListData],
        ]));

        $this->logger->debug('[GTM] Search performed', [
            'search_term' => $searchTerm,
            'results_count' => $resultsCount,
        ]);
    }

    /**
     * Cart Page - view_cart event
     */
    public function onCartPageLoaded(CheckoutCartPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        
        if (!$this->gtmService->isEnabled($salesChannelId)) {
            return;
        }

        $page = $event->getPage();
        $cart = $page->getCart();
        $context = $event->getSalesChannelContext();
        $customer = $context->getCustomer();
        $currency = $context->getCurrency()->getIsoCode();

        // Build base page data
        $pageData = $this->gtmService->buildPageData('cart', $context, $customer);

        // Build view_cart event
        $viewCartData = $this->gtmService->buildViewCartData($cart, $currency);

        $dataLayer = [
            'pageData' => $pageData,
            'events' => [$viewCartData],
        ];

        $page->addExtension('gtmDataLayer', new GtmDataLayerExtension($dataLayer));

        $this->logger->debug('[GTM] Cart page loaded', [
            'cart_value' => $cart->getPrice()->getTotalPrice(),
            'items_count' => $cart->getLineItems()->count(),
        ]);
    }

    /**
     * Checkout Confirm Page - begin_checkout event
     */
    public function onCheckoutConfirmPageLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        
        if (!$this->gtmService->isEnabled($salesChannelId)) {
            return;
        }

        $page = $event->getPage();
        $cart = $page->getCart();
        $context = $event->getSalesChannelContext();
        $customer = $context->getCustomer();
        $currency = $context->getCurrency()->getIsoCode();

        // Build base page data
        $pageData = $this->gtmService->buildPageData('checkout', $context, $customer);

        // Build begin_checkout event
        // TODO: Extract coupon code if applied
        $coupon = null;
        $beginCheckoutData = $this->gtmService->buildBeginCheckoutData($cart, $currency, $coupon);

        // Build shipping info event
        $shippingMethod = $context->getShippingMethod();
        $shippingData = $this->gtmService->buildAddShippingInfoData(
            $cart,
            $currency,
            $shippingMethod->getTranslation('name') ?? $shippingMethod->getName()
        );

        // Build payment info event
        $paymentMethod = $context->getPaymentMethod();
        $paymentData = $this->gtmService->buildAddPaymentInfoData(
            $cart,
            $currency,
            $paymentMethod->getTranslation('name') ?? $paymentMethod->getName()
        );

        $dataLayer = [
            'pageData' => $pageData,
            'events' => [$beginCheckoutData, $shippingData, $paymentData],
        ];

        $page->addExtension('gtmDataLayer', new GtmDataLayerExtension($dataLayer));

        $this->logger->debug('[GTM] Checkout confirm page loaded', [
            'cart_value' => $cart->getPrice()->getTotalPrice(),
            'shipping' => $shippingMethod->getName(),
            'payment' => $paymentMethod->getName(),
        ]);
    }

    /**
     * Order Confirmation Page - purchase event
     */
    public function onCheckoutFinishPageLoaded(CheckoutFinishPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        
        if (!$this->gtmService->isEnabled($salesChannelId)) {
            return;
        }

        $page = $event->getPage();
        $order = $page->getOrder();
        $context = $event->getSalesChannelContext();
        $customer = $context->getCustomer();

        // Build base page data
        $pageData = $this->gtmService->buildPageData('confirmation', $context, $customer);

        // Build purchase event
        $purchaseData = $this->gtmService->buildPurchaseData($order);

        $dataLayer = [
            'pageData' => $pageData,
            'events' => [$purchaseData],
        ];

        $page->addExtension('gtmDataLayer', new GtmDataLayerExtension($dataLayer));

        $this->logger->info('[GTM] Purchase tracked', [
            'order_number' => $order->getOrderNumber(),
            'order_value' => $order->getAmountTotal(),
            'currency' => $order->getCurrency()?->getIsoCode(),
        ]);
    }
}
