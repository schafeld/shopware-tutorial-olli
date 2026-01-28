<?php declare(strict_types=1);

namespace Learning\Bundle\Subscriber;

use Learning\Bundle\Service\ProductRecommendationTrackingService;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class RecommendationTrackingSubscriber implements EventSubscriberInterface
{
    private ProductRecommendationTrackingService $trackingService;
    private RequestStack $requestStack;

    public function __construct(
        ProductRecommendationTrackingService $trackingService,
        RequestStack $requestStack
    ) {
        $this->trackingService = $trackingService;
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => 'onProductPageLoaded',
        ];
    }

    public function onProductPageLoaded(ProductPageLoadedEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        
        if (!$request) {
            return;
        }

        $session = $request->getSession();
        $sessionId = $session->getId();
        $productId = $event->getPage()->getProduct()->getId();

        // Track the view
        $this->trackingService->trackProductView(
            $sessionId,
            $productId,
            $event->getContext()
        );
    }
}