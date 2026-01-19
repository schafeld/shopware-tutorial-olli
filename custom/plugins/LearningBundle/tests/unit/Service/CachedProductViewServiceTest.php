<?php declare(strict_types=1);

namespace Learning\Bundle\Tests\Unit\Service;

use Learning\Bundle\Service\CachedProductViewService;
use Learning\Bundle\Service\ProductViewService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CachedProductViewServiceTest extends TestCase
{
    private CachedProductViewService $cachedService;
    private ProductViewService $productViewService;
    private CacheInterface $cache;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->productViewService = $this->createMock(ProductViewService::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->cachedService = new CachedProductViewService(
            $this->productViewService,
            $this->cache,
            $this->logger
        );
    }

    public function testGetProductViewCountUsesCache(): void
    {
        $productId = 'test-product';
        $context = Context::createDefaultContext();
        $expectedCount = 42;

        // Mock cache to return value without calling the service
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->willReturnCallback(function ($key, $callback) use ($expectedCount) {
                // Simulate cache hit – don't call callback
                return $expectedCount;
            });

        // Service should *not* be called (cache hit)
        $this->productViewService
            ->expects($this->never())
            ->method('getProductViewCount');

        $result = $this->cachedService->getProductViewCount($productId, $context);

        $this->assertEquals($expectedCount, $result);
    }

    public function testGetProductViewCountFallsBackOnCacheError(): void
    {
        $productId = 'test-product';
        $context = Context::createDefaultContext();
        $expectedCount = 42;

        // Mock cache to throw an exception
        $this->cache
            ->method('get')
            ->willThrowException(new \RuntimeException('Cache error'));

        // Service *should* be called (cache error)
        $this->productViewService
            ->expects($this->once())
            ->method('getProductViewCount')
            ->willReturn($expectedCount);

        $result = $this->cachedService->getProductViewCount($productId, $context);

        $this->assertEquals($expectedCount, $result);
    }
}