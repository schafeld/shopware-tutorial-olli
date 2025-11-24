<?php declare(strict_types=1);

namespace Learning\Bundle\Subscriber;

use Learning\Bundle\Event\DiscountAppliedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DiscountSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DiscountAppliedEvent::class => [
                ['logDiscountApplication', 100],
                ['checkDiscountThreshold', 50],
                ['enrichMetadata', 10],
            ]
        ];
    }

    /**
     * Log every discount application
     */
    public function logDiscountApplication(DiscountAppliedEvent $event): void
    {
        $this->logger->info('Discount application logged by subscriber', [
            'discount'=> $event->getDiscountCode(),
            'amount' => $event->getDiscountAmount(),
            'customer_id' => $event->getCustomerId() ?? 'guest',
            'order_id' => $event->getOrderId(),
        ]);
    }

    /**
     * Check if discount exceeds threshold and log warning
     */
    public function checkDiscountThreshold(DiscountAppliedEvent $event): void
    {
        $threshold = 100.0; // Example threshold 100,- €

        if ($event->getDiscountAmount() > $threshold) {
            $this->logger->warning('Large discount applied', [
                'code' => $event->getDiscountCode(),
                'amount' => $event->getDiscountAmount(),
                'threshold' => $threshold,
                'customer_id' => $event->getCustomerId()
            ]);

            // Could trigger additional actions here, e.g., notify admin
        }
    }

    /**
     * Enrich event metadata with additional information
     */
    public function enrichMetadata(DiscountAppliedEvent $event): void
    {
        $metadata = $event->getMetaData();
        
        $metadata['processed_by_subscriber'] = true;
        $metadata['subscriber_timestamp'] = date('Y-m-d H:i:s');
        $metadata['is_large_discount'] = $event->getDiscountAmount() > 50.0;

        $event->setMetaData($metadata);

        $this->logger->debug('Discount metadata enriched', [
            'code' => $event->getDiscountCode(),
            'metadata' => $metadata,
        ]);
    }
}