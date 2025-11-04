# Shopware 6 Development Best Practices & Tips

## Code Quality & Standards 📏

### PSR Standards
Shopware follows PHP-FIG standards:
- **PSR-4**: Autoloading
- **PSR-12**: Coding style
- **PSR-3**: Logger interface

### Coding Conventions
```php
<?php declare(strict_types=1);

namespace MyPlugin\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

/**
 * Service for handling product operations
 */
class ProductService
{
    private EntityRepository $productRepository;

    public function __construct(EntityRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function findActiveProducts(Context $context): array
    {
        // Method implementation
    }
}
```

**Key Points:**
- Always use `declare(strict_types=1)`
- Use type declarations for all parameters and return types
- Follow camelCase for methods and variables
- Use PascalCase for classes
- Document with PHPDoc blocks

## Performance Optimization ⚡

### 1. Repository Usage
```php
// ❌ Bad: Multiple queries
foreach ($productIds as $id) {
    $product = $productRepository->search(new Criteria([$id]), $context)->first();
}

// ✅ Good: Single query
$criteria = new Criteria($productIds);
$products = $productRepository->search($criteria, $context);
```

### 2. Association Loading
```php
// ❌ Bad: Loading unnecessary associations
$criteria = new Criteria();
$criteria->addAssociation('manufacturer');
$criteria->addAssociation('categories');
$criteria->addAssociation('media');
$criteria->addAssociation('properties');
// ... only using name field

// ✅ Good: Load only what you need
$criteria = new Criteria();
$criteria->addFields(['id', 'name', 'productNumber']);
```

### 3. Caching Strategies
```php
<?php declare(strict_types=1);

namespace MyPlugin\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CachedProductService
{
    private CacheInterface $cache;
    private ProductService $productService;

    public function __construct(CacheInterface $cache, ProductService $productService)
    {
        $this->cache = $cache;
        $this->productService = $productService;
    }

    public function getPopularProducts(): array
    {
        return $this->cache->get('popular_products', function (ItemInterface $item) {
            $item->expiresAfter(3600); // 1 hour
            return $this->productService->calculatePopularProducts();
        });
    }
}
```

## Security Best Practices 🔒

### 1. Input Validation
```php
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductController
{
    private ValidatorInterface $validator;

    public function createProduct(Request $request): JsonResponse
    {
        $data = $request->request->all();
        
        // Validate input
        $constraints = new Assert\Collection([
            'name' => [new Assert\NotBlank(), new Assert\Length(['max' => 255])],
            'price' => [new Assert\NotBlank(), new Assert\Positive()],
            'stock' => [new Assert\NotBlank(), new Assert\PositiveOrZero()]
        ]);

        $violations = $this->validator->validate($data, $constraints);
        
        if (count($violations) > 0) {
            return new JsonResponse(['errors' => (string) $violations], 400);
        }

        // Process valid data
    }
}
```

### 2. SQL Injection Prevention
```php
// ❌ Bad: String concatenation (vulnerable to injection)
$sql = "SELECT * FROM product WHERE name = '" . $name . "'";

// ✅ Good: Use DAL with filters
$criteria = new Criteria();
$criteria->addFilter(new EqualsFilter('name', $name));
$products = $repository->search($criteria, $context);

// ✅ Good: If raw SQL is necessary, use parameters
$connection = $this->container->get(Connection::class);
$result = $connection->fetchAllAssociative(
    'SELECT * FROM product WHERE name = :name',
    ['name' => $name]
);
```

### 3. Access Control
```php
use Shopware\Core\Framework\Api\Acl\Role\AclRoleDefinition;
use Shopware\Core\Framework\Context;

class SecureProductService
{
    public function deleteProduct(string $productId, Context $context): void
    {
        // Check permissions
        if (!$context->isAllowed('product:delete')) {
            throw new MissingPrivilegeException(['product:delete']);
        }

        // Proceed with deletion
    }
}
```

## Error Handling & Logging 🐛

### 1. Proper Exception Handling
```php
<?php declare(strict_types=1);

namespace MyPlugin\Exception;

use Shopware\Core\Framework\ShopwareHttpException;

class ProductNotFoundException extends ShopwareHttpException
{
    public function __construct(string $productId)
    {
        parent::__construct(
            'Product with ID "{{ productId }}" not found.',
            ['productId' => $productId]
        );
    }

    public function getErrorCode(): string
    {
        return 'PRODUCT_NOT_FOUND';
    }
}
```

