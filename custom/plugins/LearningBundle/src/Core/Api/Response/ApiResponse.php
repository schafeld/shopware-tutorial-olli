<?php declare (strict_types=1);

namespace Learning\Bundle\Core\Api\Response;

class ApiResponse
{
    public static function success($data, array $meta = []): array
    {
        return [
            'success' => true,
            'data' => $data,
            'meta' => array_merge([
                'timestamp' => (new \DateTime())->format(\DateTime::ATOM),
            ], $meta),
        ];
    }

    public static function error(string $message, int $code, array $details = []): array
    {
        return [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $code,
                'details' => $details,
            ],
            'meta' => [
                'timestamp' => (new \DateTime())->format(\DateTime::ATOM),
            ],
        ];
    }

    public static function paginated($data , int $total, int $limit, int $page): array
    {
        return [
            'success' => true,
            'data' => $data,
            'meta' => [
                'pagination' => [
                    'total' => $total,
                    'limit' => $limit,
                    'page' => $page,
                    'pages' => (int) ceil($total / $limit),
                ],
                'timestamp' => (new \DateTime())->format(\DateTime::ATOM),
            ],
        ];
    }

    public static function collection($data, array $meta = []): array
    {
        return [
            'success' => true,
            'data' => $data,
            'meta' => array_merge([
                'total' => count($data),
                'timestamp' => (new \DateTime())->format(\DateTime::ATOM),
            ], $meta),
        ];
    }
}
