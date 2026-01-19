<?php declare(strict_types=1);

namespace Learning\Bundle\Subscriber;

use Learning\Bundle\Service\CachedProductViewService;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CacheInvalidationSubscriber implements EventSubscriberInterface
{
    private CachedProductViewService $cachedService;

    public function __construct(CachedProductViewService $cachedService)
    {
        $this->cachedService = $cachedService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductEvents::PRODUCT_WRITTEN_EVENT => 'onProductWritten',
        ];
    }

    public function onProductWritten(EntityWrittenEvent $event): void
    {
        // When product changes, invalidate its view cache
        foreach ($event->getIds() as $productId) {
            $this->cachedService->invalidateProductCache($productId);
        }
    }
}