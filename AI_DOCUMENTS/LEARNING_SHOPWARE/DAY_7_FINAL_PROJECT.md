# Day 7-10: Final Project - Build a Complete Feature

**Duration:** 3-4 days (20-28 hours with breaks)  
**Goal:** Build a complete, production-ready feature using everything learned

> **Note for Beginners:** This is a comprehensive project! Budget 3-4 full days. Work incrementally, test frequently, and don't hesitate to review previous days' materials.

## Project Overview

You'll build a **Product Recommendation Engine** that:
- Tracks which products are viewed together
- **Displays recommendations in beautiful storefront components** ⭐
- **Interactive "You May Also Like" carousel with JavaScript** ⭐
- **AJAX-powered "Quick Add" without page reload** ⭐
- Provides personalized recommendations via API
- **Responsive, animated UI with custom SCSS** ⭐
- **Vue.js 3 Administration Dashboard with analytics** ⭐ (NEW!)
- **PWA features: offline caching & installability** ⭐ (NEW!)
- Has complete test coverage
- Uses caching for performance
- Follows Shopware best practices

**Full-Stack Frontend Focus:** This project covers **ALL Shopware 6.7 frontend technologies**:
- 🛒 **Storefront**: Twig + Vanilla JS plugins + SCSS (customer-facing)
- 🎛️ **Administration**: Vue.js 3 + Meteor Components (backend dashboard)
- 📱 **PWA**: Service Workers + App Manifest (progressive enhancement)

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

**Backend (40%):**
- Custom database tables via migrations
- Event subscribers for tracking
- Services with DI
- Store API for recommendations
- Admin API for analytics
- Unit and integration tests
- Proper error handling
- Cache invalidation strategy

**Storefront Frontend (40%):**
- Custom Twig templates for recommendation display
- JavaScript plugin for interactive carousel
- AJAX "Quick Add to Cart" functionality
- Responsive SCSS styling with animations
- Loading states and error handling
- Cross-browser compatibility
- Accessibility (keyboard navigation, ARIA labels)

**Administration Frontend (15%) - Vue.js 3:**
- Custom admin module with Vue.js 3
- Interactive analytics dashboard
- Shopware Meteor component library (sw-* components)
- Data visualization with charts
- Real-time data updates via Admin API

**PWA Enhancement (5%):**
- Service Worker for offline recommendation caching
- Web App Manifest for installability
- "Add to Home Screen" capability
- Offline-first user experience

### Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────────┐
│  STOREFRONT (Customer-Facing) - Twig + Vanilla JS                       │
├─────────────────────────────────────────────────────────────────────────┤
│  Product Detail Page                                                    │
│       ↓                                                                 │
│  📦 Recommendation Widget (Twig Template)                               │
│       ↓                                                                 │
│  🎨 Interactive Carousel (JavaScript Plugin)                            │
│       ↓                                                                 │
│  ⚡ AJAX Quick Add (No Page Reload)                                      │
│       ↓                                                                 │
│  💅 Responsive Styling (SCSS)                                           │
│       ↓                                                                 │
│  📱 PWA: Service Worker + Offline Cache (NEW!)                          │
└─────────────────────────────────────────────────────────────────────────┘
                     ↓ Store API Call
┌─────────────────────────────────────────────────────────────────────────┐
│  BACKEND (PHP Services)                                                 │
├─────────────────────────────────────────────────────────────────────────┤
│  Store API ←──────────────────────────────→ Admin API                   │
│       ↓                                          ↓                      │
│  Cache Layer                               Analytics Service            │
│       ↓                                          ↓                      │
│  Recommendation Engine Service ←─────→ Database (Tracking + Scores)     │
│       ↑                                                                 │
│  Event Subscriber (Product Views)                                       │
└─────────────────────────────────────────────────────────────────────────┘
                     ↑ Admin API Call
┌─────────────────────────────────────────────────────────────────────────┐
│  ADMINISTRATION (Backend Dashboard) - Vue.js 3 (NEW!)                   │
├─────────────────────────────────────────────────────────────────────────┤
│  🎛️ Custom Admin Module (Vue.js 3 SPA)                                  │
│       ↓                                                                 │
│  📊 Analytics Dashboard (Meteor Components)                             │
│       ↓                                                                 │
│  📈 Interactive Charts (Data Visualization)                             │
│       ↓                                                                 │
│  ⚙️ Configuration Panel (Recommendation Settings)                       │
└─────────────────────────────────────────────────────────────────────────┘
```

### Technology Stack Overview

| Layer | Technology | Framework | Build Tool |
|-------|------------|-----------|------------|
| **Storefront** | Twig + Vanilla JS | Bootstrap 5 | Webpack |
| **Administration** | Vue.js 3 + TypeScript | Meteor Components | Vite |
| **PWA** | Service Workers | Web APIs | Native |
| **Backend** | PHP 8.2+ | Symfony | Composer |

---

## Part 2: Database Schema (60 minutes)

### Step 1: Create Migrations

Create `Migration1700000010CreateRecommendationTables.php`:

File: custom/plugins/LearningBundle/src/Migration/Migration1700000010CreateRecommendationTables.php

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

File: custom/plugins/LearningBundle/src/Core/Content/Recommendation/ProductSessionEntity.php

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
    protected string $productVersionId;
    protected \DateTimeInterface $viewedAt;
    protected ?ProductEntity $product = null;

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function setSessionId(string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getProductVersionId(): string
    {
        return $this->productVersionId;
    }

    public function setProductVersionId(string $productVersionId): void
    {
        $this->productVersionId = $productVersionId;
    }

    public function getViewedAt(): \DateTimeInterface
    {
        return $this->viewedAt;
    }

    public function setViewedAt(\DateTimeInterface $viewedAt): void
    {
        $this->viewedAt = $viewedAt;
    }

    public function getProduct(): ?ProductEntity
    {
        return $this->product;
    }

    public function setProduct(?ProductEntity $product): void
    {
        $this->product = $product;
    }
}
```

---

### Step 1b: Add a Collection Class for Product Sessions

To enable Shopware to work with groups of `ProductSessionEntity` objects, introduce a collection class:

File: custom/plugins/LearningBundle/src/Core/Content/Recommendation/ProductSessionCollection.php

**ProductSessionCollection.php:**
```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\Recommendation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class ProductSessionCollection extends EntityCollection
{
    /**
     * Returns the class name of the entity this collection contains.
     */
    protected function getExpectedClass(): string
    {
        return ProductSessionEntity::class;
    }
}
```

> **Note:** This collection is referenced in your entity definition and is necessary for Shopware’s repository and data abstraction features.

Now, update your entity definition to reference this collection:

File: custom/plugins/LearningBundle/src/Core/Content/Recommendation/ProductSessionDefinition.php

Add or update the following method:

```php
public function getCollectionClass(): string
{
    return ProductSessionCollection::class;
}
```

---

File: custom/plugins/LearningBundle/src/Core/Content/Recommendation/ProductSessionDefinition.php

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

File: custom/plugins/LearningBundle/src/Core/Content/Recommendation/ProductRecommendationEntity.php

Follow the same pattern for `ProductRecommendationEntity` and `ProductRecommendationDefinition`.

Register both definitions in `services.xml`:

File: custom/plugins/LearningBundle/src/Resources/config/services.xml

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

File: custom/plugins/LearningBundle/src/Service/ProductRecommendationTrackingService.php

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

File: custom/plugins/LearningBundle/src/Subscriber/RecommendationTrackingSubscriber.php

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

File: custom/plugins/LearningBundle/src/Core/Content/Recommendation/SalesChannel/RecommendationRoute.php

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

## Part 7: Frontend Implementation (180 minutes) 🎨

> **This is the heart of the project!** You'll build beautiful, interactive recommendation displays that users actually see and interact with.

### Step 1: Create Twig Template Extension (30 minutes)

Create `storefront/page/product-detail/recommendations.html.twig`:

File: custom/plugins/LearningBundle/src/Resources/views/storefront/page/product-detail/recommendations.html.twig

