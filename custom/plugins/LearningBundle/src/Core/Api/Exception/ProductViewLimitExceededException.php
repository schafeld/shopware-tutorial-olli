<?php declare (strict_types=1);

namespace Learning\Bundle\Core\Api\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class ProductViewLimitExceededException extends ShopwareHttpException
{
    public function __construct(int $limit, int $requested)
    {
        parent::__construct(
            'Requested limit {{ requested }} exceeds the maximum allowed limit of {{ limit }}.',
            ['requested' => $requested, 'limit' => $limit]
        );
    }
    
    public function getErrorCode(): string
    {
        return 'LEARNING__PRODUCT_VIEW_LIMIT_EXCEEDED';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}