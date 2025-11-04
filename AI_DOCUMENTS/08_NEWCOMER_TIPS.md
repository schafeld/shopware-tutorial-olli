# Shopware 6 Newcomer Tips & Common Gotchas

## Essential Developer Mindset 🧠

### 1. Think in Events & Services
Shopware 6 is heavily event-driven. Almost everything happens through:
- **Events**: Product updated, order placed, customer registered
- **Services**: Business logic encapsulation
- **Repositories**: Data access layer
- **Decorators**: Extending existing functionality

### 2. Embrace the Data Abstraction Layer (DAL)
- Never write raw SQL unless absolutely necessary
- Use Criteria builders for complex queries
- Leverage associations to avoid N+1 queries
- Understand the difference between Entities and Definitions

## Common Gotchas & How to Avoid Them ⚠️

### 1. Cache-Related Issues

#### Problem: Changes Not Visible
```bash
# ❌ You made changes but nothing happens
# ✅ Clear cache frequently during development
bin/console cache:clear
```

#### Problem: Template Changes Not Reflecting
```bash
# ❌ Template changes not showing up
# ✅ Clear template cache specifically
bin/console theme:compile
```

#### Pro Tip: Development Environment
```bash
# Edit your .env file
APP_ENV=dev  # Instead of 'prod'
```

### 2. Plugin Installation Issues

#### Problem: Plugin Not Found
```bash
# ❌ Plugin exists but not detected
bin/console plugin:refresh

# ❌ Still not working? Check composer.json structure
# ✅ Ensure proper plugin structure:
custom/plugins/MyPlugin/
├── composer.json           # Must exist!
└── src/
    └── MyPlugin.php       # Main plugin class
```

#### Problem: Services Not Loading
```xml
<!-- ❌ Common mistake: Wrong service configuration -->
<service id="MyPlugin\Service\MyService" />

<!-- ✅ Correct: Include arguments and public flag if needed -->
<service id="MyPlugin\Service\MyService" public="true">
    <argument type="service" id="product.repository"/>
</service>
```

### 3. Context Issues

#### Problem: Missing Context
```php
// ❌ Bad: Creating new context everywhere
$context = Context::createDefaultContext();

// ✅ Good: Always pass context from controller/service
public function myMethod(Context $context): void
{
    // Use the provided context
    $products = $this->productRepository->search($criteria, $context);
}
```

#### Problem: Wrong Sales Channel Context
```php
// ❌ Using wrong context for storefront operations
$context = Context::createDefaultContext();

// ✅ Use SalesChannelContext for storefront
public function storefrontAction(SalesChannelContext $context): void
{
    // Correct context with sales channel data
}
```

### 4. Association Loading Problems

#### Problem: N+1 Query Issues
```php
// ❌ Bad: Loading associations in loop
foreach ($products as $product) {
    $manufacturer = $this->manufacturerRepository->search(
        new Criteria([$product->getManufacturerId()]), 
        $context
    )->first();
}

// ✅ Good: Load associations upfront
$criteria = new Criteria();
$criteria->addAssociation('manufacturer');
$products = $this->productRepository->search($criteria, $context);
```

#### Problem: Deep Association Loading
```php
// ❌ Loading unnecessary deep associations
$criteria->addAssociation('manufacturer.media.thumbnails.formats');

// ✅ Only load what you need
$criteria->addAssociation('manufacturer');
```

### 5. Migration Issues

#### Problem: Migration Not Running
```bash
# ❌ Migration exists but doesn't execute
# ✅ Check filename format and run command
bin/console database:migrate MyPlugin --all

# ✅ Filename must be: Migration{TIMESTAMP}.php
# Example: Migration1635360000CreateMyTable.php
```

#### Problem: Duplicate Migration
```php
// ❌ Running same migration twice
public function update(Connection $connection): void
{
    // Always check if table/column exists first
    if (!$this->columnExists($connection, 'my_table', 'my_column')) {
        $connection->executeUpdate('ALTER TABLE my_table ADD COLUMN my_column VARCHAR(255)');
    }
}
```