```twig
{% sw_extends '@Storefront/storefront/page/product-detail/index.html.twig' %}

{% block page_product_detail_description_content_inner %}
    {{ parent() }}
    
    {# Recommendations Widget #}
    <div class="learning-recommendations-wrapper">
        <div class="learning-recommendations" 
             data-learning-recommendations="true"
             data-product-id="{{ page.product.id }}">
            
            <div class="recommendations-header">
                <h3 class="recommendations-title">
                    {{ "learning.recommendations.title"|trans }}
                </h3>
                <p class="recommendations-subtitle">
                    {{ "learning.recommendations.subtitle"|trans }}
                </p>
            </div>

            {# Loading State #}
            <div class="recommendations-loading">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>{{ "learning.recommendations.loading"|trans }}</p>
            </div>

            {# Recommendations Container (populated by JavaScript) #}
            <div class="recommendations-carousel" 
                 data-recommendations-carousel="true">
                {# Dynamically loaded content #}
            </div>

            {# Error State #}
            <div class="recommendations-error" style="display: none;">
                <p class="text-muted">
                    {{ "learning.recommendations.error"|trans }}
                </p>
            </div>

            {# Empty State #}
            <div class="recommendations-empty" style="display: none;">
                <p class="text-muted">
                    {{ "learning.recommendations.empty"|trans }}
                </p>
            </div>
        </div>
    </div>
{% endblock %}
```

### Step 2: Create Product Card Template (20 minutes)

Create `storefront/component/product/recommendation-card.html.twig`:

File: custom/plugins/LearningBundle/src/Resources/views/storefront/component/product/recommendation-card.html.twig

```twig
{# Recommendation Product Card Template #}
<div class="recommendation-card" data-product-id="{{ product.id }}">
    
    {# Product Image with Link #}
    <a href="{{ seoUrl('frontend.detail.page', {'productId': product.id}) }}" 
       class="recommendation-card-image-link">
        {% if product.cover %}
            <img src="{{ product.cover.media.url }}" 
                 alt="{{ product.translated.name }}"
                 class="recommendation-card-image"
                 loading="lazy">
        {% else %}
            <div class="recommendation-card-placeholder">
                <svg width="48" height="48" fill="currentColor">
                    <use xlink:href="#icon-placeholder"></use>
                </svg>
            </div>
        {% endif %}
        
        {# Affinity Badge #}
        {% if affinityScore %}
            <span class="recommendation-badge">
                {{ affinityScore|number_format(0) }}% Match
            </span>
        {% endif %}
    </a>

    {# Product Info #}
    <div class="recommendation-card-body">
        <a href="{{ seoUrl('frontend.detail.page', {'productId': product.id}) }}" 
           class="recommendation-card-title">
            {{ product.translated.name }}
        </a>
        
        {# Price Display #}
        <div class="recommendation-card-price">
            {% if product.calculatedPrice %}
                <span class="price">
                    {{ product.calculatedPrice.unitPrice|currency }}
                </span>
            {% endif %}
        </div>

        {# Quick Actions #}
        <div class="recommendation-card-actions">
            {# Quick View Button #}
            <button type="button" 
                    class="btn btn-sm btn-outline-secondary recommendation-quick-view"
                    data-quick-view-product-id="{{ product.id }}"
                    title="{{ 'learning.recommendations.quickView'|trans }}">
                <svg width="16" height="16">
                    <use xlink:href="#icon-eye"></use>
                </svg>
            </button>

            {# Quick Add to Cart Button #}
            <button type="button"
                    class="btn btn-sm btn-primary recommendation-quick-add"
                    data-product-id="{{ product.id }}"
                    data-quick-add="true">
                <span class="button-text">
                    {{ "learning.recommendations.addToCart"|trans }}
                </span>
                <span class="button-loading" style="display: none;">
                    <span class="spinner-border spinner-border-sm"></span>
                </span>
            </button>
        </div>
    </div>
</div>
```

### Step 3: Create JavaScript Plugin (90 minutes)

Create `src/Resources/app/storefront/src/plugin/recommendation/recommendation-carousel.plugin.js`:

File: custom/plugins/LearningBundle/src/Resources/app/storefront/src/plugin/recommendation/recommendation-carousel.plugin.js

