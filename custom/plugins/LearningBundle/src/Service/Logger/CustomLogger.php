<?php declare(strict_types=1);

// https://github.com/schafeld/shopware-tutorial-olli/blob/onboarding-day-5-debugging/AI_DOCUMENTS/LEARNING_SHOPWARE/DAY_5_DEBUGGING.md

namespace Learning\Bundle\Service\Logger;

use Psr\Log\LoggerInterface;

class CustomLogger
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function logPerformance(string $operation, float $startTime, array $context = []): void
    {
        $duration = microtime(true) - $startTime;

        $level = $duration > 1.0 ? 'warning' : 'info';

        $this -> logger->log($level, sprintf('Performance: %s took %.2f ms', $operation, $duration * 1000), array_merge([
            'duration_ms' => $duration * 1000,
            'operation' => $operation,
        ], $context));
    }

    public function logDatabaseQuery(string $query, array $params, float $executionTime): void
    {
        // Log slow queries taking more than 100ms
        if ($executionTime > 0.1) {
            $this->logger->warning('Slow database query detected', [
                'query' => $query,
                'params' => $params,
                'execution_time_ms' => $executionTime * 1000,
            ]);
        }
    }

    public function logUserAction(string $action, ?string $userId, array $data = []): void
    {
        $this->logger->info('User action logged', [
            'action' => $action,
            'user_id' => $userId ?? 'guest',
            'data' => $data,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
    }
}