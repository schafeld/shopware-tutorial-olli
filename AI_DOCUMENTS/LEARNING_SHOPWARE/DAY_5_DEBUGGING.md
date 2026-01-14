# Day 5: Debugging and Error Analysis

**Duration:** 1 day (6-8 hours with breaks)  
**Goal:** Master debugging techniques, log analysis, and error handling in Shopware

> **Note for Beginners:** Debugging skills are essential! This day is lighter but very important. You'll use these techniques constantly.

## Learning Objectives

- Use Shopware's logging system effectively
- Debug with Symfony Profiler
- Set up and use Xdebug with your IDE
- Analyze and interpret error messages
- Use debugging commands
- Monitor performance issues
- Handle exceptions properly
- Debug database queries

## Prerequisites

- Completed Days 1-4
- VS Code installed with PHP Debug extension
- Basic understanding of debugging concepts

---

## Part 1: Understanding Shopware Logs (45 minutes)

### Theory: Logging System

Shopware uses **Monolog** (Symfony's logging component) with multiple channels and handlers.

**Log Locations:**
```
var/log/
├── dev.log              # Development environment
├── prod.log             # Production environment
├── dev_deprecated.log   # Deprecation warnings
└── test.log             # Test environment
```

**Log Levels:**
- **DEBUG** - Detailed debug information
- **INFO** - Interesting events (user login, SQL logs)
- **NOTICE** - Normal but significant events
- **WARNING** - Exceptional occurrences that are not errors
- **ERROR** - Runtime errors
- **CRITICAL** - Critical conditions
- **ALERT** - Action must be taken immediately
- **EMERGENCY** - System is unusable

### Official Documentation

📖 **Read these resources:**
- [Symfony Logging](https://symfony.com/doc/current/logging.html)
- [Debugging in Shopware](https://developer.shopware.com/docs/guides/plugins/plugins/testing/)
- [Symfony Profiler](https://symfony.com/doc/current/profiler.html)
- [Xdebug Documentation](https://xdebug.org/docs/)

---

## Part 2: Working with Logs (60 minutes)

### Step 1: Configure Logging in Your Plugin

Update your `MessageService` with better logging:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MessageService
{
    private LoggerInterface $logger;
    private SystemConfigService $systemConfigService;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        LoggerInterface $logger,
        SystemConfigService $systemConfigService,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->logger = $logger;
        $this->systemConfigService = $systemConfigService;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function generateWelcomeMessage(string $name, Context $context): string
    {
        // Debug level - detailed information
        $this->logger->debug('Starting welcome message generation', [
            'name' => $name,
            'context_token' => $context->getToken(),
        ]);

        try {
            $prefix = $this->systemConfigService->get('LearningBundle.config.welcomePrefix') 
                ?? 'Welcome to Shopware Development';
            
            $message = sprintf('%s, %s!', $prefix, $name);

            // Info level - interesting event
            $this->logger->info('Welcome message generated', [
                'name' => $name,
                'message_length' => strlen($message),
            ]);

            return $message;

        } catch (\Exception $e) {
            // Error level - something went wrong
            $this->logger->error('Failed to generate welcome message', [
                'name' => $name,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw or return default
            return 'Welcome!';
        }
    }
}
```

### Step 2: Create Custom Logger

Create `custom/plugins/LearningBundle/src/Service/Logger/CustomLogger.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service\Logger;

use Psr\Log\LoggerInterface;

class CustomLogger
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function logPerformance(string $operation, float $startTime, array $context = []): void
    {
        $duration = microtime(true) - $startTime;
        
        $level = $duration > 1.0 ? 'warning' : 'info';
        
        $this->logger->log($level, sprintf('Performance: %s took %.2fms', $operation, $duration * 1000), array_merge([
            'duration_ms' => $duration * 1000,
            'operation' => $operation,
        ], $context));
    }

    public function logDatabaseQuery(string $query, array $params, float $executionTime): void
    {
        if ($executionTime > 0.1) { // Log slow queries (> 100ms)
            $this->logger->warning('Slow database query detected', [
                'query' => $query,
                'params' => $params,
                'execution_time_ms' => $executionTime * 1000,
            ]);
        }
    }

    public function logUserAction(string $action, ?string $userId, array $data = []): void
    {
        $this->logger->info('User action logged', [
            'action' => $action,
            'user_id' => $userId ?? 'guest',
            'data' => $data,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
    }
}
```

Register in `services.xml`:

```xml
<service id="Learning\Bundle\Service\Logger\CustomLogger">
    <argument type="service" id="logger"/>
</service>
```

### Step 3: Watch Logs in Real-Time

```bash
# Watch all logs
tail -f var/log/dev.log

# Filter specific messages
tail -f var/log/dev.log | grep "Learning"

# Watch with color highlighting (if you have grc installed)
tail -f var/log/dev.log | grep --color=always "Learning\|ERROR\|WARNING"

# Watch multiple log files
tail -f var/log/dev.log var/log/dev_deprecated.log

# Use multitail for multiple logs (install via brew install multitail)
multitail var/log/dev.log var/log/dev_deprecated.log
```

---

## Part 3: Symfony Profiler (60 minutes)

### Step 1: Enable Profiler

The profiler is enabled by default in dev mode. Access it at the bottom toolbar of any Shopware page.

**Key Profiler Sections:**
- **Timeline** - Request/response cycle visualization
- **Performance** - Execution time breakdown
- **Database** - All executed queries
- **Logs** - All log messages for the request
- **Events** - Dispatched events and listeners
- **Cache** - Cache hits/misses

### Step 2: Profile Your API Requests

When developing APIs, you can access the profiler programmatically.

Create `custom/plugins/LearningBundle/src/Core/Api/ProfiledController.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * @Route(defaults={"_routeScope"={"api"}})
 */
class ProfiledController extends AbstractController
{
    private Stopwatch $stopwatch;

    public function __construct(Stopwatch $stopwatch)
    {
        $this->stopwatch = $stopwatch;
    }

    /**
     * @Route(
     *     "/api/_action/learning/profiled-operation",
     *     name="api.action.learning.profiled",
     *     methods={"GET"}
     * )
     */
    public function profiledOperation(Request $request): JsonResponse
    {
        // Start profiling
        $this->stopwatch->start('complex_operation');

        // Simulate complex operation
        $this->stopwatch->start('database_query');
        usleep(50000); // 50ms
        $this->stopwatch->stop('database_query');

        $this->stopwatch->start('external_api_call');
        usleep(100000); // 100ms
        $this->stopwatch->stop('external_api_call');

        $this->stopwatch->start('data_processing');
        usleep(30000); // 30ms
        $this->stopwatch->stop('data_processing');

        // Stop profiling
        $event = $this->stopwatch->stop('complex_operation');

        return new JsonResponse([
            'success' => true,
            'profiling' => [
                'duration_ms' => $event->getDuration(),
                'memory_mb' => $event->getMemory() / 1024 / 1024,
            ],
        ]);
    }
}
```

### Step 3: Debug Database Queries

Enable query logging in `.env.local`:

```env
# Database connection with logging enabled (use your actual port from docker ps)
DATABASE_URL=mysql://root:root@127.0.0.1:54191/shopware?serverVersion=8.0&logging=1
```

**💡 Tip:** Check your actual database port with `docker ps | grep database` and update accordingly.

Create a query analyzer using the modern Doctrine DBAL middleware approach.

Create `custom/plugins/LearningBundle/src/Service/Debug/QueryLogger.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service\Debug;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Psr\Log\LoggerInterface;

class QueryLoggingMiddleware implements Middleware
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function wrap(Driver $driver): Driver
    {
        return new class($driver, $this->logger) extends AbstractDriverMiddleware {
            private LoggerInterface $logger;

            public function __construct(Driver $wrappedDriver, LoggerInterface $logger)
            {
                parent::__construct($wrappedDriver);
                $this->logger = $logger;
            }

            public function connect(array $params): DriverConnection
            {
                return new QueryLoggingConnection(
                    parent::connect($params),
                    $this->logger
                );
            }
        };
    }
}

class QueryLoggingConnection implements DriverConnection
{
    private DriverConnection $connection;
    private LoggerInterface $logger;

    public function __construct(DriverConnection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function prepare(string $sql): Statement
    {
        return new QueryLoggingStatement(
            $this->connection->prepare($sql),
            $this->logger,
            $sql
        );
    }

    public function query(string $sql): Result
    {
        $start = microtime(true);
        $result = $this->connection->query($sql);
        $this->logQuery($sql, [], microtime(true) - $start);
        
        return $result;
    }

    public function exec(string $sql): int
    {
        $start = microtime(true);
        $result = $this->connection->exec($sql);
        $this->logQuery($sql, [], microtime(true) - $start);
        
        return $result;
    }

    public function lastInsertId($name = null): string
    {
        return $this->connection->lastInsertId($name);
    }

    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollBack(): void
    {
        $this->connection->rollBack();
    }

    public function quote($value, $type = 2): string
    {
        return $this->connection->quote($value, $type);
    }

    public function getServerVersion(): string
    {
        return $this->connection->getServerVersion();
    }

    public function getNativeConnection()
    {
        return $this->connection->getNativeConnection();
    }

    private function logQuery(string $sql, array $params, float $executionTime): void
    {
        if ($executionTime > 0.1) { // Log queries taking more than 100ms
            $this->logger->warning('Slow query detected', [
                'query' => $sql,
                'params' => $params,
                'duration_ms' => $executionTime * 1000,
            ]);
        }
    }
}

class QueryLoggingStatement implements Statement
{
    private Statement $statement;
    private LoggerInterface $logger;
    private string $sql;

    public function __construct(Statement $statement, LoggerInterface $logger, string $sql)
    {
        $this->statement = $statement;
        $this->logger = $logger;
        $this->sql = $sql;
    }

    public function bindValue($param, $value, $type = 2): void
    {
        $this->statement->bindValue($param, $value, $type);
    }

    public function bindParam($param, &$variable, $type = 2, $length = null): void
    {
        $this->statement->bindParam($param, $variable, $type, $length);
    }

    public function execute($params = null): Result
    {
        $start = microtime(true);
        $result = $this->statement->execute($params);
        
        $this->logQuery($this->sql, $params ?? [], microtime(true) - $start);
        
        return $result;
    }

    private function logQuery(string $sql, array $params, float $executionTime): void
    {
        if ($executionTime > 0.1) { // Log queries taking more than 100ms
            $this->logger->warning('Slow query detected', [
                'query' => $sql,
                'params' => $params,
                'duration_ms' => $executionTime * 1000,
            ]);
        }
    }
}
```

**Note:** This implementation uses the modern Doctrine DBAL middleware approach instead of the deprecated `SQLLogger` interface. The middleware pattern provides better flexibility and performance. This implementation fully complies with the Doctrine DBAL driver interfaces.

Register the `ProfiledController`, `QueryLoggingMiddleware`, and `DebugTestCommand` in `custom/plugins/LearningBundle/src/Resources/config/services.xml`:

```xml
<!-- ProfiledController -->
<service id="Learning\Bundle\Core\Api\ProfiledController" public="true">
    <argument type="service" id="debug.stopwatch"/>
</service>

<!-- QueryLoggingMiddleware -->
<service id="Learning\Bundle\Service\Debug\QueryLoggingMiddleware">
    <argument type="service" id="logger"/>
</service>

<!-- Debug Test Command -->
<service id="Learning\Bundle\Command\DebugTestCommand">
    <argument type="service" id="Learning\Bundle\Service\ProductViewService"/>
    <tag name="console.command"/>
</service>
```

To register the middleware with Doctrine DBAL, you would typically add it to your database configuration. However, in Shopware, it's recommended to use the built-in profiling tools or register custom middlewares through bundle configuration.

---

## Part 4: Xdebug Setup (90 minutes)

### Step 1: Install Xdebug (macOS)

```bash
# Install via PECL (compile from source)
pecl install xdebug

# Or via Homebrew (RECOMMENDED - easier setup)
# For your current PHP version, use:
brew install shivammathur/extensions/xdebug@8.5  # For PHP 8.5
# or for other versions:
# brew install shivammathur/extensions/xdebug@8.4  # For PHP 8.4
# brew install shivammathur/extensions/xdebug@8.3  # For PHP 8.3
# etc.

# Alternative: Install specific PHP version with Xdebug pre-compiled
# brew install php@8.2-xdebug  # This installs a separate PHP 8.2 + Xdebug
```

### Step 2: Configure Xdebug

Find your `php.ini`:

```bash
php --ini | grep "Loaded Configuration File"
```

Add Xdebug configuration (or create `/usr/local/etc/php/8.2/conf.d/xdebug.ini`):

```ini
[xdebug]
zend_extension="xdebug.so"

; Xdebug 3 configuration
xdebug.mode=debug,develop
xdebug.start_with_request=trigger
xdebug.client_host=localhost
xdebug.client_port=9003

; Logging
xdebug.log=/tmp/xdebug.log
xdebug.log_level=7

; Profiling (optional)
xdebug.output_dir=/tmp/xdebug

; For Docker setups, use this instead:
; xdebug.client_host=host.docker.internal
```

Verify installation:

```bash
php -v | grep Xdebug
# Should show: with Xdebug v3.x.x
```

### Step 3: Configure VS Code

**Install PHP Debug Extension:**
```bash
# Install the PHP Debug extension by Xdebug
# In VS Code: Cmd+Shift+X, search for "PHP Debug" by Xdebug
```

**Create `.vscode/launch.json`:**
```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003
        },
        {
            "name": "Launch currently open script",
            "type": "php",
            "request": "launch",
            "program": "${file}",
            "cwd": "${fileDirname}",
            "port": 0,
            "runtimeArgs": [
                "-dxdebug.start_with_request=yes"
            ],
            "env": {
                "XDEBUG_MODE": "debug,develop",
                "XDEBUG_CONFIG": "client_port=${port}"
            }
        },
        {
            "name": "Launch Built-In web server",
            "type": "php",
            "request": "launch",
            "runtimeArgs": [
                "-dxdebug.mode=debug",
                "-dxdebug.start_with_request=yes",
                "-S",
                "localhost:8000"
            ],
            "program": "",
            "cwd": "${workspaceRoot}",
            "port": 9003,
            "serverReadyAction": {
                "pattern": "Development Server \\(http://localhost:([0-9]+)\\) started",
                "uriFormat": "http://localhost:%s",
                "action": "openExternally"
            }
        }
    ]
}
```

**Path Mapping (if using Docker):**

Add to your launch configuration:
```json
"pathMappings": {
    "/var/www/html": "${workspaceFolder}"
}
```

### Step 4: Set Breakpoints and Debug

Create a test command for debugging.

**Create the file:** `custom/plugins/LearningBundle/src/Command/DebugTestCommand.php`

This command will be available to run as `bin/console learning:debug-test` once the plugin is installed and the cache is cleared.

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Learning\Bundle\Service\ProductViewService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DebugTestCommand extends Command
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
            ->setName('learning:debug-test')
            ->setDescription('Test command for debugging with Xdebug');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $context = Context::createDefaultContext();
        
        // Set breakpoint here
        // Generate a valid UUID for testing
        $productId = Uuid::randomHex();
        
        // Step through this code
        $viewCount = $this->productViewService->getProductViewCount($productId, $context);
        
        $output->writeln("View count for product {$productId}: {$viewCount}");
        
        // Check variables in debugger
        $data = [
            'productId' => $productId,
            'count' => $viewCount,
            'timestamp' => new \DateTime(),
        ];
        
        $output->writeln("Data: " . json_encode($data, JSON_PRETTY_PRINT));
        
        return Command::SUCCESS;
    }
}
```

**Setup the command:**

1. Make sure your command is registered in `services.xml`
2. Clear cache: `bin/console cache:clear`
3. Verify command is available: `bin/console list | grep learning`
4. Test command first: `bin/console learning:debug-test`

**Debug the command:**

1. Set a breakpoint in VS Code by clicking left of the line number
2. Start debugging with F5 or click "Run and Debug" in the sidebar
3. Select "Listen for Xdebug" configuration
4. Run the command in terminal:

```bash
# Set XDEBUG_SESSION environment variable
export XDEBUG_SESSION=vscode

# Run command
php -dxdebug.mode=debug bin/console learning:debug-test

# Or use the trigger
XDEBUG_SESSION=1 bin/console learning:debug-test
```

### 🔧 Common Setup Issues & Fixes

**Database Connection Refused:**
```bash
# Check your database port
docker ps | grep database

# Update .env.local with correct port
DATABASE_URL=mysql://root:root@127.0.0.1:[YOUR_PORT]/shopware?serverVersion=8.0&logging=1
```

**Command "Cannot have empty name":**
- Use `configure()` method instead of `$defaultName` property
- Make sure to call `parent::__construct()` before setting name

**"learning" namespace not found:**
- Register command in `services.xml` with `<tag name="console.command"/>`
- Clear cache: `bin/console cache:clear`

**Invalid UUID errors:**
- Use `Uuid::randomHex()` instead of plain strings
- Import: `use Shopware\Core\Framework\Uuid\Uuid;`

**Debug web requests:**

1. Start debugging in VS Code (F5) and select "Listen for Xdebug"
2. Set breakpoints in your PHP files
3. Add `?XDEBUG_SESSION_START=vscode` to your URL
4. Or use browser extension (Xdebug Helper for Chrome/Firefox)
5. Refresh the page - VS Code will pause at your breakpoints

**VS Code Debugging Tips:**
- F5: Start/Continue debugging
- F10: Step over
- F11: Step into
- Shift+F11: Step out
- Cmd+Shift+F5: Restart debugging
- Shift+F5: Stop debugging
- Hover over variables to see their values
- Use the Debug Console to evaluate expressions

### Step 5: Debug Configuration for Docker

If using Docker, update `compose.yaml`:

```yaml
services:
  app:
    environment:
      PHP_IDE_CONFIG: "serverName=shopware-tutorial"
      XDEBUG_CONFIG: "client_host=host.docker.internal client_port=9003"
      XDEBUG_MODE: "debug,develop"
```

**VS Code Docker Path Mapping:**

In your `.vscode/launch.json`, add path mappings:
```json
{
    "name": "Listen for Xdebug (Docker)",
    "type": "php",
    "request": "launch",
    "port": 9003,
    "pathMappings": {
        "/var/www/html": "${workspaceFolder}"
    }
}
```

---

## Part 5: Error Handling and Analysis (60 minutes)

### Step 1: Create Custom Exceptions

Create `custom/plugins/LearningBundle/src/Exception/ProductViewException.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class ProductViewException extends ShopwareHttpException
{
    public const PRODUCT_NOT_FOUND = 'LEARNING__PRODUCT_NOT_FOUND';
    public const INVALID_VIEW_DATA = 'LEARNING__INVALID_VIEW_DATA';
    public const DATABASE_ERROR = 'LEARNING__DATABASE_ERROR';

    public static function productNotFound(string $productId): self
    {
        return new self(
            'Product with ID "{{ productId }}" not found',
            ['productId' => $productId]
        );
    }

    public static function invalidViewData(string $reason): self
    {
        return new self(
            'Invalid view data: {{ reason }}',
            ['reason' => $reason]
        );
    }

    public static function databaseError(\Throwable $previous): self
    {
        return new self(
            'Database operation failed: {{ message }}',
            ['message' => $previous->getMessage()],
            $previous
        );
    }

    public function getErrorCode(): string
    {
        return $this->parameters['code'] ?? self::DATABASE_ERROR;
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }
}
```

**Note:** Make sure to place this file in the `Exception` directory, not `Service`. The namespace should be `Learning\Bundle\Exception`.

### Step 2: Use Exceptions Properly

Update `custom/plugins/LearningBundle/src/Service/ProductViewService.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Learning\Bundle\Core\Content\ProductView\ProductViewEntity;
use Learning\Bundle\Exception\ProductViewException;  // Correct namespace
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class ProductViewService
{
    // ...existing code...

    public function getProductViewCount(string $productId, Context $context): int
    {
        try {
            // Validate input
            if (empty($productId)) {
                throw ProductViewException::invalidViewData('Product ID cannot be empty');
            }

            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('productId', $productId));

            $views = $this->productViewRepository->search($criteria, $context);
            
            $totalViews = 0;
            /** @var ProductViewEntity $view */
            foreach ($views as $view) {
                $totalViews += $view->getViewCount();
            }

            return $totalViews;

        } catch (ProductViewException $e) {
            // Re-throw our custom exceptions
            throw $e;
        } catch (\Throwable $e) {
            // Wrap unexpected exceptions
            throw ProductViewException::databaseError($e);
        }
    }

    // ...existing code...
}
```

**Important:** Ensure the import statement uses the correct namespace: `use Learning\Bundle\Exception\ProductViewException;`

---

## Part 6: Testing Error Handling

### Step 1: Create Test Error Handling Command

Before testing, create the command that will help test error scenarios.

Create `custom/plugins/LearningBundle/src/Command/TestErrorHandlingCommand.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Learning\Bundle\Exception\ProductViewException;
use Learning\Bundle\Service\ProductViewService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TestErrorHandlingCommand extends Command
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
            ->setName('learning:test-errors')
            ->setDescription('Test error handling and logging')
            ->addArgument('error-type', InputArgument::OPTIONAL, 'Type of error to test', 'all')
            ->addOption('throw', 't', InputOption::VALUE_NONE, 'Actually throw the exception');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $errorType = $input->getArgument('error-type');
        $shouldThrow = $input->getOption('throw');

        $io->title('Testing Error Handling');

        switch ($errorType) {
            case 'product-not-found':
                $this->testProductNotFound($io, $shouldThrow);
                break;
            case 'invalid-data':
                $this->testInvalidData($io, $shouldThrow);
                break;
            case 'database-error':
                $this->testDatabaseError($io, $shouldThrow);
                break;
            case 'all':
                $this->testProductNotFound($io, $shouldThrow);
                $this->testInvalidData($io, $shouldThrow);
                $this->testDatabaseError($io, $shouldThrow);
                break;
            default:
                $io->error("Unknown error type: {$errorType}");
                return Command::FAILURE;
        }

        $io->success('Error handling tests completed');
        $io->note('Check var/log/dev.log for logged errors');

        return Command::SUCCESS;
    }

    private function testProductNotFound(SymfonyStyle $io, bool $shouldThrow): void
    {
        $io->section('Testing: Product Not Found Exception');

        try {
            if ($shouldThrow) {
                throw ProductViewException::productNotFound('non-existent-id');
            } else {
                $io->text('Would throw: ProductViewException::productNotFound()');
            }
        } catch (ProductViewException $e) {
            $io->error("Caught exception: {$e->getMessage()}");
            $io->text("Error Code: {$e->getErrorCode()}");
            $io->text("HTTP Status: {$e->getStatusCode()}");
        }
    }

    private function testInvalidData(SymfonyStyle $io, bool $shouldThrow): void
    {
        $io->section('Testing: Invalid Data Exception');

        try {
            if ($shouldThrow) {
                throw ProductViewException::invalidViewData('Product ID cannot be empty');
            } else {
                $io->text('Would throw: ProductViewException::invalidViewData()');
            }
        } catch (ProductViewException $e) {
            $io->error("Caught exception: {$e->getMessage()}");
        }
    }

    private function testDatabaseError(SymfonyStyle $io, bool $shouldThrow): void
    {
        $io->section('Testing: Database Error Exception');

        try {
            if ($shouldThrow) {
                $previous = new \PDOException('Connection failed');
                throw ProductViewException::databaseError($previous);
            } else {
                $io->text('Would throw: ProductViewException::databaseError()');
            }
        } catch (ProductViewException $e) {
            $io->error("Caught exception: {$e->getMessage()}");
            if ($e->getPrevious()) {
                $io->text("Previous: {$e->getPrevious()->getMessage()}");
            }
        }
    }
}
```

Register the command in `services.xml`:

```xml
<!-- Test Error Handling Command -->
<service id="Learning\Bundle\Command\TestErrorHandlingCommand">
    <argument type="service" id="Learning\Bundle\Service\ProductViewService"/>
    <tag name="console.command"/>
</service>
```

Clear cache and verify:

```bash
bin/console cache:clear
bin/console list | grep learning
```

### Step 2: Available Test Commands

After setup, you'll have these commands available:

```bash
# Test error handling (dry run - doesn't throw)
bin/console learning:test-errors

# Test error handling (actually throws exceptions)
bin/console learning:test-errors --throw

# Test specific error types
bin/console learning:test-errors product-not-found --throw
bin/console learning:test-errors invalid-data --throw
bin/console learning:test-errors database-error --throw

# Test product view functionality
bin/console learning:test-product-view

# Test with Xdebug breakpoints
bin/console learning:debug-test
```

### Step 3: Test Error Logging

Create a web endpoint that triggers errors for testing:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Controller;

use Learning\Bundle\Exception\ProductViewException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/learning/test-errors")
 */
class TestErrorController extends AbstractController
{
    /**
     * @Route("/product-not-found", name="learning.test.product_not_found", methods={"GET"})
     */
    public function testProductNotFound(): JsonResponse
    {
        throw ProductViewException::productNotFound('test-product-id');
    }

    /**
     * @Route("/invalid-data", name="learning.test.invalid_data", methods={"GET"})
     */
    public function testInvalidData(): JsonResponse
    {
        throw ProductViewException::invalidViewData('Test invalid data error');
    }

    /**
     * @Route("/database-error", name="learning.test.database_error", methods={"GET"})
     */
    public function testDatabaseError(): JsonResponse
    {
        $previous = new \PDOException('Test database connection failed');
        throw ProductViewException::databaseError($previous);
    }

    /**
     * @Route("/http-error", name="learning.test.http_error", methods={"GET"})
     */
    public function testHttpError(): JsonResponse
    {
        throw new NotFoundHttpException('Test 404 error');
    }

    /**
     * @Route("/fatal-error", name="learning.test.fatal_error", methods={"GET"})
     */
    public function testFatalError(): JsonResponse
    {
        // This will cause a fatal error
        $array = [];
        $array['key']->nonExistentMethod();
        
        return new JsonResponse(['success' => true]);
    }
}
```

Register services in `services.xml`:

```xml
<!-- Test Error Handling Command -->
<service id="Learning\Bundle\Command\TestErrorHandlingCommand">
    <argument type="service" id="Learning\Bundle\Service\ProductViewService"/>
    <tag name="console.command"/>
</service>

<!-- Test Error Controller -->
<service id="Learning\Bundle\Controller\TestErrorController" public="true"/>
```

#### Testing Instructions

**1. Test via Command Line:**

```bash
# Test all error types (dry run)
bin/console learning:test-errors

# Test specific error type (dry run)
bin/console learning:test-errors product-not-found

# Actually throw exceptions to test logging
bin/console learning:test-errors --throw

# Test specific error with throwing
bin/console learning:test-errors database-error --throw
```

**2. Test via Web Endpoints:**

```bash
# Test product not found error
curl http://localhost:8000/learning/test-errors/product-not-found

# Test invalid data error
curl http://localhost:8000/learning/test-errors/invalid-data

# Test database error
curl http://localhost:8000/learning/test-errors/database-error

# Test HTTP error
curl http://localhost:8000/learning/test-errors/http-error

# Test fatal error
curl http://localhost:8000/learning/test-errors/fatal-error
```

**3. Monitor Logs:**

```bash
# Watch logs in real-time
tail -f var/log/dev.log | grep -E "(Exception occurred|Learning)"

# Check error count
grep -c "Exception occurred" var/log/dev.log

# View recent errors
tail -n 50 var/log/dev.log | grep "Exception occurred"
```

**4. Test Error Reporting Service:**

Add this method to your `DebugTestCommand`:

```php
public function testErrorReporting(ErrorReportingService $errorReportingService): void
{
    $report = $errorReportingService->generateErrorReport();
    
    echo "Error Report:\n";
    echo "Total Errors: " . $report['summary']['total_errors'] . "\n";
    echo "Critical Errors: " . $report['summary']['critical_errors'] . "\n";
    echo "Warnings: " . $report['summary']['warnings'] . "\n";
    echo "\nBy Exception Class:\n";
    foreach ($report['summary']['by_class'] as $class => $count) {
        echo "  {$class}: {$count}\n";
    }
}
```

**5. Verify Error Subscriber:**

```bash
# Check if subscriber is registered
bin/console debug:event-dispatcher kernel.exception

# Should show Learning\Bundle\Subscriber\ErrorLoggingSubscriber
```

### Expected Test Results

After running tests, you should see in `var/log/dev.log`:

```log
[2026-01-14T10:30:00+00:00] app.WARNING: Exception occurred: Product with ID "test-product-id" not found {"exception_class":"Learning\\Bundle\\Exception\\ProductViewException","message":"Product with ID \"test-product-id\" not found","code":0,"file":"/path/to/TestErrorController.php","line":25,"url":"http://localhost:8000/learning/test-errors/product-not-found","method":"GET","user_agent":"curl/7.68.0","ip_address":"127.0.0.1","timestamp":"2026-01-14T10:30:00+00:00"} []

[2026-01-14T10:30:05+00:00] app.ERROR: Exception occurred: Call to a member function nonExistentMethod() on null {"exception_class":"Error","message":"Call to a member function nonExistentMethod() on null","code":0,"file":"/path/to/TestErrorController.php","line":65,"url":"http://localhost:8000/learning/test-errors/fatal-error","method":"GET","user_agent":"curl/7.68.0","ip_address":"127.0.0.1","timestamp":"2026-01-14T10:30:05+00:00","trace":"#0 [internal function]: Learning\\Bundle\\Controller\\TestErrorController->testFatalError()\n..."} []
```

### Troubleshooting

**Issue: Error subscriber not triggered**
```bash
# Check if subscriber is registered
bin/console debug:container Learning\\Bundle\\Subscriber\\ErrorLoggingSubscriber

# Clear cache and reinstall plugin
bin/console cache:clear
bin/console plugin:uninstall LearningBundle
bin/console plugin:install --activate LearningBundle
```

**Issue: Logs not appearing**
```bash
# Check log permissions
ls -la var/log/

# Check if logger service is available
bin/console debug:container logger

# Test logging directly
bin/console debug:log "Test message"
```

**Issue: Web endpoints not accessible**
```bash
# Check routes are registered
bin/console debug:router | grep learning

# Clear routing cache
bin/console router:clear-cache
```

---

## Part 7: Exercises (45 minutes)

### Exercise 1: Performance Profiler

Create a service that profiles slow operations and logs them with stack traces.

### Exercise 2: Error Report Command

Create a command that analyzes log files and generates an error summary (count by type, most common errors, etc.).

### Exercise 3: Health Check Endpoint

Create an API endpoint that checks system health: database connection, cache, required services, etc.

---

## Key Takeaways

✅ **You've learned:**
- Shopware's logging system and log levels
- Using Symfony Profiler for performance analysis
- Setting up and using Xdebug for step debugging
- Creating and handling custom exceptions
- Debugging database queries
- Using debugging commands effectively
- Performance profiling techniques
- Error analysis and monitoring

## Debugging Checklist

When encountering issues:

1. ✅ Check logs: `tail -f var/log/dev.log`
2. ✅ Clear cache: `bin/console cache:clear`
3. ✅ Check database connection: `docker ps | grep database`
4. ✅ Verify correct database port in `.env.local`
5. ✅ Check Symfony Profiler (web requests)
6. ✅ Verify service registration: `bin/console debug:container | grep Learning`
7. ✅ Check database schema: `bin/console dal:validate`
8. ✅ Verify command registration: `bin/console list | grep learning`
9. ✅ Use Xdebug breakpoints for complex issues
10. ✅ Review error messages carefully (especially UUID format errors)
11. ✅ Check Recent changes in git: `git diff`
12. ✅ Test without debugging first to isolate Xdebug issues

---

## Additional Resources

- [Symfony Debugging](https://symfony.com/doc/current/debugging.html)
- [Xdebug Documentation](https://xdebug.org/docs/)
- [VS Code PHP Debugging](https://code.visualstudio.com/docs/languages/php#_debugging)
- [PHP Debug Extension](https://marketplace.visualstudio.com/items?itemName=xdebug.php-debug)
- [Monolog Documentation](https://github.com/Seldaek/monolog/tree/main/doc)
- [PHP Error Handling](https://www.php.net/manual/en/language.exceptions.php)

---

**Estimated Completion Time:** 4-6 hours  
**Difficulty:** Intermediate

🎉 Excellent! Tomorrow we'll cover testing and caching strategies.
