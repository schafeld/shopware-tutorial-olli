# Day 3: Database, Migrations, and Custom Entities

**Duration:** 2-3 days (14-20 hours with breaks)  
**Goal:** Master database operations, create custom entities, and work with repositories

> **Note for Beginners:** This is the most complex day! Database and entity work requires careful attention. Budget 2-3 full days and don't worry if you need to revisit concepts multiple times.

## Learning Objectives

- Understand Shopware's Data Abstraction Layer (DAL)
- Create custom database tables via migrations
- Define entity definitions and collections
- Work with repositories for CRUD operations
- Understand associations and relationships
- Add custom fields to existing entities
- Query data efficiently

## Prerequisites

- Completed Day 1 and Day 2
- Basic SQL knowledge
- Understanding of ORM concepts

---

## Part 1: Understanding the Data Abstraction Layer (60 minutes)

### Theory: DAL Architecture

Shopware's DAL is a powerful abstraction over the database that provides:
- **Entity Definitions** - Schema definitions
- **Repositories** - CRUD operations
- **Criteria** - Query building
- **Associations** - Relationships between entities
- **Events** - Lifecycle hooks

**Entity Lifecycle:**
```
Definition → Repository → Criteria → Query → Results
```

### Official Documentation

📖 **Read these resources:**
- [Data Abstraction Layer](https://developer.shopware.com/docs/guides/plugins/plugins/framework/data-handling/)
- [Add Custom Complex Data](https://developer.shopware.com/docs/guides/plugins/plugins/framework/data-handling/add-custom-complex-data)
- [Database Migrations](https://developer.shopware.com/docs/guides/plugins/plugins/plugin-fundamentals/database-migrations)
- [Using the DAL](https://developer.shopware.com/docs/concepts/framework/data-abstraction-layer)

---

## Part 2: Create Your First Migration (90 minutes)

### Step 1: Understand Migration Structure

Migrations are timestamped files that modify the database schema:

```
Migration/
├── Migration1700000001CreateProductViewTable.php
└── Migration1700000002AddIndexToProductView.php
```

### Step 2: Create Migration File

Create `custom/plugins/LearningBundle/src/Migration/Migration1700000001CreateProductViewTable.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1700000001CreateProductViewTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1700000001;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `learning_product_view` (
    `id` BINARY(16) NOT NULL,
    `product_id` BINARY(16) NOT NULL,
    `product_version_id` BINARY(16) NOT NULL,
    `customer_id` BINARY(16) NULL,
    `view_count` INT NOT NULL DEFAULT 1,
    `last_viewed_at` DATETIME(3) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    KEY `fk.learning_product_view.product_id` (`product_id`,`product_version_id`),
    KEY `fk.learning_product_view.customer_id` (`customer_id`),
    KEY `idx.learning_product_view.last_viewed_at` (`last_viewed_at`),
    CONSTRAINT `fk.learning_product_view.product_id` 
        FOREIGN KEY (`product_id`,`product_version_id`) 
        REFERENCES `product` (`id`,`version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk.learning_product_view.customer_id` 
        FOREIGN KEY (`customer_id`) 
        REFERENCES `customer` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // Called when uninstalling - drop table
        $sql = <<<SQL
DROP TABLE IF EXISTS `learning_product_view`;
SQL;

        $connection->executeStatement($sql);
    }
}
```

### Step 3: Run Migration

```bash
# Run migrations
bin/console database:migrate --all LearningBundle

# Check if table was created
bin/console dbal:run-sql "SHOW TABLES LIKE 'learning_product_view'"

# Describe table structure
bin/console dbal:run-sql "DESCRIBE learning_product_view"
```

### Step 4: Create Another Migration (Add Column)

Create `Migration1700000002AddUserAgentToProductView.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1700000002AddUserAgentToProductView extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1700000002;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `learning_product_view`
ADD COLUMN `user_agent` VARCHAR(255) NULL AFTER `view_count`;
SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `learning_product_view`
DROP COLUMN `user_agent`;
SQL;

        $connection->executeStatement($sql);
    }
}
```

Run it:
```bash
bin/console database:migrate --all LearningBundle
```

---

## Part 3: Create Entity Definition (90 minutes)

### Step 1: Create Entity Class

Create `custom/plugins/LearningBundle/src/Core/Content/ProductView/ProductViewEntity.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\ProductView;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ProductViewEntity extends Entity
{
    use EntityIdTrait;

    protected string $productId;
    protected ?string $customerId;
    protected int $viewCount;
    protected ?string $userAgent;
    protected \DateTimeInterface $lastViewedAt;
    
    // Associations
    protected ?ProductEntity $product = null;
    protected ?CustomerEntity $customer = null;

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function setCustomerId(?string $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function getViewCount(): int
    {
        return $this->viewCount;
    }

    public function setViewCount(int $viewCount): void
    {
        $this->viewCount = $viewCount;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    public function getLastViewedAt(): \DateTimeInterface
    {
        return $this->lastViewedAt;
    }

    public function setLastViewedAt(\DateTimeInterface $lastViewedAt): void
    {
        $this->lastViewedAt = $lastViewedAt;
    }

    public function getProduct(): ?ProductEntity
    {
        return $this->product;
    }

    public function setProduct(?ProductEntity $product): void
    {
        $this->product = $product;
    }

    public function getCustomer(): ?CustomerEntity
    {
        return $this->customer;
    }

    public function setCustomer(?CustomerEntity $customer): void
    {
        $this->customer = $customer;
    }
}
```

### Step 2: Create Entity Collection

Create `custom/plugins/LearningBundle/src/Core/Content/ProductView/ProductViewCollection.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\ProductView;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                    add(ProductViewEntity $entity)
 * @method void                    set(string $key, ProductViewEntity $entity)
 * @method ProductViewEntity[]     getIterator()
 * @method ProductViewEntity[]     getElements()
 * @method ProductViewEntity|null  get(string $key)
 * @method ProductViewEntity|null  first()
 * @method ProductViewEntity|null  last()
 */
class ProductViewCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ProductViewEntity::class;
    }
}
```

### Step 3: Create Entity Definition

Create `custom/plugins/LearningBundle/src/Core/Content/ProductView/ProductViewDefinition.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\ProductView;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ProductViewDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'learning_product_view';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return ProductViewEntity::class;
    }

    public function getCollectionClass(): string
    {
        return ProductViewCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            
            (new FkField('product_id', 'productId', ProductDefinition::class))->addFlags(new Required()),
            (new ReferenceVersionField(ProductDefinition::class))->addFlags(new Required()),
            
            new FkField('customer_id', 'customerId', CustomerDefinition::class),
            
            (new IntField('view_count', 'viewCount'))->addFlags(new Required()),
            new StringField('user_agent', 'userAgent'),
            (new DateTimeField('last_viewed_at', 'lastViewedAt'))->addFlags(new Required()),
            
            // Associations
            new ManyToOneAssociationField('product', 'product_id', ProductDefinition::class, 'id', false),
            new ManyToOneAssociationField('customer', 'customer_id', CustomerDefinition::class, 'id', false),
        ]);
    }
}
```

### Step 4: Register Definition

Update `services.xml`:

```xml
<!-- Entity Definition -->
<service id="Learning\Bundle\Core\Content\ProductView\ProductViewDefinition">
    <tag name="shopware.entity.definition" entity="learning_product_view"/>
</service>
```

### Step 5: Verify Registration

```bash
# Clear cache
bin/console cache:clear

# Check if entity is registered
bin/console debug:container --tag=shopware.entity.definition | grep learning

# Test DAL access
bin/console dbal:run-sql "SELECT COUNT(*) FROM learning_product_view"
```

---

## Part 4: Repository Operations (90 minutes)

### Step 1: Create Service to Use Repository

Create `custom/plugins/LearningBundle/src/Service/ProductViewService.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Learning\Bundle\Core\Content\ProductView\ProductViewEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;

class ProductViewService
{
    private EntityRepository $productViewRepository;

    public function __construct(EntityRepository $productViewRepository)
    {
        $this->productViewRepository = $productViewRepository;
    }

    /**
     * Record a product view
     */
    public function recordView(
        string $productId,
        ?string $customerId,
        ?string $userAgent,
        Context $context
    ): void {
        // Check if view already exists for this product/customer combination
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productId', $productId));
        
        if ($customerId) {
            $criteria->addFilter(new EqualsFilter('customerId', $customerId));
        }

        $existing = $this->productViewRepository->search($criteria, $context)->first();

        if ($existing) {
            // Update existing view
            $this->productViewRepository->update([
                [
                    'id' => $existing->getId(),
                    'viewCount' => $existing->getViewCount() + 1,
                    'lastViewedAt' => new \DateTime(),
                    'userAgent' => $userAgent,
                ]
            ], $context);
        } else {
            // Create new view record
            $this->productViewRepository->create([
                [
                    'id' => Uuid::randomHex(),
                    'productId' => $productId,
                    'customerId' => $customerId,
                    'viewCount' => 1,
                    'userAgent' => $userAgent,
                    'lastViewedAt' => new \DateTime(),
                ]
            ], $context);
        }
    }

    /**
     * Get view count for a product
     */
    public function getProductViewCount(string $productId, Context $context): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productId', $productId));

        $views = $this->productViewRepository->search($criteria, $context);
        
        $totalViews = 0;
        /** @var ProductViewEntity $view */
        foreach ($views as $view) {
            $totalViews += $view->getViewCount();
        }

        return $totalViews;
    }

    /**
     * Get most viewed products
     */
    public function getMostViewedProducts(int $limit, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addAssociation('product');
        $criteria->addSorting(new FieldSorting('viewCount', FieldSorting::DESCENDING));
        $criteria->setLimit($limit);

        $views = $this->productViewRepository->search($criteria, $context);
        
        $result = [];
        /** @var ProductViewEntity $view */
        foreach ($views as $view) {
            $result[] = [
                'product_id' => $view->getProductId(),
                'product_name' => $view->getProduct()?->getName(),
                'view_count' => $view->getViewCount(),
                'last_viewed' => $view->getLastViewedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return $result;
    }

    /**
     * Get customer's viewed products
     */
    public function getCustomerViewedProducts(string $customerId, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));
        $criteria->addAssociation('product');
        $criteria->addSorting(new FieldSorting('lastViewedAt', FieldSorting::DESCENDING));

        $views = $this->productViewRepository->search($criteria, $context);

        $result = [];
        /** @var ProductViewEntity $view */
        foreach ($views as $view) {
            $result[] = [
                'product_id' => $view->getProductId(),
                'product_name' => $view->getProduct()?->getName(),
                'view_count' => $view->getViewCount(),
                'last_viewed' => $view->getLastViewedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return $result;
    }
}
```

### Step 2: Register Service

Update `services.xml`:

```xml
<!-- Product View Service -->
<service id="Learning\Bundle\Service\ProductViewService">
    <argument type="service" id="learning_product_view.repository"/>
</service>
```

### Step 3: Create Test Command

Create `custom/plugins/LearningBundle/src/Command/TestProductViewCommand.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Learning\Bundle\Service\ProductViewService;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TestProductViewCommand extends Command
{
    protected static $defaultName = 'learning:test-product-view';

    private ProductViewService $productViewService;

    public function __construct(ProductViewService $productViewService)
    {
        parent::__construct();
        $this->productViewService = $productViewService;
    }

    protected function configure(): void
    {
        $this->setDescription('Test the ProductViewService');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();

        // You'll need to replace this with an actual product ID from your database
        $productId = 'YOUR_PRODUCT_ID_HERE';

        // Record some test views
        $io->section('Recording test views...');
        for ($i = 0; $i < 5; $i++) {
            $this->productViewService->recordView(
                $productId,
                null,
                'Test User Agent',
                $context
            );
        }
        $io->success('Recorded 5 views');

        // Get view count
        $viewCount = $this->productViewService->getProductViewCount($productId, $context);
        $io->info(sprintf('Product has %d total views', $viewCount));

        // Get most viewed products
        $mostViewed = $this->productViewService->getMostViewedProducts(5, $context);
        $io->section('Most Viewed Products:');
        $io->table(
            ['Product ID', 'Name', 'Views', 'Last Viewed'],
            array_map(fn($item) => [
                $item['product_id'],
                $item['product_name'] ?? 'N/A',
                $item['view_count'],
                $item['last_viewed'],
            ], $mostViewed)
        );

        return Command::SUCCESS;
    }
}
```

Register in `services.xml`:

```xml
<service id="Learning\Bundle\Command\TestProductViewCommand">
    <argument type="service" id="Learning\Bundle\Service\ProductViewService"/>
    <tag name="console.command"/>
</service>
```

---

## Part 5: Advanced Queries with Criteria (60 minutes)

### Complex Query Examples

Create `custom/plugins/LearningBundle/src/Service/ProductViewAnalyticsService.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class ProductViewAnalyticsService
{
    private EntityRepository $productViewRepository;

    public function __construct(EntityRepository $productViewRepository)
    {
        $this->productViewRepository = $productViewRepository;
    }

    /**
     * Get views for last N days
     */
    public function getViewsForLastDays(int $days, Context $context): array
    {
        $date = new \DateTime();
        $date->modify("-{$days} days");

        $criteria = new Criteria();
        $criteria->addFilter(
            new RangeFilter('lastViewedAt', [
                RangeFilter::GTE => $date->format('Y-m-d H:i:s'),
            ])
        );
        $criteria->addAggregation(
            new DateHistogramAggregation(
                'views_per_day',
                'lastViewedAt',
                DateHistogramAggregation::PER_DAY
            )
        );

        $result = $this->productViewRepository->search($criteria, $context);
        $aggregations = $result->getAggregations();
        
        return $aggregations->get('views_per_day')?->getBuckets() ?? [];
    }

    /**
     * Get total views by product
     */
    public function getTotalViewsByProduct(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addAssociation('product');
        $criteria->addAggregation(
            new SumAggregation('total_views', 'viewCount')
        );

        $result = $this->productViewRepository->search($criteria, $context);
        
        // Build summary
        $summary = [];
        foreach ($result as $view) {
            $productId = $view->getProductId();
            if (!isset($summary[$productId])) {
                $summary[$productId] = [
                    'product_id' => $productId,
                    'product_name' => $view->getProduct()?->getName(),
                    'total_views' => 0,
                ];
            }
            $summary[$productId]['total_views'] += $view->getViewCount();
        }

        return array_values($summary);
    }

    /**
     * Get views by user agent (browser analysis)
     */
    public function getViewsByBrowser(Context $context): array
    {
        $criteria = new Criteria();
        
        $result = $this->productViewRepository->search($criteria, $context);
        
        $browsers = [];
        foreach ($result as $view) {
            $userAgent = $view->getUserAgent() ?? 'Unknown';
            
            // Simple browser detection (you could use a library for better detection)
            $browser = 'Unknown';
            if (str_contains($userAgent, 'Chrome')) $browser = 'Chrome';
            elseif (str_contains($userAgent, 'Firefox')) $browser = 'Firefox';
            elseif (str_contains($userAgent, 'Safari')) $browser = 'Safari';
            elseif (str_contains($userAgent, 'Edge')) $browser = 'Edge';
            
            if (!isset($browsers[$browser])) {
                $browsers[$browser] = 0;
            }
            $browsers[$browser] += $view->getViewCount();
        }

        return $browsers;
    }
}
```

---

## Part 6: Custom Fields (Simpler Alternative) (45 minutes)

### Add Custom Fields to Existing Entities

Sometimes you don't need a full entity - custom fields are simpler:

Create `custom/plugins/LearningBundle/src/Service/CustomFieldService.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;

class CustomFieldService
{
    private EntityRepository $customFieldSetRepository;

    public function __construct(EntityRepository $customFieldSetRepository)
    {
        $this->customFieldSetRepository = $customFieldSetRepository;
    }

    public function createProductCustomFields(Context $context): void
    {
        $this->customFieldSetRepository->upsert([
            [
                'id' => Uuid::randomHex(),
                'name' => 'learning_product_fields',
                'config' => [
                    'label' => [
                        'en-GB' => 'Learning Product Fields',
                        'de-DE' => 'Learning Produktfelder',
                    ],
                ],
                'customFields' => [
                    [
                        'id' => Uuid::randomHex(),
                        'name' => 'learning_popularity_score',
                        'type' => CustomFieldTypes::INT,
                        'config' => [
                            'label' => [
                                'en-GB' => 'Popularity Score',
                                'de-DE' => 'Beliebtheitswert',
                            ],
                            'customFieldPosition' => 1,
                        ],
                    ],
                    [
                        'id' => Uuid::randomHex(),
                        'name' => 'learning_featured_until',
                        'type' => CustomFieldTypes::DATETIME,
                        'config' => [
                            'label' => [
                                'en-GB' => 'Featured Until',
                                'de-DE' => 'Hervorgehoben bis',
                            ],
                            'customFieldPosition' => 2,
                        ],
                    ],
                ],
                'relations' => [
                    [
                        'id' => Uuid::randomHex(),
                        'entityName' => 'product',
                    ],
                ],
            ],
        ], $context);
    }
}
```

---

## Part 7: Exercises (90 minutes)

### Exercise 1: Product Rating System

Create a complete rating system:
- Migration for `learning_product_rating` table (product_id, customer_id, rating, comment, created_at)
- Entity, Collection, and Definition
- Service with methods: addRating, getAverageRating, getRatingsForProduct

### Exercise 2: Wishlist Feature

Create a wishlist system:
- Migration for `learning_wishlist` table
- Track which customers have which products in their wishlist
- Methods: addToWishlist, removeFromWishlist, getWishlist

### Exercise 3: Product Comparison

Create a product comparison table:
- Allow users to compare multiple products
- Store comparison sessions
- Add timestamps and user tracking

---

## Testing Your Work

```bash
# Run migrations
bin/console database:migrate --all LearningBundle

# Check tables
bin/console dbal:run-sql "SHOW TABLES LIKE 'learning_%'"

# Test entity definition
bin/console debug:container --tag=shopware.entity.definition | grep learning

# Query data
bin/console dbal:run-sql "SELECT * FROM learning_product_view"

# Test commands
bin/console learning:test-product-view
```

---

## Key Takeaways

✅ **You've learned:**
- Creating and running database migrations
- Building entity definitions, entities, and collections
- Using repositories for CRUD operations
- Advanced queries with Criteria API
- Associations between entities
- Custom fields as a simpler alternative
- Aggregations and analytics queries

## Common Issues

**Problem:** Migration not found
- Check timestamp in filename and getCreationTimestamp()
- Ensure namespace is correct
- Run `bin/console database:migrate --all LearningBundle`

**Problem:** Entity not registered
- Verify `<tag name="shopware.entity.definition"/>`
- Check entity name matches table name
- Clear cache completely

**Problem:** Foreign key constraint fails
- Ensure referenced IDs exist
- Check foreign key definitions in migration
- Verify CASCADE rules

---

## Additional Resources

- [Data Abstraction Layer](https://developer.shopware.com/docs/concepts/framework/data-abstraction-layer)
- [Database Migrations](https://developer.shopware.com/docs/guides/plugins/plugins/plugin-fundamentals/database-migrations)
- [Doctrine DBAL](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/)
- [Criteria API Reference](https://developer.shopware.com/docs/concepts/framework/data-abstraction-layer#criteria)

---

**Estimated Completion Time:** 6-8 hours  
**Difficulty:** Intermediate to Advanced

🎉 Excellent work! Tomorrow we'll explore API architecture.
