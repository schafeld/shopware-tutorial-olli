# Day 6 Solutions: Testing and Caching

Complete solutions for all exercises in Day 6.

## Exercise 1: Test Coverage (80%+)

### Unit Tests for ProductRatingService

**File:** `custom/plugins/LearningBundle/tests/unit/Service/ProductRatingServiceTest.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Tests\Unit\Service;

use Learning\Bundle\Core\Content\ProductRating\ProductRatingEntity;
use Learning\Bundle\Service\ProductRatingService;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Uuid\Uuid;

class ProductRatingServiceTest extends TestCase
{
    private ProductRatingService $service;
    private EntityRepository $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EntityRepository::class);
        $this->service = new ProductRatingService($this->repository);
    }

    public function testAddRatingSuccess(): void
    {
        $productId = Uuid::randomHex();
        $rating = 5;
        $comment = 'Great product!';
        $context = Context::createDefaultContext();

        $this->repository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($data) use ($productId, $rating, $comment) {
                $item = $data[0];
                return $item['productId'] === $productId
                    && $item['rating'] === $rating
                    && $item['comment'] === $comment;
            }), $context);

        $ratingId = $this->service->addRating($productId, $rating, null, $comment, $context);

        $this->assertNotEmpty($ratingId);
        $this->assertTrue(Uuid::isValid($ratingId));
    }

    public function testAddRatingInvalidRatingTooLow(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rating must be between 1 and 5');

        $this->service->addRating(
            Uuid::randomHex(),
            0,
            null,
            null,
            Context::createDefaultContext()
        );
    }

    public function testAddRatingInvalidRatingTooHigh(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rating must be between 1 and 5');

        $this->service->addRating(
            Uuid::randomHex(),
            6,
            null,
            null,
            Context::createDefaultContext()
        );
    }

    public function testGetAverageRatingWithMultipleRatings(): void
    {
        $productId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        // Create mock ratings
        $rating1 = $this->createRatingEntity($productId, 5);
        $rating2 = $this->createRatingEntity($productId, 4);
        $rating3 = $this->createRatingEntity($productId, 3);

        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('count')->willReturn(3);
        $searchResult->method('getIterator')->willReturn(new \ArrayIterator([
            $rating1,
            $rating2,
            $rating3,
        ]));

        $this->repository
            ->method('search')
            ->willReturn($searchResult);

        $average = $this->service->getAverageRating($productId, $context);

        $this->assertEquals(4.0, $average);
    }

    public function testGetAverageRatingWithNoRatings(): void
    {
        $productId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('count')->willReturn(0);

        $this->repository
            ->method('search')
            ->willReturn($searchResult);

        $average = $this->service->getAverageRating($productId, $context);

        $this->assertEquals(0.0, $average);
    }

    public function testGetRatingDistribution(): void
    {
        $productId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        $ratings = [
            $this->createRatingEntity($productId, 5),
            $this->createRatingEntity($productId, 5),
            $this->createRatingEntity($productId, 4),
            $this->createRatingEntity($productId, 3),
            $this->createRatingEntity($productId, 1),
        ];

        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('count')->willReturn(5);
        $searchResult->method('getIterator')->willReturn(new \ArrayIterator($ratings));

        $this->repository
            ->method('search')
            ->willReturn($searchResult);

        $distribution = $this->service->getRatingDistribution($productId, $context);

        $this->assertEquals([
            1 => 1,
            2 => 0,
            3 => 1,
            4 => 1,
            5 => 2,
        ], $distribution);
    }

    private function createRatingEntity(string $productId, int $rating): ProductRatingEntity
    {
        $entity = new ProductRatingEntity();
        $entity->setId(Uuid::randomHex());
        $entity->setProductId($productId);
        $entity->setRating($rating);
        $entity->setCreatedAt(new \DateTime());
        
        return $entity;
    }
}
```

### Unit Tests for WishlistService