```javascript
import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';

/**
 * Product Recommendations Carousel Plugin
 * 
 * Loads and displays personalized product recommendations
 * with interactive carousel and AJAX add-to-cart
 */
export default class RecommendationCarouselPlugin extends Plugin {
    
    static options = {
        // API endpoints
        recommendationsUrl: '/store-api/learning/recommendations',
        addToCartUrl: '/checkout/line-item/add',
        
        // Display options
        limit: 6,
        autoplay: false,
        autoplaySpeed: 5000,
        
        // Selectors
        carouselSelector: '[data-recommendations-carousel]',
        loadingSelector: '.recommendations-loading',
        errorSelector: '.recommendations-error',
        emptySelector: '.recommendations-empty',
        quickAddButtonSelector: '[data-quick-add]',
        
        // Animation
        slideSpeed: 300,
    };

    init() {
        this.httpClient = new HttpClient();
        this.productId = this.el.dataset.productId;
        
        this.carousel = this.el.querySelector(this.options.carouselSelector);
        this.loadingEl = this.el.querySelector(this.options.loadingSelector);
        this.errorEl = this.el.querySelector(this.options.errorSelector);
        this.emptyEl = this.el.querySelector(this.options.emptySelector);
        
        this.currentIndex = 0;
        this.recommendations = [];
        
        // Load recommendations
        this.loadRecommendations();
    }

    /**
     * Load recommendations from API
     */
    async loadRecommendations() {
        try {
            this.showLoading();
            
            const url = `${this.options.recommendationsUrl}/${this.productId}?limit=${this.options.limit}`;
            
            const response = await this.httpClient.get(url, (responseText) => {
                return JSON.parse(responseText);
            });

            if (response.success && response.data && response.data.length > 0) {
                this.recommendations = response.data;
                this.renderRecommendations();
                this.initializeCarousel();
                this.registerQuickAddHandlers();
            } else {
                this.showEmpty();
            }
            
        } catch (error) {
            console.error('Failed to load recommendations:', error);
            this.showError();
        }
    }

    /**
     * Render recommendation cards in carousel
     */
    renderRecommendations() {
        this.hideLoading();
        
        const cardsHtml = this.recommendations.map((rec, index) => {
            return this.createProductCard(rec, index);
        }).join('');
        
        this.carousel.innerHTML = `
            <div class="carousel-track">
                ${cardsHtml}
            </div>
            <button class="carousel-nav carousel-nav-prev" data-carousel-prev>
                <svg width="24" height="24">
                    <use xlink:href="#icon-chevron-left"></use>
                </svg>
            </button>
            <button class="carousel-nav carousel-nav-next" data-carousel-next>
                <svg width="24" height="24">
                    <use xlink:href="#icon-chevron-right"></use>
                </svg>
            </button>
            <div class="carousel-indicators">
                ${this.createIndicators()}
            </div>
        `;
        
        this.carousel.style.display = 'block';
    }

    /**
     * Create HTML for a single product card
     */
    createProductCard(recommendation, index) {
        const product = recommendation.product;
        const score = recommendation.affinityScore;
        
        return `
            <div class="recommendation-card" 
                 data-index="${index}"
                 data-product-id="${product.id}">
                
                <a href="/detail/${product.id}" class="recommendation-card-image-link">
                    ${this.createProductImage(product)}
                    ${score ? `<span class="recommendation-badge">${Math.round(score)}% Match</span>` : ''}
                </a>

                <div class="recommendation-card-body">
                    <a href="/detail/${product.id}" class="recommendation-card-title">
                        ${product.translated.name}
                    </a>
                    
                    <div class="recommendation-card-price">
                        <span class="price">${this.formatPrice(product.calculatedPrice)}</span>
                    </div>

                    <div class="recommendation-card-actions">
                        <button type="button" 
                                class="btn btn-sm btn-primary recommendation-quick-add"
                                data-product-id="${product.id}"
                                data-quick-add="true">
                            <span class="button-text">Add to Cart</span>
                            <span class="button-loading" style="display: none;">
                                <span class="spinner-border spinner-border-sm"></span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Create product image HTML
     */
    createProductImage(product) {
        if (product.cover && product.cover.media) {
            return `<img src="${product.cover.media.url}" 
                         alt="${product.translated.name}"
                         class="recommendation-card-image"
                         loading="lazy">`;
        }
        return `<div class="recommendation-card-placeholder">
                    <svg width="48" height="48" fill="currentColor">
                        <use xlink:href="#icon-placeholder"></use>
                    </svg>
                </div>`;
    }

    /**
     * Initialize carousel navigation
     */
    initializeCarousel() {
        const prevBtn = this.carousel.querySelector('[data-carousel-prev]');
        const nextBtn = this.carousel.querySelector('[data-carousel-next]');
        
        if (prevBtn) {
            prevBtn.addEventListener('click', () => this.slideToPrev());
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', () => this.slideToNext());
        }
        
        // Autoplay if enabled
        if (this.options.autoplay) {
            this.startAutoplay();
        }
    }

    /**
     * Slide to previous item
     */
    slideToPrev() {
        if (this.currentIndex > 0) {
            this.currentIndex--;
            this.updateCarouselPosition();
        }
    }

    /**
     * Slide to next item
     */
    slideToNext() {
        if (this.currentIndex < this.recommendations.length - 1) {
            this.currentIndex++;
            this.updateCarouselPosition();
        }
    }

    /**
     * Update carousel visual position
     */
    updateCarouselPosition() {
        const track = this.carousel.querySelector('.carousel-track');
        const cardWidth = 250; // Adjust based on your design
        const offset = -(this.currentIndex * cardWidth);
        
        track.style.transform = `translateX(${offset}px)`;
        track.style.transition = `transform ${this.options.slideSpeed}ms ease-in-out`;
        
        this.updateIndicators();
    }

    /**
     * Register handlers for quick add buttons
     */
    registerQuickAddHandlers() {
        const buttons = this.carousel.querySelectorAll(this.options.quickAddButtonSelector);
        
        buttons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleQuickAdd(button);
            });
        });
    }

    /**
     * Handle AJAX add to cart
     */
    async handleQuickAdd(button) {
        const productId = button.dataset.productId;
        const buttonText = button.querySelector('.button-text');
        const buttonLoading = button.querySelector('.button-loading');
        
        try {
            // Show loading state
            button.disabled = true;
            buttonText.style.display = 'none';
            buttonLoading.style.display = 'inline-block';
            
            // Prepare form data
            const formData = new FormData();
            formData.append('lineItems[0][id]', productId);
            formData.append('lineItems[0][type]', 'product');
            formData.append('lineItems[0][referencedId]', productId);
            formData.append('lineItems[0][quantity]', '1');
            
            // Send AJAX request
            await this.httpClient.post(
                this.options.addToCartUrl,
                formData,
                () => {
                    // Success!
                    this.showSuccessFeedback(button);
                    
                    // Dispatch event for cart widget update
                    this.$emitter.publish('addToCart', { productId });
                }
            );
            
        } catch (error) {
            console.error('Failed to add to cart:', error);
            this.showErrorFeedback(button);
        } finally {
            // Reset button state
            button.disabled = false;
            buttonText.style.display = 'inline-block';
            buttonLoading.style.display = 'none';
        }
    }

    /**
     * Show success feedback after adding to cart
     */
    showSuccessFeedback(button) {
        const originalText = button.querySelector('.button-text').textContent;
        button.querySelector('.button-text').textContent = '✓ Added!';
        button.classList.add('btn-success');
        
        setTimeout(() => {
            button.querySelector('.button-text').textContent = originalText;
            button.classList.remove('btn-success');
        }, 2000);
    }

    /**
     * Show error feedback
     */
    showErrorFeedback(button) {
        button.classList.add('btn-danger');
        setTimeout(() => {
            button.classList.remove('btn-danger');
        }, 2000);
    }

    // State management helpers
    showLoading() {
        this.loadingEl.style.display = 'block';
        this.carousel.style.display = 'none';
        this.errorEl.style.display = 'none';
        this.emptyEl.style.display = 'none';
    }

    hideLoading() {
        this.loadingEl.style.display = 'none';
    }

    showError() {
        this.loadingEl.style.display = 'none';
        this.errorEl.style.display = 'block';
    }

    showEmpty() {
        this.loadingEl.style.display = 'none';
        this.emptyEl.style.display = 'block';
    }

    // Helper methods
    formatPrice(priceObj) {
        if (!priceObj) return '';
        // Simple formatting - adjust for your locale
        return `$${priceObj.unitPrice.toFixed(2)}`;
    }

    createIndicators() {
        return this.recommendations
            .map((_, i) => `<span class="indicator ${i === 0 ? 'active' : ''}" data-index="${i}"></span>`)
            .join('');
    }

    updateIndicators() {
        const indicators = this.carousel.querySelectorAll('.indicator');
        indicators.forEach((indicator, i) => {
            indicator.classList.toggle('active', i === this.currentIndex);
        });
    }

    startAutoplay() {
        this.autoplayInterval = setInterval(() => {
            if (this.currentIndex < this.recommendations.length - 1) {
                this.slideToNext();
            } else {
                this.currentIndex = 0;
                this.updateCarouselPosition();
            }
        }, this.options.autoplaySpeed);
    }
}
```

Register the plugin in `main.js`:

File: custom/plugins/LearningBundle/src/Resources/app/storefront/src/main.js

```javascript
import RecommendationCarouselPlugin from './plugin/recommendation/recommendation-carousel.plugin';

const PluginManager = window.PluginManager;
PluginManager.register('RecommendationCarousel', RecommendationCarouselPlugin, '[data-learning-recommendations]');
```

### Step 4: Create SCSS Styling (40 minutes)

Create `src/Resources/app/storefront/src/scss/component/_recommendation-carousel.scss`:

File: custom/plugins/LearningBundle/src/Resources/app/storefront/src/scss/component/_recommendation-carousel.scss

```scss
// Recommendation Widget Styles
.learning-recommendations-wrapper {
    margin: 3rem 0;
    padding: 2rem 0;
    border-top: 1px solid #e0e0e0;
}

.learning-recommendations {
    position: relative;

    .recommendations-header {
        margin-bottom: 2rem;
        text-align: center;

        .recommendations-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 0.5rem;
        }

        .recommendations-subtitle {
            color: #666;
            font-size: 0.95rem;
        }
    }

    .recommendations-loading,
    .recommendations-error,
    .recommendations-empty {
        text-align: center;
        padding: 3rem 0;
        color: #666;

        .spinner-border {
            width: 3rem;
            height: 3rem;
            margin-bottom: 1rem;
        }
    }
}

// Carousel Container
.recommendations-carousel {
    position: relative;
    overflow: hidden;
    padding: 0 3rem; // Space for nav buttons

    .carousel-track {
        display: flex;
        gap: 1.5rem;
        transition: transform 0.3s ease-in-out;
    }
}

// Individual Recommendation Card
.recommendation-card {
    flex: 0 0 250px;
    width: 250px;
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;

    &:hover {
        transform: translateY(-8px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        border-color: #007bff;
    }

    .recommendation-card-image-link {
        display: block;
        position: relative;
        overflow: hidden;
        aspect-ratio: 1 / 1;
        background: #f5f5f5;

        .recommendation-card-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        &:hover .recommendation-card-image {
            transform: scale(1.1);
        }

        .recommendation-badge {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            animation: pulse 2s infinite;
        }

        .recommendation-card-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            color: #ccc;
        }
    }

    .recommendation-card-body {
        padding: 1rem;

        .recommendation-card-title {
            display: block;
            font-size: 0.95rem;
            font-weight: 500;
            color: #1a1a1a;
            margin-bottom: 0.5rem;
            text-decoration: none;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;

            &:hover {
                color: #007bff;
            }
        }

        .recommendation-card-price {
            margin-bottom: 1rem;

            .price {
                font-size: 1.25rem;
                font-weight: 700;
                color: #007bff;
            }
        }

        .recommendation-card-actions {
            display: flex;
            gap: 0.5rem;

            .recommendation-quick-add {
                flex: 1;
                position: relative;
                transition: all 0.2s ease;

                &:not(:disabled):hover {
                    transform: scale(1.05);
                }

                &.btn-success {
                    background-color: #28a745;
                    border-color: #28a745;
                }

                &.btn-danger {
                    background-color: #dc3545;
                    border-color: #dc3545;
                }
            }
        }
    }
}

