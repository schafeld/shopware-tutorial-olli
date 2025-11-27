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

## Quick Command Reference

**Migration Commands:**
```bash
# Run all pending CORE migrations
bin/console database:migrate --all

# Run plugin migrations (after adding new ones to an installed plugin)
bin/console plugin:update YourPluginName

# Install plugin (runs all plugin migrations)
bin/console plugin:install --activate YourPluginName

# Create a new migration file
bin/console database:create-migration YourMigrationName LearningBundle
```

**Database Access (via Docker):**
```bash
# Run a single SQL query
docker exec -it shopware-tutorial-olli-database-1 mariadb -uroot -proot shopware -e "YOUR_SQL_QUERY"

# Interactive database access
docker exec -it shopware-tutorial-olli-database-1 mariadb -uroot -proot shopware

# Example queries
docker exec -it shopware-tutorial-olli-database-1 mariadb -uroot -proot shopware -e "SHOW TABLES LIKE 'learning_%';"
docker exec -it shopware-tutorial-olli-database-1 mariadb -uroot -proot shopware -e "DESCRIBE learning_product_view;"
```

**Entity Debugging:**
```bash
# Check if entity is registered
bin/console debug:container --tag=shopware.entity.definition | grep learning

# Clear cache after changes
bin/console cache:clear
```

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

**Important:** Before creating migrations, your plugin class must register the migration namespace!

### Step 1.5: Register Migration Namespace in Plugin Class

Edit `custom/plugins/LearningBundle/src/LearningBundle.php` and add these methods:

```php
public function getMigrationNamespace(): string
{
    return 'Learning\Bundle\Migration';
}

public function getMigrationPath(): string
{
    return $this->getPath() . '/src/Migration';
}
```

These methods tell Shopware where to find your plugin's migrations. **Without these, your migrations won't be detected!**

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
# Migrations run automatically when you install/update a plugin
# To manually trigger migrations for your plugin, reinstall it:
bin/console plugin:uninstall LearningBundle
bin/console plugin:install --activate LearningBundle

# Or run all pending migrations manually (only works for NEW migrations):
bin/console database:migrate --all

# ⚠️ Note: database:migrate only runs migrations that haven't been executed yet!
# If you've already run a migration, it won't run again even if you drop the table.
# Use plugin:uninstall/install to re-run migrations.

# Check if table was created using Docker
docker exec -it shopware-tutorial-olli-database-1 mariadb -uroot -proot shopware -e "SHOW TABLES LIKE 'learning_product_view';"

# Describe table structure
docker exec -it shopware-tutorial-olli-database-1 mariadb -uroot -proot shopware -e "DESCRIBE learning_product_view;"

# Alternative: Connect to the database directly
# docker exec -it shopware-tutorial-olli-database-1 mariadb -uroot -proot shopware
# Then run SQL commands interactively
```

### Step 3.5: Rolling Back Migrations (Optional)

If you need to undo a migration (for testing or fixing mistakes):

```bash
# Option 1: Run destructive migrations (calls updateDestructive() method)
bin/console database:migrate-destructive --all

# Option 2: Manually drop the table (🤓 this worked for me)
docker exec -it shopware-tutorial-olli-database-1 mariadb -uroot -proot shopware -e "DROP TABLE IF EXISTS learning_product_view;"

# Option 3: Uninstall the plugin (runs updateDestructive() automatically)
bin/console plugin:uninstall LearningBundle

# Verify table is gone
docker exec -it shopware-tutorial-olli-database-1 mariadb -uroot -proot shopware -e "SHOW TABLES LIKE 'learning_product_view';"

# To re-apply the migration after rollback:
bin/console plugin:install --activate LearningBundle
# Or
bin/console database:migrate --all
```

**Important Notes:**
- `updateDestructive()` is called during `plugin:uninstall` or `database:migrate-destructive`
- Always test rollbacks in development before running in production
- Destructive migrations should safely handle data loss (e.g., create backups first)
- The migration system tracks which migrations have been executed in the `migration` table
- **Key concept:** Once a migration runs, Shopware records it. Even if you manually drop the table, `database:migrate` won't re-run it. You must uninstall/reinstall the plugin to reset the migration tracking.

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
# Option 1: Update the plugin (runs pending migrations automatically)
bin/console plugin:update LearningBundle

# Option 2: Use database:migrate (but this doesn't work for plugin migrations without the plugin being updated first)
# bin/console database:migrate --all
```