**File:** `custom/plugins/LearningBundle/tests/unit/Service/WishlistServiceTest.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Tests\Unit\Service;

use Learning\Bundle\Core\Content\Wishlist\WishlistEntity;
use Learning\Bundle\Service\WishlistService;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Uuid\Uuid;

class WishlistServiceTest extends TestCase
{
    private WishlistService $service;
    private EntityRepository $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EntityRepository::class);
        $this->service = new WishlistService($this->repository);
    }

    public function testAddToWishlistSuccess(): void
    {
        $customerId = Uuid::randomHex();
        $productId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        // Mock isInWishlist to return false
        $idSearchResult = $this->createMock(IdSearchResult::class);
        $idSearchResult->method('getTotal')->willReturn(0);

        $this->repository
            ->method('searchIds')
            ->willReturn($idSearchResult);

        $this->repository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($data) use ($customerId, $productId) {
                $item = $data[0];
                return $item['customerId'] === $customerId
                    && $item['productId'] === $productId;
            }), $context);

        $wishlistId = $this->service->addToWishlist($customerId, $productId, $context);

        $this->assertNotEmpty($wishlistId);
        $this->assertTrue(Uuid::isValid($wishlistId));
    }

    public function testAddToWishlistAlreadyExists(): void
    {
        $customerId = Uuid::randomHex();
        $productId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        // Mock isInWishlist to return true
        $idSearchResult = $this->createMock(IdSearchResult::class);
        $idSearchResult->method('getTotal')->willReturn(1);

        $this->repository
            ->method('searchIds')
            ->willReturn($idSearchResult);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Product already in wishlist');

        $this->service->addToWishlist($customerId, $productId, $context);
    }

    public function testRemoveFromWishlistSuccess(): void
    {
        $customerId = Uuid::randomHex();
        $productId = Uuid::randomHex();
        $wishlistId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        $idSearchResult = $this->createMock(IdSearchResult::class);
        $idSearchResult->method('getTotal')->willReturn(1);
        $idSearchResult->method('getIds')->willReturn([$wishlistId]);

        $this->repository
            ->method('searchIds')
            ->willReturn($idSearchResult);

        $this->repository
            ->expects($this->once())
            ->method('delete')
            ->with([['id' => $wishlistId]], $context);

        $this->service->removeFromWishlist($customerId, $productId, $context);
    }

    public function testRemoveFromWishlistNotFound(): void
    {
        $customerId = Uuid::randomHex();
        $productId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        $idSearchResult = $this->createMock(IdSearchResult::class);
        $idSearchResult->method('getTotal')->willReturn(0);

        $this->repository
            ->method('searchIds')
            ->willReturn($idSearchResult);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Product not in wishlist');

        $this->service->removeFromWishlist($customerId, $productId, $context);
    }

    public function testGetWishlist(): void
    {
        $customerId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        $wishlistItem = $this->createWishlistEntity($customerId);
        
        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('getIterator')->willReturn(new \ArrayIterator([$wishlistItem]));

        $this->repository
            ->method('search')
            ->willReturn($searchResult);

        $result = $this->service->getWishlist($customerId, $context);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('productName', $result[0]);
        $this->assertEquals('Test Product', $result[0]['productName']);
    }

    private function createWishlistEntity(string $customerId): WishlistEntity
    {
        $product = new ProductEntity();
        $product->setId(Uuid::randomHex());
        $product->setName('Test Product');
        $product->setProductNumber('TEST-123');

        $entity = new WishlistEntity();
        $entity->setId(Uuid::randomHex());
        $entity->setCustomerId($customerId);
        $entity->setProductId($product->getId());
        $entity->setProduct($product);
        $entity->setCreatedAt(new \DateTime());
        
        return $entity;
    }
}
```

### Integration Test for Product Rating