// Carousel Navigation
.carousel-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    z-index: 10;

    &:hover {
        background: #007bff;
        border-color: #007bff;
        color: white;
    }

    &.carousel-nav-prev {
        left: 0;
    }

    &.carousel-nav-next {
        right: 0;
    }
}

// Carousel Indicators
.carousel-indicators {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    margin-top: 1.5rem;

    .indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #d0d0d0;
        cursor: pointer;
        transition: all 0.2s ease;

        &.active {
            background: #007bff;
            width: 24px;
            border-radius: 4px;
        }

        &:hover {
            background: #999;
        }
    }
}

// Animations
@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

// Responsive Design
@media (max-width: 768px) {
    .recommendations-carousel {
        padding: 0 2rem;
    }

    .recommendation-card {
        flex: 0 0 200px;
        width: 200px;
    }

    .carousel-nav {
        width: 32px;
        height: 32px;
    }
}

@media (max-width: 480px) {
    .recommendations-carousel {
        padding: 0 1rem;
    }

    .recommendation-card {
        flex: 0 0 160px;
        width: 160px;
    }
}
```

Import in your main SCSS file:

```scss
@import 'component/recommendation-carousel';
```

### Step 5: Add Translations (10 minutes)

Add translations in `src/Resources/snippet/en_GB/storefront.en-GB.json`:

File: custom/plugins/LearningBundle/src/Resources/snippet/en_GB/storefront.en-GB.json

```json
{
    "learning": {
        "recommendations": {
            "title": "You May Also Like",
            "subtitle": "Based on products viewed together",
            "loading": "Loading recommendations...",
            "error": "Could not load recommendations. Please try again later.",
            "empty": "No recommendations available yet.",
            "addToCart": "Add to Cart",
            "quickView": "Quick View",
            "viewDetails": "View Details"
        }
    }
}
```

### Step 6: Build and Test (10 minutes)

```bash
# Build storefront assets
./bin/build-storefront.sh

# Clear cache
bin/console cache:clear

# Test in browser
# 1. Navigate to any product detail page
# 2. Recommendations should load automatically
# 3. Test carousel navigation
# 4. Test quick add to cart
# 5. Verify responsive behavior on mobile
```

### Frontend Checklist

✅ **Visual Design:**
- [ ] Recommendations display beautifully below product description
- [ ] Cards have hover effects and animations
- [ ] Loading spinner shows while fetching data
- [ ] Error and empty states display appropriately
- [ ] Match badges show affinity scores

✅ **Interactivity:**
- [ ] Carousel slides smoothly left/right
- [ ] Navigation buttons work correctly
- [ ] Indicators update as carousel slides
- [ ] Quick add button adds to cart without reload
- [ ] Success/error feedback displays

✅ **User Experience:**
- [ ] No layout shift during loading
- [ ] Smooth transitions and animations
- [ ] Accessible (keyboard navigation works)
- [ ] Mobile responsive (tested on small screens)
- [ ] Fast performance (no lag)

✅ **Code Quality:**
- [ ] JavaScript follows plugin pattern
- [ ] SCSS is well-organized and reusable
- [ ] Templates use Twig best practices
- [ ] Proper event handling and cleanup
- [ ] Console has no errors

---

## Part 8: Caching Layer (45 minutes)

### Add Caching to Recommendation Service

Create cached wrapper service following Day 6 patterns:

File: custom/plugins/LearningBundle/src/Service/CachedRecommendationService.php

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

## Part 9: Testing (90 minutes)

### Write Comprehensive Tests

**Backend Unit Tests:**

- Test affinity score calculation
- Test recommendation logic
- Test cache behavior

**Backend Integration Tests:**

- Test full tracking flow
- Test API endpoints
- Test database operations

**Frontend Testing (Manual):**

- Test carousel navigation in multiple browsers
- Test AJAX add to cart functionality
- Test loading/error states
- Test responsive behavior on mobile
- Test keyboard accessibility
- Test with slow network (throttle in DevTools)

Example test:

File: custom/plugins/LearningBundle/tests/Integration/Service/RecommendationTrackingIntegrationTest.php

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

File: custom/plugins/LearningBundle/RECOMMENDATION_ENGINE.md

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

## Part 11: Administration Module with Vue.js 3 (120 minutes) 🎛️

> **Learn Vue.js 3 within Shopware!** The Administration uses Vue.js 3 with Shopware's Meteor component library. This section teaches you to build professional admin interfaces.

### Understanding Shopware Administration Architecture

**Key Concepts:**
- Vue.js 3 Single Page Application (SPA)
- Component-based architecture with `sw-*` prefixed components
- Hybrid Twig/Vue templates (unique to Shopware)
- Service-based data fetching via Admin API
- State management with Pinia (replacing Vuex)

### Step 1: Create Admin Module Structure (15 minutes)

Create the module directory structure:

File: custom/plugins/LearningBundle/src/Resources/app/administration/src/module/learning-recommendations/

```
src/Resources/app/administration/
├── src/
│   ├── main.js                          # Entry point
│   └── module/
│       └── learning-recommendations/
│           ├── index.js                 # Module registration
│           ├── page/
│           │   └── learning-recommendations-dashboard/
│           │       ├── index.js
│           │       └── learning-recommendations-dashboard.html.twig
│           ├── component/
│           │   ├── learning-stats-card/
│           │   │   ├── index.js
│           │   │   └── learning-stats-card.html.twig
│           │   └── learning-recommendation-chart/
│           │       ├── index.js
│           │       └── learning-recommendation-chart.html.twig
│           └── snippet/
│               ├── en-GB.json
│               └── de-DE.json
```

### Step 2: Create Module Entry Point (15 minutes)

Create `src/Resources/app/administration/src/main.js`:

File: custom/plugins/LearningBundle/src/Resources/app/administration/src/main.js

```javascript
// Main entry point for the administration module
import './module/learning-recommendations';
```

Create `src/Resources/app/administration/src/module/learning-recommendations/index.js`:

File: custom/plugins/LearningBundle/src/Resources/app/administration/src/module/learning-recommendations/index.js

```javascript
import './page/learning-recommendations-dashboard';
import './component/learning-stats-card';
import './component/learning-recommendation-chart';

import enGB from './snippet/en-GB.json';
import deDE from './snippet/de-DE.json';

// Get the Shopware object
const { Module } = Shopware;

