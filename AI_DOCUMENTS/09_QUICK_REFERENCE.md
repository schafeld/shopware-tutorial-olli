# Shopware 6 Quick Reference & Cheat Sheet

## Essential Commands Quick Reference 🚀

### Plugin Management
```bash
bin/console plugin:refresh                    # Discover new plugins
bin/console plugin:list                       # List all plugins
bin/console plugin:install --activate MyPlugin  # Install & activate
bin/console plugin:deactivate MyPlugin       # Deactivate plugin
bin/console plugin:update MyPlugin           # Update plugin
bin/console plugin:uninstall MyPlugin        # Uninstall plugin
bin/console plugin:create MyPlugin           # Create new plugin
```

### Cache & Performance
```bash
bin/console cache:clear                       # Clear all caches
bin/console cache:warmup                      # Warm up cache
bin/console theme:compile                     # Compile themes
bin/console http:cache:warm:up               # Warm up HTTP cache
```

### Database & Migrations
```bash
bin/console database:migrate --all           # Run all migrations
bin/console database:migrate MyPlugin --all  # Run plugin migrations
bin/console database:create-migration -p MyPlugin  # Create migration
```

### Asset Building
```bash
./bin/build-storefront.sh                     # Build storefront assets
./bin/build-administration.sh                 # Build admin assets
./bin/watch-storefront.sh                     # Watch storefront changes
./bin/watch-administration.sh                 # Watch admin changes
```

## Code Snippets Library 📚

### 1. Basic Plugin Structure
```php
<?php declare(strict_types=1);

namespace MyPlugin;

use Shopware\Core\Framework\Plugin;

class MyPlugin extends Plugin
{
    public const PLUGIN_NAME = 'MyPlugin';
}
```

### 2. Service Registration (services.xml)
```xml
<?xml version="1.0" ?>
<container xmlns="http://symfony.com/schema/dic/services"
           xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
           xsi:schemaLocation="http://symfony.com/schema/dic/services 
           http://symfony.com/schema/dic/services/services-1.0.xsd">
    <services>
        <service id="MyPlugin\Service\MyService">
            <argument type="service" id="product.repository"/>
        </service>
        
        <service id="MyPlugin\Subscriber\MySubscriber">
            <tag name="kernel.event_subscriber"/>
        </service>
        
        <service id="MyPlugin\Controller\MyController" public="true">
            <call method="setContainer">
                <argument type="service" id="service_container"/>
            </call>
        </service>
    </services>
</container>
```

### 3. Repository Usage Pattern
```php
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class ProductService
{
    private EntityRepository $productRepository;
    
    public function getActiveProducts(Context $context): EntitySearchResult
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addAssociation('manufacturer');
        $criteria->addSorting(new FieldSorting('name'));
        
        return $this->productRepository->search($criteria, $context);
    }
}
```

### 4. Event Subscriber Template
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
            // Handle product changes
        }
    }
}
```

### 5. Custom Controller Template
```php
<?php declare(strict_types=1);

namespace MyPlugin\Controller;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 */
class MyController extends StorefrontController
{
    /**
     * @Route("/my-page", name="frontend.my.page", methods={"GET"})
     */
    public function myPage(): Response
    {
        return $this->renderStorefront('@MyPlugin/storefront/page/my-page.html.twig');
    }
}
```

### 6. Migration Template
```php
<?php declare(strict_types=1);

namespace MyPlugin\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1640000000CreateMyTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1640000000;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `my_table` (
    `id` BINARY(16) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeUpdate($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // Destructive changes
    }
}
```

## Common Criteria Patterns 🔍

### Basic Filtering
```php
$criteria = new Criteria();

// Simple filters
$criteria->addFilter(new EqualsFilter('active', true));
$criteria->addFilter(new ContainsFilter('name', 'search term'));
$criteria->addFilter(new RangeFilter('stock', [RangeFilter::GTE => 1]));

// Multiple values
$criteria->addFilter(new EqualsAnyFilter('id', [$id1, $id2, $id3]));

// Date ranges
$criteria->addFilter(new RangeFilter('createdAt', [
    RangeFilter::GTE => '2023-01-01 00:00:00',
    RangeFilter::LTE => '2023-12-31 23:59:59'
]));
```

### Advanced Filtering
```php
// OR conditions
$criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
    new EqualsFilter('manufacturerId', $manufacturerId),
    new EqualsFilter('categoryIds', $categoryId)
]));

// NOT conditions
$criteria->addFilter(new NotFilter(NotFilter::CONNECTION_AND, [
    new EqualsFilter('active', false)
]));

// Nested filters
$criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
    new EqualsFilter('active', true),
    new MultiFilter(MultiFilter::CONNECTION_OR, [
        new RangeFilter('stock', [RangeFilter::GT => 0]),
        new EqualsFilter('isCloseout', false)
    ])
]));
```

### Associations & Sorting
```php
// Load associations
$criteria->addAssociation('manufacturer');
$criteria->addAssociation('categories');
$criteria->addAssociation('media');

// Nested associations
$criteria->addAssociation('manufacturer.media');

// Sorting
$criteria->addSorting(new FieldSorting('name', FieldSorting::ASCENDING));
$criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));

// Pagination
$criteria->setOffset(0);
$criteria->setLimit(20);

// Total count
$criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_EXACT);
```

## Template Patterns 📄

### Basic Template Extension
```twig
{% sw_extends '@Storefront/storefront/page/product-detail/index.html.twig' %}