### 2. Logging Best Practices
```php
use Psr\Log\LoggerInterface;

class ProductService
{
    private LoggerInterface $logger;

    public function updateProduct(string $id, array $data, Context $context): void
    {
        try {
            $this->logger->info('Updating product', ['productId' => $id]);
            
            // Update logic
            
            $this->logger->info('Product updated successfully', ['productId' => $id]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update product', [
                'productId' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
}
```

## Testing Strategies 🧪

### 1. Unit Tests
```php
<?php declare(strict_types=1);

namespace MyPlugin\Test\Unit\Service;

use PHPUnit\Framework\TestCase;
use MyPlugin\Service\ProductCalculator;

class ProductCalculatorTest extends TestCase
{
    private ProductCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new ProductCalculator();
    }

    public function testCalculateDiscount(): void
    {
        $price = 100.0;
        $discountPercentage = 20.0;
        
        $result = $this->calculator->calculateDiscount($price, $discountPercentage);
        
        $this->assertEquals(80.0, $result);
    }
}
```

### 2. Integration Tests
```php
<?php declare(strict_types=1);

namespace MyPlugin\Test\Integration\Service;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Test\TestDefaults;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

class ProductServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testCreateProduct(): void
    {
        $repository = $this->getContainer()->get('product.repository');
        $context = Context::createDefaultContext();
        
        $data = [
            'id' => Uuid::randomHex(),
            'name' => 'Test Product',
            'productNumber' => 'TEST-001',
            'stock' => 10,
            'price' => [
                [
                    'currencyId' => TestDefaults::CURRENCY,
                    'gross' => 15.99,
                    'net' => 13.44,
                    'linked' => true
                ]
            ]
        ];
        
        $repository->create([$data], $context);
        
        $product = $repository->search(new Criteria([$data['id']]), $context)->first();
        $this->assertNotNull($product);
        $this->assertEquals('Test Product', $product->getName());
    }
}
```

## Configuration Management ⚙️

### 1. Plugin Configuration
```xml
<!-- config/config.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/shopware/platform/master/src/Core/System/SystemConfig/Schema/config.xsd">

    <card>
        <title>My Plugin Settings</title>
        <title lang="de-DE">Meine Plugin Einstellungen</title>

        <input-field>
            <name>apiKey</name>
            <label>API Key</label>
            <label lang="de-DE">API Schlüssel</label>
            <helpText>Your API key for external service</helpText>
            <helpText lang="de-DE">Ihr API Schlüssel für externen Service</helpText>
        </input-field>

        <input-field type="bool">
            <name>enableFeature</name>
            <label>Enable Feature</label>
            <label lang="de-DE">Feature aktivieren</label>
        </input-field>

        <input-field type="int">
            <name>maxItems</name>
            <label>Maximum Items</label>
            <label lang="de-DE">Maximale Anzahl</label>
            <defaultValue>10</defaultValue>
        </input-field>
    </card>
</config>
```

### 2. Reading Configuration
```php
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ConfigurableService
{
    private SystemConfigService $systemConfig;

    public function __construct(SystemConfigService $systemConfig)
    {
        $this->systemConfig = $systemConfig;
    }

    public function doSomething(?string $salesChannelId = null): void
    {
        $apiKey = $this->systemConfig->get('MyPlugin.config.apiKey', $salesChannelId);
        $isEnabled = $this->systemConfig->getBool('MyPlugin.config.enableFeature', $salesChannelId);
        $maxItems = $this->systemConfig->getInt('MyPlugin.config.maxItems', $salesChannelId);
        
        if (!$isEnabled) {
            return;
        }
        
        // Use configuration
    }
}
```

## Internationalization (i18n) 🌍

### 1. Translation Files
```json
// snippet/en_GB.json
{
    "myPlugin": {
        "general": {
            "title": "My Plugin",
            "description": "This is my awesome plugin"
        },
        "messages": {
            "success": "Operation completed successfully",
            "error": "An error occurred"
        }
    }
}

// snippet/de_DE.json
{
    "myPlugin": {
        "general": {
            "title": "Mein Plugin",
            "description": "Das ist mein großartiges Plugin"
        },
        "messages": {
            "success": "Vorgang erfolgreich abgeschlossen",
            "error": "Ein Fehler ist aufgetreten"
        }
    }
}
```