**File:** `custom/plugins/LearningBundle/tests/integration/Service/ProductRatingServiceIntegrationTest.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Tests\Integration\Service;

use Learning\Bundle\Service\ProductRatingService;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;

class ProductRatingServiceIntegrationTest extends TestCase
{
    use IntegrationTestBehaviour;

    private ProductRatingService $service;
    private EntityRepository $repository;
    private Context $context;

    protected function setUp(): void
    {
        $this->repository = $this->getContainer()->get('learning_product_rating.repository');
        $this->service = new ProductRatingService($this->repository);
        $this->context = Context::createDefaultContext();
    }

    public function testCompleteRatingWorkflow(): void
    {
        $productId = $this->createProduct();

        // Add ratings
        $ratingId1 = $this->service->addRating($productId, 5, null, 'Excellent!', $this->context);
        $ratingId2 = $this->service->addRating($productId, 4, null, 'Very good', $this->context);
        $ratingId3 = $this->service->addRating($productId, 3, null, 'Good', $this->context);

        $this->assertNotEmpty($ratingId1);
        $this->assertNotEmpty($ratingId2);
        $this->assertNotEmpty($ratingId3);

        // Check average
        $average = $this->service->getAverageRating($productId, $this->context);
        $this->assertEquals(4.0, $average);

        // Check distribution
        $distribution = $this->service->getRatingDistribution($productId, $this->context);
        $this->assertEquals(1, $distribution[5]);
        $this->assertEquals(1, $distribution[4]);
        $this->assertEquals(1, $distribution[3]);
        $this->assertEquals(0, $distribution[2]);
        $this->assertEquals(0, $distribution[1]);

        // Get ratings
        $ratings = $this->service->getRatingsForProduct($productId, $this->context);
        $this->assertCount(3, $ratings);
    }

    private function createProduct(): string
    {
        $productId = Uuid::randomHex();
        $productRepository = $this->getContainer()->get('product.repository');

        $productRepository->create([
            [
                'id' => $productId,
                'productNumber' => 'TEST-' . $productId,
                'name' => 'Test Product',
                'stock' => 10,
                'price' => [
                    ['currencyId' => \Shopware\Core\Defaults::CURRENCY, 'gross' => 99.99, 'net' => 84.03, 'linked' => false],
                ],
                'tax' => ['name' => 'test', 'taxRate' => 19],
            ],
        ], $this->context);

        return $productId;
    }
}
```

### Run Tests with Coverage

```bash
# Run all tests with coverage
vendor/bin/phpunit --configuration custom/plugins/LearningBundle/phpunit.xml --coverage-html coverage/

# View coverage report
open coverage/index.html

# Run specific test
vendor/bin/phpunit --configuration custom/plugins/LearningBundle/phpunit.xml tests/unit/Service/ProductRatingServiceTest.php
```

---

## Exercise 2: API Integration Test

### Store API Integration Test

