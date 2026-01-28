<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\Recommendation;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ProductRecommendationEntity extends Entity
{
    use EntityIdTrait;

    protected string $sourceProductId;
    protected string $sourceProductVersionId;
    protected string $recommendedProductId;
    protected string $recommendedProductVersionId;
    protected float $affinityScore;
    protected int $viewCount;
    protected \DateTimeInterface $lastUpdated;
    protected ?ProductEntity $sourceProduct = null;
    protected ?ProductEntity $recommendedProduct = null;

    public function getSourceProductId(): string
    {
        return $this->sourceProductId;
    }

    public function setSourceProductId(string $sourceProductId): void
    {
        $this->sourceProductId = $sourceProductId;
    }

    public function getSourceProductVersionId(): string
    {
        return $this->sourceProductVersionId;
    }

    public function setSourceProductVersionId(string $sourceProductVersionId): void
    {
        $this->sourceProductVersionId = $sourceProductVersionId;
    }

    public function getRecommendedProductId(): string
    {
        return $this->recommendedProductId;
    }

    public function setRecommendedProductId(string $recommendedProductId): void
    {
        $this->recommendedProductId = $recommendedProductId;
    }

    public function getRecommendedProductVersionId(): string
    {
        return $this->recommendedProductVersionId;
    }

    public function setRecommendedProductVersionId(string $recommendedProductVersionId): void
    {
        $this->recommendedProductVersionId = $recommendedProductVersionId;
    }

    public function getAffinityScore(): float
    {
        return $this->affinityScore;
    }

    public function setAffinityScore(float $affinityScore): void
    {
        $this->affinityScore = $affinityScore;
    }

    public function getViewCount(): int
    {
        return $this->viewCount;
    }

    public function setViewCount(int $viewCount): void
    {
        $this->viewCount = $viewCount;
    }

    public function getLastUpdated(): \DateTimeInterface
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated(\DateTimeInterface $lastUpdated): void
    {
        $this->lastUpdated = $lastUpdated;
    }

    public function getSourceProduct(): ?ProductEntity
    {
        return $this->sourceProduct;
    }

    public function setSourceProduct(?ProductEntity $sourceProduct): void
    {
        $this->sourceProduct = $sourceProduct;
    }

    public function getRecommendedProduct(): ?ProductEntity
    {
        return $this->recommendedProduct;
    }

    public function setRecommendedProduct(?ProductEntity $recommendedProduct): void
    {
        $this->recommendedProduct = $recommendedProduct;
    }
}