<?php declare(strict_types=1);

namespace Learning\Bundle\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class ProductViewException extends ShopwareHttpException
{
    public const PRODUCT_NOT_FOUND = 'LEARNING__PRODUCT_NOT_FOUND';
    public const INVALID_VIEW_DATA = 'LEARNING__INVALID_VIEW_DATA';
    public const DATABASE_ERROR = 'LEARNING__DATABASE_ERROR';

    public static function productNotFound(string $productId): self
    {
        return new self(
            sprintf('Product with ID %s was not found.', $productId),
            ['productId' => $productId]
        );
    }

    public static function invalidViewData(string $reason): self
    {
        return new self(
            sprintf('Invalid product view data: %s', $reason),
            ['reason' => $reason]
        );
    }

    public static function databaseError(\Throwable $previous): self
    {
        return new self(
            'Database operation failed: {{ message }}',
            ['message' => $previous->getMessage()],
            $previous
        );
    }

    public function getErrorCode(): string
    {
        return $this->parameters['code'] ?? self::DATABASE_ERROR;
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }
}