**Important:** When you add new migrations to an already-installed plugin, use `plugin:update` to trigger them. The `database:migrate --all` command only processes core migrations by default and won't detect new plugin migrations until the plugin is updated.

---

## Part 3: Create Entity Definition (90 minutes)

### What's the Purpose of This Part?

**Important:** At the end of this section, your database will still show "COUNT 0" - that's correct! This part doesn't create data, it creates the **tools to work with data**.

Think of it like this:
- **Part 2 (Migrations)** = Built the garage (database table)
- **Part 3 (Entity Definition)** = Built the car and got the keys (PHP classes to interact with the table)
- **Part 4 (Repository Operations)** = Actually driving the car (creating, reading, updating data)

**What you're building:**
1. **Entity Class** - Represents one row as a PHP object (like a Product or Customer object)
2. **Collection Class** - Container for multiple entities (like an array, but type-safe)
3. **Definition Class** - Schema that tells Shopware how to map database columns to PHP properties
4. **Repository Service** - Automatically created by Shopware to perform CRUD operations

**Without this infrastructure**, you'd have to write raw SQL queries every time you want to interact with your table. With it, you can write clean PHP code like:
```php
// Instead of: "INSERT INTO learning_product_view VALUES..."
$repository->create([['productId' => $id, 'viewCount' => 1]], $context);

// Instead of: "SELECT * FROM learning_product_view WHERE product_id = ?"
$criteria = new Criteria();
$criteria->addFilter(new EqualsFilter('productId', $productId));
$views = $repository->search($criteria, $context);
```

**Real-world use case:** Once this is set up, Part 4 will show you how to automatically track when customers view products, count total views, and display "Most Viewed Products" on your storefront.

---

### Step 1: Create Entity Class

**Purpose:** The Entity class represents a single row from your database table as a PHP object. It contains properties for each column and getter/setter methods to access them. The `EntityIdTrait` provides the `id` property and related methods automatically.

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

**Purpose:** The Collection class is a type-safe container for multiple Entity objects. It extends `EntityCollection` and defines what type of entities it can hold. The PHPDoc annotations provide IDE autocompletion for array-like operations (add, get, first, etc.).

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

**Purpose:** The Definition class is the schema blueprint that tells Shopware's DAL:
- What database table to use (`getEntityName()`)
- What fields/columns exist (`defineFields()`)
- Data types, validation rules, and flags for each field
- Relationships to other entities (associations)
- Which Entity and Collection classes to use

