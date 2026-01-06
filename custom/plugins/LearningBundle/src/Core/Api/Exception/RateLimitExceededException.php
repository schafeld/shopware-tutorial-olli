<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Api\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class RateLimitExceededException extends ShopwareHttpException
{
    public function __construct(int $retryAfter)
    {
        parent::__construct(
            'Rate limit exceeded. Try again in {{ seconds }} seconds.',
            ['seconds' => $retryAfter]
        );
    }

    public function getErrorCode(): string
    {
        return 'LEARNING__RATE_LIMIT_EXCEEDED';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_TOO_MANY_REQUESTS;
    }
}