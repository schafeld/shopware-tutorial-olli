<?php declare(strict_types=1);

namespace Learning\Bundle\Subscriber;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Event\BeforeLineItemAddedEvent;
use Shopware\Core\Checkout\Cart\Event\CartCreatedEvent;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CartSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CartCreatedEvent::class => 'onCartCreated',
            BeforeLineItemAddedEvent::class => 'onBeforeLineItemAdded',
        ];
    }

    public function onCartCreated(CartCreatedEvent $event): void
    {
        $this->logger->info('New cart created', [
            'cart_token' => $event->getCart()->getToken(),
        ]);
    }

    public function onBeforeLineItemAdded(BeforeLineItemAddedEvent $event): void
    {
        $lineItem = $event->getLineItem();

        $this->logger->info('Item being added to cart', [
            // 'cart_token' => $event->getCart()->getToken(),
            'product_id' => $lineItem->getReferencedId(),
            'quantity' => $lineItem->getQuantity(),
            'type' => $lineItem->getType(),
        ]);

        // You can modify or validate the line item here
        // Example: Apply business rules, check stock, add free items

        if ($lineItem->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE) {
            // Add custom payload or modify quantity based on rules
            $lineItem->setPayloadValue('added_by_plugin', true);
        }
    }
}