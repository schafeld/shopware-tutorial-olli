<?php declare(strict_types=1);

namespace Learning\Bundle\Subscriber;

use Learning\Bundle\Service\ProductViewCounterService;
use Psr\Log\LoggerInterface;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductViewSubscriber implements EventSubscriberInterface
{
    private ProductViewCounterService $viewCounterService;
    private LoggerInterface $logger;

    public function __construct(
        ProductViewCounterService $viewCounterService,
        LoggerInterface $logger
    ) {
        $this->viewCounterService = $viewCounterService;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => 'onProductPageLoaded',
        ];
    }

    public function onProductPageLoaded(ProductPageLoadedEvent $event): void
    {
        $product = $event->getPage()->getProduct();

        if (!$product) {
            return;
        }

        $productId = $product->getId();

        try {
            $this->viewCounterService->recordView($productId);

            $this->logger->info('Product page viewed', [
                'product_id'=> $productId,
                'product_name' => $product->getName(),
                'cusomer_logged_in' => $event->getSalesChannelContext()->getCustomer() !== null,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to record product view', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}