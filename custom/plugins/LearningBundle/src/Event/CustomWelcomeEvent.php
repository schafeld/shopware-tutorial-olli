<?php declare(strict_types= 1);

namespace Learning\Bundle\Event;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\ShopwareEvent;
use Symfony\Contracts\EventDispatcher\Event;

class CustomWelcomeEvent extends Event implements ShopwareEvent
{
    private string $customerName;
    private string $message;
    private Context $context;

    public function __construct(
        string $customerName, 
        string $message, 
        Context $context)
    {
        $this->customerName = $customerName;
        $this->message = $message;
        $this->context = $context;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}