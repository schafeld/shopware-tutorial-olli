<?php declare(strict_types=1);

namespace Learning\Bundle\Subscriber;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Shopware\Core\System\StateMachine\Event\StateMachineStateChangeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'state_machine.order.state_changed' => 'onOrderStateChange',
        ];
    }

    public function onOrderStatechange(StateMachineStateChangeEvent $event): void
    {
        $fromState = $event -> getTransition()->getFromPlace()->getName();
        $toState = $event -> getTransition()->getToPlace()->getName(); // TODO: check if this is correct

        $this->logger->info(sprintf('Order state changed', [
            'order_id' => $event->getTransition()->getEntityId(),
            'from_state' => $fromState,
            'to_state' => $toState,
        ]));

        // Implement custom logic based on state transitions
        if ($toState === 'completed') {
            $this->handlerOrderCompletied($event);
        } elseif ($toState === 'cancelled') {
            $this->handleOrderCancelled($event);
        }
    }

    private function handlerOrderCompletied(StateMachineStateChangeEvent $event): void
    {
        // Custom logic for completed orders
        $this->logger->info('Order completed – triggering completion workflow', [
            'order_id' => $event->getTransition()->getEntityId(),
        ]);
    }

    private function handleOrderCancelled(StateMachineStateChangeEvent $event): void
    {
        // Example: Restore inventory, send cancellation email
        $this->logger->info('Order cancelled – triggering cancellation workflow', [
            'order_id' => $event->getTransition()->getEntityId(),
        ]);
    }


    
}