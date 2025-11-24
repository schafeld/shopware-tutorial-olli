<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Psr\Log\LoggerInterface;

class ProductViewCounterService
{
    private const STORAGE_FILE = 'var/product_views.json';

    private LoggerInterface $logger;
    private string $filePath;

    public function __construct(LoggerInterface $logger, string $projectDir)
    {
        $this->logger = $logger;
        $this->filePath = $projectDir . '/' . self::STORAGE_FILE;
    }

    /**
     * Record a product view
     */
    public function recordView(string $productId): void
    {
        $views = $this->loadViews();

        if (!isset($views[$productId])) {
            $views[$productId] = [
                'count' => 0,
                'first_viewed' => date('Y-m-d H:i:s'),
                'last_viewed' => null,
            ];
        }

        $views[$productId]['count']++;
        $views[$productId]['last_viewed'] = date('Y-m-d H:i:s');

        $this->saveViews($views);

        $this->logger->info('Product view recorded', [
            'product_id' => $productId,
            'total_views' => $views[$productId]['count'],
        ]);
    }

    /**
     * Get view count for a specific product
     */
    public function getViewCount(string $productId): int
    {
        $views = $this->loadViews();
        return $views[$productId]['count'] ?? 0;
    }

    /**
     * Get all product views
     */
    public function getAllViews(): array
    {
        return $this->loadViews();
    }

    /**
     * Get top N most viewed products
     */
    public function getTopViewedProducts(int $limit = 10): array
    {
        $views = $this->loadViews();

        // sort by view count descending
        uasort($views, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return array_slice($views, 0, $limit, true);
    }

    /**
     * Load views from file
     */
    private function loadViews(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }

        $content = file_get_contents($this->filePath);
        if ($content === false) {
            $this->logger->error('Failed to read product views file');
            return [];
        }

        $views = json_decode($content, true);
        if (Json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('Failed to decode product views JSON', [
                'error' => json_last_error_msg(),
            ]);
            return [];
        }

        return $views;
    }

    /**
     * Save views to file
     */
    private function saveViews(array $views): void
    {
        $directory = dirname($this->filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $json = json_encode($views, JSON_PRETTY_PRINT);
        $result = file_put_contents($this->filePath, $json);

        if ($result === false) {
            $this->logger->error('Failed to write product views to file');
        }
    }

    /*
     * Reset all views
     */
    public function reset(): void
    {
        $this->saveViews([]);
        $this->logger->info('Product views reset');
    }

    
}