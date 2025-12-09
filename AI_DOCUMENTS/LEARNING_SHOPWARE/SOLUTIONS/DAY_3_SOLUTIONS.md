# Day 3 Solutions: Database, Migrations, and Custom Entities

Complete solutions for all exercises in Day 3.

## Exercise 1: Product Rating System

### Migration

**File:** `custom/plugins/LearningBundle/src/Migration/Migration1700000010CreateProductRatingTable.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1700000010CreateProductRatingTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1700000010;
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
    KEY `idx.learning_product_rating.rating` (`rating`),
    CONSTRAINT `fk.learning_product_rating.product_id` 
        FOREIGN KEY (`product_id`,`product_version_id`) 
        REFERENCES `product` (`id`,`version_id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk.learning_product_rating.customer_id` 
        FOREIGN KEY (`customer_id`) 
        REFERENCES `customer` (`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `chk.learning_product_rating.rating` 
        CHECK (`rating` >= 1 AND `rating` <= 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        $connection->executeStatement('DROP TABLE IF EXISTS `learning_product_rating`');
    }
}
```

### Entity

**File:** `custom/plugins/LearningBundle/src/Core/Content/ProductRating/ProductRatingEntity.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\ProductRating;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ProductRatingEntity extends Entity
{
    use EntityIdTrait;

    protected string $productId;
    protected ?ProductEntity $product = null;
    protected ?string $customerId = null;
    protected ?CustomerEntity $customer = null;
    protected int $rating;
    protected ?string $comment = null;
    protected \DateTimeInterface $createdAt;
    protected ?\DateTimeInterface $updatedAt = null;

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getProduct(): ?ProductEntity
    {
        return $this->product;
    }

    public function setProduct(?ProductEntity $product): void
    {
        $this->product = $product;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function setCustomerId(?string $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function getCustomer(): ?CustomerEntity
    {
        return $this->customer;
    }

    public function setCustomer(?CustomerEntity $customer): void
    {
        $this->customer = $customer;
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    public function setRating(int $rating): void
    {
        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5');
        }
        $this->rating = $rating;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
```

### Collection

**File:** `custom/plugins/LearningBundle/src/Core/Content/ProductRating/ProductRatingCollection.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\ProductRating;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                       add(ProductRatingEntity $entity)
 * @method void                       set(string $key, ProductRatingEntity $entity)
 * @method ProductRatingEntity[]      getIterator()
 * @method ProductRatingEntity[]      getElements()
 * @method ProductRatingEntity|null   get(string $key)
 * @method ProductRatingEntity|null   first()
 * @method ProductRatingEntity|null   last()
 */
class ProductRatingCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ProductRatingEntity::class;
    }

    public function getAverageRating(): float
    {
        if ($this->count() === 0) {
            return 0.0;
        }

        $total = 0;
        foreach ($this->elements as $rating) {
            $total += $rating->getRating();
        }

        return round($total / $this->count(), 2);
    }

    public function getRatingDistribution(): array
    {
        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        foreach ($this->elements as $rating) {
            $distribution[$rating->getRating()]++;
        }

        return $distribution;
    }
}
```

### Definition

**File:** `custom/plugins/LearningBundle/src/Core/Content/ProductRating/ProductRatingDefinition.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\ProductRating;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ProductRatingDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'learning_product_rating';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return ProductRatingEntity::class;
    }

    public function getCollectionClass(): string
    {
        return ProductRatingCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            
            (new FkField('product_id', 'productId', ProductDefinition::class))->addFlags(new Required()),
            (new ReferenceVersionField(ProductDefinition::class))->addFlags(new Required()),
            
            new FkField('customer_id', 'customerId', CustomerDefinition::class),
            
            (new IntField('rating', 'rating'))->addFlags(new Required()),
            new LongTextField('comment', 'comment'),

            new ManyToOneAssociationField('product', 'product_id', ProductDefinition::class, 'id'),
            new ManyToOneAssociationField('customer', 'customer_id', CustomerDefinition::class, 'id'),
        ]);
    }
}
```

### Service

**File:** `custom/plugins/LearningBundle/src/Service/ProductRatingService.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Learning\Bundle\Core\Content\ProductRating\ProductRatingEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
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
        int $rating,
        ?string $customerId = null,
        ?string $comment = null,
        Context $context
    ): string {
        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5');
        }

        $ratingId = Uuid::randomHex();
        
        $this->ratingRepository->create([
            [
                'id' => $ratingId,
                'productId' => $productId,
                'customerId' => $customerId,
                'rating' => $rating,
                'comment' => $comment,
            ],
        ], $context);

        return $ratingId;
    }

    public function getAverageRating(string $productId, Context $context): float
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productId', $productId));

        $ratings = $this->ratingRepository->search($criteria, $context);

        if ($ratings->count() === 0) {
            return 0.0;
        }

        $total = 0;
        /** @var ProductRatingEntity $rating */
        foreach ($ratings as $rating) {
            $total += $rating->getRating();
        }

        return round($total / $ratings->count(), 2);
    }

    public function getRatingsForProduct(string $productId, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productId', $productId));
        $criteria->addAssociation('customer');
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));

        $ratings = $this->ratingRepository->search($criteria, $context);

        $result = [];
        /** @var ProductRatingEntity $rating */
        foreach ($ratings as $rating) {
            $result[] = [
                'id' => $rating->getId(),
                'rating' => $rating->getRating(),
                'comment' => $rating->getComment(),
                'customerName' => $rating->getCustomer() 
                    ? $rating->getCustomer()->getFirstName() . ' ' . $rating->getCustomer()->getLastName()
                    : 'Anonymous',
                'createdAt' => $rating->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return $result;
    }

    public function getRatingDistribution(string $productId, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productId', $productId));

        $ratings = $this->ratingRepository->search($criteria, $context);

        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        /** @var ProductRatingEntity $rating */
        foreach ($ratings as $rating) {
            $distribution[$rating->getRating()]++;
        }

        return $distribution;
    }
}
```

### Services Configuration

Add to `custom/plugins/LearningBundle/src/Resources/config/services.xml`:

```xml
<service id="Learning\Bundle\Core\Content\ProductRating\ProductRatingDefinition">
    <tag name="shopware.entity.definition" entity="learning_product_rating"/>
