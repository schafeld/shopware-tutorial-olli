<?php declare(strict_types= 1);

namespace Learning\Bundle\Service\ProductInfo;

interface ProductInfoServiceInterface
{
    public function getInfo(string $productId): string;
}