// Register the module
Module.register('learning-recommendations', {
    type: 'plugin',
    name: 'learning-recommendations',
    title: 'learning-recommendations.general.mainMenuItemGeneral',
    description: 'learning-recommendations.general.description',
    color: '#ff68b4',
    icon: 'regular-lightbulb',
    
    // Snippets for translations
    snippets: {
        'en-GB': enGB,
        'de-DE': deDE
    },

    // Main menu entry
    navigation: [{
        id: 'learning-recommendations',
        label: 'learning-recommendations.general.mainMenuItemGeneral',
        color: '#ff68b4',
        icon: 'regular-lightbulb',
        path: 'learning.recommendations.dashboard',
        position: 100,
        parent: 'sw-marketing'
    }],

    // Routes
    routes: {
        dashboard: {
            component: 'learning-recommendations-dashboard',
            path: 'dashboard',
            meta: {
                parentPath: 'sw.marketing.index'
            }
        }
    }
});
```

### Step 3: Create Dashboard Page Component (30 minutes)

Create `src/Resources/app/administration/src/module/learning-recommendations/page/learning-recommendations-dashboard/index.js`:

File: custom/plugins/LearningBundle/src/Resources/app/administration/src/module/learning-recommendations/page/learning-recommendations-dashboard/index.js

```javascript
const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('learning-recommendations-dashboard', {
    template,

    inject: ['repositoryFactory', 'acl'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isLoading: true,
            statistics: {
                totalRecommendations: 0,
                totalTrackedViews: 0,
                averageAffinityScore: 0,
                topPairs: []
            },
            dateRange: {
                from: null,
                to: null
            },
            chartData: null
        };
    },

    computed: {
        recommendationRepository() {
            return this.repositoryFactory.create('learning_product_recommendation');
        },

        sessionRepository() {
            return this.repositoryFactory.create('learning_product_session');
        },

        columns() {
            return [
                {
                    property: 'sourceProduct.name',
                    label: this.$tc('learning-recommendations.dashboard.columnSource'),
                    allowResize: true
                },
                {
                    property: 'recommendedProduct.name', 
                    label: this.$tc('learning-recommendations.dashboard.columnRecommended'),
                    allowResize: true
                },
                {
                    property: 'affinityScore',
                    label: this.$tc('learning-recommendations.dashboard.columnScore'),
                    allowResize: true
                },
                {
                    property: 'viewCount',
                    label: this.$tc('learning-recommendations.dashboard.columnViews'),
                    allowResize: true
                }
            ];
        }
    },

    created() {
        this.loadStatistics();
    },

    methods: {
        async loadStatistics() {
            this.isLoading = true;

            try {
                // Load recommendation count
                const recommendationCriteria = new Criteria(1, 1);
                const recommendations = await this.recommendationRepository.search(
                    recommendationCriteria,
                    Shopware.Context.api
                );
                this.statistics.totalRecommendations = recommendations.total;

                // Load session view count
                const sessionCriteria = new Criteria(1, 1);
                const sessions = await this.sessionRepository.search(
                    sessionCriteria,
                    Shopware.Context.api
                );
                this.statistics.totalTrackedViews = sessions.total;

                // Load top recommendation pairs
                await this.loadTopPairs();

                // Build chart data
                this.buildChartData();

            } catch (error) {
                this.createNotificationError({
                    title: this.$tc('learning-recommendations.dashboard.errorTitle'),
                    message: error.message
                });
            } finally {
                this.isLoading = false;
            }
        },

        async loadTopPairs() {
            const criteria = new Criteria(1, 10);
            criteria.addAssociation('sourceProduct');
            criteria.addAssociation('recommendedProduct');
            criteria.addSorting(Criteria.sort('affinityScore', 'DESC'));

            const result = await this.recommendationRepository.search(
                criteria,
                Shopware.Context.api
            );

            this.statistics.topPairs = result;

            // Calculate average score
            if (result.length > 0) {
                const totalScore = result.reduce((sum, item) => sum + item.affinityScore, 0);
                this.statistics.averageAffinityScore = (totalScore / result.length).toFixed(1);
            }
        },

        buildChartData() {
            // Prepare data for the chart component
            this.chartData = {
                labels: this.statistics.topPairs.slice(0, 5).map(
                    pair => pair.sourceProduct?.translated?.name?.substring(0, 15) + '...' || 'Unknown'
                ),
                datasets: [{
                    label: this.$tc('learning-recommendations.dashboard.chartLabel'),
                    data: this.statistics.topPairs.slice(0, 5).map(pair => pair.affinityScore),
                    backgroundColor: [
                        '#ff68b4',
                        '#57d9a3',
                        '#189eff',
                        '#ffc107',
                        '#dc3545'
                    ]
                }]
            };
        },

        onRefresh() {
            this.loadStatistics();
        }
    }
});
```

Create `learning-recommendations-dashboard.html.twig`:

File: custom/plugins/LearningBundle/src/Resources/app/administration/src/module/learning-recommendations/page/learning-recommendations-dashboard/learning-recommendations-dashboard.html.twig

```twig
{% block learning_recommendations_dashboard %}
<sw-page class="learning-recommendations-dashboard">
    
    {% block learning_recommendations_dashboard_header %}
    <template #smart-bar-header>
        <h2>{{ $tc('learning-recommendations.dashboard.title') }}</h2>
    </template>
    {% endblock %}

    {% block learning_recommendations_dashboard_actions %}
    <template #smart-bar-actions>
        <sw-button 
            variant="ghost"
            :isLoading="isLoading"
            @click="onRefresh">
            <sw-icon name="regular-redo" small></sw-icon>
            {{ $tc('learning-recommendations.dashboard.refresh') }}
        </sw-button>
    </template>
    {% endblock %}

    {% block learning_recommendations_dashboard_content %}
    <template #content>
        <sw-card-view>
            
            {# Statistics Cards Row #}
            {% block learning_recommendations_stats_row %}
            <div class="learning-stats-row">
                <learning-stats-card
                    :value="statistics.totalRecommendations"
                    :label="$tc('learning-recommendations.dashboard.totalRecommendations')"
                    icon="regular-link"
                    color="#ff68b4">
                </learning-stats-card>

                <learning-stats-card
                    :value="statistics.totalTrackedViews"
                    :label="$tc('learning-recommendations.dashboard.totalViews')"
                    icon="regular-eye"
                    color="#57d9a3">
                </learning-stats-card>

                <learning-stats-card
                    :value="statistics.averageAffinityScore"
                    :label="$tc('learning-recommendations.dashboard.averageScore')"
                    icon="regular-chart-line"
                    color="#189eff"
                    suffix="%">
                </learning-stats-card>
            </div>
            {% endblock %}

            {# Chart Section #}
            {% block learning_recommendations_chart %}
            <sw-card 
                :title="$tc('learning-recommendations.dashboard.chartTitle')"
                :isLoading="isLoading">
                <learning-recommendation-chart
                    v-if="chartData"
                    :chart-data="chartData">
                </learning-recommendation-chart>
            </sw-card>
            {% endblock %}

            {# Top Pairs Table #}
            {% block learning_recommendations_table %}
            <sw-card 
                :title="$tc('learning-recommendations.dashboard.topPairsTitle')"
                :isLoading="isLoading">
                <sw-data-grid
                    :dataSource="statistics.topPairs"
                    :columns="columns"
                    :showSelection="false"
                    :showActions="false">
                    
                    <template #column-affinityScore="{ item }">
                        <sw-label 
                            variant="success"
                            size="small">
                            {{ item.affinityScore.toFixed(1) }}%
                        </sw-label>
                    </template>

                    <template #column-viewCount="{ item }">
                        <sw-label 
                            variant="info"
                            size="small">
                            {{ item.viewCount }} views
                        </sw-label>
                    </template>
                    
                </sw-data-grid>
            </sw-card>
            {% endblock %}

        </sw-card-view>
    </template>
    {% endblock %}

</sw-page>
{% endblock %}
```

### Step 4: Create Stats Card Component (20 minutes)

Create `src/Resources/app/administration/src/module/learning-recommendations/component/learning-stats-card/index.js`:

File: custom/plugins/LearningBundle/src/Resources/app/administration/src/module/learning-recommendations/component/learning-stats-card/index.js

```javascript
const { Component } = Shopware;

Component.register('learning-stats-card', {
    template,

    props: {
        value: {
            type: [Number, String],
            required: true
        },
        label: {
            type: String,
            required: true
        },
        icon: {
            type: String,
            default: 'regular-chart-bar'
        },
        color: {
            type: String,
            default: '#189eff'
        },
        suffix: {
            type: String,
            default: ''
        }
    },

    computed: {
        cardStyles() {
            return {
                '--stats-card-color': this.color
            };
        },

        displayValue() {
            if (typeof this.value === 'number') {
                return this.value.toLocaleString() + this.suffix;
            }
            return this.value + this.suffix;
        }
    }
});
```

Create `learning-stats-card.html.twig`:

File: custom/plugins/LearningBundle/src/Resources/app/administration/src/module/learning-recommendations/component/learning-stats-card/learning-stats-card.html.twig

```twig
{% block learning_stats_card %}
<div class="learning-stats-card" :style="cardStyles">
    <div class="learning-stats-card__icon">
        <sw-icon :name="icon" size="32px"></sw-icon>
    </div>
    <div class="learning-stats-card__content">
        <span class="learning-stats-card__value">{{ displayValue }}</span>
        <span class="learning-stats-card__label">{{ label }}</span>
    </div>
</div>
{% endblock %}
```

### Step 5: Create Chart Component (20 minutes)

Create `src/Resources/app/administration/src/module/learning-recommendations/component/learning-recommendation-chart/index.js`:

File: custom/plugins/LearningBundle/src/Resources/app/administration/src/module/learning-recommendations/component/learning-recommendation-chart/index.js

```javascript
const { Component } = Shopware;

Component.register('learning-recommendation-chart', {
    template,

    props: {
        chartData: {
            type: Object,
            required: true
        }
    },

    data() {
        return {
            chartOptions: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                return `Score: ${context.raw.toFixed(1)}%`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Affinity Score (%)'
                        }
                    }
                }
            }
        };
    }
});
```

Create `learning-recommendation-chart.html.twig`:

File: custom/plugins/LearningBundle/src/Resources/app/administration/src/module/learning-recommendations/component/learning-recommendation-chart/learning-recommendation-chart.html.twig

```twig
{% block learning_recommendation_chart %}
<div class="learning-recommendation-chart">
    <sw-chart
        type="bar"
        :data="chartData"
        :options="chartOptions"
        style="height: 300px;">
    </sw-chart>
