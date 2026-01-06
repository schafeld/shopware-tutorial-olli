<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Api\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class ProductViewNotFoundException extends ShopwareHttpException
{
    public function __construct(string $productId)
    {
        parent::__construct(
            'Product view data for product with ID "{{ productId }}" not found.',
            ['productId' => $productId]
        );
    }

    public function getErrorCode(): string
    {
        return 'LEARNING__PRODUCT_VIEW_NOT_FOUND';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}