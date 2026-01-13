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
# Show all database queries in profiler
DATABASE_URL=mysql://user:pass@localhost:3306/shopware?serverVersion=8.0
# Add logging parameter
DATABASE_URL=mysql://user:pass@localhost:3306/shopware?serverVersion=8.0&logging=1
```

Create a query analyzer.

Create `custom/plugins/LearningBundle/src/Service/Debug/QueryLogger.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service\Debug;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Logging\SQLLogger;
use Psr\Log\LoggerInterface;

class QueryLogger implements SQLLogger
{
    private LoggerInterface $logger;
    private ?float $start = null;
    private ?string $currentQuery = null;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function startQuery($sql, ?array $params = null, ?array $types = null): void
    {
        $this->start = microtime(true);
        $this->currentQuery = $sql;
    }

    public function stopQuery(): void
    {
        $duration = microtime(true) - $this->start;
        
        if ($duration > 0.1) { // Log queries taking more than 100ms
            $this->logger->warning('Slow query detected', [
                'query' => $this->currentQuery,
                'duration_ms' => $duration * 1000,
            ]);
        }

        $this->start = null;
        $this->currentQuery = null;
    }
}
```

Register the `ProfiledController` and `QueryLogger` in `custom/plugins/LearningBundle/src/Resources/config/services.xml`:

```xml
<!-- ProfiledController -->
<service id="Learning\Bundle\Core\Api\ProfiledController" public="true">
    <argument type="service" id="debug.stopwatch"/>
</service>

<!-- QueryLogger -->
<service id="Learning\Bundle\Service\Debug\QueryLogger">
    <argument type="service" id="logger"/>
</service>
```

---

## Part 4: Xdebug Setup (90 minutes)

### Step 1: Install Xdebug (macOS)

```bash
# Install via PECL
pecl install xdebug

# Or via Homebrew (if using Homebrew PHP)
brew install php@8.2-xdebug  # Adjust version as needed
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

Create a test command for debugging:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Learning\Bundle\Service\ProductViewService;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DebugTestCommand extends Command
{
    protected static $defaultName = 'learning:debug-test';

    private ProductViewService $productViewService;

    public function __construct(ProductViewService $productViewService)
    {
        parent::__construct();
        $this->productViewService = $productViewService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $context = Context::createDefaultContext();
        
        // Set breakpoint here
        $productId = 'test-product-id';
        
        // Step through this code
        $viewCount = $this->productViewService->getProductViewCount($productId, $context);
        
        $output->writeln("View count: {$viewCount}");
        
        // Check variables in debugger
        $data = [
            'count' => $viewCount,
            'timestamp' => new \DateTime(),
        ];
        
        return Command::SUCCESS;
    }
}
```

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

### Step 2: Use Exceptions Properly

Update `ProductViewService.php`:

```php
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
```

### Step 3: Error Subscriber

Create error logging subscriber:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Subscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ErrorLoggingSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onException', 100],
        ];
    }

    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        
        // Log with full context
        $this->logger->error('Exception occurred', [
            'exception_class' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'url' => $event->getRequest()->getUri(),
            'method' => $event->getRequest()->getMethod(),
        ]);
    }
}
```

---

## Part 6: Debugging Commands (30 minutes)

### Useful Shopware Debugging Commands

```bash
# Check container services
bin/console debug:container | grep Learning

# Check event listeners
bin/console debug:event-dispatcher

# Check routes
bin/console debug:router | grep learning

# Check configuration
bin/console debug:config

# Dump database schema
bin/console dbal:run-sql "SHOW CREATE TABLE learning_product_view"

# Check entity definitions
bin/console debug:container --tag=shopware.entity.definition

# Clear specific cache
bin/console cache:pool:clear cache.object

# Validate database schema
bin/console dal:validate

# Check plugin status
bin/console plugin:list

# Check system requirements
bin/console system:check
```

### Create Debug Command

Create `custom/plugins/LearningBundle/src/Command/DebugInfoCommand.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DebugInfoCommand extends Command
{
    protected static $defaultName = 'learning:debug-info';

    protected function configure(): void
    {
        $this->setDescription('Show debug information for Learning Bundle');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Learning Bundle Debug Information');

        // System info
        $io->section('System');
        $io->table(
            ['Key', 'Value'],
            [
                ['PHP Version', PHP_VERSION],
                ['Xdebug', extension_loaded('xdebug') ? '✓ Enabled' : '✗ Disabled'],
                ['Environment', $_ENV['APP_ENV'] ?? 'unknown'],
                ['Debug Mode', $_ENV['APP_DEBUG'] ?? 'unknown'],
            ]
        );

        // Plugin info
        $io->section('Plugin Configuration');
        $io->listing([
            'Tables: learning_product_view',
            'Services: ProductViewService, ProductViewAnalyticsService',
            'Events: CustomerSubscriber, ProductSubscriber, OrderSubscriber',
        ]);

        // Check log files
        $io->section('Log Files');
        $logDir = dirname(__DIR__, 5) . '/var/log';
        $logFiles = glob($logDir . '/*.log');
        
        foreach ($logFiles as $logFile) {
            $size = filesize($logFile);
            $io->text(sprintf('%s (%s)', basename($logFile), $this->formatBytes($size)));
        }

        $io->success('Debug information displayed');

        return Command::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
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
3. ✅ Check Symfony Profiler (web requests)
4. ✅ Verify service registration: `bin/console debug:container`
5. ✅ Check database: `bin/console dal:validate`
6. ✅ Use Xdebug breakpoints for complex issues
7. ✅ Review error messages carefully
8. ✅ Check Recent changes in git: `git diff`

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