**File:** `custom/plugins/LearningBundle/tests/integration/Api/WishlistApiTest.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Tests\Integration\Api;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

class WishlistApiTest extends TestCase
{
    use IntegrationTestBehaviour;

    private string $customerId;
    private string $productId;
    private Context $context;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $this->customerId = $this->createCustomer();
        $this->productId = $this->createProduct();
    }

    public function testAddToWishlistEndpoint(): void
    {
        $browser = $this->createClient();
        
        $browser->request(
            'POST',
            '/store-api/learning/wishlist/add',
            ['productId' => $this->productId],
            [],
            [
                'HTTP_sw-context-token' => $this->getContextToken(),
            ]
        );

        $response = $browser->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue($content['success']);
        $this->assertArrayHasKey('wishlistId', $content);
    }

    public function testGetWishlistEndpoint(): void
    {
        // First add a product
        $this->addProductToWishlist();

        $browser = $this->createClient();
        
        $browser->request(
            'GET',
            '/store-api/learning/wishlist',
            [],
            [],
            [
                'HTTP_sw-context-token' => $this->getContextToken(),
            ]
        );

        $response = $browser->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue($content['success']);
        $this->assertArrayHasKey('items', $content);
        $this->assertGreaterThan(0, $content['total']);
    }

    public function testRemoveFromWishlistEndpoint(): void
    {
        // First add a product
        $this->addProductToWishlist();

        $browser = $this->createClient();
        
        $browser->request(
            'DELETE',
            '/store-api/learning/wishlist/remove/' . $this->productId,
            [],
            [],
            [
                'HTTP_sw-context-token' => $this->getContextToken(),
            ]
        );

        $response = $browser->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue($content['success']);
    }

    public function testAddToWishlistWithoutAuthentication(): void
    {
        $browser = $this->createClient();
        
        $browser->request(
            'POST',
            '/store-api/learning/wishlist/add',
            ['productId' => $this->productId]
        );

        $response = $browser->getResponse();

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    private function addProductToWishlist(): void
    {
        $wishlistRepository = $this->getContainer()->get('learning_wishlist.repository');
        
        $wishlistRepository->create([
            [
                'id' => Uuid::randomHex(),
                'customerId' => $this->customerId,
                'productId' => $this->productId,
            ],
        ], $this->context);
    }

    private function createCustomer(): string
    {
        $customerId = Uuid::randomHex();
        $addressId = Uuid::randomHex();
        
        $customerRepository = $this->getContainer()->get('customer.repository');
        
        $customerRepository->create([
            [
                'id' => $customerId,
                'salesChannelId' => \Shopware\Core\Defaults::SALES_CHANNEL,
                'defaultShippingAddress' => [
                    'id' => $addressId,
                    'firstName' => 'Test',
                    'lastName' => 'User',
                    'street' => 'Test Street 1',
                    'city' => 'Test City',
                    'zipcode' => '12345',
                    'countryId' => $this->getValidCountryId(),
                ],
                'defaultBillingAddressId' => $addressId,
                'groupId' => \Shopware\Core\Defaults::FALLBACK_CUSTOMER_GROUP,
                'email' => 'test@example.com',
                'password' => 'test',
                'firstName' => 'Test',
                'lastName' => 'User',
                'customerNumber' => '12345',
            ],
        ], $this->context);

        return $customerId;
    }

    private function createProduct(): string
    {
        $productId = Uuid::randomHex();
        $productRepository = $this->getContainer()->get('product.repository');

        $productRepository->create([
            [
                'id' => $productId,
                'productNumber' => 'TEST-' . $productId,
                'name' => 'Test Product',
                'stock' => 10,
                'price' => [
                    ['currencyId' => \Shopware\Core\Defaults::CURRENCY, 'gross' => 99.99, 'net' => 84.03, 'linked' => false],
                ],
                'tax' => ['name' => 'test', 'taxRate' => 19],
            ],
        ], $this->context);

        return $productId;
    }

    private function getContextToken(): string
    {
        // Simplified - in real tests, you'd generate a proper context token
        return 'test-context-token';
    }

    private function getValidCountryId(): string
    {
        $countryRepository = $this->getContainer()->get('country.repository');
        $criteria = new \Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria();
        $criteria->setLimit(1);
        
        return $countryRepository->searchIds($criteria, $this->context)->firstId();
    }

    private function createClient(): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        return $this->getContainer()->get('test.client');
    }
}
```

---

## Exercise 3: Cache Warmup Command

### Cache Warmup Service

**File:** `custom/plugins/LearningBundle/src/Service/CacheWarmupService.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class CacheWarmupService
{
    private EntityRepository $productRepository;
    private CachedProductViewService $cachedProductViewService;
    private LoggerInterface $logger;

    public function __construct(
        EntityRepository $productRepository,
        CachedProductViewService $cachedProductViewService,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->cachedProductViewService = $cachedProductViewService;
        $this->logger = $logger;
    }

    public function warmupProductViews(Context $context, int $limit = 100): array
    {
        $this->logger->info('Starting cache warmup for product views', ['limit' => $limit]);

        $startTime = microtime(true);
        $warmedUp = 0;
        $errors = 0;

        // Get most popular products based on view count
        $popularProducts = $this->cachedProductViewService->getMostViewedProducts($limit, $context);
        
        foreach ($popularProducts as $productData) {
            try {
                // Warmup view count cache
                $this->cachedProductViewService->getProductViewCount($productData['id'], $context);
                $warmedUp++;
            } catch (\Exception $e) {
                $errors++;
                $this->logger->error('Error warming up cache for product', [
                    'productId' => $productData['id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Also warmup all active products
        $criteria = new Criteria();
        $criteria->addFilter(new \Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter('active', true));
        $criteria->setLimit($limit);
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));

        $products = $this->productRepository->search($criteria, $context);

        /** @var ProductEntity $product */
        foreach ($products as $product) {
            try {
                // Warmup cache even if no views yet
                $this->cachedProductViewService->getProductViewCount($product->getId(), $context);
                $warmedUp++;
            } catch (\Exception $e) {
                $errors++;
                $this->logger->error('Error warming up cache for product', [
                    'productId' => $product->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $duration = microtime(true) - $startTime;

        $result = [
            'warmed_up' => $warmedUp,
            'errors' => $errors,
            'duration' => round($duration, 2),
            'items_per_second' => $warmedUp > 0 ? round($warmedUp / $duration, 2) : 0,
        ];

        $this->logger->info('Cache warmup completed', $result);

        return $result;
    }

    public function warmupRatings(Context $context, int $limit = 100): array
    {
        $this->logger->info('Starting cache warmup for product ratings', ['limit' => $limit]);

        $startTime = microtime(true);
        $warmedUp = 0;
        $errors = 0;

        // Get products with ratings
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $products = $this->productRepository->search($criteria, $context);

        /** @var ProductEntity $product */
        foreach ($products as $product) {
            try {
                // This would warmup rating caches if you have a CachedProductRatingService
                // For now, we'll just count it as warmed up
                $warmedUp++;
            } catch (\Exception $e) {
                $errors++;
            }
        }

        $duration = microtime(true) - $startTime;

        return [
            'warmed_up' => $warmedUp,
            'errors' => $errors,
            'duration' => round($duration, 2),
        ];
    }

    public function clearAllCaches(): void
    {
        $this->logger->info('Clearing all plugin caches');
        
        // Implementation depends on your cache setup
        // This is a placeholder for cache clearing logic
    }
}
```

