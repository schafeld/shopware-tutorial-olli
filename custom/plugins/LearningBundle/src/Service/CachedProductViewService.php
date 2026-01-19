<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Adapter\Cache\CacheValueCompressor;
use Shopware\Core\Framework\Context;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CachedProductViewService
{
    private const CACHE_KEY_PREFIX = 'learning_product_view_';
    private const CACHE_TTL = 3600; // 1 hour

    private ProductViewService $productViewService;
    private CacheInterface $cache;
    private LoggerInterface $logger;

    public function __construct(
        ProductViewService $productViewService,
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        $this->productViewService = $productViewService;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * Get product view count with caching.
     */
    public function getProductViewCount(string $productId, Context $context): int
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $productId;

        try {
            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($productId, $context) {
                $this->logger->debug('Cache miss for product view count', ['productId' => $productId]);

                // Set TTL
                $item->expiresAfter(self::CACHE_TTL);

                // Add cache tags for invalidation
                $item->tag(['learning-product-view', 'product-' . $productId]);

                // Fetch from service
                return $this->productViewService->getProductViewCount($productId, $context);
            });
        } catch (\Throwable $e) {
            $this->logger->error('Error fetching product view count from cache', [
                'productId' => $productId,
                'error' => $e->getMessage(),
            ]);

            // Fallback to non-cached service
            return $this->productViewService->getProductViewCount($productId, $context);
        }
    }

    /**
     * Get most viewed products with caching.
     */
    public function getMostViewedProducts(int $limit, Context $context): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX . 'popular_' . $limit;

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($limit, $context) {
            $item->expiresAfter(self::CACHE_TTL);
            $item->tag(['learning-product-view', 'popular-products']);

            return $this->productViewService->getMostViewedProducts($limit, $context);
        });
    }

    /**
     * Invalidate cache for a specific product.
     */
    public function invalidateProductCache(string $productId): void
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $productId;
        $this->cache->delete($cacheKey);

        $this->logger->info('Invalidated cache for product view count', ['productId' => $productId]);
    }

    /**
     * Invalidate all product view related caches.
     */
    public function invalidateAllCaches(): void
    {
        // This requires a cache implementation that supports tag-based invalidation
        if ($this->cache instanceof \Symfony\Contracts\Cache\TagAwareCacheInterface) {
            $this->cache->invalidateTags(['learning-product-view']);
            $this->logger->info('Invalidated all product view related caches by tag');
        } else {
            $this->logger->warning('Cache does not support tag-based invalidation');
        }
    }
}