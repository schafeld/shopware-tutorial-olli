<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Api\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class InvalidAnalyticsRequestException extends ShopwareHttpException
{
    public function __construct(array $errors)
    {
        parent::__construct(
            'Invalid request parameters: {{ errors }}',
            ['errors' => json_encode($errors)]
        );
    }
    public function getErrorCode(): string
    {
        return 'LEARNING__INVALID_ANALYTICS_REQUEST';
    }
    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}