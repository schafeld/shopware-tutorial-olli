# Day 7-10: Final Project - Build a Complete Feature

**Duration:** 3-4 days (20-28 hours with breaks)  
**Goal:** Build a complete, production-ready feature using everything learned

> **Note for Beginners:** This is a comprehensive project! Budget 3-4 full days. Work incrementally, test frequently, and don't hesitate to review previous days' materials.

## Project Overview

You'll build a **Product Recommendation Engine** that:
- Tracks which products are viewed together
- Provides personalized recommendations
- Exposes data via API
- Includes admin interface
- Has complete test coverage
- Uses caching for performance
- Follows Shopware best practices

---

## Part 1: Project Planning (30 minutes)

### Feature Requirements

**Core Functionality:**
1. Track when products are viewed together in a session
2. Calculate product affinity scores
3. Store recommendations in database
4. Provide Store API to get recommendations
5. Provide Admin API for analytics
6. Background job to update recommendations
7. Cache frequently accessed data

**Technical Requirements:**
- Custom database tables via migrations
- Event subscribers for tracking
- Services with DI
- Both Store and Admin APIs
- Unit and integration tests
- Proper error handling
- Logging and debugging support
- Cache invalidation strategy

### Architecture Diagram

```
User Views Product
      ↓
Event Subscriber → Track Service → Database
                         ↓
                  Recommendation Engine
                         ↓
                  Cache Layer
                         ↓
                  API (Store + Admin)
                         ↓
                  Storefront / Dashboard
```

---

## Part 2: Database Schema (60 minutes)

### Step 1: Create Migrations