</div>
{% endblock %}
```

### Step 6: Add Translations (10 minutes)

Create `src/Resources/app/administration/src/module/learning-recommendations/snippet/en-GB.json`:

File: custom/plugins/LearningBundle/src/Resources/app/administration/src/module/learning-recommendations/snippet/en-GB.json

```json
{
    "learning-recommendations": {
        "general": {
            "mainMenuItemGeneral": "Recommendations",
            "description": "Product Recommendation Engine Analytics"
        },
        "dashboard": {
            "title": "Recommendation Analytics",
            "refresh": "Refresh",
            "totalRecommendations": "Total Recommendations",
            "totalViews": "Tracked Views",
            "averageScore": "Average Score",
            "chartTitle": "Top Product Affinities",
            "chartLabel": "Affinity Score",
            "topPairsTitle": "Top Recommended Pairs",
            "columnSource": "Source Product",
            "columnRecommended": "Recommended Product",
            "columnScore": "Affinity Score",
            "columnViews": "View Count",
            "errorTitle": "Error loading data"
        }
    }
}
```

Create `de-DE.json` with German translations.

### Step 7: Add SCSS Styling (10 minutes)

Create `src/Resources/app/administration/src/module/learning-recommendations/page/learning-recommendations-dashboard/learning-recommendations-dashboard.scss`:

File: custom/plugins/LearningBundle/src/Resources/app/administration/src/module/learning-recommendations/page/learning-recommendations-dashboard/learning-recommendations-dashboard.scss

```scss
.learning-recommendations-dashboard {
    .learning-stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
}

.learning-stats-card {
    background: white;
    border-radius: 8px;
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    &__icon {
        width: 64px;
        height: 64px;
        border-radius: 12px;
        background: var(--stats-card-color, #189eff);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
    }

    &__content {
        display: flex;
        flex-direction: column;
    }

    &__value {
        font-size: 28px;
        font-weight: 700;
        color: #1a1a1a;
        line-height: 1.2;
    }

    &__label {
        font-size: 14px;
        color: #666;
        margin-top: 4px;
    }
}

.learning-recommendation-chart {
    min-height: 300px;
    padding: 16px;
}
```

### Step 8: Build and Test Administration (10 minutes)

```bash
# Build administration assets
./bin/build-administration.sh

# Or use watch mode for development
./bin/watch-administration.sh

# Clear cache
bin/console cache:clear

# Test in browser
# 1. Log into Administration (/admin)
# 2. Navigate to Marketing → Recommendations
# 3. Verify dashboard loads with statistics
# 4. Test refresh button
# 5. Verify chart displays correctly
```

### Vue.js Administration Checklist

✅ **Module Structure:**
- [ ] Module registered in Shopware Module system
- [ ] Navigation entry appears in sidebar
- [ ] Routes configured correctly
- [ ] Snippets load for translations

✅ **Components:**
- [ ] Dashboard page loads without errors
- [ ] Stats cards display values correctly
- [ ] Chart renders with data
- [ ] Data grid shows top pairs

✅ **Data Loading:**
- [ ] Repository factory injects correctly
- [ ] Criteria queries work
- [ ] Error handling shows notifications
- [ ] Loading states display

✅ **Styling:**
- [ ] SCSS compiles correctly
- [ ] Cards have hover effects
- [ ] Responsive grid layout works
- [ ] Matches Shopware admin design

---

## Part 12: PWA Enhancement (60 minutes) 📱

> **Add Progressive Web App features!** Make the storefront installable and work offline with Service Workers.

### Understanding PWA in Shopware Context

**What we're adding:**
- Service Worker for caching recommendations
- Web App Manifest for "Add to Home Screen"
- Offline-first experience for recommendations
- Background sync for tracking

### Step 1: Create Web App Manifest (10 minutes)

Create `src/Resources/app/storefront/src/manifest.json`:

File: custom/plugins/LearningBundle/src/Resources/app/storefront/src/manifest.json

```json
{
    "name": "My Shop - Smart Recommendations",
    "short_name": "My Shop",
    "description": "Shop with personalized product recommendations",
    "start_url": "/",
    "display": "standalone",
    "background_color": "#ffffff",
    "theme_color": "#007bff",
    "orientation": "portrait-primary",
    "icons": [
        {
            "src": "/bundles/learningbundle/icons/icon-192x192.png",
            "sizes": "192x192",
            "type": "image/png",
            "purpose": "any maskable"
        },
        {
            "src": "/bundles/learningbundle/icons/icon-512x512.png",
            "sizes": "512x512",
            "type": "image/png",
            "purpose": "any maskable"
        }
    ],
    "categories": ["shopping"],
    "screenshots": [
        {
            "src": "/bundles/learningbundle/screenshots/recommendations.png",
            "sizes": "1280x720",
            "type": "image/png",
            "label": "Product Recommendations"
        }
    ]
}
```

### Step 2: Create Service Worker (30 minutes)

Create `src/Resources/app/storefront/src/service-worker/recommendation-sw.js`:

File: custom/plugins/LearningBundle/src/Resources/app/storefront/src/service-worker/recommendation-sw.js

```javascript
/**
 * Recommendation Engine Service Worker
 * 
 * Caches recommendation API responses for offline access
 * and provides background sync for tracking.
 */

const CACHE_NAME = 'learning-recommendations-v1';
const RECOMMENDATION_API_PATTERN = /\/store-api\/learning\/recommendations\//;

// Resources to cache on install
const STATIC_CACHE = [
    '/bundles/learningbundle/recommendation-offline.html'
];

// Install event - cache static resources
self.addEventListener('install', (event) => {
    console.log('[SW] Installing Recommendation Service Worker');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[SW] Caching static resources');
                return cache.addAll(STATIC_CACHE);
            })
            .then(() => self.skipWaiting())
    );
});

// Activate event - clean old caches
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating Service Worker');
    
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((name) => name !== CACHE_NAME)
                        .map((name) => caches.delete(name))
                );
            })
            .then(() => self.clients.claim())
    );
});

// Fetch event - network first, cache fallback for recommendations
self.addEventListener('fetch', (event) => {
    const { request } = event;
    
    // Handle recommendation API requests
    if (RECOMMENDATION_API_PATTERN.test(request.url)) {
        event.respondWith(handleRecommendationRequest(request));
        return;
    }
});

/**
 * Handle recommendation API requests with caching strategy
 * Strategy: Network First with Cache Fallback
 */
async function handleRecommendationRequest(request) {
    const cache = await caches.open(CACHE_NAME);
    
    try {
        // Try network first
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            // Clone and cache the response
            const responseToCache = networkResponse.clone();
            
            // Cache with product ID as key for efficient lookup
            const cacheKey = createCacheKey(request.url);
            await cache.put(cacheKey, responseToCache);
            
            console.log('[SW] Cached recommendation:', cacheKey);
        }
        
        return networkResponse;
        
    } catch (error) {
        // Network failed, try cache
        console.log('[SW] Network failed, trying cache:', request.url);
        
        const cacheKey = createCacheKey(request.url);
        const cachedResponse = await cache.match(cacheKey);
        
        if (cachedResponse) {
            console.log('[SW] Serving from cache:', cacheKey);
            return cachedResponse;
        }
        
        // Return offline JSON response
        return new Response(
            JSON.stringify({
                success: false,
                offline: true,
                message: 'Recommendations unavailable offline',
                data: []
            }),
            {
                status: 200,
                headers: { 'Content-Type': 'application/json' }
            }
        );
    }
}

/**
 * Create a normalized cache key from URL
 */