{% block page_product_detail_content %}
    <div class="custom-content">
        <!-- Your custom content -->
    </div>
    {{ parent() }}
{% endblock %}
```

### Component Include
```twig
{% sw_include '@MyPlugin/storefront/component/my-component.html.twig' with {
    'product': product,
    'showPrice': true
} %}
```

### Conditional Rendering
```twig
{% if product.stock > 0 %}
    <span class="in-stock">In Stock</span>
{% elseif product.isCloseout %}
    <span class="limited-stock">Limited Stock</span>
{% else %}
    <span class="out-of-stock">Out of Stock</span>
{% endif %}
```

## JavaScript Plugin Pattern 🔧

### Basic Plugin Structure
```javascript
import Plugin from 'src/plugin-system/plugin.class';

export default class MyPlugin extends Plugin {
    static options = {
        autoplay: true,
        delay: 1000
    };

    init() {
        this._registerEvents();
    }

    _registerEvents() {
        this.el.addEventListener('click', this._onClick.bind(this));
    }

    _onClick(event) {
        event.preventDefault();
        // Handle click
    }

    destroy() {
        // Cleanup
        super.destroy();
    }
}
```

### Plugin Registration
```javascript
// In main.js
import MyPlugin from './plugin/my-plugin.plugin';

const PluginManager = window.PluginManager;
PluginManager.register('MyPlugin', MyPlugin, '[data-my-plugin]');
```

## SCSS Patterns 🎨

### Variable Override Pattern
```scss
// Import Shopware variables first
@import "~@shopware/storefront/src/scss/abstract/variables";

// Override variables
$sw-color-brand-primary: #ff6b6b;
$sw-color-brand-secondary: #4ecdc4;

// Import base styles
@import "~@shopware/storefront/src/scss/base";

// Your custom styles
.my-component {
    color: $sw-color-brand-primary;
    
    &:hover {
        color: $sw-color-brand-secondary;
    }
}
```

### Responsive Mixins
```scss
.my-component {
    padding: 1rem;
    
    @include media-breakpoint-up(md) {
        padding: 2rem;
    }
    
    @include media-breakpoint-up(lg) {
        padding: 3rem;
    }
}
```

## API Patterns 🔌

### Basic API Controller
```php
<?php declare(strict_types=1);

namespace MyPlugin\Controller\Api;

use Shopware\Core\Framework\Api\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 */
class MyApiController extends ApiController
{
    /**
     * @Route("/api/_action/my-plugin/endpoint", methods={"POST"})
     */
    public function myEndpoint(): JsonResponse
    {
        return new JsonResponse(['success' => true]);
    }
}
```

### API Service Usage
```javascript
// In Administration
this.httpClient.post('/api/_action/my-plugin/endpoint', {
    data: 'value'
}).then(response => {
    console.log(response.data);
});
```

## Useful Helpers & Utilities 🛠️

### UUID Generation
```php
use Shopware\Core\Framework\Uuid\Uuid;

$id = Uuid::randomHex();
```

### Price Formatting
```php
use Shopware\Core\Framework\Util\FloatComparator;

if (FloatComparator::equals($price1, $price2)) {
    // Prices are equal
}
```

### Array Helpers
```php
use Shopware\Core\Framework\Struct\ArrayStruct;

$data = new ArrayStruct(['key' => 'value']);
```

## Configuration Patterns ⚙️

### Plugin Configuration (config.xml)
```xml
<?xml version="1.0" encoding="UTF-8"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/shopware/platform/master/src/Core/System/SystemConfig/Schema/config.xsd">
    <card>
        <title>My Plugin Settings</title>
        
        <input-field>
            <name>apiKey</name>
            <label>API Key</label>
        </input-field>
        
        <input-field type="bool">
            <name>enabled</name>
            <label>Enable Feature</label>
            <defaultValue>true</defaultValue>
        </input-field>
        
        <input-field type="int">
            <name>limit</name>
            <label>Item Limit</label>
            <defaultValue>10</defaultValue>
        </input-field>
    </card>
</config>
```

### Reading Configuration
```php
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ConfigService
{
    private SystemConfigService $systemConfig;
    
    public function getApiKey(?string $salesChannelId = null): ?string
    {
        return $this->systemConfig->get('MyPlugin.config.apiKey', $salesChannelId);
    }
}
```

## Debugging Snippets 🐛

### Debug Logging
```php
$this->logger->info('Debug info', [
    'productId' => $productId,
    'context' => $context->getLanguageId()
]);
```

### Template Debugging
```twig
{# Debug variables #}
{{ dump(product) }}

{# Check if variable exists #}
{% if product is defined and product %}
    {{ product.name }}
{% endif %}
```

### SQL Query Debugging
```php
use Shopware\Core\Profiling\Doctrine\DebugStack;

// In development environment
$debugStack = new DebugStack();
$connection->getConfiguration()->setSQLLogger($debugStack);

// Later check executed queries
foreach ($debugStack->queries as $query) {
    echo $query['sql'] . "\n";
}
```

## Testing Patterns 🧪

### Basic Unit Test
```php
<?php declare(strict_types=1);

namespace MyPlugin\Tests\Unit;

use PHPUnit\Framework\TestCase;
use MyPlugin\Service\MyService;

class MyServiceTest extends TestCase
{
    private MyService $service;
    
    protected function setUp(): void
    {
        $this->service = new MyService();
    }
    
    public function testServiceMethod(): void
    {
        $result = $this->service->doSomething();
        $this->assertNotNull($result);
    }
}
```

Keep this cheat sheet handy for quick reference during your Shopware development!