### 2. Using Translations in PHP
```php
use Shopware\Core\Framework\Context;
use Shopware\Core\System\Snippet\SnippetService;

class TranslatableService
{
    private SnippetService $snippetService;

    public function getMessage(Context $context): string
    {
        return $this->snippetService->getStorefrontSnippets(
            new SnippetFile('myPlugin.json', 'myPlugin.json', 'en_GB'),
            $context->getLanguageId()
        )['myPlugin']['messages']['success'] ?? 'Success';
    }
}
```

## API Development 🔌

### 1. Custom API Routes
```php
<?php declare(strict_types=1);

namespace MyPlugin\Controller\Api;

use Shopware\Core\Framework\Api\Controller\ApiController;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class CustomApiController extends ApiController
{
    #[Route(
        path: '/api/_action/my-plugin/custom-endpoint',
        name: 'api.action.my-plugin.custom-endpoint',
        methods: ['POST']
    )]
    public function customEndpoint(): JsonResponse
    {
        return new JsonResponse(['status' => 'success']);
    }
}
```

### 2. API Error Handling
```php
use Shopware\Core\Framework\Api\Exception\ResourceNotFoundException;

#[Route(
    path: '/api/_action/my-plugin/product/{id}',
    name: 'api.action.my-plugin.product',
    methods: ['GET']
)]
public function getProduct(string $id, Context $context): JsonApiResponse
{
    $criteria = new Criteria([$id]);
    $product = $this->productRepository->search($criteria, $context)->first();
    
    if (!$product) {
        throw new ResourceNotFoundException('Product', $id);
    }
    
    return new JsonApiResponse($product);
}
```

## Common Pitfalls & Solutions ⚠️

### 1. Context Issues
```php
// ❌ Bad: Creating new context
$context = Context::createDefaultContext();

// ✅ Good: Use injected context
public function myMethod(Context $context): void
{
    // Use $context parameter
}
```

### 2. Memory Issues with Large Datasets
```php
// ❌ Bad: Loading all at once
$criteria = new Criteria();
$allProducts = $repository->search($criteria, $context);

// ✅ Good: Use iteration for large datasets
$criteria = new Criteria();
$criteria->setLimit(100);
$offset = 0;

do {
    $criteria->setOffset($offset);
    $result = $repository->search($criteria, $context);
    
    foreach ($result as $product) {
        // Process product
    }
    
    $offset += 100;
} while ($result->count() === 100);
```

### 3. Event Subscriber Performance
```php
// ❌ Bad: Heavy operations in event subscriber
public function onProductWritten(EntityWrittenEvent $event): void
{
    foreach ($event->getWriteResults() as $result) {
        // Heavy API call for each product
        $this->heavyApiCall($result->getPayload());
    }
}

// ✅ Good: Queue heavy operations
public function onProductWritten(EntityWrittenEvent $event): void
{
    foreach ($event->getWriteResults() as $result) {
        $this->messageBus->dispatch(new ProcessProductMessage($result->getPrimaryKey()));
    }
}
```

## Development Tools & Debugging 🔧

### 1. Debugging with Xdebug
Add to your `.env`:
```
XDEBUG_MODE=debug
XDEBUG_START_WITH_REQUEST=yes
```

### 2. Profiling Queries
```php
use Shopware\Core\Profiling\Doctrine\DebugStack;

// Enable SQL logging in development
$debugStack = new DebugStack();
$connection->getConfiguration()->setSQLLogger($debugStack);

// After operations, check queries
foreach ($debugStack->queries as $query) {
    echo $query['sql'] . "\n";
}
```

### 3. Using Shopware's Profiler
Enable the profiler in development:
```yaml
# config/packages/dev/web_profiler.yaml
web_profiler:
    toolbar: true
    intercept_redirects: false
```

## Deployment Checklist ✅

### Before Going Live:
1. **Environment Check**
   ```bash
   # Set production environment
   APP_ENV=prod
   
   # Clear cache
   bin/console cache:clear --env=prod
   
   # Build assets
   ./bin/build-storefront.sh
   ./bin/build-administration.sh
   ```

2. **Performance Optimization**
   ```bash
   # Enable OPcache
   # Configure Redis/Memcached
   # Set up proper database indexes
   ```

3. **Security Verification**
   - Remove development dependencies
   - Check file permissions
   - Validate SSL certificates
   - Review security headers

4. **Monitoring Setup**
   - Configure error logging
   - Set up health checks
   - Monitor performance metrics

Following these best practices will help you build robust, maintainable Shopware plugins and avoid common development pitfalls.