This is the bridge between your database table and Shopware's data layer.

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
            // Primary key - every entity needs this
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            
            // Foreign key to product table (first parameter: column name, second: property name)
            (new FkField('product_id', 'productId', ProductDefinition::class))->addFlags(new Required()),
            // Shopware's versioning system requires this for product references
            (new ReferenceVersionField(ProductDefinition::class))->addFlags(new Required()),
            
            // Optional foreign key (no Required flag means it can be NULL)
            new FkField('customer_id', 'customerId', CustomerDefinition::class),
            
            // Simple data fields with their types
            (new IntField('view_count', 'viewCount'))->addFlags(new Required()),
            new StringField('user_agent', 'userAgent'),
            (new DateTimeField('last_viewed_at', 'lastViewedAt'))->addFlags(new Required()),
            
            // Associations allow loading related entities (lazy-loaded by default)
            // These create the $product and $customer properties in ProductViewEntity
            new ManyToOneAssociationField('product', 'product_id', ProductDefinition::class, 'id', false),
            new ManyToOneAssociationField('customer', 'customer_id', CustomerDefinition::class, 'id', false),
        ]);
    }
}
```

### Step 4: Register Definition

**Purpose:** Registering the Definition with the `shopware.entity.definition` tag makes it discoverable by Shopware's DAL. This automatically:
- Creates a repository service named `learning_product_view.repository`
- Enables Criteria-based querying
- Integrates with the Admin API
- Makes the entity available throughout Shopware

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

# Query the table directly using Docker
docker exec -it shopware-tutorial-olli-database-1 mariadb -uroot -proot shopware -e "SELECT COUNT(*) FROM learning_product_view"
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
    private ProductViewService $productViewService;

    public function __construct(ProductViewService $productViewService)
    {
        parent::__construct();
        $this->productViewService = $productViewService;
    }

    protected function configure(): void
    {
        $this
            ->setName('learning:test-product-view')
            ->setDescription('Test the ProductViewService');
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

use Learning\Bundle\Core\Content\ProductView\ProductViewEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\BucketResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;

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
        $date->modify(sprintf('-%d days', $days));

        $criteria = new Criteria();
        $criteria->addFilter(
            new RangeFilter('lastViewedAt',[
            RangeFilter::GTE => $date->format('Y-m-d H:i:s')
            ])
        );

        $criteria->addAggregation(
            new DateHistogramAggregation(
                'views_per_day',
                'lastViewedAt',
                DateHistogramAggregation::PER_DAY,
                    )
        );

        $result = $this->productViewRepository->search($criteria, $context);
        $aggregations = $result->getAggregations();
        
        /** @var BucketResult|null $bucketResult */
        $bucketResult = $aggregations->get('views_per_day');

        return $bucketResult?->getBuckets() ?? [];
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

        // Build Summary
        $summary = [];
        /** @var ProductViewEntity $view */
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
        /** @var ProductViewEntity $view */
        foreach ($result as $view) {
            $userAgent = $view->getUserAgent() ?? 'Unknown';

            // Simple browser detection (use a library for better results)
            $browser = 'Unknown';
            if (str_contains($userAgent,'Chrome')) {
                $browser = 'Chrome';
            } elseif (str_contains($userAgent,'Firefox')) {
                $browser = 'Firefox';
            } elseif (str_contains($userAgent,'Safari')) {
                $browser = 'Safari';
            } elseif (str_contains($userAgent,'Edge')) {
                $browser = 'Edge';
            }

            if (!isset($browsers[$browser])) {
                $browsers[$browser] = 0;
            }
            $browsers[$browser] += $view->getViewCount();
        }

        return $browsers;
    }
}
```

### Step 2: Register Analytics Service

Update `services.xml`:

```xml
<!-- Product View Analytics Service -->
<service id="Learning\Bundle\Service\ProductViewAnalyticsService">
    <argument type="service" id="learning_product_view.repository"/>
</service>
```

### Step 3: Create Analytics Test Command