## Performance Gotchas 🚀

### 1. Repository Usage
```php
// ❌ Bad: Multiple individual queries
foreach ($productIds as $id) {
    $product = $repository->search(new Criteria([$id]), $context)->first();
}

// ✅ Good: Single batch query
$criteria = new Criteria($productIds);
$products = $repository->search($criteria, $context);
```

### 2. Event Subscriber Performance
```php
// ❌ Bad: Heavy operations in event subscriber
public function onProductWritten(EntityWrittenEvent $event): void
{
    foreach ($event->getWriteResults() as $result) {
        $this->sendEmailNotification($result); // Blocks execution
    }
}

// ✅ Good: Use message queue for heavy operations
public function onProductWritten(EntityWrittenEvent $event): void
{
    foreach ($event->getWriteResults() as $result) {
        $this->messageBus->dispatch(new SendEmailMessage($result->getPrimaryKey()));
    }
}
```

### 3. Template Performance
```twig
{# ❌ Bad: Database queries in templates #}
{% for product in products %}
    {% set manufacturer = product.manufacturerId|manufacturer_by_id %}
{% endfor %}

{# ✅ Good: Load data in controller/service #}
{% for product in products %}
    {{ product.manufacturer.name }}
{% endfor %}
```

## Frontend Development Tips 🎨

### 1. SCSS Development
```bash
# Start watch mode for instant compilation
./bin/watch-storefront.sh

# Problem: Changes not reflecting?
# Clear theme cache
bin/console theme:compile
```

### 2. JavaScript Plugin Pattern
```javascript
// ✅ Always use Shopware's plugin system
export default class MyPlugin extends Plugin {
    init() {
        this._registerEvents();
    }
    
    _registerEvents() {
        // Event registration
    }
    
    // Always clean up
    destroy() {
        // Cleanup logic
    }
}
```

### 3. Template Extension Strategy
```twig
{# ✅ Good: Extend existing templates #}
{% sw_extends '@Storefront/storefront/page/product-detail/index.html.twig' %}

{% block page_product_detail_content %}
    <div class="my-custom-content">
        <!-- Custom content -->
    </div>
    
    {# Keep original content #}
    {{ parent() }}
{% endblock %}
```

## API Development Gotchas 🔌

### 1. Route Scope Issues
```php
// ❌ Wrong route scope
/**
 * @Route("/api/my-endpoint", methods={"GET"})
 */

// ✅ Correct route scope
/**
 * @Route("/api/my-endpoint", methods={"GET"})
 * @RouteScope(scopes={"api"})
 */
```

### 2. Request Validation
```php
// ❌ No input validation
public function createProduct(Request $request): JsonResponse
{
    $data = $request->request->all();
    // Direct usage without validation
}

// ✅ Always validate input
public function createProduct(Request $request): JsonResponse
{
    $data = $request->request->all();
    
    // Validate required fields
    if (empty($data['name'])) {
        return new JsonResponse(['error' => 'Name is required'], 400);
    }
}
```

## Database & Migration Tips 💾

### 1. Safe Migration Patterns
```php
public function update(Connection $connection): void
{
    // ✅ Always check before altering
    if (!$this->columnExists($connection, 'product', 'my_field')) {
        $sql = 'ALTER TABLE `product` ADD COLUMN `my_field` VARCHAR(255) NULL';
        $connection->executeUpdate($sql);
    }
}

private function columnExists(Connection $connection, string $table, string $column): bool
{
    $sql = 'SHOW COLUMNS FROM `' . $table . '` LIKE :column';
    $result = $connection->executeQuery($sql, ['column' => $column]);
    
    return $result->rowCount() > 0;
}
```