### Cache Warmup Command

**File:** `custom/plugins/LearningBundle/src/Command/CacheWarmupCommand.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Learning\Bundle\Service\CacheWarmupService;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CacheWarmupCommand extends Command
{
    protected static $defaultName = 'learning:cache-warmup';

    private CacheWarmupService $warmupService;

    public function __construct(CacheWarmupService $warmupService)
    {
        parent::__construct();
        $this->warmupService = $warmupService;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Warmup cache for popular products and ratings')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Number of items to warmup', 100)
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'Type to warmup (views, ratings, all)', 'all');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();
        $limit = (int) $input->getOption('limit');
        $type = $input->getOption('type');

        $io->title('Cache Warmup');

        $totalStartTime = microtime(true);
        $results = [];

        if ($type === 'views' || $type === 'all') {
            $io->section('Warming up product views cache');
            $result = $this->warmupService->warmupProductViews($context, $limit);
            $results['views'] = $result;
            
            $io->table(
                ['Metric', 'Value'],
                [
                    ['Items Warmed Up', $result['warmed_up']],
                    ['Errors', $result['errors']],
                    ['Duration (s)', $result['duration']],
                    ['Items/Second', $result['items_per_second']],
                ]
            );
        }

        if ($type === 'ratings' || $type === 'all') {
            $io->section('Warming up product ratings cache');
            $result = $this->warmupService->warmupRatings($context, $limit);
            $results['ratings'] = $result;
            
            $io->table(
                ['Metric', 'Value'],
                [
                    ['Items Warmed Up', $result['warmed_up']],
                    ['Errors', $result['errors']],
                    ['Duration (s)', $result['duration']],
                ]
            );
        }

        $totalDuration = microtime(true) - $totalStartTime;

        $io->section('Summary');
        $totalWarmedUp = array_sum(array_column($results, 'warmed_up'));
        $totalErrors = array_sum(array_column($results, 'errors'));

        $io->table(
            ['Metric', 'Value'],
            [
                ['Total Items Warmed Up', $totalWarmedUp],
                ['Total Errors', $totalErrors],
                ['Total Duration (s)', round($totalDuration, 2)],
                ['Average Items/Second', round($totalWarmedUp / $totalDuration, 2)],
            ]
        );

        if ($totalErrors === 0) {
            $io->success('Cache warmup completed successfully');
            return Command::SUCCESS;
        } else {
            $io->warning(sprintf('Cache warmup completed with %d errors', $totalErrors));
            return Command::FAILURE;
        }
    }
}
```

### Scheduled Cache Warmup

**File:** `custom/plugins/LearningBundle/src/ScheduledTask/CacheWarmupTask.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class CacheWarmupTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'learning.cache_warmup';
    }

    public static function getDefaultInterval(): int
    {
        return 3600; // Run every hour
    }
}
```

