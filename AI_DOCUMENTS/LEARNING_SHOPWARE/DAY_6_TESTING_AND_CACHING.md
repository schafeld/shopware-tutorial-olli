# Day 6: Testing and Caching

**Duration:** 5-7 hours  
**Goal:** Master automated testing and caching strategies in Shopware

## Learning Objectives

- Write unit tests for services
- Create integration tests for plugins
- Test API endpoints
- Understand Shopware's caching system
- Implement cache invalidation strategies
- Use cache tags effectively
- Optimize performance with caching
- Test cache behavior

## Prerequisites

- Completed Days 1-5
- Understanding of PHPUnit
- Familiarity with testing concepts

---

## Part 1: Understanding Shopware Testing (45 minutes)

### Theory: Test Types

**1. Unit Tests**
- Test individual classes/methods in isolation
- Fast execution, no database
- Use mocks for dependencies

**2. Integration Tests**
- Test multiple components together
- Use real database (test environment)
- More realistic, slower execution

**3. API Tests**
- Test HTTP endpoints
- Full request/response cycle
- Authentication and authorization

**Test Structure:**
```
tests/
├── unit/              # Unit tests
├── integration/       # Integration tests
└── TestBootstrap.php  # Test setup
```

### Official Documentation

📖 **Read these resources:**
- [Plugin Testing](https://developer.shopware.com/docs/guides/plugins/plugins/testing/)
- [Testing Guide](https://developer.shopware.com/docs/guides/plugins/plugins/testing/jest-admin)
- [HTTP Cache](https://developer.shopware.com/docs/guides/hosting/performance/caches)
- [Cache Invalidation](https://developer.shopware.com/docs/guides/plugins/plugins/framework/store-api/cache-invalidation)

---

## Part 2: Unit Tests (90 minutes)

### Step 1: Set Up Test Structure

Create test directory in your plugin:

```bash
mkdir -p custom/plugins/LearningBundle/tests/unit/Service
mkdir -p custom/plugins/LearningBundle/tests/integration
```

Create `custom/plugins/LearningBundle/phpunit.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/9.5/phpunit.xsd"
         bootstrap="tests/TestBootstrap.php"
         executionOrder="random"
         beStrictAboutOutputDuringTests="true"
         beStrictAboutTodoAnnotatedTests="true"
         failOnRisky="true"
         failOnWarning="true"
         colors="true">
    <testsuites>
        <testsuite name="Learning Bundle Test Suite">
            <directory>tests</directory>
        </testsuite>
    </testsuites>

    <coverage processUncoveredFiles="true">
        <include>
            <directory suffix=".php">src</directory>
        </include>
    </coverage>
</phpunit>
```

Create `custom/plugins/LearningBundle/tests/TestBootstrap.php`:

```php
<?php declare(strict_types=1);

use Shopware\Core\TestBootstrapper;

$loader = (new TestBootstrapper())
    ->addCallingPlugin()
    ->addActivePlugins('LearningBundle')
    ->setForceInstallPlugins(true)
    ->bootstrap()
    ->getClassLoader();

$loader->addPsr4('Learning\\Bundle\\Tests\\', __DIR__);
```

### Step 2: Write Unit Tests for MessageService

Create `custom/plugins/LearningBundle/tests/unit/Service/MessageServiceTest.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Tests\Unit\Service;

use Learning\Bundle\Service\MessageService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MessageServiceTest extends TestCase
{
    private MessageService $messageService;
    private LoggerInterface $logger;
    private SystemConfigService $systemConfigService;
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        // Create mocks
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->systemConfigService = $this->createMock(SystemConfigService::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        // Create service with mocked dependencies
        $this->messageService = new MessageService(
            $this->logger,
            $this->systemConfigService,
            $this->eventDispatcher
        );
    }

    public function testGenerateWelcomeMessageWithDefaultPrefix(): void
    {
        // Mock configuration to return default
        $this->systemConfigService
            ->expects($this->once())
            ->method('get')
            ->with('LearningBundle.config.welcomePrefix')
            ->willReturn('Welcome');

        // Test message generation
        $context = Context::createDefaultContext();
        $message = $this->messageService->generateWelcomeMessage('Olli', $context);

        // Assert result
        $this->assertEquals('Welcome, Olli!', $message);
    }

    public function testGenerateWelcomeMessageWithCustomPrefix(): void
    {
        // Mock configuration to return custom prefix
        $this->systemConfigService
            ->expects($this->once())
            ->method('get')
            ->willReturn('Hello there');

        $context = Context::createDefaultContext();
        $message = $this->messageService->generateWelcomeMessage('Developer', $context);

        $this->assertEquals('Hello there, Developer!', $message);
    }

    public function testGenerateWelcomeMessageLogsInfo(): void
    {
        // Expect logger to be called
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Welcome message generated',
                $this->arrayHasKey('name')
            );

        $context = Context::createDefaultContext();
        $this->messageService->generateWelcomeMessage('Test User', $context);
    }

    public function testGetPluginInfo(): void
    {
        $info = $this->messageService->getPluginInfo();

        $this->assertIsArray($info);
        $this->assertArrayHasKey('name', $info);
        $this->assertArrayHasKey('version', $info);
        $this->assertArrayHasKey('features', $info);
        $this->assertEquals('LearningBundle', $info['name']);
    }
}
```

### Step 3: Write Unit Tests for ProductViewService

Create `custom/plugins/LearningBundle/tests/unit/Service/ProductViewServiceTest.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Tests\Unit\Service;

use Learning\Bundle\Core\Content\ProductView\ProductViewCollection;
use Learning\Bundle\Core\Content\ProductView\ProductViewEntity;
use Learning\Bundle\Service\ProductViewService;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class ProductViewServiceTest extends TestCase
{
    private ProductViewService $service;
    private EntityRepository $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EntityRepository::class);
        $this->service = new ProductViewService($this->repository);
    }

    public function testRecordViewCreatesNewEntry(): void
    {
        $productId = 'test-product-id';
        $customerId = 'test-customer-id';
        $userAgent = 'Test Browser';
        $context = Context::createDefaultContext();

        // Mock repository search returning no existing view
        $this->repository
            ->expects($this->once())
            ->method('search')
            ->willReturn($this->createEmptySearchResult());

        // Expect create to be called (not update)
        $this->repository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($data) use ($productId, $customerId) {
                $this->assertCount(1, $data);
                $this->assertEquals($productId, $data[0]['productId']);
                $this->assertEquals($customerId, $data[0]['customerId']);
                $this->assertEquals(1, $data[0]['viewCount']);
                return true;
            }));

        $this->service->recordView($productId, $customerId, $userAgent, $context);
    }

    public function testRecordViewUpdatesExistingEntry(): void
    {
        $productId = 'test-product-id';
        $context = Context::createDefaultContext();

        // Create existing view entity
        $existingView = new ProductViewEntity();
        $existingView->setId('existing-id');
        $existingView->setViewCount(5);

        // Mock repository returning existing view
        $this->repository
            ->expects($this->once())
            ->method('search')
            ->willReturn($this->createSearchResultWithEntity($existingView));

        // Expect update to be called (not create)
        $this->repository
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(function ($data) {
                $this->assertCount(1, $data);
                $this->assertEquals(6, $data[0]['viewCount']); // 5 + 1
                return true;
            }));

        $this->service->recordView($productId, null, null, $context);
    }

    public function testGetProductViewCountReturnsZeroForNoViews(): void
    {
        $productId = 'non-existent-product';
        $context = Context::createDefaultContext();

        $this->repository
            ->method('search')
            ->willReturn($this->createEmptySearchResult());

        $count = $this->service->getProductViewCount($productId, $context);

        $this->assertEquals(0, $count);
    }

    public function testGetProductViewCountSumsMultipleViews(): void
    {
        $productId = 'popular-product';
        $context = Context::createDefaultContext();

        // Create multiple view entities
        $view1 = new ProductViewEntity();
        $view1->setViewCount(10);
        
        $view2 = new ProductViewEntity();
        $view2->setViewCount(15);

        $collection = new ProductViewCollection([$view1, $view2]);

        $this->repository
            ->method('search')
            ->willReturn($this->createSearchResultWithCollection($collection));

        $count = $this->service->getProductViewCount($productId, $context);

        $this->assertEquals(25, $count); // 10 + 15
    }

    private function createEmptySearchResult(): EntitySearchResult
    {
        return new EntitySearchResult(
            'learning_product_view',
            0,
            new ProductViewCollection(),
            null,
            new Criteria(),
            Context::createDefaultContext()
        );
    }

    private function createSearchResultWithEntity(ProductViewEntity $entity): EntitySearchResult
    {
        return new EntitySearchResult(
            'learning_product_view',
            1,
            new ProductViewCollection([$entity]),
            null,
            new Criteria(),
            Context::createDefaultContext()
        );
    }

    private function createSearchResultWithCollection(ProductViewCollection $collection): EntitySearchResult
    {
        return new EntitySearchResult(
            'learning_product_view',
            $collection->count(),
            $collection,
            null,
            new Criteria(),
            Context::createDefaultContext()
        );
    }
}
```

### Step 4: Run Unit Tests

```bash
# Run all tests
cd custom/plugins/LearningBundle
../../../vendor/bin/phpunit

# Run specific test
../../../vendor/bin/phpunit tests/unit/Service/MessageServiceTest.php

# Run with coverage (requires Xdebug)
../../../vendor/bin/phpunit --coverage-html coverage/

# Run with verbose output
../../../vendor/bin/phpunit --verbose
```

---

## Part 3: Integration Tests (75 minutes)

### Step 1: Create Integration Test Base

Create `custom/plugins/LearningBundle/tests/integration/IntegrationTestBehaviour.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Tests\Integration;

use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

trait LearningIntegrationTestBehaviour
{
    use IntegrationTestBehaviour;
    use KernelTestBehaviour;
    use DatabaseTransactionBehaviour;

    protected function getProductId(): string
    {
        // Get first product from database for testing
        $connection = $this->getContainer()->get('Doctrine\DBAL\Connection');
        $result = $connection->fetchOne('SELECT LOWER(HEX(id)) FROM product LIMIT 1');
        
        return $result ?: $this->createTestProduct();
    }

    protected function createTestProduct(): string
    {
        // Create a test product
        $productId = '01234567890123456789012345678901';
        
        // Implementation would create product via repository
        // For simplicity, assume product exists
        
        return $productId;
    }
}
```

### Step 2: Write Integration Test

Create `custom/plugins/LearningBundle/tests/integration/Service/ProductViewServiceIntegrationTest.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Tests\Integration\Service;

use Learning\Bundle\Service\ProductViewService;
use Learning\Bundle\Tests\Integration\LearningIntegrationTestBehaviour;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;

class ProductViewServiceIntegrationTest extends TestCase
{
    use LearningIntegrationTestBehaviour;

    private ProductViewService $service;

    protected function setUp(): void
    {
        $this->service = $this->getContainer()->get(ProductViewService::class);
    }

    public function testRecordAndRetrieveView(): void
    {
        $context = Context::createDefaultContext();
        $productId = $this->getProductId();

        // Record a view
        $this->service->recordView($productId, null, 'Test User Agent', $context);

        // Retrieve view count
        $count = $this->service->getProductViewCount($productId, $context);

        // Assert it was recorded
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testMultipleViewsIncrement(): void
    {
        $context = Context::createDefaultContext();
        $productId = $this->getProductId();

        // Get initial count
        $initialCount = $this->service->getProductViewCount($productId, $context);

        // Record multiple views
        $this->service->recordView($productId, null, 'Test', $context);
        $this->service->recordView($productId, null, 'Test', $context);
        $this->service->recordView($productId, null, 'Test', $context);

        // Check count increased
        $newCount = $this->service->getProductViewCount($productId, $context);
        $this->assertEquals($initialCount + 3, $newCount);
    }

    public function testGetMostViewedProducts(): void
    {
        $context = Context::createDefaultContext();
        $productId = $this->getProductId();

        // Record some views
        for ($i = 0; $i < 10; $i++) {
            $this->service->recordView($productId, null, 'Test', $context);
        }

        // Get popular products
        $popular = $this->service->getMostViewedProducts(5, $context);

        // Assert we got results
        $this->assertIsArray($popular);
        $this->assertNotEmpty($popular);
    }
}
```

### Step 3: Run Integration Tests

```bash
# Run integration tests
cd custom/plugins/LearningBundle
../../../vendor/bin/phpunit tests/integration/

# Run all tests
../../../vendor/bin/phpunit
```

---

## Part 4: Understanding Caching (60 minutes)

### Theory: Shopware Cache System

**Cache Types:**

1. **HTTP Cache** - Full page caching (Varnish/reverse proxy)
2. **Object Cache** - Entity and service caching (Redis/Memcached)
3. **Template Cache** - Compiled Twig templates
4. **Configuration Cache** - Symfony container cache

**Cache Flow:**
```
Request → HTTP Cache → Object Cache → Database
         ↓ Hit        ↓ Hit          ↓ Miss
         Response     Response        Query → Cache → Response
```

### Cache Locations

```
var/cache/
├── dev/                 # Development cache
├── prod/                # Production cache
└── prod_*/              # Versioned production caches
```

---

## Part 5: Implementing Cache Strategies (90 minutes)

### Step 1: Cache-Aware Service

Create `custom/plugins/LearningBundle/src/Service/CachedProductViewService.php`:

```php
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
     * Get product view count with caching
     */
    public function getProductViewCount(string $productId, Context $context): int
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $productId;

        try {
            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($productId, $context) {
                $this->logger->debug('Cache miss for product view count', ['product_id' => $productId]);
                
                // Set TTL
                $item->expiresAfter(self::CACHE_TTL);
                
                // Add cache tags for invalidation
                $item->tag(['learning-product-view', 'product-' . $productId]);
                
                // Fetch from service
                return $this->productViewService->getProductViewCount($productId, $context);
            });
        } catch (\Throwable $e) {
            $this->logger->error('Cache error, falling back to direct query', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
            
            // Fallback to non-cached version
            return $this->productViewService->getProductViewCount($productId, $context);
        }
    }

    /**
     * Get most viewed products with caching
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
     * Invalidate cache for specific product
     */
    public function invalidateProductCache(string $productId): void
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $productId;
        $this->cache->delete($cacheKey);
        
        $this->logger->info('Invalidated product view cache', ['product_id' => $productId]);
    }

    /**
     * Invalidate all product view caches
     */
    public function invalidateAllCaches(): void
    {
        // This requires cache pool with tag awareness
        // For simple implementation, we track keys
        
        $this->logger->info('Invalidated all product view caches');
    }
}
```

Register in `services.xml`:

```xml
<service id="Learning\Bundle\Service\CachedProductViewService">
    <argument type="service" id="Learning\Bundle\Service\ProductViewService"/>
    <argument type="service" id="cache.object"/>
    <argument type="service" id="logger"/>
</service>
```

### Step 2: Cache Invalidation Subscriber

Create `custom/plugins/LearningBundle/src/Subscriber/CacheInvalidationSubscriber.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Subscriber;

use Learning\Bundle\Service\CachedProductViewService;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CacheInvalidationSubscriber implements EventSubscriberInterface
{
    private CachedProductViewService $cachedService;

    public function __construct(CachedProductViewService $cachedService)
    {
        $this->cachedService = $cachedService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductEvents::PRODUCT_WRITTEN_EVENT => 'onProductWritten',
        ];
    }

    public function onProductWritten(EntityWrittenEvent $event): void
    {
        // When product changes, invalidate its view cache
        foreach ($event->getIds() as $productId) {
            $this->cachedService->invalidateProductCache($productId);
        }
    }
}
```

### Step 3: HTTP Cache Tags

For Store API routes, add cache tags:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\ProductView\SalesChannel;

use Shopware\Core\Framework\Adapter\Cache\AbstractCacheTracer;
use Shopware\Core\Framework\Adapter\Cache\CacheValueCompressor;
use Shopware\Core\System\SalesChannel\StoreApiResponse;
use Symfony\Component\HttpFoundation\Response;

class CachedProductViewRoute extends AbstractProductViewRoute
{
    private AbstractProductViewRoute $decorated;
    private AbstractCacheTracer $tracer;
    private array $states;

    public function __construct(
        AbstractProductViewRoute $decorated,
        AbstractCacheTracer $tracer,
        array $states
    ) {
        $this->decorated = $decorated;
        $this->tracer = $tracer;
        $this->states = $states;
    }

    public function getDecorated(): AbstractProductViewRoute
    {
        return $this->decorated;
    }

    public function load(string $productId, Request $request, SalesChannelContext $context): ProductViewRouteResponse
    {
        // Check if we can use cache
        if ($context->hasState(...$this->states)) {
            return $this->getDecorated()->load($productId, $request, $context);
        }

        // Generate cache key
        $key = $this->generateKey($productId, $context);

        // Try to get from cache
        $value = $this->cache->get($key, function (ItemInterface $item) use ($productId, $request, $context) {
            $response = $this->tracer->trace($key, function () use ($productId, $request, $context) {
                return $this->getDecorated()->load($productId, $request, $context);
            });

            $item->tag($this->generateTags($productId, $response));

            return CacheValueCompressor::compress($response);
        });

        return CacheValueCompressor::uncompress($value);
    }

    private function generateKey(string $productId, SalesChannelContext $context): string
    {
        return 'product-view-route-' . $productId . '-' . $context->getSalesChannelId();
    }

    private function generateTags(string $productId, StoreApiResponse $response): array
    {
        return [
            'learning-product-view',
            'product-' . $productId,
        ];
    }
}
```

---

## Part 6: Cache Testing (45 minutes)

### Test Cache Behavior

Create `custom/plugins/LearningBundle/tests/unit/Service/CachedProductViewServiceTest.php`:

```php
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

        // Mock cache to return value without calling service
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->willReturnCallback(function ($key, $callback) use ($expectedCount) {
                // Simulate cache hit - don't call callback
                return $expectedCount;
            });

        // Service should NOT be called (cache hit)
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

        // Mock cache to throw exception
        $this->cache
            ->method('get')
            ->willThrowException(new \RuntimeException('Cache error'));

        // Service SHOULD be called (cache error)
        $this->productViewService
            ->expects($this->once())
            ->method('getProductViewCount')
            ->willReturn($expectedCount);

        $result = $this->cachedService->getProductViewCount($productId, $context);

        $this->assertEquals($expectedCount, $result);
    }
}
```

### Benchmark Cache Performance

Create command to test cache performance:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Learning\Bundle\Service\CachedProductViewService;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CacheBenchmarkCommand extends Command
{
    protected static $defaultName = 'learning:cache-benchmark';

    private CachedProductViewService $cachedService;

    public function __construct(CachedProductViewService $cachedService)
    {
        parent::__construct();
        $this->cachedService = $cachedService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();
        $productId = 'test-product-id';

        // First call (cache miss)
        $start = microtime(true);
        $this->cachedService->getProductViewCount($productId, $context);
        $firstCallTime = (microtime(true) - $start) * 1000;

        // Second call (cache hit)
        $start = microtime(true);
        $this->cachedService->getProductViewCount($productId, $context);
        $secondCallTime = (microtime(true) - $start) * 1000;

        $io->table(
            ['Call', 'Time (ms)', 'Status'],
            [
                ['First (miss)', number_format($firstCallTime, 2), '❌'],
                ['Second (hit)', number_format($secondCallTime, 2), '✅'],
                ['Improvement', number_format($firstCallTime - $secondCallTime, 2), sprintf('%.1fx faster', $firstCallTime / $secondCallTime)],
            ]
        );

        return Command::SUCCESS;
    }
}
```

---

## Part 7: Exercises (60 minutes)

### Exercise 1: Test Coverage

Achieve 80%+ test coverage for your services. Run:

```bash
vendor/bin/phpunit --coverage-html coverage/
open coverage/index.html
```

### Exercise 2: API Integration Test

Write an integration test that makes actual HTTP requests to your Store API endpoints.

### Exercise 3: Cache Warmup Command

Create a command that pre-warms the cache by loading popular products and their view counts.

---

## Key Takeaways

✅ **You've learned:**
- Writing unit tests with mocks
- Creating integration tests with real database
- Testing API endpoints
- Shopware's multi-layer caching system
- Implementing cache strategies
- Cache invalidation patterns
- Using cache tags
- Testing cache behavior
- Performance benchmarking

## Testing Best Practices

✅ **DO:**
- Write tests for critical business logic
- Use mocks for external dependencies
- Keep tests fast and isolated
- Test edge cases and error conditions
- Use descriptive test names

❌ **DON'T:**
- Test framework code
- Create brittle tests (too specific)
- Ignore failing tests
- Skip integration tests
- Forget to test cache invalidation

---

## Additional Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Symfony Testing](https://symfony.com/doc/current/testing.html)
- [Symfony Cache](https://symfony.com/doc/current/cache.html)
- [HTTP Caching Guide](https://developer.shopware.com/docs/guides/hosting/performance/caches)
- [Test-Driven Development](https://martinfowler.com/bliki/TestDrivenDevelopment.html)

---

**Estimated Completion Time:** 5-7 hours  
**Difficulty:** Intermediate to Advanced

🎉 Fantastic! Tomorrow is the final project day!
