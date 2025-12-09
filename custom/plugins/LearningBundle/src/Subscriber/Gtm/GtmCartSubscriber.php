<?php declare(strict_types=1);

namespace Learning\Bundle\Subscriber\Gtm;

use Learning\Bundle\Service\Gtm\GtmDataLayerService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Event\AfterLineItemAddedEvent;
use Shopware\Core\Checkout\Cart\Event\AfterLineItemRemovedEvent;
use Shopware\Core\Checkout\Cart\Event\AfterLineItemQuantityChangedEvent;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Customer\Event\CustomerLoginEvent;
use Shopware\Core\Checkout\Customer\Event\CustomerRegisterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * GTM Cart & Auth Subscriber
 * 
 * Handles cart modification events and authentication events.
 * These events are stored in session flash data to be rendered
 * on the next page load (since cart changes are typically AJAX).
 */
class GtmCartSubscriber implements EventSubscriberInterface
{
    private GtmDataLayerService $gtmService;
    private LoggerInterface $logger;
    private RequestStack $requestStack;

    public function __construct(
        GtmDataLayerService $gtmService,
        LoggerInterface $logger,
        RequestStack $requestStack
    ) {
        $this->gtmService = $gtmService;
        $this->logger = $logger;
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Cart Events
            AfterLineItemAddedEvent::class => 'onLineItemAdded',
            AfterLineItemRemovedEvent::class => 'onLineItemRemoved',
            AfterLineItemQuantityChangedEvent::class => 'onLineItemQuantityChanged',
            
            // Auth Events
            CustomerLoginEvent::class => 'onCustomerLogin',
            CustomerRegisterEvent::class => 'onCustomerRegister',
        ];
    }

    /**
     * Handle item added to cart
     */
    public function onLineItemAdded(AfterLineItemAddedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        
        if (!$this->gtmService->isEnabled($salesChannelId)) {
            return;
        }

        $cart = $event->getCart();
        $currency = $event->getSalesChannelContext()->getCurrency()->getIsoCode();

        foreach ($event->getLineItems() as $lineItem) {
            if ($lineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                continue;
            }

            // Find the line item in cart to get full data
            $cartLineItem = $cart->getLineItems()->get($lineItem->getId());
            
            if ($cartLineItem) {
                $addToCartData = $this->gtmService->buildAddToCartData(
                    $cartLineItem,
                    $currency
                );

                $this->storeFlashEvent($addToCartData);

                $this->logger->debug('[GTM] Add to cart event queued', [
                    'product_id' => $lineItem->getReferencedId(),
                    'quantity' => $lineItem->getQuantity(),
                ]);
            }
        }
    }

    /**
     * Handle item removed from cart
     */
    public function onLineItemRemoved(AfterLineItemRemovedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        
        if (!$this->gtmService->isEnabled($salesChannelId)) {
            return;
        }

        $currency = $event->getSalesChannelContext()->getCurrency()->getIsoCode();

        foreach ($event->getLineItems() as $lineItem) {
            if ($lineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                continue;
            }

            $removeFromCartData = $this->gtmService->buildRemoveFromCartData(
                $lineItem,
                $currency,
                $lineItem->getQuantity()
            );

            $this->storeFlashEvent($removeFromCartData);

            $this->logger->debug('[GTM] Remove from cart event queued', [
                'product_id' => $lineItem->getReferencedId(),
                'quantity' => $lineItem->getQuantity(),
            ]);
        }
    }

    /**
     * Handle quantity change
     */
    public function onLineItemQuantityChanged(AfterLineItemQuantityChangedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        
        if (!$this->gtmService->isEnabled($salesChannelId)) {
            return;
        }

        $cart = $event->getCart();
        $currency = $event->getSalesChannelContext()->getCurrency()->getIsoCode();

        foreach ($event->getItems() as $itemData) {
            $lineItemId = $itemData['id'];
            $oldQuantity = $itemData['oldQuantity'] ?? 0;
            $newQuantity = $itemData['newQuantity'] ?? 0;

            $lineItem = $cart->getLineItems()->get($lineItemId);
            
            if (!$lineItem || $lineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                continue;
            }

            $quantityDiff = $newQuantity - $oldQuantity;

            if ($quantityDiff > 0) {
                // Quantity increased = add_to_cart
                $lineItemCopy = clone $lineItem;
                $lineItemCopy->setQuantity($quantityDiff);
                
                $eventData = $this->gtmService->buildAddToCartData($lineItemCopy, $currency);
                $this->storeFlashEvent($eventData);
            } elseif ($quantityDiff < 0) {
                // Quantity decreased = remove_from_cart
                $eventData = $this->gtmService->buildRemoveFromCartData(
                    $lineItem,
                    $currency,
                    abs($quantityDiff)
                );
                $this->storeFlashEvent($eventData);
            }
        }
    }

    /**
     * Handle customer login
     */
    public function onCustomerLogin(CustomerLoginEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        
        if (!$this->gtmService->isEnabled($salesChannelId)) {
            return;
        }

        $loginData = $this->gtmService->buildLoginData('email');
        $this->storeFlashEvent($loginData);

        $this->logger->debug('[GTM] Login event queued');
    }

    /**
     * Handle customer registration
     */
    public function onCustomerRegister(CustomerRegisterEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        
        if (!$this->gtmService->isEnabled($salesChannelId)) {
            return;
        }

        $signUpData = $this->gtmService->buildSignUpData('email');
        $this->storeFlashEvent($signUpData);

        $this->logger->debug('[GTM] Sign up event queued');
    }

    /**
     * Store event in session flash for next page render
     * 
     * Since cart changes happen via AJAX, we store events in session
     * to be rendered on the subsequent page load or via JavaScript callback.
     */
    private function storeFlashEvent(array $eventData): void
    {
        $request = $this->requestStack->getCurrentRequest();
        
        if (!$request || !$request->hasSession()) {
            return;
        }

        $session = $request->getSession();
        
        // Get existing flash events or initialize empty array
        $flashEvents = $session->get('gtm_flash_events', []);
        $flashEvents[] = $eventData;
        
        // Store back in session (not as flash, so we can accumulate)
        $session->set('gtm_flash_events', $flashEvents);
    }

    /**
     * Clear and return flash events (called from Twig or service)
     */
    public static function consumeFlashEvents(SessionInterface $session): array
    {
        $events = $session->get('gtm_flash_events', []);
        $session->remove('gtm_flash_events');
        return $events;
    }
}
