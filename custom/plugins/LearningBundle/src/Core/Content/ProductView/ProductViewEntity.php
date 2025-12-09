<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\ProductView;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ProductViewEntity extends Entity
{
    use EntityIdTrait;

    protected string $productId;
    protected ?string $customerId;
    protected int $viewCount;
    protected ?string $puserAgent;
    protected \DateTimeInterface $lastViewedAt;

    // Associations
    protected ?ProductEntity $product = null;
    protected ?CustomerEntity $customer = null;

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function setCustomerId(?string $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function getViewCount(): int
    {
        return $this->viewCount;
    }

    public function setViewCount(int $viewCount): void
    {
        $this->viewCount = $viewCount;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    public function getLastViewedAt(): \DateTimeInterface
    {
        return $this->lastViewedAt;
    }

    public function setLastViewedAt(\DateTimeInterface $lastViewedAt): void
    {
        $this->lastViewedAt = $lastViewedAt;
    }

    public function getProduct(): ?ProductEntity
    {
        return $this->product;
    }

    public function setProduct(?ProductEntity $product): void
    {
        $this->product = $product;
    }

    public function getCustomer() : ?CustomerEntity
    {
        return $this->customer;
    }

    public function setCustomer(?CustomerEntity $customer): void
    {
        $this->customer = $customer;
    }
}