function createCacheKey(url) {
    const urlObj = new URL(url);
    // Include product ID but normalize query params
    return `recommendations:${urlObj.pathname}`;
}

// Background Sync for tracking (when online again)
self.addEventListener('sync', (event) => {
    if (event.tag === 'recommendation-tracking-sync') {
        event.waitUntil(syncTrackingData());
    }
});

/**
 * Sync queued tracking data when back online
 */
async function syncTrackingData() {
    const db = await openTrackingDB();
    const pendingTracks = await db.getAll('pending-tracks');
    
    for (const track of pendingTracks) {
        try {
            await fetch('/store-api/learning/track', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(track)
            });
            
            await db.delete('pending-tracks', track.id);
            console.log('[SW] Synced tracking data:', track.id);
            
        } catch (error) {
            console.error('[SW] Failed to sync tracking:', error);
        }
    }
}

/**
 * Simple IndexedDB wrapper for tracking queue
 */
function openTrackingDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('LearningRecommendations', 1);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => {
            const db = request.result;
            resolve({
                getAll: (store) => {
                    return new Promise((res, rej) => {
                        const tx = db.transaction(store, 'readonly');
                        const req = tx.objectStore(store).getAll();
                        req.onsuccess = () => res(req.result);
                        req.onerror = () => rej(req.error);
                    });
                },
                delete: (store, key) => {
                    return new Promise((res, rej) => {
                        const tx = db.transaction(store, 'readwrite');
                        const req = tx.objectStore(store).delete(key);
                        req.onsuccess = () => res();
                        req.onerror = () => rej(req.error);
                    });
                }
            });
        };
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('pending-tracks')) {
                db.createObjectStore('pending-tracks', { keyPath: 'id', autoIncrement: true });
            }
        };
    });
}

console.log('[SW] Recommendation Service Worker loaded');
```

### Step 3: Register Service Worker in Storefront (10 minutes)

Create `src/Resources/app/storefront/src/plugin/pwa/pwa-installer.plugin.js`:

File: custom/plugins/LearningBundle/src/Resources/app/storefront/src/plugin/pwa/pwa-installer.plugin.js

```javascript
import Plugin from 'src/plugin-system/plugin.class';

/**
 * PWA Installer Plugin
 * 
 * Registers the service worker and handles PWA install prompts
 */
export default class PwaInstallerPlugin extends Plugin {
    
    static options = {
        swPath: '/bundles/learningbundle/recommendation-sw.js',
        installBannerSelector: '.learning-pwa-install-banner'
    };

    init() {
        this.deferredPrompt = null;
        this.installBanner = document.querySelector(this.options.installBannerSelector);
        
        this.registerServiceWorker();
        this.handleInstallPrompt();
    }

    /**
     * Register the service worker
     */
    async registerServiceWorker() {
        if (!('serviceWorker' in navigator)) {
            console.log('[PWA] Service Workers not supported');
            return;
        }

        try {
            const registration = await navigator.serviceWorker.register(
                this.options.swPath,
                { scope: '/' }
            );

            console.log('[PWA] Service Worker registered:', registration.scope);

            // Handle updates
            registration.addEventListener('updatefound', () => {
                const newWorker = registration.installing;
                console.log('[PWA] New Service Worker installing...');
                
                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        this.showUpdateNotification();
                    }
                });
            });

        } catch (error) {
            console.error('[PWA] Service Worker registration failed:', error);
        }
    }

    /**
     * Handle the beforeinstallprompt event
     */
    handleInstallPrompt() {
        window.addEventListener('beforeinstallprompt', (event) => {
            // Prevent Chrome's default prompt
            event.preventDefault();
            
            // Store the event for later use
            this.deferredPrompt = event;
            
            // Show custom install banner
            this.showInstallBanner();
            
            console.log('[PWA] Install prompt captured');
        });

        // Track when app is installed
        window.addEventListener('appinstalled', () => {
            console.log('[PWA] App installed successfully');
            this.hideInstallBanner();
            this.deferredPrompt = null;
        });
    }

    /**
     * Show custom install banner
     */
    showInstallBanner() {
        if (!this.installBanner) return;
        
        this.installBanner.style.display = 'flex';
        
        // Handle install button click
        const installBtn = this.installBanner.querySelector('[data-pwa-install]');
        if (installBtn) {
            installBtn.addEventListener('click', () => this.promptInstall());
        }
        
        // Handle dismiss button
        const dismissBtn = this.installBanner.querySelector('[data-pwa-dismiss]');
        if (dismissBtn) {
            dismissBtn.addEventListener('click', () => this.hideInstallBanner());
        }
    }

    /**
     * Trigger the install prompt
     */
    async promptInstall() {
        if (!this.deferredPrompt) return;
        
        this.deferredPrompt.prompt();
        
        const { outcome } = await this.deferredPrompt.userChoice;
        console.log('[PWA] User choice:', outcome);
        
        this.deferredPrompt = null;
    }

    /**
     * Hide install banner
     */
    hideInstallBanner() {
        if (this.installBanner) {
            this.installBanner.style.display = 'none';
        }
    }

    /**
     * Show update available notification
     */
    showUpdateNotification() {
        // You can use Shopware's notification system or custom UI
        if (confirm('A new version is available. Reload to update?')) {
            window.location.reload();
        }
    }
}
```

Register in `main.js`:

```javascript
import PwaInstallerPlugin from './plugin/pwa/pwa-installer.plugin';

const PluginManager = window.PluginManager;
PluginManager.register('PwaInstaller', PwaInstallerPlugin, 'body');
```

### Step 4: Add PWA Install Banner Template (5 minutes)

Create `storefront/layout/header/pwa-install-banner.html.twig`:

File: custom/plugins/LearningBundle/src/Resources/views/storefront/layout/header/pwa-install-banner.html.twig

```twig
{% block learning_pwa_install_banner %}
<div class="learning-pwa-install-banner" style="display: none;">
    <div class="pwa-install-content">
        <div class="pwa-install-icon">
            📱
        </div>
        <div class="pwa-install-text">
            <strong>{{ "learning.pwa.installTitle"|trans }}</strong>
            <p>{{ "learning.pwa.installDescription"|trans }}</p>
        </div>
    </div>
    <div class="pwa-install-actions">
        <button class="btn btn-primary btn-sm" data-pwa-install>
            {{ "learning.pwa.installButton"|trans }}
        </button>
        <button class="btn btn-link btn-sm" data-pwa-dismiss>
            {{ "learning.pwa.dismissButton"|trans }}
        </button>
    </div>
