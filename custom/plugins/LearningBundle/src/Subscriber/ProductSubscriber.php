<?php declare(strict_types=1);

namespace Learning\Bundle\Subscriber;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Framework\DataAbstarctionLayer\Event\EntityWrittenEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductEvents::PRODUCT_WRITTEN_EVENT => "onProductWritten",
            ];
    }

    /**
     * Called when a product is created or updated
     */
    public function onProductWritten(EntityWrittenEvent $event): void
    {
        foreach ($event->getWriteResults() as $writeResult) {
            
            $payload = $writeResult->getPayload();

            $this->logger->info('Product modified', [
                'product_id' => $writeResult -> getPrimaryKey(),
                'loperation' => $writeResult -> getOperation(),
                'product_name' => $payload['name'] ?? 'N/A',
            ]);

            // Custom logic examples:
            // - Sync with external systems
            // - Update search indices
            // - Trigger cache invalidation
            // - Send notifications to admins
        }
    }

}