Create `custom/plugins/LearningBundle/src/Command/TestAnalyticsCommand.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Learning\Bundle\Service\ProductViewAnalyticsService;
use Learning\Bundle\Service\ProductViewService;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TestAnalyticsCommand extends Command
{
    private ProductViewAnalyticsService $analyticsService;
    private ProductViewService $viewService;

    public function __construct(
        ProductViewAnalyticsService $analyticsService,
        ProductViewService $viewService
    ) {
        parent::__construct();
        $this->analyticsService = $analyticsService;
        $this->viewService = $viewService;
    }

    protected function configure(): void
    {
        $this
            ->setName('learning:test-analytics')
            ->setDescription('Test the ProductViewAnalyticsService')
            ->addOption(
                'generate-data',
                'g',
                InputOption::VALUE_NONE,
                'Generate sample data first'
            )
            ->addOption(
                'product-id',
                'p',
                InputOption::VALUE_REQUIRED,
                'Product ID to generate views for'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();

        // Generate sample data if requested
        if ($input->getOption('generate-data')) {
            $productId = $input->getOption('product-id');
            if (!$productId) {
                $io->error('Please provide a product ID with --product-id when using --generate-data');
                return Command::FAILURE;
            }

            $io->section('Generating sample data...');
            $this->generateSampleData($productId, $context, $io);
        }

        // Test 1: Views for last N days
        $io->section('Views for Last 7 Days');
        $viewsByDay = $this->analyticsService->getViewsForLastDays(7, $context);
        
        if (empty($viewsByDay)) {
            $io->warning('No data found. Use --generate-data to create sample data.');
        } else {
            $io->table(
                ['Date', 'View Count'],
                array_map(fn($bucket) => [
                    $bucket->getKey(),
                    $bucket->getCount(),
                ], $viewsByDay)
            );
        }

        // Test 2: Total views by product
        $io->section('Total Views by Product');
        $viewsByProduct = $this->analyticsService->getTotalViewsByProduct($context);
        
        if (empty($viewsByProduct)) {
            $io->warning('No data found.');
        } else {
            $io->table(
                ['Product ID', 'Product Name', 'Total Views'],
                array_map(fn($item) => [
                    substr($item['product_id'], 0, 8) . '...',
                    $item['product_name'] ?? 'N/A',
                    $item['total_views'],
                ], array_slice($viewsByProduct, 0, 10)) // Show top 10
            );
        }

        // Test 3: Views by browser
        $io->section('Views by Browser');
        $viewsByBrowser = $this->analyticsService->getViewsByBrowser($context);
        
        if (empty($viewsByBrowser)) {
            $io->warning('No data found.');
        } else {
            $io->table(
                ['Browser', 'Total Views'],
                array_map(fn($browser, $count) => [$browser, $count], 
                    array_keys($viewsByBrowser), 
                    array_values($viewsByBrowser))
            );
        }

        $io->success('Analytics test completed!');
        
        return Command::SUCCESS;
    }

    private function generateSampleData(string $productId, Context $context, SymfonyStyle $io): void
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36 Edg/91.0.864.59',
        ];

        // Generate views for the last 7 days
        for ($day = 0; $day < 7; $day++) {
            $viewsPerDay = rand(5, 20);
            for ($i = 0; $i < $viewsPerDay; $i++) {
                $this->viewService->recordView(
                    $productId,
                    null,
                    $userAgents[array_rand($userAgents)],
                    $context
                );
            }
            $io->writeln("Generated {$viewsPerDay} views for day -{$day}");
        }

        $io->success('Sample data generated successfully!');
    }
}
```

Register in `services.xml`:

```xml
<service id="Learning\Bundle\Command\TestAnalyticsCommand">
    <argument type="service" id="Learning\Bundle\Service\ProductViewAnalyticsService"/>
    <argument type="service" id="Learning\Bundle\Service\ProductViewService"/>
    <tag name="console.command"/>
</service>
```

### Step 4: Test the Analytics

