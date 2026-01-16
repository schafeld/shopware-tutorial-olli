# AI Assistant Coding Guide for Shopware 6 Development

> **Purpose:** This document defines coding standards, best practices, and preferred solutions that AI assistants (like GitHub Copilot, ChatGPT, etc.) should follow when generating code for this Shopware 6 project.

---

## Table of Contents
- [Development Environment](#development-environment)
- [General Principles](#general-principles)
- [Shopware-Specific Rules](#shopware-specific-rules)
- [PHP Standards](#php-standards)
- [Service & Dependency Injection](#service--dependency-injection)
- [Commands](#commands)
- [Event Handling](#event-handling)
- [Database & Migrations](#database--migrations)
- [Frontend/Twig](#fronendtwig)
- [Testing](#testing)

---

## Development Environment

### Docker Configuration

This project uses Docker Compose for local development. **Critical:** The Docker container ports are **dynamically assigned** and change when containers are restarted.

#### Database Connection

- **Configuration file:** `.env.local` (not `.env`)
- **Database port:** Check with `docker ps` - the MySQL container maps to a random host port
- **Connection string format:** `mysql://root:root@127.0.0.1:PORT/shopware?serverVersion=8.0`

#### When "Connection refused" errors occur:

1. **Don't assume containers are stopped** - they are likely running with a different port
2. **Check current port:** `docker ps --format "table {{.Names}}\t{{.Ports}}"`
3. **Update `.env.local`** with the correct port from the `0.0.0.0:XXXXX->3306/tcp` mapping
4. **Clear cache** after updating: `bin/console cache:clear`

#### Example:
```bash
# Check running containers and ports
docker ps --format "table {{.Names}}\t{{.Ports}}"

# Output shows:
# shopware-tutorial-olli-database-1   0.0.0.0:50170->3306/tcp
#                                            ^^^^^
#                                     This is the host port to use

# Update .env.local:
DATABASE_URL=mysql://root:root@127.0.0.1:50170/shopware?serverVersion=8.0
```

---

## General Principles

1. **Always use strict types**: Every PHP file must start with `<?php declare(strict_types=1);`
2. **Type everything**: Use type hints for all parameters and return types
3. **Follow PSR-12**: Adhere to PSR-12 coding standards
4. **Document intentionally**: Add PHPDoc blocks for complex methods, but avoid obvious comments
5. **Fail fast**: Use early returns and guard clauses
6. **Log appropriately**: Use the logger service for debugging and error tracking

---

## Shopware-Specific Rules

### Commands

#### ✅ Correct: Define command name in `configure()` method

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MyCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('my-plugin:my-command')
            ->setDescription('Description of what the command does')
            ->addOption('option-name', 'o', InputOption::VALUE_OPTIONAL, 'Option description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Command logic here
        return Command::SUCCESS;
    }
}
```

#### ❌ Incorrect: Using deprecated static property

```php
// DON'T DO THIS - This is deprecated
protected static $defaultName = 'my-plugin:my-command';
```

**Rationale:** The `$defaultName` static property is deprecated in Symfony/Shopware. Always use `setName()` in the `configure()` method instead.

---

### Service Registration

#### ✅ Correct: Proper service definition in `services.xml`

```xml
<service id="MyPlugin\Service\MyService">
    <argument type="service" id="logger"/>
    <argument type="service" id="product.repository"/>
    <argument type="string">%kernel.project_dir%</argument>
</service>

<service id="MyPlugin\Command\MyCommand">
    <argument type="service" id="MyPlugin\Service\MyService"/>
    <tag name="console.command"/>
</service>
```

**Key Points:**
- Always specify full class names with namespace
- Use `type="service"` for injected services
- Use `type="string"` for scalar values
- Add appropriate tags (`console.command`, `kernel.event_subscriber`, etc.)

---

### Repository Usage

#### ✅ Correct: Inject specific repository interface

```php
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

class MyService
{
    private EntityRepository $productRepository;

    public function __construct(EntityRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }
}
```

**services.xml:**
```xml
<service id="MyPlugin\Service\MyService">
    <argument type="service" id="product.repository"/>
</service>
```

#### ❌ Incorrect: Using RepositoryInterface or wrong type

```php
// Don't use generic interface - use EntityRepository
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
```

---

## PHP Standards

### File Structure

```php
<?php declare(strict_types=1);

namespace MyPlugin\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;

/**
 * Brief class description if needed
 */
class MyService
{
    private LoggerInterface $logger;
    private string $projectDir;

    public function __construct(
        LoggerInterface $logger,
        string $projectDir
    ) {
        $this->logger = $logger;
        $this->projectDir = $projectDir;
    }

    public function doSomething(string $input): array
    {
        // Implementation
    }

    private function helperMethod(): void
    {
        // Implementation
    }
}
```

**Key Points:**
- Constructor first
- Public methods before private
- Properties before methods
- Group related methods together

---

### Error Handling

#### ✅ Correct: Try-catch with logging

```php
public function processOrder(string $orderId): bool
{
    try {
        // Processing logic
        
        $this->logger->info('Order processed', ['order_id' => $orderId]);
        return true;
        
    } catch (\Throwable $e) {
        $this->logger->error('Order processing failed', [
            'order_id' => $orderId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        
        return false;
    }
}
```

**Key Points:**
- Catch `\Throwable` not `\Exception` (catches both exceptions and errors)
- Always log errors with context
- Return meaningful values or re-throw if appropriate

---

## Service & Dependency Injection

### Service Decoration

#### ✅ Correct: Proper decoration chain

```php
<?php declare(strict_types=1);

namespace MyPlugin\Service\Decorator;

use MyPlugin\Service\MyServiceInterface;

class MyDecorator implements MyServiceInterface
{
    private MyServiceInterface $decoratedService;

    public function __construct(MyServiceInterface $decoratedService)
    {
        $this->decoratedService = $decoratedService;
    }

    public function doSomething(): string
    {
        $result = $this->decoratedService->doSomething();
        
        // Add decoration logic
        return $result . ' - decorated';
    }
}
```

**services.xml:**
```xml
<service id="MyPlugin\Service\Decorator\MyDecorator"
         decorates="MyPlugin\Service\MyService"
         decoration-priority="100">
    <argument type="service" id=".inner"/>
</service>
```

**Key Points:**
- Higher priority number = executed first
- Always inject via interface
- Use `.inner` to reference decorated service

---

## Event Handling

### Event Subscribers

#### ✅ Correct: Proper subscriber with priorities

```php
<?php declare(strict_types=1);

namespace MyPlugin\Subscriber;

use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;

class ProductSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => [
                ['onProductPageLoaded', 100], // Higher priority
                ['enrichProductData', 50],    // Lower priority
            ],
        ];
    }

    public function onProductPageLoaded(ProductPageLoadedEvent $event): void
    {
        $product = $event->getPage()->getProduct();
        
        $this->logger->info('Product page loaded', [
            'product_id' => $product->getId(),
            'product_name' => $product->getName(),
        ]);
    }

    public function enrichProductData(ProductPageLoadedEvent $event): void
    {
        // Additional logic
    }
}
```

**services.xml:**
```xml
<service id="MyPlugin\Subscriber\ProductSubscriber">
    <argument type="service" id="logger"/>
    <tag name="kernel.event_subscriber"/>
</service>
```

---

### Custom Events

#### ✅ Correct: Proper custom event implementation

```php
<?php declare(strict_types=1);

namespace MyPlugin\Event;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\ShopwareEvent;
use Symfony\Contracts\EventDispatcher\Event;

class CustomEvent extends Event implements ShopwareEvent
{
    private string $entityId;
    private Context $context;
    private array $metadata;

    public function __construct(
        string $entityId,
        Context $context,
        array $metadata = []
    ) {
        $this->entityId = $entityId;
        $this->context = $context;
        $this->metadata = $metadata;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }
}
```

**Key Points:**
- Extend `Event` and implement `ShopwareEvent`
- Provide getters for all important data
- Include Context if the event relates to data operations
- Make events immutable where possible (except for metadata enrichment)

---

## Database & Migrations

### Entity Definitions

#### ✅ Correct: Proper entity definition

```php
<?php declare(strict_types=1);

namespace MyPlugin\Core\Content\MyEntity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;

class MyEntityDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'my_entity';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('name', 'name'))->addFlags(new Required()),
        ]);
    }
}
```

---

### Migrations

#### ✅ Correct: Proper migration structure

```php
<?php declare(strict_types=1);

namespace MyPlugin\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1700000001CreateMyEntityTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1700000001;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `my_entity` (
    `id` BINARY(16) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // Only include if needed for destructive operations
    }
}
```

**Key Points:**
- Use `BINARY(16)` for IDs
- Use `DATETIME(3)` for timestamps (millisecond precision)
- Use `utf8mb4_unicode_ci` collation
- Add appropriate indexes
- Use `IF NOT EXISTS` for safety

---

## Frontend/Twig

### Template Extension

#### ✅ Correct: Proper template extension

```twig
{% sw_extends '@Storefront/storefront/page/product-detail/index.html.twig' %}

{% block page_product_detail_content %}
    {{ parent() }}
    
    <div class="my-custom-content">
        {# Custom content #}
    </div>
{% endblock %}
```

**Key Points:**
- Use `{% sw_extends %}` not `{% extends %}`
- Always call `{{ parent() }}` to preserve original content
- Use descriptive block names from Shopware core
- Keep custom classes prefixed with plugin name

---

## Testing

### Unit Tests

#### ✅ Correct: Proper test structure

```php
<?php declare(strict_types=1);

namespace MyPlugin\Tests\Unit\Service;

use MyPlugin\Service\MyService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MyServiceTest extends TestCase
{
    public function testDoSomething(): void
    {
        // Arrange
        $logger = $this->createMock(LoggerInterface::class);
        $service = new MyService($logger);
        
        // Act
        $result = $service->doSomething('input');
        
        // Assert
        static::assertIsArray($result);
        static::assertNotEmpty($result);
    }
}
```

**Key Points:**
- Use `TestCase` from PHPUnit
- Follow Arrange-Act-Assert pattern
- Use `static::assert*()` methods
- Mock dependencies appropriately
- Test one thing per test method

---

## Common Pitfalls to Avoid

### ❌ Don't: Use deprecated patterns

```php
// Deprecated command name definition
protected static $defaultName = 'my:command';

// Deprecated repository interface
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

// Missing strict types
<?php
namespace MyPlugin;
```

### ❌ Don't: Skip error handling

```php
// No try-catch, no logging
public function riskyOperation(): void
{
    $this->doSomethingDangerous();
}
```

### ❌ Don't: Use unclear variable names

```php
// Bad naming
$d = $this->getData();
$arr = [];
```

---

## Quick Reference Checklist

When generating code, always verify:

- [ ] `declare(strict_types=1)` at the top of every PHP file
- [ ] All parameters and return types are type-hinted
- [ ] Commands use `setName()` in `configure()`, not `$defaultName`
- [ ] Services are properly registered in `services.xml`
- [ ] Error handling includes try-catch with logging
- [ ] Event subscribers are tagged with `kernel.event_subscriber`
- [ ] Migrations use proper SQL with `BINARY(16)` for IDs
- [ ] Twig templates use `{% sw_extends %}` and `{{ parent() }}`
- [ ] Tests follow Arrange-Act-Assert pattern
- [ ] Code follows PSR-12 standards

---

## Version Info

- **Shopware Version:** 6.5+
- **PHP Version:** 8.1+
- **Standards:** PSR-4, PSR-12, PSR-3

---

## Updates

This guide should be updated whenever:
- New patterns are established in the project
- Shopware releases breaking changes
- Team discovers better practices
- Common mistakes are identified

**Last Updated:** 2024-11-27
