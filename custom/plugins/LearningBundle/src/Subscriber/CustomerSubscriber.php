<?php declare(strict_types=1);

namespace Learning\Bundle\Subscriber;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Customer\Event\CustomerLoginEvent;
use Shopware\Core\Checkout\Customer\Event\CustomerLogoutEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CustomerSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Returns an array of events this subscriber wants to listen to
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CustomerLoginEvent::class => 'onCustomerLogin',
            CustomerLogoutEvent::class => 'onCustomerLogout',
        ];
    }

    /**
     * Called when a customer logs in
     */
    public function onCustomerLogin(CustomerLoginEvent $event): void
    {
        $customer = $event->getCustomer();

        $this->logger->info('Customer logged in', [
            'customer_id' => $customer->getId(),
            'email' => $customer->getEmail(),
            'first_name' => $customer->getFirstName(),
            'last_name' => $customer->getLastName(),
        ]);

        // You can add custom logic here:
        // - Track login times
        // - Send notifications
        // - Update customer data
        // - Trigger third-party integrations
    }

    /**
     * Called when a customer logs out
     */
    public function onCustomerLogout(CustomerLogoutEvent $event): void
    {
        $customer = $event->getCustomer();

        $this->logger->info('Customer logged out', [
            'customer_id' => $customer->getId(),
            'email' => $customer->getEmail(),
        ]);
    }
}