```bash
# First, get a product ID from your database (use lowercase hex format)
docker exec -it shopware-tutorial-olli-database-1 mariadb -uroot -proot shopware -e "SELECT LOWER(HEX(id)) as product_id, product_number FROM product LIMIT 5;"

# Generate sample data (replace YOUR_PRODUCT_ID with an actual ID - use lowercase hex)
bin/console learning:test-analytics --generate-data --product-id=YOUR_PRODUCT_ID

# Run analytics without generating new data
bin/console learning:test-analytics

# Check the database directly
docker exec -it shopware-tutorial-olli-database-1 mariadb -uroot -proot shopware -e "SELECT product_id, view_count, user_agent, last_viewed_at FROM learning_product_view ORDER BY last_viewed_at DESC LIMIT 10;"

# Note: Product names are fetched using getTranslated()['name'] which accesses 
# the product_translation table. If a product variant doesn't have a name in 
# translations, the service falls back to showing the product_number instead.
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

Create a complete rating system with testing capability.

**Step 1: Create Migration**

Create `Migration1700000003CreateProductRatingTable.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1700000003CreateProductRatingTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1700000003;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `learning_product_rating` (
    `id` BINARY(16) NOT NULL,
    `product_id` BINARY(16) NOT NULL,
    `product_version_id` BINARY(16) NOT NULL,
    `customer_id` BINARY(16) NULL,
    `rating` INT NOT NULL,
    `comment` TEXT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    KEY `fk.learning_product_rating.product_id` (`product_id`,`product_version_id`),
    KEY `fk.learning_product_rating.customer_id` (`customer_id`),
    CONSTRAINT `fk.learning_product_rating.product_id` 
        FOREIGN KEY (`product_id`,`product_version_id`) 
        REFERENCES `product` (`id`,`version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk.learning_product_rating.customer_id` 
        FOREIGN KEY (`customer_id`) 
        REFERENCES `customer` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `chk.learning_product_rating.rating` CHECK (`rating` >= 1 AND `rating` <= 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        $sql = <<<SQL
DROP TABLE IF EXISTS `learning_product_rating`;
SQL;

        $connection->executeStatement($sql);
    }
}
```

**Step 2: Create Entity, Collection, and Definition**

Similar pattern to ProductView - create:
- `ProductRatingEntity.php` with properties: id, productId, customerId, rating, comment, createdAt
- `ProductRatingCollection.php`
- `ProductRatingDefinition.php` with ENTITY_NAME = 'learning_product_rating'

**Step 3: Create Rating Service**

Create `ProductRatingService.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\AvgAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;

class ProductRatingService
{
    private EntityRepository $ratingRepository;

    public function __construct(EntityRepository $ratingRepository)
    {
        $this->ratingRepository = $ratingRepository;
    }

    public function addRating(
        string $productId,
        ?string $customerId,
        int $rating,
        ?string $comment,
        Context $context
    ): void {
        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5');
        }

        $this->ratingRepository->create([
            [
                'id' => Uuid::randomHex(),
                'productId' => $productId,
                'customerId' => $customerId,
                'rating' => $rating,
                'comment' => $comment,
            ]
        ], $context);
    }

    public function getAverageRating(string $productId, Context $context): ?float
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productId', $productId));
        $criteria->addAggregation(new AvgAggregation('avg_rating', 'rating'));

        $result = $this->ratingRepository->search($criteria, $context);
        $avgAggregation = $result->getAggregations()->get('avg_rating');

        return $avgAggregation?->getAvg();
    }

    public function getRatingsForProduct(string $productId, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productId', $productId));
        $criteria->addAssociation('customer');
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));

        return $this->ratingRepository->search($criteria, $context)->getElements();
    }
}
```

**Step 4: Create Test Command**

Create `TestRatingCommand.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Learning\Bundle\Service\ProductRatingService;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TestRatingCommand extends Command
{
    private ProductRatingService $ratingService;

    public function __construct(ProductRatingService $ratingService)
    {
        parent::__construct();
        $this->ratingService = $ratingService;
    }

    protected function configure(): void
    {
        $this
            ->setName('learning:test-rating')
            ->setDescription('Test the ProductRatingService')
            ->addOption('product-id', 'p', InputOption::VALUE_REQUIRED, 'Product ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();
        
        $productId = $input->getOption('product-id');
        if (!$productId) {
            $io->error('Please provide --product-id');
            return Command::FAILURE;
        }

        // Add sample ratings
        $io->section('Adding sample ratings...');
        $this->ratingService->addRating($productId, null, 5, 'Excellent product!', $context);
        $this->ratingService->addRating($productId, null, 4, 'Very good', $context);
        $this->ratingService->addRating($productId, null, 5, 'Amazing!', $context);
        $this->ratingService->addRating($productId, null, 3, 'Good but could be better', $context);
        $io->success('Added 4 sample ratings');

        // Get average
        $average = $this->ratingService->getAverageRating($productId, $context);
        $io->info(sprintf('Average Rating: %.2f / 5.00', $average ?? 0));

        // Get all ratings
        $ratings = $this->ratingService->getRatingsForProduct($productId, $context);
        $io->section('All Ratings:');
        
        $rows = [];
        foreach ($ratings as $rating) {
            $rows[] = [
                $rating->getRating() . ' ★',
                $rating->getComment() ?? 'No comment',
                $rating->getCreatedAt()->format('Y-m-d H:i'),
            ];
        }
        
        $io->table(['Rating', 'Comment', 'Date'], $rows);

        return Command::SUCCESS;
    }
}
```

**Test it:**
```bash
bin/console plugin:update LearningBundle
bin/console learning:test-rating --product-id=YOUR_PRODUCT_ID
```

---

### Exercise 2: Wishlist Feature

Create a wishlist system - track which customers have which products saved.

**Goal:** Create migration, entities, service with methods:
- `addToWishlist(customerId, productId)`
- `removeFromWishlist(customerId, productId)`
- `getWishlist(customerId)` - returns list of products
- `isInWishlist(customerId, productId)` - boolean check

**Test with:**
```bash
bin/console learning:test-wishlist --customer-id=YOUR_CUSTOMER_ID --product-id=YOUR_PRODUCT_ID
```

---

### Exercise 3: Product Comparison Table

Create a comparison tracking system.

**Goal:** 
- Store which products users are comparing together
- Track comparison sessions (session_id, product_ids[], timestamp)
- Find most commonly compared product pairs

**Bonus:** Create a command that shows insights like "Products A and B are compared together 45% of the time"

---

## Testing Your Work

### Complete Testing Workflow

```bash
# 1. Run migrations (install/update plugin)
bin/console plugin:update LearningBundle

# 2. Check tables were created
docker exec -it shopware-tutorial-olli-database-1 mariadb -uroot -proot shopware -e "SHOW TABLES LIKE 'learning_%';"

# 3. Verify entity definitions are registered
bin/console debug:container --tag=shopware.entity.definition | grep learning

# 4. Get a product ID to test with (lowercase hex format required)
docker exec -it shopware-tutorial-olli-database-1 mariadb -uroot -proot shopware -e "SELECT LOWER(HEX(id)) as product_id, product_number FROM product LIMIT 5;"

# 5. Test basic product view tracking
bin/console learning:test-product-view

# 6. Test analytics (with data generation)
bin/console learning:test-analytics --generate-data --product-id=YOUR_PRODUCT_ID

# 7. View analytics results
bin/console learning:test-analytics

# 8. Test rating system (if you completed Exercise 1)
bin/console learning:test-rating --product-id=YOUR_PRODUCT_ID

# 9. Query data directly to verify
docker exec -it shopware-tutorial-olli-database-1 mariadb -uroot -proot shopware -e "
SELECT 
    pv.view_count, 
    pv.last_viewed_at, 
    p.product_number,
    HEX(pv.product_id) as product_id
FROM learning_product_view pv
LEFT JOIN product p ON pv.product_id = p.id AND pv.product_version_id = p.version_id
ORDER BY pv.last_viewed_at DESC
LIMIT 10;
"

# 10. Check rating data (if applicable)
docker exec -it shopware-tutorial-olli-database-1 mariadb -uroot -proot shopware -e "
SELECT 
    rating, 
    comment, 
    created_at 
FROM learning_product_rating 
ORDER BY created_at DESC 
LIMIT 10;
"

# Interactive database access for manual queries
docker exec -it shopware-tutorial-olli-database-1 mariadb -uroot -proot shopware
```

### Troubleshooting Commands

```bash
# Clear all caches
bin/console cache:clear

# Rebuild container
bin/console cache:clear && bin/console debug:container --tag=shopware.entity.definition | grep learning

# Check migration status
docker exec -it shopware-tutorial-olli-database-1 mariadb -uroot -proot shopware -e "SELECT * FROM migration WHERE class LIKE '%Learning%';"

# Drop and recreate (for testing migrations)
bin/console plugin:uninstall LearningBundle
docker exec -it shopware-tutorial-olli-database-1 mariadb -uroot -proot shopware -e "DROP TABLE IF EXISTS learning_product_view, learning_product_rating;"
bin/console plugin:install --activate LearningBundle
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

**Problem:** Migration not found (0 out of 0 migrations)
- **Most common:** Missing `getMigrationNamespace()` and `getMigrationPath()` methods in your plugin class
- Check timestamp in filename matches getCreationTimestamp()
- Ensure namespace is correct: `Learning\Bundle\Migration`
- Reinstall plugin: `bin/console plugin:uninstall LearningBundle && bin/console plugin:install --activate LearningBundle`

**Problem:** SQL syntax error in migration
- **Heredoc syntax:** Don't indent the SQL in heredoc strings - start SQL commands at the beginning of the line
- Missing columns that are referenced in CONSTRAINT or KEY definitions
- Check foreign key column names match exactly

**Problem:** Entity not registered
- Verify `<tag name="shopware.entity.definition"/>`
- Check entity name matches table name
- Clear cache completely

**Problem:** Foreign key constraint fails
- Ensure referenced IDs exist
- Check foreign key definitions in migration
- Verify CASCADE rules
- Make sure `product_version_id` is included when referencing product table

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
