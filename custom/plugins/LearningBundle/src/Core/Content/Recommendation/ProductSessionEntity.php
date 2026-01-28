<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\Recommendation;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ProductSessionEntity extends Entity
{
    use EntityIdTrait;

    protected string $sessionId;
    protected string $productId;
    protected \DateTimeInterface $viewedAt;
    protected ?ProductEntity $product = null;

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function setSessionId(string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getProductVersionId(): ?string
    {
        return $this->getProductVersionId();
    }

    public function getViewedAt(): \DateTimeInterface
    {
        return $this->viewedAt;
    }

    public function setViewedAt(\DateTimeInterface $viewedAt): void
    {
        $this->viewedAt = $viewedAt;
    }

    public function getProduct(): ?ProductEntity
    {
        return $this->product;
    }

    public function setProduct(?ProductEntity $product): void
    {
        $this->product = $product;
    }
}