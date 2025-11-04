# Shopware 6 Data Structure & Entity System

## Understanding Shopware's Data Layer 🗄️

Shopware 6 uses a sophisticated Data Abstraction Layer (DAL) that provides a unified interface for database operations.

## Core Concepts

### 1. Entities vs Definitions
- **Entity**: The actual data object (like `ProductEntity`)
- **Definition**: Defines the structure, fields, and relationships (`ProductDefinition`)

### 2. The Repository Pattern
All data access goes through repositories:
```php
// Get repository from container
$productRepository = $this->container->get('product.repository');

// Search with criteria
$criteria = new Criteria();
$products = $productRepository->search($criteria, $context);
```

## Essential Entities & Their Relationships

### Product System 🛍️
```
Product (product)
├── ProductMedia (product_media)
├── ProductPrice (product_price) 
├── ProductCategory (product_category)
├── ProductProperty (product_property)
├── ProductReview (product_review)
└── ProductTranslation (product_translation)
```

**Key Product Fields:**
```php
ProductEntity:
- id (UUID)
- productNumber (string)
- name (string) - translatable
- description (text) - translatable
- price (array)
- stock (int)
- active (bool)
- categoryIds (array)
- media (ProductMediaCollection)
- translations (ProductTranslationCollection)
```

### Category System 🗂️
```
Category (category)
├── CategoryTranslation (category_translation)
└── ProductCategory (product_category) - Many-to-Many with Product
```

**Key Category Fields:**
```php
CategoryEntity:
- id (UUID)
- parentId (UUID)
- name (string) - translatable
- active (bool)
- level (int)
- path (string)
- products (ProductCollection)
```

### Customer & Order System 👥
```
Customer (customer)
├── CustomerAddress (customer_address)
├── CustomerGroup (customer_group)
└── Order (order)
    ├── OrderLineItem (order_line_item)
    ├── OrderAddress (order_address)
    ├── OrderDelivery (order_delivery)
    └── OrderTransaction (order_transaction)
```

**Key Customer Fields:**
```php
CustomerEntity:
- id (UUID)
- customerNumber (string)
- email (string)
- firstName (string)
- lastName (string)
- active (bool)
- guest (bool)
- groupId (UUID)
- addresses (CustomerAddressCollection)
- orders (OrderCollection)
```

**Key Order Fields:**
```php
OrderEntity:
- id (UUID)
- orderNumber (string)
- customerId (UUID)
- amountTotal (float)
- orderDate (DateTime)
- stateId (UUID)
- lineItems (OrderLineItemCollection)
- deliveries (OrderDeliveryCollection)
- transactions (OrderTransactionCollection)
```

### Sales Channel System 🏪
```
SalesChannel (sales_channel)
├── SalesChannelDomain (sales_channel_domain)
├── SalesChannelTranslation (sales_channel_translation)
└── SalesChannelType (sales_channel_type)
```

## Working with the DAL

### 1. Basic Repository Operations

#### Fetching Data
```php
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

// Get repository
$productRepository = $this->container->get('product.repository');

// Simple search
$criteria = new Criteria();
$products = $productRepository->search($criteria, $context);

// Search with filters
$criteria = new Criteria();
$criteria->addFilter(new EqualsFilter('active', true));
$criteria->addFilter(new EqualsFilter('stock', 0));
$outOfStockProducts = $productRepository->search($criteria, $context);

// Get single entity
$product = $productRepository->search(new Criteria([$productId]), $context)->first();
```

#### Creating Data
```php
$data = [
    'id' => Uuid::randomHex(),
    'productNumber' => 'SW-' . time(),
    'name' => 'My New Product',
    'stock' => 100,
    'price' => [
        [
            'currencyId' => Defaults::CURRENCY,
            'gross' => 19.99,
            'net' => 16.80,
            'linked' => true
        ]
    ],
    'tax' => ['id' => $taxId],
    'manufacturer' => ['id' => $manufacturerId],
    'categories' => [
        ['id' => $categoryId]
    ]
];

$productRepository->create([$data], $context);
```

#### Updating Data
```php
$updates = [
    [
        'id' => $productId,
        'name' => 'Updated Product Name',
        'stock' => 50
    ]
];

$productRepository->update($updates, $context);
```

#### Deleting Data
```php
$productRepository->delete([
    ['id' => $productId]
], $context);
```

### 2. Advanced Criteria Building

#### Associations (Loading Related Data)
```php
$criteria = new Criteria();
$criteria->addAssociation('media');
$criteria->addAssociation('categories');
$criteria->addAssociation('manufacturer');

$products = $productRepository->search($criteria, $context);

// Access related data
foreach ($products as $product) {
    $mediaCollection = $product->getMedia();
    $categoryCollection = $product->getCategories();
    $manufacturer = $product->getManufacturer();
}
```

