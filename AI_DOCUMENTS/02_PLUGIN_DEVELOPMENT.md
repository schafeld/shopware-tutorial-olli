# Shopware 6 Plugin Development Guide

## Plugin Architecture & Best Practices

### Plugin Structure
Every Shopware plugin follows this structure:
```
custom/plugins/MyPlugin/
├── src/
│   ├── MyPlugin.php                    # Main plugin class
│   ├── Controller/                     # Controllers
│   ├── Service/                        # Business logic services
│   ├── Subscriber/                     # Event subscribers
│   ├── Migration/                      # Database migrations
│   └── Resources/
│       ├── app/
│       │   ├── administration/         # Admin panel extensions
│       │   └── storefront/            # Frontend extensions
│       ├── config/
│       │   ├── services.xml           # Service definitions
│       │   └── config.xml             # Plugin configuration
│       └── views/
│           ├── administration/         # Admin templates
│           └── storefront/            # Storefront templates
└── composer.json                       # Plugin metadata
```

### Creating Your First Plugin

#### 1. Generate Plugin Structure
```bash
# Create a new plugin
bin/console plugin:create MyFirstPlugin

# Navigate to your plugin
cd custom/plugins/MyFirstPlugin
```

#### 2. Main Plugin Class Example
```php
<?php declare(strict_types=1);

namespace MyFirstPlugin;

use Shopware\Core\Framework\Plugin;

class MyFirstPlugin extends Plugin
{
    /**
     * Called when plugin is activated
     */
    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);
        // Your activation logic here
    }

    /**
     * Called when plugin is deactivated
     */
    public function deactivate(DeactivateContext $deactivateContext): void
    {
        parent::deactivate($deactivateContext);
        // Your deactivation logic here
    }

    /**
     * Called when plugin is installed
     */
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
        // Your installation logic here
    }

    /**
     * Called when plugin is uninstalled
     */
    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);
        // Your uninstallation logic here
    }
}
```

#### 3. Service Configuration (services.xml)
```xml
<?xml version="1.0" ?>
<container xmlns="http://symfony.com/schema/dic/services"
           xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
           xsi:schemaLocation="http://symfony.com/schema/dic/services 
           http://symfony.com/schema/dic/services/services-1.0.xsd">

    <services>
        <!-- Controllers -->
        <service id="MyFirstPlugin\Controller\ExampleController" public="true">
            <call method="setContainer">
                <argument type="service" id="service_container" />
            </call>
        </service>

        <!-- Services -->
        <service id="MyFirstPlugin\Service\ExampleService">
            <argument type="service" id="product.repository"/>
        </service>

        <!-- Subscribers -->
        <service id="MyFirstPlugin\Subscriber\ExampleSubscriber">
            <argument type="service" id="MyFirstPlugin\Service\ExampleService"/>
            <tag name="kernel.event_subscriber"/>
        </service>
    </services>
</container>
```

## Common Development Patterns

### 1. Event Subscribers
Use subscribers to hook into Shopware events:

```php
<?php declare(strict_types=1);

namespace MyFirstPlugin\Subscriber;

use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            OrderStateMachineStateChangeEvent::class => 'onOrderStateChange'
        ];
    }

    public function onOrderStateChange(OrderStateMachineStateChangeEvent $event): void
    {
        $order = $event->getOrder();
        $toState = $event->getToPlace()->getTechnicalName();
        
        if ($toState === 'completed') {
            // Do something when order is completed
        }
    }
}
```

### 2. Custom Controllers

#### Storefront Controller
```php
<?php declare(strict_types=1);

namespace MyFirstPlugin\Controller;

use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class MyStorefrontController extends StorefrontController
{
    #[Route(
        path: '/my-custom-page/{id}',
        name: 'frontend.my.custom.page',
        methods: ['GET']
    )]
    public function customPage(string $id): Response
    {
        return $this->renderStorefront('@MyFirstPlugin/storefront/page/custom-page.html.twig', [
            'customId' => $id
        ]);
    }
}
```

#### API Controller
```php
<?php declare(strict_types=1);

namespace MyFirstPlugin\Controller;

use Shopware\Core\Framework\Api\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class MyApiController extends ApiController
{
    #[Route(
        path: '/api/my-endpoint',
        name: 'api.my.endpoint',
        methods: ['GET']
    )]
    public function myEndpoint(): JsonResponse
    {
        return new JsonResponse(['message' => 'Hello from API']);
    }
}
```

### 3. Working with Repositories
```php
<?php declare(strict_types=1);

namespace MyFirstPlugin\Service;

use Shopware\Core\Content\Product\ProductEntity;
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

    public function findProductByName(string $name, Context $context): ?ProductEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $name));
        $criteria->setLimit(1);

        $result = $this->productRepository->search($criteria, $context);
        
        return $result->first();
    }
}
```

## Plugin Development Workflow

### 1. Development Cycle
```bash
# 1. Create plugin
bin/console plugin:create MyPlugin

# 2. Develop your features
# Edit files in custom/plugins/MyPlugin/

# 3. Install and activate
bin/console plugin:refresh
bin/console plugin:install --activate MyPlugin

# 4. Test changes
# Make changes and refresh

# 5. Update plugin (if needed)
bin/console plugin:update MyPlugin

# 6. Clear cache
bin/console cache:clear
```

### 2. Frontend Development
```bash
# Build storefront assets
./bin/build-storefront.sh

# Watch for changes during development
./bin/watch-storefront.sh
```

### 3. Administration Development
```bash
# Build administration assets
./bin/build-administration.sh

# Watch for changes during development
./bin/watch-administration.sh
```

## Best Practices

### ✅ Do:
- Follow PSR-12 coding standards
- Use dependency injection
- Write unit tests for your services
- Use proper namespacing
- Handle exceptions gracefully
- Document your code
- Use Shopware's built-in components

### ❌ Don't:
- Modify core files directly
- Use static calls when DI is available
- Forget to clear cache after changes
- Hardcode configuration values
- Skip error handling
- Create plugins without proper structure

## Testing Your Plugin
```bash
# Run PHPUnit tests
vendor/bin/phpunit custom/plugins/MyPlugin/tests

# Run code quality checks
vendor/bin/phpstan analyse custom/plugins/MyPlugin/src

# Validate plugin structure
bin/console plugin:validate MyPlugin
```

## Common Issues & Solutions

### Plugin Not Loading
- Check composer.json structure
- Verify plugin is in correct directory
- Run `plugin:refresh` command
- Clear cache

### Services Not Injected
- Check services.xml configuration
- Verify service IDs match class names
- Ensure proper container setup

### Templates Not Found
- Check template paths in controllers
- Verify template files exist
- Check template inheritance

## Next Steps
- Learn about custom entities (`05_CUSTOM_ENTITIES.md`)
- Explore administration customization (`06_ADMIN_CUSTOMIZATION.md`)
- Study storefront theming (`07_STOREFRONT_THEMING.md`)