</div>
{% endblock %}
```

### Step 5: Add PWA SCSS Styling (5 minutes)

Create `src/Resources/app/storefront/src/scss/component/_pwa-install-banner.scss`:

```scss
.learning-pwa-install-banner {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 16px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.2);
    z-index: 9999;
    animation: slideUp 0.3s ease-out;

    @keyframes slideUp {
        from {
            transform: translateY(100%);
        }
        to {
            transform: translateY(0);
        }
    }

    .pwa-install-content {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .pwa-install-icon {
        font-size: 32px;
    }

    .pwa-install-text {
        strong {
            font-size: 16px;
            display: block;
        }
        p {
            margin: 4px 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
    }

    .pwa-install-actions {
        display: flex;
        gap: 8px;

        .btn-primary {
            background: white;
            color: #667eea;
            border: none;
            font-weight: 600;

            &:hover {
                background: #f0f0f0;
            }
        }

        .btn-link {
            color: white;
            opacity: 0.8;

            &:hover {
                opacity: 1;
            }
        }
    }

    @media (max-width: 768px) {
        flex-direction: column;
        gap: 12px;
        text-align: center;

        .pwa-install-content {
            flex-direction: column;
        }
    }
}
```

### Step 6: Update Recommendation Plugin for Offline Support (5 minutes)

Update `recommendation-carousel.plugin.js` to handle offline state:

```javascript
// Add to loadRecommendations() method:
async loadRecommendations() {
    try {
        this.showLoading();
        
        const url = `${this.options.recommendationsUrl}/${this.productId}?limit=${this.options.limit}`;
        
        const response = await fetch(url);
        const data = await response.json();

        // Check if we're showing cached/offline data
        if (data.offline) {
            this.showOfflineNotice();
        }

        if (data.success && data.data && data.data.length > 0) {
            this.recommendations = data.data;
            this.renderRecommendations();
            this.initializeCarousel();
            this.registerQuickAddHandlers();
        } else {
            this.showEmpty();
        }
        
    } catch (error) {
        console.error('Failed to load recommendations:', error);
        this.showError();
    }
}

showOfflineNotice() {
    const notice = document.createElement('div');
    notice.className = 'recommendations-offline-notice';
    notice.innerHTML = `
        <span class="offline-icon">📡</span>
        <span>Showing cached recommendations</span>
    `;
    this.el.querySelector('.recommendations-header').appendChild(notice);
}
```

### PWA Checklist

✅ **Service Worker:**
- [ ] Registered successfully (check DevTools → Application)
- [ ] Caches recommendation API responses
- [ ] Returns cached data when offline
- [ ] Cleans up old caches on activation

✅ **Web App Manifest:**
- [ ] Linked in HTML head
- [ ] Icons display correctly
- [ ] App name and colors defined
- [ ] "Add to Home Screen" prompt works

✅ **Offline Experience:**
- [ ] Recommendations show cached data offline
- [ ] Offline indicator displays
- [ ] No JavaScript errors when offline
- [ ] Graceful degradation

✅ **Install Banner:**
- [ ] Appears on supported browsers
- [ ] Install button triggers prompt
- [ ] Dismiss button hides banner
- [ ] Banner slides up smoothly

---

## Part 13: Review and Polish (45 minutes)

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

A complete, production-ready Shopware plugin demonstrating **ALL Shopware 6.7 frontend technologies**:

**Backend (30%):**

- ✅ Custom database schema with migrations
- ✅ Entity definitions and repositories
- ✅ Business logic services
- ✅ Event-driven tracking architecture
- ✅ Store API endpoint for recommendations
- ✅ Admin API for analytics
- ✅ Caching strategy for performance
- ✅ Unit and integration tests
- ✅ Error handling and logging

**Storefront Frontend (40%) - Twig + Vanilla JS:**

- ✅ Beautiful recommendation carousel UI
- ✅ Interactive JavaScript plugin (Shopware plugin pattern)
- ✅ AJAX add to cart (no page reload)
- ✅ Smooth animations and transitions
- ✅ Responsive mobile design
- ✅ Loading, error, and empty states
- ✅ Accessibility features
- ✅ Professional SCSS styling
- ✅ Twig template extension and blocks
- ✅ Cross-browser compatibility

**Administration Frontend (20%) - Vue.js 3:** 🆕

- ✅ Custom admin module with Vue.js 3
- ✅ Interactive analytics dashboard
- ✅ Shopware Meteor component library (sw-* components)
- ✅ Data grid with sorting and filtering
- ✅ Chart visualization
- ✅ Custom Vue components
- ✅ Repository Factory data fetching
- ✅ Notification mixins
- ✅ Proper TypeScript integration
- ✅ Hybrid Twig/Vue templates

**PWA Enhancement (10%):** 🆕

- ✅ Service Worker for offline caching
- ✅ Web App Manifest for installability
- ✅ "Add to Home Screen" functionality
- ✅ Offline-first recommendation display
- ✅ Background sync for tracking
- ✅ Cache management strategy
- ✅ Update notifications

### What You've Learned This Week

**Day 1:** Plugin structure, services, configuration  
**Day 2:** Events, subscribers, dependency injection  
**Day 2.5:** 🎨 **Twig templates, JavaScript plugins, SCSS styling** (Storefront Focus!)  
**Day 3:** Database, migrations, entities, repositories  
**Day 4:** API architecture, Store API, Admin API  
**Day 5:** Debugging, logging, error handling  
**Day 6:** Testing, caching, performance  
**Day 7-10:** 🎨 **Complete feature with ALL frontend technologies**  

**Storefront Skills (Twig + Vanilla JS):**

- ✅ Twig template extension and blocks
- ✅ JavaScript plugin development (Shopware plugin pattern)
- ✅ AJAX requests with HttpClient
- ✅ Event-driven JavaScript architecture
- ✅ Component-based SCSS styling
- ✅ Responsive design patterns
- ✅ User feedback and loading states
- ✅ Cross-browser compatibility
- ✅ Accessibility best practices

**Administration Skills (Vue.js 3):** 🆕

- ✅ Vue.js 3 component development
- ✅ Shopware Module system
- ✅ Meteor component library (sw-* components)
- ✅ Repository Factory pattern
- ✅ Criteria-based data queries
- ✅ Hybrid Twig/Vue templates
- ✅ Admin API integration
- ✅ Notification mixins
- ✅ Custom component styling

**PWA Skills:** 🆕

- ✅ Service Worker lifecycle
- ✅ Cache strategies (Network First)
- ✅ Web App Manifest
- ✅ Install prompt handling
- ✅ Offline-first patterns
- ✅ Background sync
- ✅ IndexedDB for queuing  

---

## Submission Checklist

Before considering the project complete:

**Backend:**

- [ ] All code is committed to git
- [ ] Plugin installs without errors
- [ ] All migrations run successfully
- [ ] Backend tests pass
- [ ] Store API returns recommendations
- [ ] Admin API returns analytics
- [ ] Cache is working
- [ ] Error handling works

**Storefront (Twig + JS):**

- [ ] Storefront built successfully (`./bin/build-storefront.sh`)
- [ ] Recommendations display on product pages
- [ ] Carousel navigation works smoothly
- [ ] AJAX add to cart functions correctly
- [ ] Loading states display appropriately
- [ ] Error handling works in UI
- [ ] Mobile responsive (tested on small screens)
- [ ] Works in Chrome, Firefox, Safari
- [ ] No console errors
- [ ] Animations are smooth

**Administration (Vue.js 3):** 🆕

- [ ] Administration built successfully (`./bin/build-administration.sh`)
- [ ] Module appears in Marketing menu
- [ ] Dashboard loads without errors
- [ ] Stats cards display correct values
- [ ] Chart renders with data
- [ ] Data grid shows top pairs
- [ ] Refresh button works
- [ ] Translations display correctly
- [ ] No Vue.js console warnings

**PWA:** 🆕

- [ ] Service Worker registered (check DevTools → Application)
- [ ] Manifest linked and valid
- [ ] App installable ("Add to Home Screen" works)
- [ ] Recommendations cached for offline
- [ ] Offline indicator shows when disconnected
- [ ] No errors when network is unavailable

**General:**

- [ ] Documentation is complete
- [ ] Code is clean and commented
- [ ] Ready for code review

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

**Estimated Completion Time Breakdown:**

- Backend Development: 5-6 hours (30%)
- **Storefront Development: 3-4 hours (Twig + JS, 40%)**
- **Administration Development: 2-3 hours (Vue.js 3, 20%)**
- **PWA Enhancement: 1 hour (10%)**
- Testing & Polish: 2-3 hours

**Total:** 25-35 hours (4-5 days)

**Difficulty:** Advanced

---

🎉 **CONGRATULATIONS!** 🎉

You've completed the **comprehensive full-stack** Shopware 6.7 plugin development course covering **ALL frontend technologies**!

**Your Portfolio Now Includes:**

- ✅ A working recommendation engine with **visual carousel** (Twig + Vanilla JS)
- ✅ **Interactive JavaScript plugin** following Shopware patterns
- ✅ **AJAX functionality** without page reloads
- ✅ **Responsive, animated UI** that looks professional
- ✅ **Vue.js 3 Admin Dashboard** with analytics and charts 🆕
- ✅ **PWA features** with offline support and installability 🆕
- ✅ Full-stack integration (Backend + Storefront + Administration)

**Technologies Mastered:**

| Area | Technology | You Can Now... |
|------|------------|----------------|
| **Storefront** | Twig + Vanilla JS | Build interactive customer-facing features |
| **Administration** | Vue.js 3 | Create professional admin dashboards |
| **PWA** | Service Workers | Enable offline and installable experiences |
| **Backend** | PHP + Symfony | Build robust APIs and services |

**Keep coding, keep learning, and welcome to the Shopware developer community!** 🚀

*This project demonstrates expertise in ALL Shopware 6.7 frontend technologies—a powerful portfolio piece for employers and clients!*