</service>

<service id="Learning\Bundle\Service\ProductRatingService">
    <argument type="service" id="learning_product_rating.repository"/>
</service>
```

---

## Exercise 2: Wishlist Feature

### Migration

**File:** `custom/plugins/LearningBundle/src/Migration/Migration1700000011CreateWishlistTable.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1700000011CreateWishlistTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1700000011;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `learning_wishlist` (
    `id` BINARY(16) NOT NULL,
    `customer_id` BINARY(16) NOT NULL,
    `product_id` BINARY(16) NOT NULL,
    `product_version_id` BINARY(16) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq.learning_wishlist.customer_product` (`customer_id`, `product_id`, `product_version_id`),
    KEY `fk.learning_wishlist.customer_id` (`customer_id`),
    KEY `fk.learning_wishlist.product_id` (`product_id`,`product_version_id`),
    CONSTRAINT `fk.learning_wishlist.customer_id` 
        FOREIGN KEY (`customer_id`) 
        REFERENCES `customer` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk.learning_wishlist.product_id` 
        FOREIGN KEY (`product_id`,`product_version_id`) 
        REFERENCES `product` (`id`,`version_id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        $connection->executeStatement('DROP TABLE IF EXISTS `learning_wishlist`');
    }
}
```

### Entity, Collection, and Definition

**File:** `custom/plugins/LearningBundle/src/Core/Content/Wishlist/WishlistEntity.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\Wishlist;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class WishlistEntity extends Entity
{
    use EntityIdTrait;

    protected string $customerId;
    protected ?CustomerEntity $customer = null;
    protected string $productId;
    protected ?ProductEntity $product = null;
    protected \DateTimeInterface $createdAt;

    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function setCustomerId(string $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function getCustomer(): ?CustomerEntity
    {
        return $this->customer;
    }

    public function setCustomer(?CustomerEntity $customer): void
    {
        $this->customer = $customer;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getProduct(): ?ProductEntity
    {
        return $this->product;
    }

    public function setProduct(?ProductEntity $product): void
    {
        $this->product = $product;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
```

**File:** `custom/plugins/LearningBundle/src/Core/Content/Wishlist/WishlistCollection.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\Wishlist;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                   add(WishlistEntity $entity)
 * @method void                   set(string $key, WishlistEntity $entity)
 * @method WishlistEntity[]       getIterator()
 * @method WishlistEntity[]       getElements()
 * @method WishlistEntity|null    get(string $key)
 * @method WishlistEntity|null    first()
 * @method WishlistEntity|null    last()
 */
class WishlistCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return WishlistEntity::class;
    }

    public function getProductIds(): array
    {
        return $this->fmap(fn(WishlistEntity $item) => $item->getProductId());
    }
}
```

**File:** `custom/plugins/LearningBundle/src/Core/Content/Wishlist/WishlistDefinition.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\Wishlist;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class WishlistDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'learning_wishlist';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return WishlistEntity::class;
    }

    public function getCollectionClass(): string
    {
        return WishlistCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            
            (new FkField('customer_id', 'customerId', CustomerDefinition::class))->addFlags(new Required()),
            (new FkField('product_id', 'productId', ProductDefinition::class))->addFlags(new Required()),
            (new ReferenceVersionField(ProductDefinition::class))->addFlags(new Required()),

            new ManyToOneAssociationField('customer', 'customer_id', CustomerDefinition::class, 'id'),
            new ManyToOneAssociationField('product', 'product_id', ProductDefinition::class, 'id'),
        ]);
    }
}
```

### Service

**File:** `custom/plugins/LearningBundle/src/Service/WishlistService.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Learning\Bundle\Core\Content\Wishlist\WishlistEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class WishlistService
{
    private EntityRepository $wishlistRepository;

    public function __construct(EntityRepository $wishlistRepository)
    {
        $this->wishlistRepository = $wishlistRepository;
    }

    public function addToWishlist(string $customerId, string $productId, Context $context): string
    {
        // Check if already in wishlist
        if ($this->isInWishlist($customerId, $productId, $context)) {
            throw new \RuntimeException('Product already in wishlist');
        }

        $wishlistId = Uuid::randomHex();
        
        $this->wishlistRepository->create([
            [
                'id' => $wishlistId,
                'customerId' => $customerId,
                'productId' => $productId,
            ],
        ], $context);

        return $wishlistId;
    }

    public function removeFromWishlist(string $customerId, string $productId, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new MultiFilter(MultiFilter::CONNECTION_AND, [
                new EqualsFilter('customerId', $customerId),
                new EqualsFilter('productId', $productId),
            ])
        );

        $wishlistItems = $this->wishlistRepository->searchIds($criteria, $context);

        if ($wishlistItems->getTotal() === 0) {
            throw new \RuntimeException('Product not in wishlist');
        }

        $ids = array_map(fn($id) => ['id' => $id], $wishlistItems->getIds());
        $this->wishlistRepository->delete($ids, $context);
    }

    public function getWishlist(string $customerId, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));
        $criteria->addAssociation('product');

        $wishlistItems = $this->wishlistRepository->search($criteria, $context);

        $result = [];
        /** @var WishlistEntity $item */
        foreach ($wishlistItems as $item) {
            $product = $item->getProduct();
            if ($product) {
                $result[] = [
                    'id' => $item->getId(),
                    'productId' => $product->getId(),
                    'productName' => $product->getName(),
                    'productNumber' => $product->getProductNumber(),
                    'addedAt' => $item->getCreatedAt()->format('Y-m-d H:i:s'),
                ];
            }
        }

        return $result;
    }

    public function isInWishlist(string $customerId, string $productId, Context $context): bool
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new MultiFilter(MultiFilter::CONNECTION_AND, [
                new EqualsFilter('customerId', $customerId),
                new EqualsFilter('productId', $productId),
            ])
        );

        return $this->wishlistRepository->searchIds($criteria, $context)->getTotal() > 0;
    }

    public function getWishlistCount(string $customerId, Context $context): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));

        return $this->wishlistRepository->searchIds($criteria, $context)->getTotal();
    }
}
```

### Services Configuration

Add to `custom/plugins/LearningBundle/src/Resources/config/services.xml`:

```xml
<service id="Learning\Bundle\Core\Content\Wishlist\WishlistDefinition">
    <tag name="shopware.entity.definition" entity="learning_wishlist"/>
</service>

<service id="Learning\Bundle\Service\WishlistService">
    <argument type="service" id="learning_wishlist.repository"/>
</service>
```

---

## Exercise 3: Product Comparison

### Migration

**File:** `custom/plugins/LearningBundle/src/Migration/Migration1700000012CreateProductComparisonTable.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1700000012CreateProductComparisonTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1700000012;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `learning_product_comparison` (
    `id` BINARY(16) NOT NULL,
    `session_id` VARCHAR(255) NOT NULL,
    `customer_id` BINARY(16) NULL,
    `product_id` BINARY(16) NOT NULL,
    `product_version_id` BINARY(16) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx.learning_product_comparison.session_id` (`session_id`),
    KEY `fk.learning_product_comparison.customer_id` (`customer_id`),
    KEY `fk.learning_product_comparison.product_id` (`product_id`,`product_version_id`),
    CONSTRAINT `fk.learning_product_comparison.customer_id` 
        FOREIGN KEY (`customer_id`) 
        REFERENCES `customer` (`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.learning_product_comparison.product_id` 
        FOREIGN KEY (`product_id`,`product_version_id`) 
        REFERENCES `product` (`id`,`version_id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        $connection->executeStatement('DROP TABLE IF EXISTS `learning_product_comparison`');
    }
}
```

### Entity, Collection, and Definition

**File:** `custom/plugins/LearningBundle/src/Core/Content/ProductComparison/ProductComparisonEntity.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\ProductComparison;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ProductComparisonEntity extends Entity
{
    use EntityIdTrait;

    protected string $sessionId;
    protected ?string $customerId = null;
    protected ?CustomerEntity $customer = null;
    protected string $productId;
    protected ?ProductEntity $product = null;
    protected \DateTimeInterface $createdAt;

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function setSessionId(string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function setCustomerId(?string $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function getCustomer(): ?CustomerEntity
    {
        return $this->customer;
    }

    public function setCustomer(?CustomerEntity $customer): void
    {
        $this->customer = $customer;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getProduct(): ?ProductEntity
    {
        return $this->product;
    }

    public function setProduct(?ProductEntity $product): void
    {
        $this->product = $product;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
```

**File:** `custom/plugins/LearningBundle/src/Core/Content/ProductComparison/ProductComparisonCollection.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\ProductComparison;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                           add(ProductComparisonEntity $entity)
 * @method void                           set(string $key, ProductComparisonEntity $entity)
 * @method ProductComparisonEntity[]      getIterator()
 * @method ProductComparisonEntity[]      getElements()
 * @method ProductComparisonEntity|null   get(string $key)
 * @method ProductComparisonEntity|null   first()
 * @method ProductComparisonEntity|null   last()
 */
class ProductComparisonCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ProductComparisonEntity::class;
    }

    public function getProductIds(): array
    {
        return $this->fmap(fn(ProductComparisonEntity $item) => $item->getProductId());
    }
}
```

**File:** `custom/plugins/LearningBundle/src/Core/Content/ProductComparison/ProductComparisonDefinition.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\ProductComparison;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ProductComparisonDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'learning_product_comparison';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return ProductComparisonEntity::class;
    }

    public function getCollectionClass(): string
    {
        return ProductComparisonCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            
            (new StringField('session_id', 'sessionId'))->addFlags(new Required()),
            new FkField('customer_id', 'customerId', CustomerDefinition::class),
            (new FkField('product_id', 'productId', ProductDefinition::class))->addFlags(new Required()),
            (new ReferenceVersionField(ProductDefinition::class))->addFlags(new Required()),

            new ManyToOneAssociationField('customer', 'customer_id', CustomerDefinition::class, 'id'),
            new ManyToOneAssociationField('product', 'product_id', ProductDefinition::class, 'id'),
        ]);
    }
}
```

### Service

**File:** `custom/plugins/LearningBundle/src/Service/ProductComparisonService.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Learning\Bundle\Core\Content\ProductComparison\ProductComparisonEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class ProductComparisonService
{
    private EntityRepository $comparisonRepository;

    public function __construct(EntityRepository $comparisonRepository)
    {
        $this->comparisonRepository = $comparisonRepository;
    }

    public function addToComparison(
        string $sessionId,
        string $productId,
        ?string $customerId = null,
        Context $context
    ): string {
        $comparisonId = Uuid::randomHex();
        
        $this->comparisonRepository->create([
            [
                'id' => $comparisonId,
                'sessionId' => $sessionId,
                'customerId' => $customerId,
                'productId' => $productId,
            ],
        ], $context);

        return $comparisonId;
    }

    public function getComparisonForSession(string $sessionId, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('sessionId', $sessionId));
        $criteria->addAssociation('product');

        $comparisons = $this->comparisonRepository->search($criteria, $context);

        $result = [];
        /** @var ProductComparisonEntity $comparison */
        foreach ($comparisons as $comparison) {
            $product = $comparison->getProduct();
            if ($product) {
                $result[] = [
                    'id' => $comparison->getId(),
                    'productId' => $product->getId(),
                    'productName' => $product->getName(),
                    'productNumber' => $product->getProductNumber(),
                    'addedAt' => $comparison->getCreatedAt()->format('Y-m-d H:i:s'),
                ];
            }
        }

        return $result;
    }

    public function clearComparison(string $sessionId, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('sessionId', $sessionId));

        $comparisonIds = $this->comparisonRepository->searchIds($criteria, $context);

        if ($comparisonIds->getTotal() === 0) {
            return;
        }

        $ids = array_map(fn($id) => ['id' => $id], $comparisonIds->getIds());
        $this->comparisonRepository->delete($ids, $context);
    }
}
```

### Services Configuration

Add to `custom/plugins/LearningBundle/src/Resources/config/services.xml`:

```xml
<service id="Learning\Bundle\Core\Content\ProductComparison\ProductComparisonDefinition">
    <tag name="shopware.entity.definition" entity="learning_product_comparison"/>
</service>

<service id="Learning\Bundle\Service\ProductComparisonService">
    <argument type="service" id="learning_product_comparison.repository"/>
</service>
```

---

## Running Migrations

```bash
# Run all migrations
bin/console database:migrate --all LearningBundle

# Verify tables
bin/console dbal:run-sql "SHOW TABLES LIKE 'learning_%'"

# Check entity definitions
bin/console debug:container --tag=shopware.entity.definition | grep learning
```

---

## Testing Commands

Create test commands to verify your implementations:

**File:** `custom/plugins/LearningBundle/src/Command/TestRatingCommand.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Learning\Bundle\Service\ProductRatingService;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TestRatingCommand extends Command
{
    protected static $defaultName = 'learning:test-rating';

    private ProductRatingService $ratingService;

    public function __construct(ProductRatingService $ratingService)
    {
        parent::__construct();
        $this->ratingService = $ratingService;
    }

    protected function configure(): void
    {
        $this->setDescription('Test product rating system')
            ->addArgument('productId', InputArgument::REQUIRED, 'Product ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();
        $productId = $input->getArgument('productId');

        // Add some ratings
        $this->ratingService->addRating($productId, 5, null, 'Excellent product!', $context);
        $this->ratingService->addRating($productId, 4, null, 'Very good', $context);
        $this->ratingService->addRating($productId, 5, null, 'Amazing!', $context);

        // Get average
        $average = $this->ratingService->getAverageRating($productId, $context);
        $io->success(sprintf('Average rating: %.2f', $average));

        // Get distribution
        $distribution = $this->ratingService->getRatingDistribution($productId, $context);
        $io->table(['Rating', 'Count'], [
            ['5 stars', $distribution[5]],
            ['4 stars', $distribution[4]],
            ['3 stars', $distribution[3]],
            ['2 stars', $distribution[2]],
            ['1 star', $distribution[1]],
        ]);

        return Command::SUCCESS;
    }
}
```

---

## Key Takeaways

✅ **You've mastered:**
- Creating complex database schemas with constraints
- Building complete entity systems (Entity, Collection, Definition)
- Implementing services with repository operations
- Handling many-to-many relationships
- Working with associations and foreign keys
- Creating unique constraints
- Session-based tracking

## Common Pitfalls

❌ **Mistakes to avoid:**
- Forgetting version fields for product references
- Not adding proper indexes for frequently queried columns
- Missing CASCADE rules on foreign keys
- Not validating input data
- Forgetting to clear cache after migrations

---

**Next:** Day 4 - API Architecture
