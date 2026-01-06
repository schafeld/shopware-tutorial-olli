<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\ProductView\SalesChannel;

use Shopware\Core\Framework\Struct\Struct;

class ProductViewResult extends Struct
{
    protected string $productId;
    protected int $viewCount;

    public function __construct(string $productId, int $viewCount)
    {
        $this->productId = $productId;
        $this->viewCount = $viewCount;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getViewCount(): int
    {
        return $this->viewCount;
    }
}