Create `Migration1700000010CreateRecommendationTables.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1700000010CreateRecommendationTables extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1700000010;
    }

    public function update(Connection $connection): void
    {
        // Table for tracking product views in sessions
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `learning_product_session` (
                `id` BINARY(16) NOT NULL,
                `session_id` VARCHAR(255) NOT NULL,
                `product_id` BINARY(16) NOT NULL,
                `product_version_id` BINARY(16) NOT NULL,
                `viewed_at` DATETIME(3) NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx.session_id` (`session_id`),
                KEY `idx.viewed_at` (`viewed_at`),
                CONSTRAINT `fk.learning_product_session.product_id` 
                    FOREIGN KEY (`product_id`, `product_version_id`) 
                    REFERENCES `product` (`id`, `version_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        // Table for storing product recommendations
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `learning_product_recommendation` (
                `id` BINARY(16) NOT NULL,
                `source_product_id` BINARY(16) NOT NULL,
                `source_product_version_id` BINARY(16) NOT NULL,
                `recommended_product_id` BINARY(16) NOT NULL,
                `recommended_product_version_id` BINARY(16) NOT NULL,
                `affinity_score` FLOAT NOT NULL DEFAULT 0,
                `view_count` INT NOT NULL DEFAULT 0,
                `last_updated` DATETIME(3) NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq.source_recommended` (`source_product_id`, `recommended_product_id`),
                KEY `idx.affinity_score` (`affinity_score`),
                CONSTRAINT `fk.learning_recommendation.source_product_id` 
                    FOREIGN KEY (`source_product_id`, `source_product_version_id`) 
                    REFERENCES `product` (`id`, `version_id`) ON DELETE CASCADE,
                CONSTRAINT `fk.learning_recommendation.recommended_product_id` 
                    FOREIGN KEY (`recommended_product_id`, `recommended_product_version_id`) 
                    REFERENCES `product` (`id`, `version_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        $connection->executeStatement('DROP TABLE IF EXISTS `learning_product_recommendation`');
        $connection->executeStatement('DROP TABLE IF EXISTS `learning_product_session`');
    }
}
```

Run migration:

```bash
bin/console database:migrate --all LearningBundle
```

---

## Part 3: Entity Definitions (60 minutes)

### Step 1: Create Product Session Entity

Create the entity class, collection, and definition following Day 3 patterns:

**ProductSessionEntity.php:**
```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\Recommendation;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ProductSessionEntity extends Entity
{
    use EntityIdTrait;

    protected string $sessionId;
    protected string $productId;
    protected \DateTimeInterface $viewedAt;
    protected ?ProductEntity $product = null;

    // Getters and setters...
}
```

**ProductSessionDefinition.php:**
```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\Recommendation;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ProductSessionDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'learning_product_session';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return ProductSessionEntity::class;
    }

    public function getCollectionClass(): string
    {
        return ProductSessionCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('session_id', 'sessionId'))->addFlags(new Required()),
            (new FkField('product_id', 'productId', ProductDefinition::class))->addFlags(new Required()),
            (new ReferenceVersionField(ProductDefinition::class))->addFlags(new Required()),
            (new DateTimeField('viewed_at', 'viewedAt'))->addFlags(new Required()),
            new ManyToOneAssociationField('product', 'product_id', ProductDefinition::class),
        ]);
    }
}
```

### Step 2: Create Product Recommendation Entity

Follow the same pattern for `ProductRecommendationEntity` and `ProductRecommendationDefinition`.

Register both definitions in `services.xml`:

```xml
<service id="Learning\Bundle\Core\Content\Recommendation\ProductSessionDefinition">
    <tag name="shopware.entity.definition" entity="learning_product_session"/>
</service>

<service id="Learning\Bundle\Core\Content\Recommendation\ProductRecommendationDefinition">
    <tag name="shopware.entity.definition" entity="learning_product_recommendation"/>
</service>
```

---

## Part 4: Tracking Service (75 minutes)

### Create Recommendation Tracking Service

Create `ProductRecommendationTrackingService.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class ProductRecommendationTrackingService
{
    private const SESSION_WINDOW_MINUTES = 30;

    private EntityRepository $productSessionRepository;
    private EntityRepository $recommendationRepository;
    private LoggerInterface $logger;

    public function __construct(
        EntityRepository $productSessionRepository,
        EntityRepository $recommendationRepository,
        LoggerInterface $logger
    ) {
        $this->productSessionRepository = $productSessionRepository;
        $this->recommendationRepository = $recommendationRepository;
        $this->logger = $logger;
    }

    /**
     * Track a product view in a session
     */
    public function trackProductView(string $sessionId, string $productId, Context $context): void
    {
        try {
            // Record the view
            $this->productSessionRepository->create([
                [
                    'id' => Uuid::randomHex(),
                    'sessionId' => $sessionId,
                    'productId' => $productId,
                    'viewedAt' => new \DateTime(),
                ]
            ], $context);

            // Update recommendations based on recent views in this session
            $this->updateRecommendationsForSession($sessionId, $productId, $context);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to track product view', [
                'session_id' => $sessionId,
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update recommendations based on recently viewed products in session
     */
    private function updateRecommendationsForSession(string $sessionId, string $currentProductId, Context $context): void
    {
        // Get products viewed in this session within the time window
        $windowStart = new \DateTime();
        $windowStart->modify('-' . self::SESSION_WINDOW_MINUTES . ' minutes');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('sessionId', $sessionId));
        $criteria->addFilter(new RangeFilter('viewedAt', [
            RangeFilter::GTE => $windowStart->format('Y-m-d H:i:s'),
        ]));

        $recentViews = $this->productSessionRepository->search($criteria, $context);

        // Create/update recommendations between viewed products
        foreach ($recentViews as $view) {
            $otherProductId = $view->getProductId();
            
            // Don't create recommendation to itself
            if ($otherProductId === $currentProductId) {
                continue;
            }

            // Create bidirectional recommendations
            $this->upsertRecommendation($currentProductId, $otherProductId, $context);
            $this->upsertRecommendation($otherProductId, $currentProductId, $context);
        }
    }

    /**
     * Create or update a recommendation relationship
     */
    private function upsertRecommendation(string $sourceProductId, string $targetProductId, Context $context): void
    {
        // Check if recommendation exists
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('sourceProductId', $sourceProductId));
        $criteria->addFilter(new EqualsFilter('recommendedProductId', $targetProductId));

        $existing = $this->recommendationRepository->search($criteria, $context)->first();

        if ($existing) {
            // Update existing
            $newViewCount = $existing->getViewCount() + 1;
            $newScore = $this->calculateAffinityScore($newViewCount);

            $this->recommendationRepository->update([
                [
                    'id' => $existing->getId(),
                    'viewCount' => $newViewCount,
                    'affinityScore' => $newScore,
                    'lastUpdated' => new \DateTime(),
                ]
            ], $context);
        } else {
            // Create new
            $this->recommendationRepository->create([
                [
                    'id' => Uuid::randomHex(),
                    'sourceProductId' => $sourceProductId,
                    'recommendedProductId' => $targetProductId,
                    'viewCount' => 1,
                    'affinityScore' => $this->calculateAffinityScore(1),
                    'lastUpdated' => new \DateTime(),
                ]
            ], $context);
        }
    }

    /**
     * Calculate affinity score based on view count
     * This is a simple algorithm - you can make it more sophisticated
     */
    private function calculateAffinityScore(int $viewCount): float
    {
        // Logarithmic scale: score = log10(views + 1) * 10
        return log10($viewCount + 1) * 10;
    }

    /**
     * Get recommendations for a product
     */
    public function getRecommendations(string $productId, int $limit, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('sourceProductId', $productId));
        $criteria->addAssociation('recommendedProduct');
        $criteria->addSorting(new FieldSorting('affinityScore', FieldSorting::DESCENDING));
        $criteria->setLimit($limit);

        $recommendations = $this->recommendationRepository->search($criteria, $context);

        $result = [];
        foreach ($recommendations as $recommendation) {
            $result[] = [
                'product_id' => $recommendation->getRecommendedProductId(),
                'product_name' => $recommendation->getRecommendedProduct()?->getName(),
                'affinity_score' => $recommendation->getAffinityScore(),
                'view_count' => $recommendation->getViewCount(),
            ];
        }

        return $result;
    }
}
```

Register service:

```xml
<service id="Learning\Bundle\Service\ProductRecommendationTrackingService">
    <argument type="service" id="learning_product_session.repository"/>
    <argument type="service" id="learning_product_recommendation.repository"/>
    <argument type="service" id="logger"/>
</service>
```

---

## Part 5: Event Tracking (45 minutes)

### Create Subscriber to Track Views

Create `RecommendationTrackingSubscriber.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Subscriber;

use Learning\Bundle\Service\ProductRecommendationTrackingService;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class RecommendationTrackingSubscriber implements EventSubscriberInterface
{
    private ProductRecommendationTrackingService $trackingService;
    private RequestStack $requestStack;

    public function __construct(
        ProductRecommendationTrackingService $trackingService,
        RequestStack $requestStack
    ) {
        $this->trackingService = $trackingService;
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => 'onProductPageLoaded',
        ];
    }

    public function onProductPageLoaded(ProductPageLoadedEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        
        if (!$request) {
            return;
        }

        $session = $request->getSession();
        $sessionId = $session->getId();
        $productId = $event->getPage()->getProduct()->getId();

        // Track the view
        $this->trackingService->trackProductView(
            $sessionId,
            $productId,
            $event->getContext()
        );
    }
}
```

Register subscriber:

```xml
<service id="Learning\Bundle\Subscriber\RecommendationTrackingSubscriber">
    <argument type="service" id="Learning\Bundle\Service\ProductRecommendationTrackingService"/>
    <argument type="service" id="request_stack"/>
    <tag name="kernel.event_subscriber"/>
</service>
```

---

## Part 6: API Endpoints (75 minutes)

### Step 1: Store API Route

Create `RecommendationRoute.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\Recommendation\SalesChannel;

use Learning\Bundle\Service\ProductRecommendationTrackingService;
use OpenApi\Annotations as OA;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"store-api"}})
 */
class RecommendationRoute
{
    private ProductRecommendationTrackingService $trackingService;

    public function __construct(ProductRecommendationTrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * @OA\Get(
     *     path="/store-api/learning/recommendations/{productId}",
     *     summary="Get product recommendations",
     *     operationId="getRecommendations",
     *     tags={"Store API", "Recommendations"},
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         @OA\Schema(type="integer", default=5)
     *     ),
     *     @OA\Response(response="200", description="Product recommendations")
     * )
     * @Route(
     *     "/store-api/learning/recommendations/{productId}",
     *     name="store-api.learning.recommendations",
     *     methods={"GET"}
     * )
     */
    public function getRecommendations(
        string $productId,
        Request $request,
        SalesChannelContext $context
    ): JsonResponse {
        $limit = (int) $request->query->get('limit', 5);
        
        $recommendations = $this->trackingService->getRecommendations(
            $productId,
            $limit,
            $context->getContext()
        );

        return new JsonResponse([
            'success' => true,
            'data' => $recommendations,
            'total' => count($recommendations),
        ]);
    }
}
```

### Step 2: Admin API Controller

Create admin controller for analytics (similar to Day 4).

---

## Part 7: Caching Layer (45 minutes)

### Add Caching to Recommendation Service

Create cached wrapper service following Day 6 patterns:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Shopware\Core\Framework\Context;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CachedRecommendationService
{
    private const CACHE_PREFIX = 'learning_recommendations_';
    private const CACHE_TTL = 1800; // 30 minutes

    private ProductRecommendationTrackingService $trackingService;
    private CacheInterface $cache;

    public function __construct(
        ProductRecommendationTrackingService $trackingService,
        CacheInterface $cache
    ) {
        $this->trackingService = $trackingService;
        $this->cache = $cache;
    }

    public function getRecommendations(string $productId, int $limit, Context $context): array
    {
        $cacheKey = self::CACHE_PREFIX . $productId . '_' . $limit;

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($productId, $limit, $context) {
            $item->expiresAfter(self::CACHE_TTL);
            $item->tag(['recommendations', 'product-' . $productId]);
            
            return $this->trackingService->getRecommendations($productId, $limit, $context);
        });
    }

    public function invalidate(string $productId): void
    {
        $this->cache->delete(self::CACHE_PREFIX . $productId . '_5');
        // Invalidate other common limits
        $this->cache->delete(self::CACHE_PREFIX . $productId . '_10');
    }
}
```

---

## Part 8: Testing (90 minutes)

### Write Comprehensive Tests

**Unit Tests:**
- Test affinity score calculation
- Test recommendation logic
- Test cache behavior

**Integration Tests:**
- Test full tracking flow
- Test API endpoints
- Test database operations

Example test:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Tests\Integration\Service;

use Learning\Bundle\Service\ProductRecommendationTrackingService;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

class RecommendationTrackingIntegrationTest extends TestCase
{
    use IntegrationTestBehaviour;

    private ProductRecommendationTrackingService $service;

    protected function setUp(): void
    {
        $this->service = $this->getContainer()->get(ProductRecommendationTrackingService::class);
    }

    public function testTrackingCreatesRecommendations(): void
    {
        $context = Context::createDefaultContext();
        $sessionId = 'test-session';
        $productA = $this->getTestProductId();
        $productB = $this->getTestProductId();

        // Track views in session
        $this->service->trackProductView($sessionId, $productA, $context);
        $this->service->trackProductView($sessionId, $productB, $context);

        // Should create bidirectional recommendations
        $recommendationsForA = $this->service->getRecommendations($productA, 10, $context);
        $recommendationsForB = $this->service->getRecommendations($productB, 10, $context);

        // Assert recommendations were created
        $this->assertNotEmpty($recommendationsForA);
        $this->assertNotEmpty($recommendationsForB);
    }

    private function getTestProductId(): string
    {
        // Get or create test product
        return 'test-product-id';
    }
}
```

Run tests:

```bash
cd custom/plugins/LearningBundle
../../../vendor/bin/phpunit
```

---

## Part 9: Documentation (30 minutes)

### Create Project Documentation

Create `RECOMMENDATION_ENGINE.md`:

```markdown
# Product Recommendation Engine

## Overview
Tracks product views in sessions and generates intelligent product recommendations based on co-viewing patterns.

## Features
- Session-based view tracking
- Affinity score calculation
- Store API for recommendations
- Admin API for analytics
- Cached for performance
- Comprehensive test coverage

## API Endpoints

### Store API

**GET** `/store-api/learning/recommendations/{productId}?limit=5`

Returns recommended products for a given product.

### Admin API

**GET** `/api/_action/learning/recommendations/analytics`

Returns analytics about the recommendation engine.

## Database Schema

### learning_product_session
Tracks individual product views in sessions.

### learning_product_recommendation
Stores calculated product relationships and affinity scores.

## Configuration

No configuration needed - works out of the box!

## Testing

```bash
bin/phpunit custom/plugins/LearningBundle
```

## Performance

- Recommendations are cached for 30 minutes
- Background job can recalculate scores (optional)
- Handles high traffic scenarios

## Future Enhancements

- Machine learning integration
- Personalized recommendations per customer
- Category-based fallbacks
- A/B testing support
```

---

## Part 10: Review and Polish (45 minutes)

### Checklist

✅ **Code Quality:**
- [ ] All services have proper type hints
- [ ] Error handling is comprehensive
- [ ] Logging is appropriately verbose
- [ ] Code follows PSR standards

✅ **Testing:**
- [ ] Unit tests for core logic
- [ ] Integration tests for workflows
- [ ] API tests pass
- [ ] Test coverage > 70%

✅ **Performance:**
- [ ] Caching implemented
- [ ] Database queries optimized
- [ ] No N+1 query problems

✅ **Documentation:**
- [ ] Code is well-commented
- [ ] README exists
- [ ] API documented
- [ ] Setup instructions clear

✅ **Best Practices:**
- [ ] Events used for loose coupling
- [ ] Dependency injection used correctly
- [ ] Services are stateless
- [ ] Following Shopware conventions

---

## Bonus Challenges

If you finish early, try these enhancements:

### 1. Admin Dashboard Widget
Create a dashboard widget showing top recommended product pairs.

### 2. Scheduled Task
Create a scheduled task that recalculates recommendations nightly:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class RecalculateRecommendationsTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'learning.recalculate_recommendations';
    }

    public static function getDefaultInterval(): int
    {
        return 86400; // Daily
    }
}
```

### 3. Recommendation Quality Metrics
Add tracking to measure if recommendations lead to actual purchases.

### 4. Export Functionality
Add ability to export recommendation data as CSV via Admin API.

---

## Final Review

### What You've Built

A complete, production-ready Shopware plugin with:
- ✅ Custom database schema
- ✅ Entity definitions
- ✅ Business logic services
- ✅ Event-driven architecture
- ✅ Store API endpoints
- ✅ Admin API endpoints
- ✅ Caching strategy
- ✅ Comprehensive tests
- ✅ Error handling
- ✅ Logging
- ✅ Documentation

### What You've Learned This Week

**Day 1:** Plugin structure, services, configuration  
**Day 2:** Events, subscribers, dependency injection  
**Day 3:** Database, migrations, entities, repositories  
**Day 4:** API architecture, Store API, Admin API  
**Day 5:** Debugging, logging, error handling  
**Day 6:** Testing, caching, performance  
**Day 7:** Complete feature implementation  

---

## Submission Checklist

Before considering the project complete:

- [ ] All code is committed to git
- [ ] Plugin installs without errors
- [ ] All migrations run successfully
- [ ] Tests pass
- [ ] APIs are functional
- [ ] Cache is working
- [ ] Documentation is complete
- [ ] Code is clean and commented

---

## Next Steps

**Continue Learning:**
1. Build more complex features
2. Contribute to Shopware community plugins
3. Explore Shopware Administration customization
4. Learn about Shopware PWA
5. Study advanced patterns (CQRS, Event Sourcing)

**Resources:**
- [Shopware Developer Documentation](https://developer.shopware.com/)
- [Shopware Community Slack](https://slack.shopware.com/)
- [Shopware Forum](https://forum.shopware.com/)
- [GitHub - Shopware](https://github.com/shopware)

---

**Estimated Completion Time:** 6-8 hours  
**Difficulty:** Advanced

🎉 **CONGRATULATIONS!** 🎉

You've completed the intensive Shopware 6 plugin development course. You now have the skills to build professional Shopware plugins independently!

**Keep coding, keep learning, and welcome to the Shopware developer community!** 🚀