#### Filters
```php
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\{
    EqualsFilter,
    EqualsAnyFilter,
    ContainsFilter,
    RangeFilter,
    NotFilter,
    MultiFilter
};

$criteria = new Criteria();

// Exact match
$criteria->addFilter(new EqualsFilter('active', true));

// Multiple values
$criteria->addFilter(new EqualsAnyFilter('id', [$id1, $id2, $id3]));

// Text search
$criteria->addFilter(new ContainsFilter('name', 'search term'));

// Range filter
$criteria->addFilter(new RangeFilter('price.gross', [
    RangeFilter::GTE => 10.00,
    RangeFilter::LTE => 100.00
]));

// NOT filter
$criteria->addFilter(new NotFilter(
    NotFilter::CONNECTION_AND,
    [new EqualsFilter('active', false)]
));

// Complex filter (AND/OR combinations)
$criteria->addFilter(new MultiFilter(
    MultiFilter::CONNECTION_OR,
    [
        new EqualsFilter('manufacturerId', $manufacturerId),
        new EqualsFilter('categoryIds', $categoryId)
    ]
));
```

#### Sorting & Pagination
```php
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

$criteria = new Criteria();

// Sorting
$criteria->addSorting(new FieldSorting('name', FieldSorting::ASCENDING));
$criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));

// Pagination
$criteria->setOffset(20);  // Skip first 20 results
$criteria->setLimit(10);   // Return 10 results

$result = $productRepository->search($criteria, $context);
$total = $result->getTotal();  // Total count (ignoring limit/offset)
```

### 3. Aggregations
```php
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\{
    Bucket\TermsAggregation,
    Metric\CountAggregation,
    Metric\SumAggregation,
    Metric\AvgAggregation
};

$criteria = new Criteria();

// Count products by manufacturer
$criteria->addAggregation(new TermsAggregation(
    'manufacturers',
    'manufacturerId',
    null,
    null,
    new CountAggregation('count', 'manufacturerId')
));

// Average price
$criteria->addAggregation(new AvgAggregation('avgPrice', 'price.gross'));

$result = $productRepository->search($criteria, $context);
$aggregations = $result->getAggregations();
```

## Custom Entities

### 1. Entity Definition
```php
<?php declare(strict_types=1);

namespace MyPlugin\Entity\MyEntity;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class MyEntityEntity extends Entity
{
    use EntityIdTrait;

    protected string $name;
    protected ?string $description;
    protected \DateTimeInterface $createdAt;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    // ... other getters and setters
}
```

### 2. Entity Definition Class
```php
<?php declare(strict_types=1);

namespace MyPlugin\Entity\MyEntity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\{
    IdField,
    StringField,
    TextField,
    DateTimeField,
    Flag\PrimaryKey,
    Flag\Required
};
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class MyEntityDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'my_entity';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return MyEntityEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('name', 'name'))->addFlags(new Required()),
            new TextField('description', 'description'),
            (new DateTimeField('created_at', 'createdAt'))->addFlags(new Required())
        ]);
    }
}
```

### 3. Collection Class
```php
<?php declare(strict_types=1);

namespace MyPlugin\Entity\MyEntity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void               add(MyEntityEntity $entity)
 * @method void               set(string $key, MyEntityEntity $entity)
 * @method MyEntityEntity[]   getIterator()
 * @method MyEntityEntity[]   getElements()
 * @method MyEntityEntity|null get(string $key)
 * @method MyEntityEntity|null first()
 * @method MyEntityEntity|null last()
 */
class MyEntityCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return MyEntityEntity::class;
    }
}
```

## Performance Tips 🚀

### 1. Use Associations Wisely
```php
// ❌ Bad: N+1 queries
$products = $productRepository->search(new Criteria(), $context);
foreach ($products as $product) {
    $manufacturer = $manufacturerRepository->search(
        new Criteria([$product->getManufacturerId()]), 
        $context
    )->first();
}

// ✅ Good: Single query with association
$criteria = new Criteria();
$criteria->addAssociation('manufacturer');
$products = $productRepository->search($criteria, $context);
foreach ($products as $product) {
    $manufacturer = $product->getManufacturer();
}
```

### 2. Limit Fields
```php
// Only load specific fields
$criteria = new Criteria();
$criteria->addFields(['id', 'name', 'productNumber']);
$products = $productRepository->search($criteria, $context);
```

### 3. Use Proper Indexing
Create database indexes for frequently filtered fields in your migrations.

## Common Patterns

### 1. Service with Repository
```php
<?php declare(strict_types=1);

namespace MyPlugin\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class ProductService
{
    private EntityRepository $productRepository;

    public function __construct(EntityRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function getActiveProducts(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        
        return $this->productRepository->search($criteria, $context)->getElements();
    }
}
```

### 2. Event Subscriber for Data Changes
```php
<?php declare(strict_types=1);

namespace MyPlugin\Subscriber;

use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ProductEvents::PRODUCT_WRITTEN_EVENT => 'onProductWritten'
        ];
    }

    public function onProductWritten(EntityWrittenEvent $event): void
    {
        foreach ($event->getWriteResults() as $result) {
            $payload = $result->getPayload();
            // Do something with the written product data
        }
    }
}
```

This data structure forms the foundation of all Shopware operations. Understanding these relationships and patterns will help you build efficient, maintainable extensions.