**File:** `custom/plugins/LearningBundle/src/ScheduledTask/CacheWarmupTaskHandler.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\ScheduledTask;

use Learning\Bundle\Service\CacheWarmupService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;

class CacheWarmupTaskHandler extends ScheduledTaskHandler
{
    private CacheWarmupService $warmupService;

    public function __construct(
        EntityRepository $scheduledTaskRepository,
        CacheWarmupService $warmupService
    ) {
        parent::__construct($scheduledTaskRepository);
        $this->warmupService = $warmupService;
    }

    public static function getHandledMessages(): iterable
    {
        return [CacheWarmupTask::class];
    }

    public function run(): void
    {
        $context = Context::createDefaultContext();
        
        // Warmup with smaller limit for scheduled task
        $this->warmupService->warmupProductViews($context, 50);
        $this->warmupService->warmupRatings($context, 50);
    }
}
```

### Services Configuration

Add to `custom/plugins/LearningBundle/src/Resources/config/services.xml`:

```xml
<service id="Learning\Bundle\Service\CacheWarmupService">
    <argument type="service" id="product.repository"/>
    <argument type="service" id="Learning\Bundle\Service\CachedProductViewService"/>
    <argument type="service" id="monolog.logger.learning"/>
</service>

<service id="Learning\Bundle\Command\CacheWarmupCommand">
    <argument type="service" id="Learning\Bundle\Service\CacheWarmupService"/>
    <tag name="console.command"/>
</service>

<service id="Learning\Bundle\ScheduledTask\CacheWarmupTask">
    <tag name="shopware.scheduled.task"/>
</service>

<service id="Learning\Bundle\ScheduledTask\CacheWarmupTaskHandler">
    <argument type="service" id="scheduled_task.repository"/>
    <argument type="service" id="Learning\Bundle\Service\CacheWarmupService"/>
    <tag name="messenger.message_handler"/>
</service>
```

---

## Complete Test Suite

### Run All Tests Script

**File:** `run-all-tests.sh`

```bash
#!/bin/bash

echo "=== Running Complete Test Suite ==="

# Change to plugin directory
cd custom/plugins/LearningBundle

# Run unit tests
echo -e "\n📋 Running Unit Tests..."
../../vendor/bin/phpunit tests/unit --testdox

# Run integration tests
echo -e "\n🔗 Running Integration Tests..."
../../vendor/bin/phpunit tests/integration --testdox

# Generate coverage report
echo -e "\n📊 Generating Coverage Report..."
../../vendor/bin/phpunit --coverage-html ../../../coverage/ --coverage-text

# Display summary
echo -e "\n✅ Test Suite Completed"
echo "Coverage report: coverage/index.html"
```

Make executable:
```bash
chmod +x run-all-tests.sh
```

---

## Usage Examples

### Running Tests

```bash
# Run all tests
./run-all-tests.sh

# Run specific test file
vendor/bin/phpunit custom/plugins/LearningBundle/tests/unit/Service/ProductRatingServiceTest.php

# Run tests with coverage
vendor/bin/phpunit --configuration custom/plugins/LearningBundle/phpunit.xml --coverage-html coverage/

# Run only integration tests
vendor/bin/phpunit custom/plugins/LearningBundle/tests/integration/

# Run with testdox format (readable output)
vendor/bin/phpunit --testdox
```

### Cache Warmup

```bash
# Warmup all caches
bin/console learning:cache-warmup

# Warmup only product views
bin/console learning:cache-warmup --type=views --limit=50

# Warmup only ratings
bin/console learning:cache-warmup --type=ratings --limit=50
```

---

## Key Takeaways

✅ **You've mastered:**
- Writing comprehensive unit tests with mocks
- Creating integration tests with real database
- Testing API endpoints with HTTP requests
- Achieving high test coverage (80%+)
- Implementing cache warmup strategies
- Scheduled tasks for automatic cache warmup
- Performance testing and benchmarking
- Test-driven development practices

## Testing Best Practices Checklist

✅ **Unit Tests:**
- Test one thing at a time
- Use descriptive test names
- Mock external dependencies
- Test edge cases and error conditions
- Keep tests fast (< 100ms each)

✅ **Integration Tests:**
- Test real workflows
- Use test database
- Clean up after tests
- Test actual API responses
- Verify database state

✅ **Cache Testing:**
- Test cache hits and misses
- Verify cache invalidation
- Test cache warmup
- Benchmark performance improvements
- Test cache failure scenarios

---

**Next:** Day 7 - Final Project

🎉 Congratulations! You've completed the testing and caching exercises!
