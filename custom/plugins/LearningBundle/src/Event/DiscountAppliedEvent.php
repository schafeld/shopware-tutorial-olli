<?php declare(strict_types=1);

namespace Learning\Bundle\Event;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\ShopwareEvent;
use Symfony\Contracts\EventDispatcher\Event;

class DiscountAppliedEvent extends Event implements ShopwareEvent
{
    private string $discountCode;
    private float $discountAmount;
    private ?string $customerId;
    private string $orderId;
    private Context $context;
    private array $metadata = [];

    public function __construct(
        string $discountCode,
        float $discountAmount,
        ?string $customerId,
        string $orderId,
        Context $context,
        array $metadata = []
    ) {
        $this->discountCode = $discountCode;
        $this->discountAmount = $discountAmount;
        $this->customerId = $customerId;
        $this->orderId = $orderId;
        $this->context = $context;
        $this->metadata = $metadata;
    }

    public function getDiscountCode(): string
    {
        return $this->discountCode;
    }

    public function getDiscountAmount(): float
    {
        return $this->discountAmount;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getMetaData(): array
    {
        return $this->metadata;
    }

    public function setMetaData(array $metadata): void
    {
        $this->metadata = $metadata;
    }
}