### 2. Custom Entity Creation
```php
// ✅ Complete entity setup checklist:
// 1. Entity class
// 2. Definition class  
// 3. Collection class
// 4. Migration for table creation
// 5. Service registration in services.xml
```

## Debugging & Development Tools 🔧

### 1. Enable Debug Mode
```bash
# Edit .env
APP_ENV=dev
APP_DEBUG=1

# Install dev dependencies
composer install
```

### 2. Useful Debug Commands
```bash
# Check service container
bin/console debug:container my_service

# List all routes
bin/console debug:router

# Check events
bin/console debug:event-dispatcher

# Validate services
bin/console lint:container
```

### 3. Log Everything During Development
```php
use Psr\Log\LoggerInterface;

class MyService
{
    private LoggerInterface $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    public function doSomething(): void
    {
        $this->logger->info('Starting operation', ['context' => 'debug']);
        
        try {
            // Your logic
            $this->logger->info('Operation completed');
        } catch (\Exception $e) {
            $this->logger->error('Operation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
```

## Quick Wins & Productivity Hacks ⚡

### 1. Bash Aliases
```bash
# Add to your ~/.zshrc or ~/.bashrc
alias sw-clear='bin/console cache:clear'
alias sw-plugins='bin/console plugin:list'
alias sw-refresh='bin/console plugin:refresh'
alias sw-build='./bin/build-storefront.sh && ./bin/build-administration.sh'
alias sw-watch-fe='./bin/watch-storefront.sh'
alias sw-watch-admin='./bin/watch-administration.sh'
```

### 2. VS Code Extensions
- **PHP Intelephense**: Best PHP language server
- **Twig Language 2**: Twig syntax support
- **Vetur**: Vue.js support for admin development
- **SCSS IntelliSense**: SCSS autocomplete

### 3. Development Workflow
```bash
# 1. Start development
sw-clear
sw-watch-fe      # Terminal 1
sw-watch-admin   # Terminal 2 (if needed)

# 2. After code changes
sw-clear         # Clear cache
sw-plugins       # Check plugin status

# 3. Before committing
sw-build         # Build production assets
```

## Environment-Specific Tips 🌍

### 1. Local Development Setup
```yaml
# docker-compose.override.yml for local development
version: '3.8'
services:
  app:
    environment:
      APP_ENV: dev
      XDEBUG_MODE: debug
    volumes:
      - .:/var/www/html
```

### 2. Production Checklist
```bash
# Before deployment
APP_ENV=prod
composer install --no-dev --optimize-autoloader
bin/console cache:clear --env=prod
./bin/build-storefront.sh
./bin/build-administration.sh
```

## Testing Your Code 🧪

### 1. Quick Manual Tests
```bash
# Test plugin installation cycle
bin/console plugin:uninstall MyPlugin
bin/console plugin:refresh
bin/console plugin:install --activate MyPlugin
bin/console cache:clear
```

### 2. API Testing with cURL
```bash
# Test your custom API endpoints
curl -X GET "http://localhost:8000/api/my-endpoint" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

## When Things Go Wrong 🆘

### 1. Emergency Commands
```bash
# Nuclear option: Complete reset
rm -rf var/cache/*
bin/console cache:clear
bin/console theme:compile

# Plugin issues
bin/console plugin:refresh
bin/console plugin:uninstall --keep-user-data=false ProblematicPlugin

# Database issues (CAREFUL!)
bin/console database:migrate --all
```

### 2. Common Error Messages & Solutions

#### "Service not found"
- Check services.xml configuration
- Verify service ID matches class name
- Clear cache

#### "Template not found"
- Check template path spelling
- Verify template inheritance
- Clear theme cache

#### "Column not found" 
- Run migrations: `bin/console database:migrate --all`
- Check migration file naming
- Verify database connection

Remember: Shopware development has a learning curve, but following these patterns and avoiding common pitfalls will make you productive faster. Don't hesitate to experiment in your development environment!