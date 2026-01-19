# Day 6: Testing and Caching

**Duration:** 5-7 hours  
**Goal:** Master automated testing and caching strategies in Shopware

## Learning Objectives

- Write unit tests for services
- Create integration tests for plugins
- Test API endpoints
- Understand Shopware's caching system
- Implement cache invalidation strategies
- Use cache tags effectively
- Optimize performance with caching
- Test cache behavior

## Prerequisites

- Completed Days 1-5
- Understanding of PHPUnit
- Familiarity with testing concepts

---

## Part 1: Understanding Shopware Testing (45 minutes)

### Theory: Test Types

**1. Unit Tests**
- Test individual classes/methods in isolation
- Fast execution, no database
- Use mocks for dependencies

**2. Integration Tests**
- Test multiple components together
- Use real database (test environment)
- More realistic, slower execution

**3. API Tests**
- Test HTTP endpoints
- Full request/response cycle
- Authentication and authorization

**Test Structure:**
```
tests/
├── unit/              # Unit tests
├── integration/       # Integration tests
└── TestBootstrap.php  # Test setup
```

### Official Documentation

📖 **Read these resources:**
- [Plugin Testing](https://developer.shopware.com/docs/guides/plugins/plugins/testing/)
- [Testing Guide](https://developer.shopware.com/docs/guides/plugins/plugins/testing/jest-admin)
- [HTTP Cache](https://developer.shopware.com/docs/guides/hosting/performance/caches)
- [Cache Invalidation](https://developer.shopware.com/docs/guides/plugins/plugins/framework/store-api/cache-invalidation)

---

## Part 2: Unit Tests (90 minutes)

### Step 1: Verify PHPUnit Installation

**What is PHPUnit?**  
PHPUnit is a testing framework for PHP that allows you to write automated tests for your code. Think of it like writing a checklist that automatically verifies your code works correctly - instead of manually testing everything in the browser, you write code that tests your code!

**Check if PHPUnit is installed:**

```bash
# Navigate to your Shopware root directory
cd /path/to/shopware-tutorial-olli

# Check if PHPUnit exists
ls -la vendor/bin/phpunit
```

**Expected outcome:**
- ✅ You should see a file listed: `vendor/bin/phpunit`
- ❌ If you see "No such file or directory", PHPUnit is not installed

**If PHPUnit is missing:**

PHPUnit should already be installed as part of Shopware's development dependencies. If it's missing:

```bash
# Make sure you're in the Shopware root directory
cd /path/to/shopware-tutorial-olli

# Install/update all dependencies (including dev dependencies)
composer install

# Or if already installed, update:
composer update

# Verify PHPUnit is now available
vendor/bin/phpunit --version
```

**You should see output like:**
```
PHPUnit 9.5.x by Sebastian Bergmann and contributors.
```

**Common Issues:**

1. **"composer: command not found"**
   - You need to install Composer first: https://getcomposer.org/download/
   
2. **"vendor/bin/phpunit: Permission denied"**
   ```bash
   chmod +x vendor/bin/phpunit
   ```

3. **PHPUnit still not found after composer install/update**
   
   **Problem:** `vendor/bin/phpunit` doesn't exist even after running composer.
   
   **Step 1: Check if PHPUnit is installed**
   ```bash
   # See all installed packages
   composer show | grep phpunit
   
   # Check specifically for phpunit/phpunit
   composer show phpunit/phpunit
   ```
   
   **If you see "Package phpunit/phpunit not found":**
   
   PHPUnit is NOT installed. Install it:
   ```bash
   # Install PHPUnit as a dev dependency
   composer require --dev phpunit/phpunit:^9.5
   
   # Verify installation
   ls -la vendor/bin/phpunit
   vendor/bin/phpunit --version
   ```
   
   **If you see phpunit/phpunit listed but vendor/bin/phpunit doesn't exist:**
   
   The binary links weren't created. Rebuild them:
   ```bash
   # Remove vendor directory
   rm -rf vendor/
   
   # Reinstall everything
   composer install
   
   # Check again
   ls -la vendor/bin/
   ```
   
   **Alternative: Use Composer's PHPUnit directly**
   
   If vendor/bin/phpunit still doesn't exist, you can run it via composer:
   ```bash
   # Run PHPUnit through composer
   composer exec phpunit -- --version
   
   # Run your tests
   composer exec phpunit -- tests/unit/Service/MessageServiceTest.php
   ```
   
   **For Shopware specifically:**
   
   Shopware might not include PHPUnit by default in production installs. Check your `composer.json`:
   ```bash
   # View your composer.json
   cat composer.json | grep -A 10 "require-dev"
   ```
   
   If `"phpunit/phpunit"` is NOT listed under `"require-dev"`, add it:
   ```bash
   composer require --dev phpunit/phpunit:^9.5
   ```

4. **"Your lock file does not contain a compatible set of packages" or PHP version errors**
   
   **Problem:** Your PHP version is too new (e.g., PHP 8.5) and Shopware doesn't support it yet.
   
   **Solution 1: Switch to Compatible PHP Version (Recommended)**
   
   Check your current PHP version:
   ```bash
   php -v
   ```
   
   If you see PHP 8.5 or newer, you need to downgrade to PHP 8.3 or 8.4:
   
   **On macOS (using Homebrew):**
   ```bash
   # Install PHP 8.3
   brew install php@8.3
   
   # Link it to make it the default
   brew unlink php
   brew link php@8.3 --force --overwrite
   
   # Verify the change
   php -v
   # Should show: PHP 8.3.x
   
   # Now try composer install again
   composer install
   ```
   
   **On Ubuntu/Debian:**
   ```bash
   # Add PHP repository
   sudo add-apt-repository ppa:ondrej/php
   sudo apt update
   
   # Install PHP 8.3
   sudo apt install php8.3 php8.3-cli php8.3-common
   
   # Switch to PHP 8.3
   sudo update-alternatives --set php /usr/bin/php8.3
   
   # Verify
   php -v
   ```
   
   **Solution 2: Update Composer Dependencies (Advanced - May Break Things)**
   
   ⚠️ **Warning:** Only do this if you know what you're doing!
   
   ```bash
   # This updates all packages to latest compatible versions
   composer update
   
   # If that doesn't work, try with --ignore-platform-reqs (risky!)
   composer install --ignore-platform-reqs
   ```
   
   **Why this happens:**
   - Shopware 6.7 officially supports PHP 8.2, 8.3, and 8.4
   - PHP 8.5 is very new and libraries haven't caught up yet
   - Always check Shopware's system requirements before upgrading PHP

5. **"Fatal error: Allowed memory size exhausted" during composer install**
   
   **Problem:** PHP doesn't have enough memory allocated (default is often 128MB, Shopware needs more).
   
   **Solution: Increase PHP Memory Limit**
   
   **Quick Fix (Temporary - for this command only):**
   ```bash
   # Run composer with unlimited memory
   php -d memory_limit=-1 $(which composer) install
   ```
   
   **Permanent Fix (Recommended):**
   
   Find your php.ini file:
   ```bash
   php --ini
   # Look for "Loaded Configuration File"
   ```
   
   Edit the php.ini file and change:
   ```ini
   ; Change this line:
   memory_limit = 128M
   
   ; To this (512MB is recommended for Shopware):
   memory_limit = 512M
   
   ; Or for development, you can use:
   memory_limit = -1  ; (unlimited - use with caution!)
   ```
   
   **On macOS with Homebrew:**
   ```bash
   # Edit php.ini
   nano /opt/homebrew/etc/php/8.3/php.ini
   
   # Find memory_limit and change to 512M
   # Save with Ctrl+X, then Y, then Enter
   
   # Restart PHP if using PHP-FPM
   brew services restart php@8.3
   
   # Verify the change
   php -i | grep memory_limit
   # Should show: memory_limit => 512M => 512M
   ```
   
   **On Ubuntu/Debian:**
   ```bash
   # Edit php.ini
   sudo nano /etc/php/8.3/cli/php.ini
   
   # Find memory_limit and change to 512M
   # Save with Ctrl+X, then Y, then Enter
   
   # Verify the change
   php -i | grep memory_limit
   ```
   
   After increasing the memory limit, try again:
   ```bash
   composer install
   ```
   
   **Why Shopware needs more memory:**
   - Shopware is a large application with many dependencies
   - During installation, it compiles assets and caches
   - Development environments especially need more memory for debugging tools

6. **Security advisories blocking `composer update`**
   
   **Problem:** Composer warns about security vulnerabilities in your current Shopware version.
   
   ```
   Problem 1
   - Root composer.json requires shopware/core v6.7.3.1 (exact version match)
   - These were not loaded, because they are affected by security advisories
   ```
   
   **What are security advisories?**  
   Security advisories are warnings about known vulnerabilities (security holes) in packages. Composer (as of v2.x) blocks installing packages with known security issues to protect you.
   
   **Solution 1: Update All Shopware Packages Together (RECOMMENDED for production)**
   
   This is the RIGHT way to fix security issues, but may require additional testing:
   
   ```bash
   # Backup database first!
   bin/console database:dump backup_before_update.sql
   
   # Update ALL Shopware packages together (including dependencies)
   composer update shopware/* --with-all-dependencies
   
   # After update, run migrations
   bin/console database:migrate --all
   bin/console cache:clear
   ```
   
   **⚠️ Important:**
   - This updates multiple packages and may introduce breaking changes
   - Test thoroughly after updating
   - Read the changelog: https://github.com/shopware/shopware/releases
   - **For beginners learning:** This might be overkill - see Solution 2
   
   **Solution 2: For Learning/Testing ONLY (NOT for production!)** ✅ **Recommended for Tutorial**
   
   If you're just learning with this tutorial and want to focus on testing (not security updates):
   
   **Step 1: Create or edit `composer.json`**
   
   Add this configuration to disable security checks (only for local development!):
   
   ```bash
   cd /path/to/shopware-tutorial-olli
   
   # Open composer.json in editor
   nano composer.json  # or use VS Code
   ```
   
   **Step 2: Add this to your composer.json**
   
   Find the `"config"` section (or create it if it doesn't exist) and add:
   
   ```json
   {
       "require": {
           ...existing requirements...
       },
       "config": {
           "audit": {
               "block-insecure": false
           },
           ...other config options...
       }
   }
   ```
   
   **Complete example:**
   ```json
   {
       "name": "shopware/production",
       "require": {
           "shopware/core": "v6.7.3.1",
           "shopware/administration": "*",
           "shopware/storefront": "*"
       },
       "config": {
           "audit": {
               "block-insecure": false
           },
           "optimize-autoloader": true,
           "sort-packages": true
       }
   }
   ```
   
   **Step 3: Run composer install**
   ```bash
   composer install
   ```
   
   **This will now work** because you've told Composer: "I know about the security issues, let me proceed anyway for learning purposes."
   
   **⚠️ CRITICAL WARNINGS:**
   - 🚫 **NEVER use this on a live/production website**
   - 🚫 **NEVER store customer data with security vulnerabilities**
   - 🚫 **NEVER accept real payments with an insecure version**
   - ✅ **Only use for local learning/development**
   - ✅ **Delete `"block-insecure": false` before going live**
   
   **Why this is okay for learning:**
   - You're working on localhost only
   - No real customer data involved
   - No internet exposure
   - Focus is on learning testing, not security
   - You can update later when you deploy
   
   **Before deploying to production, you MUST:**
   1. Remove the `"block-insecure": false` line
   2. Update all Shopware packages to secure versions
   3. Run security audit: `composer audit`
   4. Keep Shopware updated with security patches
   
   **Checking for security issues:**
   ```bash
   # Check your current installation for vulnerabilities
   composer audit
   
   # See advisory details
   composer audit --format=json
   ```

**Understanding the Test Setup:**

- `vendor/bin/phpunit` - The PHPUnit executable that runs your tests
- Tests live in: `custom/plugins/YourPlugin/tests/`
- PHPUnit reads configuration from: `phpunit.xml` in your plugin folder

### Step 2: Set Up Test Structure

**Why these directories?**
- `tests/unit/` - Fast tests that test individual classes in isolation (no database)
- `tests/Integration/` - Slower tests that test multiple components together (uses database)

**⚠️ IMPORTANT: Directory Case Matters!**
- Integration tests MUST be in `tests/Integration/` (capital I)
- This is required for PSR-4 autoloading: `Learning\Bundle\Tests\Integration`
- macOS/Windows have case-insensitive filesystems but PSR-4 is case-sensitive
- Wrong case = "Trait not found" errors

Create test directories in your plugin:

```bash
# Make sure you're in the Shopware root
cd /path/to/shopware-tutorial-olli

# Create test directories (note capital I for Integration)
mkdir -p custom/plugins/LearningBundle/tests/unit/Service
mkdir -p custom/plugins/LearningBundle/tests/Integration
```

**What we're creating:**
```
LearningBundle/
├── src/                    # Your plugin code
└── tests/                  # Your test code
    ├── unit/               # Unit tests (fast, isolated)
    │   └── Service/        # Tests for services
    ├── Integration/        # Integration tests (capital I!)
    └── TestBootstrap.php   # Test setup file
```

### Step 3: Create PHPUnit Configurations

**What is phpunit.xml?**  
This file tells PHPUnit where to find your tests, how to run them, and what code to check for coverage. Think of it as the "settings file" for your tests.

**We'll create TWO configurations:**
1. `phpunit.unit.xml` - For unit tests (fast, no database)
2. `phpunit.xml` - For integration tests (slower, needs database)

**First, create `custom/plugins/LearningBundle/phpunit.unit.xml` (for unit tests):**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/9.5/phpunit.xsd"
         bootstrap="tests/bootstrap-unit.php"
         executionOrder="random"
         beStrictAboutOutputDuringTests="true"
         beStrictAboutTodoAnnotatedTests="true"
         failOnRisky="true"
         failOnWarning="true"
         colors="true">
    <testsuites>
        <testsuite name="Learning Bundle Unit Tests">
            <directory>tests/unit</directory>
        </testsuite>
    </testsuites>

    <coverage processUncoveredFiles="true">
        <include>
            <directory suffix=".php">src</directory>
        </include>
    </coverage>
</phpunit>
```

**Next, create `custom/plugins/LearningBundle/phpunit.xml` (for integration tests):**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/9.5/phpunit.xsd"
         bootstrap="tests/TestBootstrap.php"
         executionOrder="random"
         beStrictAboutOutputDuringTests="true"
         beStrictAboutTodoAnnotatedTests="true"
         failOnRisky="true"
         failOnWarning="true"
         colors="true">
    <testsuites>
        <testsuite name="Learning Bundle Test Suite">
            <directory>tests</directory>
        </testsuite>
    </testsuites>

    <coverage processUncoveredFiles="true">
        <include>
            <directory suffix=".php">src</directory>
        </include>
    </coverage>
</phpunit>
```

**What this configuration means:**
- `bootstrap="tests/TestBootstrap.php"` - File to run before tests (sets up Shopware)
- `executionOrder="random"` - Tests run in random order (ensures they don't depend on each other)
- `colors="true"` - Pretty colored output in terminal
- `<testsuite>` - Defines where to find test files
- `<coverage>` - What code to include in coverage reports

### Step 4: Create Test Bootstrap Files

**We need TWO bootstrap files:**

**1. For Unit Tests (Minimal Bootstrap)**

**What is bootstrap-unit.php?**  
This file ONLY loads the Composer autoloader and registers your plugin namespace. It does NOT connect to the database or load Shopware services. This makes unit tests fast!

Create `custom/plugins/LearningBundle/tests/bootstrap-unit.php`:

```php
<?php declare(strict_types=1);

/**
 * Bootstrap file for UNIT TESTS ONLY
 * 
 * This file only loads the Composer autoloader.
 * It does NOT connect to database or bootstrap Shopware.
 * 
 * Unit tests should be fast and isolated - they don't need database.
 */

// Load Composer autoloader (4 levels up: tests → LearningBundle → plugins → custom → root)
require_once __DIR__ . '/../../../../vendor/autoload.php';

// Register plugin's PSR-4 namespace for autoloading
$loader = require __DIR__ . '/../../../../vendor/autoload.php';
$loader->addPsr4('Learning\\Bundle\\', __DIR__ . '/../src/');
```

**2. For Integration Tests (Full Shopware Bootstrap)**

**What is TestBootstrap.php?**  
This file sets up the FULL Shopware environment so your integration tests can use Shopware classes, services, and database. It's slower but more realistic.

Create `custom/plugins/LearningBundle/tests/TestBootstrap.php`:

```php
<?php declare(strict_types=1);

use Shopware\Core\TestBootstrapper;

$loader = (new TestBootstrapper())
    ->addCallingPlugin()
    ->addActivePlugins('LearningBundle')
    ->setForceInstallPlugins(true)
    ->bootstrap()
    ->getClassLoader();

$loader->addPsr4('Learning\\Bundle\\Tests\\', __DIR__);
```

**What this does:**
- Loads Shopware's test framework
- Registers your plugin as active during tests
- Sets up autoloading so PHP can find your test classes
- Creates a test environment separate from your actual Shopware instance

**Important:** This bootstrap is for **integration tests**. Unit tests (which we'll write first) don't need the full Shopware bootstrap since they use mocks instead of real services.

### Step 5: Write Your First Unit Test

**For Beginners: What is a Unit Test?**

A unit test checks that ONE small piece (unit) of code works correctly, completely isolated from everything else:
- ✅ Tests ONE method at a time
- ✅ Uses "mocks" (fake versions) of dependencies
- ✅ Runs very fast (milliseconds)
- ✅ Doesn't touch the database
- ❌ Doesn't test how components work together

**Example:** If you have a service that sends emails, a unit test would check that it formats the email correctly, but wouldn't actually send a real email (it would use a fake/mock email sender).

Now let's write unit tests for `MessageService`.

Create `custom/plugins/LearningBundle/tests/unit/Service/MessageServiceTest.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Tests\Unit\Service;

use Learning\Bundle\Event\CustomWelcomeEvent;
use Learning\Bundle\Exception\ValidationException;
use Learning\Bundle\Service\CounterService;
use Learning\Bundle\Service\MessageService;
use Learning\Bundle\Service\ValidationService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MessageServiceTest extends TestCase
{
    private MessageService $messageService;
    private LoggerInterface $logger;
    private SystemConfigService $systemConfigService;
    private EventDispatcherInterface $eventDispatcher;
    private CounterService $counterService;
    private ValidationService $validationService;

    protected function setUp(): void
    {
        // Create mocks for all dependencies
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->systemConfigService = $this->createMock(SystemConfigService::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->counterService = $this->createMock(CounterService::class);
        $this->validationService = $this->createMock(ValidationService::class);

        // Create service with all mocked dependencies
        $this->messageService = new MessageService(
            $this->logger,
            $this->systemConfigService,
            $this->counterService,
            $this->validationService,
            $this->eventDispatcher
        );
    }

    public function testGenerateWelcomeMessageWithSimpleFormat(): void
    {
        // Mock validation to return processed name
        $this->validationService
            ->method('processName')
            ->willReturn('Olli');

        // Mock counter increment
        $this->counterService
            ->method('incrementCount')
            ->willReturn(1);

        // Mock system config for language and format
        $this->systemConfigService
            ->method('get')
            ->willReturnMap([
                ['LearningBundle.config.greetingLanguage', null, 'en'],
            ]);

        $this->systemConfigService
            ->method('getString')
            ->willReturnMap([
                ['LearningBundle.config.messageFormat', 'simple'],
            ]);

        $this->systemConfigService
            ->method('getBool')
            ->willReturn(false); // Disable logging for this test

        $context = Context::createDefaultContext();
        $message = $this->messageService->generateWelcomeMessage('Olli', $context);

        // Assert the message contains the name
        $this->assertStringContainsString('Olli', $message);
    }

    public function testGenerateWelcomeMessageWithDetailedFormat(): void
    {
        $this->validationService->method('processName')->willReturn('Developer');
        $this->counterService->method('incrementCount')->willReturn(2);
        
        $this->systemConfigService
            ->method('get')
            ->willReturn('en');

        $this->systemConfigService
            ->method('getString')
            ->willReturnMap([
                ['LearningBundle.config.messageFormat', 'detailed'],
            ]);

        $this->systemConfigService->method('getBool')->willReturn(false);

        $context = Context::createDefaultContext();
        $message = $this->messageService->generateWelcomeMessage('Developer', $context);

        // Assert detailed format includes name and date
        $this->assertStringContainsString('Developer', $message);
        $this->assertStringContainsString(date('Y-m-d'), $message);
    }

    public function testGenerateWelcomeMessageDispatchesEvent(): void
    {
        $this->validationService->method('processName')->willReturn('Test');
        $this->counterService->method('incrementCount')->willReturn(1);
        $this->systemConfigService->method('getBool')->willReturn(false);

        // Expect event to be dispatched
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(CustomWelcomeEvent::class));

        $context = Context::createDefaultContext();
        $this->messageService->generateWelcomeMessage('Test', $context);
    }

    public function testGenerateWelcomeMessageLogsWhenEnabled(): void
    {
        $this->validationService->method('processName')->willReturn('User');
        $this->counterService->method('incrementCount')->willReturn(1);
        
        // Enable logging
        $this->systemConfigService->method('getBool')->willReturn(true);

        // Expect logger to be called
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Generated welcome message for {name}', $this->isType('array'));

        $context = Context::createDefaultContext();
        $this->messageService->generateWelcomeMessage('User', $context);
    }

    public function testGenerateWelcomeMessageDoesNotLogWhenDisabled(): void
    {
        $this->validationService->method('processName')->willReturn('User');
        $this->counterService->method('incrementCount')->willReturn(1);
        
        // Disable logging
        $this->systemConfigService->method('getBool')->willReturn(false);

        // Logger should NOT be called
        $this->logger->expects($this->never())->method('info');

        $context = Context::createDefaultContext();
        $this->messageService->generateWelcomeMessage('User', $context);
    }

    public function testGenerateWelcomeMessageThrowsValidationException(): void
    {
        // Mock validation to throw exception
        $this->validationService
            ->method('processName')
            ->willThrowException(new ValidationException('Invalid name'));

        // Expect logger warning
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('Validation failed for name: {name}', $this->isType('array'));

        $this->expectException(ValidationException::class);

        $context = Context::createDefaultContext();
        $this->messageService->generateWelcomeMessage('', $context);
    }

    public function testGetGreetingReturnsCorrectTranslation(): void
    {
        // Mock German language
        $this->systemConfigService->method('get')->willReturn('de');

        $greeting = $this->messageService->getGreeting('hello');

        $this->assertEquals('Hallo', $greeting);
    }

    public function testGetGreetingFallsBackToEnglish(): void
    {
        // Mock invalid language
        $this->systemConfigService->method('get')->willReturn('invalid');

        $greeting = $this->messageService->getGreeting('hello');

        // Should fallback to English
        $this->assertEquals('Hello', $greeting);
    }

    public function testGetPluginInfoReturnsCorrectStructure(): void
    {
        // Mock counter statistics
        $this->counterService
            ->method('getStatistics')
            ->willReturn(['count' => 42]);

        $info = $this->messageService->getPluginInfo();

        $this->assertIsArray($info);
        $this->assertArrayHasKey('name', $info);
        $this->assertArrayHasKey('version', $info);
        $this->assertArrayHasKey('features', $info);
        $this->assertArrayHasKey('total_messages_generated', $info);
        $this->assertEquals('LearningBundle', $info['name']);
        $this->assertEquals(42, $info['total_messages_generated']);
    }
}
```

**Key Testing Concepts Explained for Beginners:**

**1. Mocking Dependencies**
```php
$this->logger = $this->createMock(LoggerInterface::class);
```
- Creates a "fake" logger that we control
- We can tell it what to return when called
- Real logger would write to files - we don't want that in tests!

**2. Setting Up Mock Behavior**
```php
$this->validationService
    ->method('processName')
    ->willReturn('Olli');
```
- When `processName()` is called with any parameter, return 'Olli'
- We control exactly what the mock returns

**3. Verifying Method Calls**
```php
$this->counterService
    ->expects($this->once())
    ->method('incrementCount');
```
- Checks that `incrementCount()` was called exactly once
- Test fails if it's called 0 times or 2+ times

**4. Testing Different Scenarios**
- Each `testXxx()` method tests ONE specific scenario
- Name describes what is being tested
- Tests should be independent (order doesn't matter)

**5. Assertions**
```php
$this->assertStringContainsString('Olli', $message);
```
- Verifies that the actual result matches expectations
- Test passes if assertion is true, fails if false

**Common Testing Patterns You'll See:**

- `willReturn()` - Mock returns a simple value
- `willReturnMap()` - Mock returns different values based on input parameters
- `expects($this->once())` - Verify method is called exactly once
- `expects($this->never())` - Verify method is NEVER called
- `willThrowException()` - Mock throws an exception to test error handling
- `willReturnCallback()` - Mock uses a function to determine return value

### Step 6: Run Your Tests

**🚨 IMPORTANT: Always Use `-c phpunit.unit.xml` for Unit Tests! 🚨**

Even if your database is running, unit tests should use `phpunit.unit.xml` because:
- ✅ They're designed to run with mocks (no real database needed)
- ✅ They're much faster (20ms vs 2+ seconds)
- ✅ They don't pollute your database with test data
- ✅ They test code in isolation

**Running `../../../vendor/bin/phpunit` without `-c phpunit.unit.xml` will:**
- ❌ Try to use `phpunit.xml` (which needs database setup)
- ❌ Attempt to run TestBootstrap.php (which installs test database)
- ❌ Be much slower and may fail even with database running

**TL;DR:** Always include `-c phpunit.unit.xml` for unit tests!

---

**From your plugin directory:**

```bash
# Navigate to plugin directory
cd /path/to/shopware-tutorial-olli/custom/plugins/LearningBundle

# Run all unit tests with the unit configuration
../../../vendor/bin/phpunit -c phpunit.unit.xml

# Run only unit tests directory
../../../vendor/bin/phpunit -c phpunit.unit.xml tests/unit/

# Run specific test file
../../../vendor/bin/phpunit -c phpunit.unit.xml tests/unit/Service/MessageServiceTest.php

# Run with verbose output (shows test names)
../../../vendor/bin/phpunit -c phpunit.unit.xml --verbose tests/unit/

# Run specific test method
../../../vendor/bin/phpunit -c phpunit.unit.xml --filter testGenerateWelcomeMessageWithSimpleFormat tests/unit/Service/MessageServiceTest.php

# Generate coverage report (requires Xdebug)
../../../vendor/bin/phpunit -c phpunit.unit.xml --coverage-html coverage/
open coverage/index.html
```

**Why `-c phpunit.unit.xml`?**
- `phpunit.unit.xml` uses `bootstrap-unit.php` (only loads Composer autoloader)
- `phpunit.xml` uses `TestBootstrap.php` (requires database connection)
- Unit tests should be fast and isolated - no database needed!
- Integration tests (later) will use `phpunit.xml`

**Understanding Test Output:**

```
PHPUnit 9.5.x by Sebastian Bergmann and contributors.

..........                                                        10 / 10 (100%)

Time: 00:00.234, Memory: 12.00 MB

OK (10 tests, 25 assertions)
```

- `.` = One test passed
- `F` = One test failed
- `E` = One test had an error
- `S` = One test was skipped
- Numbers show: (tests passed / total tests)

**If a test fails:**

```
There was 1 failure:

1) Learning\Bundle\Tests\Unit\Service\MessageServiceTest::testGetGreeting
Failed asserting that two strings are equal.
--- Expected
+++ Actual
@@ @@
-'Hallo'
+'Hello'
```

This shows:
- Which test failed
- What was expected vs what actually happened
- Where the failure occurred

**Common Test Errors for Beginners:**

1. **"PDOException: SQLSTATE[HY000] [2002] Connection refused"**
   
   **Problem:** You ran `../../../vendor/bin/phpunit` without the `-c phpunit.unit.xml` flag.
   
   **Why it happens:**
   - Without `-c` flag, PHPUnit uses default `phpunit.xml`
   - Default config uses `TestBootstrap.php` which needs database
   - Your Docker database container isn't running
   
   **Solution:**
   ```bash
   # For UNIT tests (no database needed) - USE THIS:
   ../../../vendor/bin/phpunit -c phpunit.unit.xml tests/unit/
   
   # For INTEGRATION tests (needs database):
   # First, start your database
   docker-compose up -d
   # Then run integration tests
   ../../../vendor/bin/phpunit -c phpunit.xml tests/integration/
   ```
   
   **Remember:**
   - ✅ Unit tests → Use `-c phpunit.unit.xml` → Fast, no database
   - ✅ Integration tests → Use `-c phpunit.xml` (or no flag) → Needs database
   
   **Verify database is running:**
   ```bash
   # Check if database container is running (works for MySQL or MariaDB)
   docker ps | grep -E "mysql|mariadb"
   # If empty, start it:
   docker-compose up -d
   ```

2. **"Class not found"**
   - Missing `use` statement at top of test file
   - Check that class name is spelled correctly

3. **"Call to undefined method"**
   - Typo in method name
   - Method doesn't exist in the class you're testing

4. **"Too few arguments"**
   - Constructor needs more dependencies
   - Check the actual service's `__construct()` method

5. **Mock setup doesn't work**
   - Make sure you set up the mock BEFORE calling the method
   - Check that method names match exactly (case-sensitive!)

### Step 7: Understanding Test Coverage

**What is test coverage?**  
It shows what percentage of your code is executed during tests. Higher coverage = more of your code is tested.

**Generate coverage report (requires Xdebug):**

```bash
# First, check if Xdebug is installed
php -m | grep xdebug

# If not installed (macOS):
sudo pecl install xdebug

# IMPORTANT: Configure Xdebug for coverage mode
echo "xdebug.mode=coverage" | sudo tee -a $(php --ini | grep "Loaded Configuration File" | awk '{print $4}')

# Verify Xdebug is loaded with coverage mode
php -v  # Should show "with Xdebug v3.x.x"
php -i | grep xdebug.mode  # Should show "xdebug.mode => coverage => coverage"

# Generate HTML coverage report (for unit tests)
cd custom/plugins/LearningBundle
../../../vendor/bin/phpunit -c phpunit.unit.xml --coverage-html coverage/

# Open the report
open coverage/index.html
```

#### Xdebug Installation Troubleshooting

**Problem 1: "pecl: command not found"**
```bash
# On macOS with Homebrew, PECL comes with PHP
# Make sure PHP is properly installed
brew install php

# Or reinstall if needed
brew reinstall php
```

**Problem 2: "Xdebug not showing in php -m"**

This means Xdebug installed but isn't loaded. Check:

```bash
# Find where Xdebug was installed
find /opt/homebrew -name "xdebug.so" 2>/dev/null
find /usr/local -name "xdebug.so" 2>/dev/null

# Check php.ini location
php --ini

# You should see a line like:
# zend_extension="/path/to/xdebug.so"
# If not, add it manually:
echo 'zend_extension="xdebug.so"' | sudo tee -a /opt/homebrew/etc/php/8.3/php.ini
```

**Problem 3: "Wrong Xdebug version installed"**

If you accidentally installed Xdebug for PHP 8.5 but are running PHP 8.3:

```bash
# Check your PHP version first
php -v  # e.g., "PHP 8.3.29"

# Remove wrong version (if installed via Homebrew)
brew uninstall xdebug@8.5

# Install correct version via PECL (automatically detects PHP version)
sudo pecl install xdebug
```

**Problem 4: "Coverage report not generating (no error)"**

If tests pass but no coverage/ directory is created:

```bash
# Check if Xdebug is in correct mode
php -i | grep xdebug.mode

# If it shows "develop" instead of "coverage", fix it:
# Edit php.ini (find location with: php --ini)
sudo nano /opt/homebrew/etc/php/8.3/php.ini

# Change or add:
# xdebug.mode=coverage

# Verify change:
php -i | grep xdebug.mode  # Should show "coverage"
```

**Problem 5: "Permission denied when installing"**

If you see `ERROR: failed to mkdir`, use sudo:

```bash
# Install with sudo permissions
sudo pecl install xdebug

# Enter your macOS password when prompted
```

**Problem 6: "Installation takes forever or fails"**

Sometimes Xdebug compilation can be slow or fail. Alternative method:

```bash
# On macOS with Homebrew, try installing via formula:
brew install php-xdebug

# Or for specific PHP version:
brew install php@8.3-xdebug
```

**Interpreting coverage:**
- 🟢 Green lines - Code was executed during tests
- 🔴 Red lines - Code was NOT executed during tests
- 80%+ coverage is considered good
- 100% coverage doesn't mean bug-free!

**Note for beginners:** Don't worry too much about coverage initially. Focus on writing meaningful tests first. Code coverage is a nice metric to have later, but it's not essential when you're learning to write tests.

---

### Step 8: Write Unit Tests for ProductViewService

Now that you understand the basics, let's write tests for another service.

Create `custom/plugins/LearningBundle/tests/unit/Service/ProductViewServiceTest.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Tests\Unit\Service;

use Learning\Bundle\Core\Content\ProductView\ProductViewCollection;
use Learning\Bundle\Core\Content\ProductView\ProductViewEntity;
use Learning\Bundle\Service\ProductViewService;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class ProductViewServiceTest extends TestCase
{
    private ProductViewService $service;
    private EntityRepository $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EntityRepository::class);
        $this->service = new ProductViewService($this->repository);
    }

    public function testRecordViewCreatesNewEntry(): void
    {
        $productId = 'test-product-id';
        $customerId = 'test-customer-id';
        $userAgent = 'Test Browser';
        $context = Context::createDefaultContext();

        // Mock repository search returning no existing view
        $this->repository
            ->expects($this->once())
            ->method('search')
            ->willReturn($this->createEmptySearchResult());

        // Expect create to be called (not update)
        $this->repository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($data) use ($productId, $customerId) {
                $this->assertCount(1, $data);
                $this->assertEquals($productId, $data[0]['productId']);
                $this->assertEquals($customerId, $data[0]['customerId']);
                $this->assertEquals(1, $data[0]['viewCount']);
                return true;
            }));

        $this->service->recordView($productId, $customerId, $userAgent, $context);
    }

    public function testRecordViewUpdatesExistingEntry(): void
    {
        $productId = 'test-product-id';
        $context = Context::createDefaultContext();

        // Create existing view entity
        $existingView = new ProductViewEntity();
        $existingView->setId('existing-id');
        $existingView->setViewCount(5);

        // Mock repository returning existing view
        $this->repository
            ->expects($this->once())
            ->method('search')
            ->willReturn($this->createSearchResultWithEntity($existingView));

        // Expect update to be called (not create)
        $this->repository
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(function ($data) {
                $this->assertCount(1, $data);
                $this->assertEquals(6, $data[0]['viewCount']); // 5 + 1
                return true;
            }));

        $this->service->recordView($productId, null, null, $context);
    }

    public function testGetProductViewCountReturnsZeroForNoViews(): void
    {
        $productId = 'non-existent-product';
        $context = Context::createDefaultContext();

        $this->repository
            ->method('search')
            ->willReturn($this->createEmptySearchResult());

        $count = $this->service->getProductViewCount($productId, $context);

        $this->assertEquals(0, $count);
    }

    public function testGetProductViewCountSumsMultipleViews(): void
    {
        $productId = 'popular-product';
        $context = Context::createDefaultContext();

        // Create multiple view entities
        $view1 = new ProductViewEntity();
        $view1->setViewCount(10);
        
        $view2 = new ProductViewEntity();
        $view2->setViewCount(15);

        $collection = new ProductViewCollection([$view1, $view2]);

        $this->repository
            ->method('search')
            ->willReturn($this->createSearchResultWithCollection($collection));

        $count = $this->service->getProductViewCount($productId, $context);

        $this->assertEquals(25, $count); // 10 + 15
    }

    private function createEmptySearchResult(): EntitySearchResult
    {
        return new EntitySearchResult(
            'learning_product_view',
            0,
            new ProductViewCollection(),
            null,
            new Criteria(),
            Context::createDefaultContext()
        );
    }

    private function createSearchResultWithEntity(ProductViewEntity $entity): EntitySearchResult
    {
        return new EntitySearchResult(
            'learning_product_view',
            1,
            new ProductViewCollection([$entity]),
            null,
            new Criteria(),
            Context::createDefaultContext()
        );
    }

    private function createSearchResultWithCollection(ProductViewCollection $collection): EntitySearchResult
    {
        return new EntitySearchResult(
            'learning_product_view',
            $collection->count(),
            $collection,
            null,
            new Criteria(),
            Context::createDefaultContext()
        );
    }
}
```

**What we're testing here:**
- ✅ Creating new product view records
- ✅ Updating existing product view records
- ✅ Handling missing data (returns 0)
- ✅ Summing multiple view counts

**Notice:** This test is more complex because it deals with database entities. We're still using mocks, but we need to create fake entity objects.

---

### Step 9: Run Unit Tests

**From your plugin directory:**

```bash
# Navigate to plugin
cd custom/plugins/LearningBundle

# Run all unit tests
../../../vendor/bin/phpunit -c phpunit.unit.xml

# Run only MessageService tests
../../../vendor/bin/phpunit -c phpunit.unit.xml tests/unit/Service/MessageServiceTest.php

# Run only ProductViewService tests  
../../../vendor/bin/phpunit -c phpunit.unit.xml tests/unit/Service/ProductViewServiceTest.php

# Run with detailed output
../../../vendor/bin/phpunit -c phpunit.unit.xml --verbose

# Run and generate coverage report
../../../vendor/bin/phpunit -c phpunit.unit.xml --coverage-html coverage/
```

**Expected output:**
```
PHPUnit 9.5.x by Sebastian Bergmann and contributors.

....................                                              20 / 20 (100%)

Time: 00:00.456, Memory: 14.00 MB

OK (20 tests, 45 assertions)
```

✅ **Congratulations!** You've written your first unit tests!

---

## Part 3: Integration Tests (75 minutes)

### Step 1: Create Integration Test Base

**Important:** Create this in `tests/Integration/` (capital I) to match PSR-4 autoloading requirements.

Create `custom/plugins/LearningBundle/tests/Integration/IntegrationTestBehaviour.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Tests\Integration;

use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;

trait LearningIntegrationTestBehaviour
{
    use IntegrationTestBehaviour;
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    protected function getProductId(): string
    {
        // Get first product from database for testing
        $connection = $this->getContainer()->get('Doctrine\DBAL\Connection');
        $result = $connection->fetchOne('SELECT LOWER(HEX(id)) FROM product LIMIT 1');

        return $result ?: $this->createTestProduct();
    }

    protected function createTestProduct(): string
    {
        $productId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        
        /** @var EntityRepository $productRepository */
        $productRepository = $this->getContainer()->get('product.repository');
        
        // Get tax ID
        $connection = $this->getContainer()->get('Doctrine\DBAL\Connection');
        $taxId = $connection->fetchOne('SELECT LOWER(HEX(id)) FROM tax LIMIT 1');
        
        // Get sales channel ID  
        $salesChannelId = $connection->fetchOne('SELECT LOWER(HEX(id)) FROM sales_channel LIMIT 1');
        
        $productRepository->create([
            [
                'id' => $productId,
                'productNumber' => 'TEST-' . $productId,
                'name' => 'Test Product',
                'stock' => 10,
                'price' => [
                    [
                        'currencyId' => Defaults::CURRENCY,
                        'gross' => 99.99,
                        'net' => 84.03,
                        'linked' => false,
                    ],
                ],
                'taxId' => $taxId,
                'visibilities' => [
                    [
                        'salesChannelId' => $salesChannelId,
                        'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                    ],
                ],
            ],
        ], $context);
        
        return $productId;
    }
}
```

**What this does:**
- ✅ Actually creates a real product in the test database
- ✅ Gets tax and sales channel IDs from existing data
- ✅ Uses proper Shopware data structure
- ✅ Returns a valid product ID for use in tests

### Step 2: Write Integration Test

**Important:** File path must be `tests/Integration/Service/` (capital I).

Create `custom/plugins/LearningBundle/tests/Integration/Service/ProductViewServiceIntegrationTest.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Tests\Integration\Service;

use Learning\Bundle\Service\ProductViewService;
use Learning\Bundle\Tests\Integration\LearningIntegrationTestBehaviour;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;

class ProductViewServiceIntegrationTest extends TestCase
{
    use LearningIntegrationTestBehaviour;

    private ProductViewService $service;

    protected function setUp(): void
    {
        $this->service = $this->getContainer()->get(ProductViewService::class);
    }

    public function testRecordAndRetrieveView(): void
    {
        $context = Context::createDefaultContext();
        $productId = $this->getProductId();

        // Record a view
        $this->service->recordView($productId, null, 'Test User Agent', $context);

        // Retrieve view count
        $count = $this->service->getProductViewCount($productId, $context);

        // Assert it was recorded
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testMultipleViewsIncrement(): void
    {
        $context = Context::createDefaultContext();
        $productId = $this->getProductId();

        // Get initial count
        $initialCount = $this->service->getProductViewCount($productId, $context);

        // Record multiple views
        $this->service->recordView($productId, null, 'Test', $context);
        $this->service->recordView($productId, null, 'Test', $context);
        $this->service->recordView($productId, null, 'Test', $context);

        // Check count increased
        $newCount = $this->service->getProductViewCount($productId, $context);
        $this->assertEquals($initialCount + 3, $newCount);
    }

    public function testGetMostViewedProducts(): void
    {
        $context = Context::createDefaultContext();
        $productId = $this->getProductId();

        // Record some views
        for ($i = 0; $i < 10; $i++) {
            $this->service->recordView($productId, null, 'Test', $context);
        }

        // Get popular products
        $popular = $this->service->getMostViewedProducts(5, $context);

        // Assert we got results
        $this->assertIsArray($popular);
        $this->assertNotEmpty($popular);
    }
}
```

### Step 3: Run Integration Tests

**Important:** Integration tests use `phpunit.xml` (with TestBootstrap.php) because they need database access.

**⚠️ Prerequisites:**
1. Your Docker containers must be running (`docker ps` should show database)
2. Create `.env.test` file in Shopware root with database config:

```bash
# Create .env.test in Shopware root
cat > ../../../.env.test << 'EOF'
# Test environment configuration
KERNEL_CLASS='App\Kernel'
APP_SECRET='$ecretf0rt3st'
SYMFONY_DEPRECATIONS_HELPER=999999

# Database configuration for integration tests
# Update port to match your docker ps output
DATABASE_URL=mysql://root:root@127.0.0.1:YOUR_PORT/shopware_test
APP_URL=http://127.0.0.1:8000
EOF
```

**Find your database port:**
```bash
docker ps | grep mariadb
# Look for the port mapping like: 0.0.0.0:54191->3306/tcp
# Use the first port (54191 in this example) in DATABASE_URL above
```

**Update TestBootstrap to explicitly load the trait:**

Edit `custom/plugins/LearningBundle/tests/TestBootstrap.php` and add at the end:

```php
// Explicitly register the Integration test trait
require_once __DIR__ . '/Integration/IntegrationTestBehaviour.php';
```

**Run the tests:**
```bash
# Run integration tests (note: capital I in Integration/)
cd custom/plugins/LearningBundle
../../../vendor/bin/phpunit -c phpunit.xml tests/Integration/

# Run specific integration test
../../../vendor/bin/phpunit -c phpunit.xml tests/Integration/Service/ProductViewServiceIntegrationTest.php

# Run all tests (both unit and integration)
../../../vendor/bin/phpunit -c phpunit.xml

# Or run unit tests separately
../../../vendor/bin/phpunit -c phpunit.unit.xml tests/unit/
```

**Expected output:**
```
Shopware Plugin Service
=======================
[OK] Plugin list refreshed

... (plugin installation messages) ...

PHPUnit 9.6.31 by Sebastian Bergmann and contributors.

...                                                                 3 / 3 (100%)

Time: 00:00.032, Memory: 151.50 MB

OK (3 tests, 5 assertions)
```

**Configuration Summary:**
- `phpunit.unit.xml` → Unit tests only (no database)
- `phpunit.xml` → Integration tests (needs database, runs plugin installation)
- Default (no `-c` flag) → Uses `phpunit.xml` if found
- Directory must be `tests/Integration/` (capital I) for PSR-4 autoloading

**Common Integration Test Issues:**

1. **"Trait not found"**
   - Verify directory is `tests/Integration/` (capital I)
   - Add explicit `require_once` in TestBootstrap.php
   - Run `composer dump-autoload` from root

2. **"Connection refused"**
   - Check Docker containers: `docker ps`
   - Verify `.env.test` has correct port
   - Database port matches your `docker ps` output

3. **"Foreign key constraint violation"**
   - Product doesn't exist in test database
   - Use the updated `createTestProduct()` method shown above
   - Tests create their own test data

---

## Part 4: Understanding Caching (60 minutes)

### Theory: Shopware Cache System

**Cache Types:**

1. **HTTP Cache** - Full page caching (Varnish/reverse proxy)
2. **Object Cache** - Entity and service caching (Redis/Memcached)
3. **Template Cache** - Compiled Twig templates
4. **Configuration Cache** - Symfony container cache

**Cache Flow:**
```
Request → HTTP Cache → Object Cache → Database
         ↓ Hit        ↓ Hit          ↓ Miss
         Response     Response        Query → Cache → Response
```

### Cache Locations

```
var/cache/
├── dev/                 # Development cache
├── prod/                # Production cache
└── prod_*/              # Versioned production caches
```

---

## Part 5: Implementing Cache Strategies (90 minutes)

### Step 1: Cache-Aware Service

Create `custom/plugins/LearningBundle/src/Service/CachedProductViewService.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Adapter\Cache\CacheValueCompressor;
use Shopware\Core\Framework\Context;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CachedProductViewService
{
    private const CACHE_KEY_PREFIX = 'learning_product_view_';
    private const CACHE_TTL = 3600; // 1 hour

    private ProductViewService $productViewService;
    private CacheInterface $cache;
    private LoggerInterface $logger;

    public function __construct(
        ProductViewService $productViewService,
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        $this->productViewService = $productViewService;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * Get product view count with caching
     */
    public function getProductViewCount(string $productId, Context $context): int
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $productId;

        try {
            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($productId, $context) {
                $this->logger->debug('Cache miss for product view count', ['product_id' => $productId]);
                
                // Set TTL
                $item->expiresAfter(self::CACHE_TTL);
                
                // Add cache tags for invalidation
                $item->tag(['learning-product-view', 'product-' . $productId]);
                
                // Fetch from service
                return $this->productViewService->getProductViewCount($productId, $context);
            });
        } catch (\Throwable $e) {
            $this->logger->error('Cache error, falling back to direct query', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
            
            // Fallback to non-cached version
            return $this->productViewService->getProductViewCount($productId, $context);
        }
    }

    /**
     * Get most viewed products with caching
     */
    public function getMostViewedProducts(int $limit, Context $context): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX . 'popular_' . $limit;

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($limit, $context) {
            $item->expiresAfter(self::CACHE_TTL);
            $item->tag(['learning-product-view', 'popular-products']);
            
            return $this->productViewService->getMostViewedProducts($limit, $context);
        });
    }

    /**
     * Invalidate cache for specific product
     */
    public function invalidateProductCache(string $productId): void
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $productId;
        $this->cache->delete($cacheKey);
        
        $this->logger->info('Invalidated product view cache', ['product_id' => $productId]);
    }

    /**
     * Invalidate all product view related caches.
     */
    public function invalidateAllCaches(): void
    {
        // This requires a cache implementation that supports tag-based invalidation
        if ($this->cache instanceof \Symfony\Contracts\Cache\TagAwareCacheInterface) {
            $this->cache->invalidateTags(['learning-product-view']);
            $this->logger->info('Invalidated all product view related caches by tag');
        } else {
            $this->logger->warning('Cache does not support tag-based invalidation');
        }
    }
}
```

Register in `custom/plugins/LearningBundle/src/Resources/config/services.xml` (add this inside the `<services>` tag):

```xml
<service id="Learning\Bundle\Service\CachedProductViewService">
    <argument type="service" id="Learning\Bundle\Service\ProductViewService"/>
    <argument type="service" id="cache.object"/>
    <argument type="service" id="logger"/>
</service>
```

### Step 2: Cache Invalidation Subscriber

Create `custom/plugins/LearningBundle/src/Subscriber/CacheInvalidationSubscriber.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Subscriber;

use Learning\Bundle\Service\CachedProductViewService;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CacheInvalidationSubscriber implements EventSubscriberInterface
{
    private CachedProductViewService $cachedService;

    public function __construct(CachedProductViewService $cachedService)
    {
        $this->cachedService = $cachedService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductEvents::PRODUCT_WRITTEN_EVENT => 'onProductWritten',
        ];
    }

    public function onProductWritten(EntityWrittenEvent $event): void
    {
        // When product changes, invalidate its view cache
        foreach ($event->getIds() as $productId) {
            $this->cachedService->invalidateProductCache($productId);
        }
    }
}
```

### Step 3: HTTP Cache Tags

For Store API routes, add cache tags.

Create `custom/plugins/LearningBundle/src/Core/Content/ProductView/SalesChannel/CachedProductViewRoute.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\ProductView\SalesChannel;

use Shopware\Core\Framework\Adapter\Cache\AbstractCacheTracer;
use Shopware\Core\Framework\Adapter\Cache\CacheValueCompressor;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\StoreApiResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CachedProductViewRoute extends AbstractProductViewRoute
{
    private AbstractProductViewRoute $decorated;
    private AbstractCacheTracer $tracer;
    private CacheInterface $cache;
    private array $states;

    public function __construct(
        AbstractProductViewRoute $decorated,
        AbstractCacheTracer $tracer,
        CacheInterface $cache,
        array $states
    ) {
        $this->decorated = $decorated;
        $this->tracer = $tracer;
        $this->cache = $cache;
        $this->states = $states;
    }

    public function getDecorated(): AbstractProductViewRoute
    {
        return $this->decorated;
    }

    public function load(string $productId, Request $request, SalesChannelContext $context): ProductViewRouteResponse
    {
        // Check if we can use cache
        if ($context->hasState(...$this->states)) {
            return $this->getDecorated()->load($productId, $request, $context);
        }

        // Generate cache key
        $key = $this->generateKey($productId, $context);

        // Try to get from cache
        $value = $this->cache->get($key, function (ItemInterface $item) use ($productId, $request, $context) {
            $response = $this->tracer->trace($key, function () use ($productId, $request, $context) {
                return $this->getDecorated()->load($productId, $request, $context);
            });

            $item->tag($this->generateTags($productId, $response));

            return CacheValueCompressor::compress($response);
        });

        return CacheValueCompressor::uncompress($value);
    }

    private function generateKey(string $productId, SalesChannelContext $context): string
    {
        return 'product-view-route-' . $productId . '-' . $context->getSalesChannelId();
    }

    private function generateTags(string $productId, StoreApiResponse $response): array
    {
        return [
            'learning-product-view',
            'product-' . $productId,
        ];
    }
}
```

### Step 4: Test Your Cache Implementation

After creating the cache services, verify they work correctly:

**1. Clear the cache and rebuild:**
```bash
cd /path/to/shopware-tutorial-olli
bin/console cache:clear
```

**2. Verify the service is registered:**
```bash
bin/console debug:container CachedProductViewService
```

**3. Test cache behavior via browser or API:**
- Visit a product page twice
- Check the logs (`var/log/dev.log`) for "Cache miss" on first visit
- Second visit should be faster (cache hit, no log entry)

**4. Manually test cache invalidation:**
```bash
# View cache statistics
bin/console cache:pool:list

# Clear object cache
bin/console cache:pool:clear cache.object
```

✅ **Part 5 Complete!** You've implemented a full caching layer with:
- Cached service wrapper with TTL
- Cache tags for granular invalidation
- Automatic cache invalidation on product updates
- HTTP cache support for Store API routes

---

## Part 6: Cache Testing (45 minutes)

### Test Cache Behavior

Create `custom/plugins/LearningBundle/tests/unit/Service/CachedProductViewServiceTest.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Tests\Unit\Service;

use Learning\Bundle\Service\CachedProductViewService;
use Learning\Bundle\Service\ProductViewService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CachedProductViewServiceTest extends TestCase
{
    private CachedProductViewService $cachedService;
    private ProductViewService $productViewService;
    private CacheInterface $cache;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->productViewService = $this->createMock(ProductViewService::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->cachedService = new CachedProductViewService(
            $this->productViewService,
            $this->cache,
            $this->logger
        );
    }

    public function testGetProductViewCountUsesCache(): void
    {
        $productId = 'test-product';
        $context = Context::createDefaultContext();
        $expectedCount = 42;

        // Mock cache to return value without calling service
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->willReturnCallback(function ($key, $callback) use ($expectedCount) {
                // Simulate cache hit - don't call callback
                return $expectedCount;
            });

        // Service should NOT be called (cache hit)
        $this->productViewService
            ->expects($this->never())
            ->method('getProductViewCount');

        $result = $this->cachedService->getProductViewCount($productId, $context);

        $this->assertEquals($expectedCount, $result);
    }

    public function testGetProductViewCountFallsBackOnCacheError(): void
    {
        $productId = 'test-product';
        $context = Context::createDefaultContext();
        $expectedCount = 42;

        // Mock cache to throw exception
        $this->cache
            ->method('get')
            ->willThrowException(new \RuntimeException('Cache error'));

        // Service SHOULD be called (cache error)
        $this->productViewService
            ->expects($this->once())
            ->method('getProductViewCount')
            ->willReturn($expectedCount);

        $result = $this->cachedService->getProductViewCount($productId, $context);

        $this->assertEquals($expectedCount, $result);
    }
}
```

### Benchmark Cache Performance

Create `custom/plugins/LearningBundle/src/Command/CacheBenchmarkCommand.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Doctrine\DBAL\Connection;
use Learning\Bundle\Service\CachedProductViewService;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CacheBenchmarkCommand extends Command
{
    protected static $defaultName = 'learning:cache:benchmark';

    private CachedProductViewService $cachedService;
    private Connection $connection;

    public function __construct(
        CachedProductViewService $cachedService,
        Connection $connection
    ) {
        parent::__construct();
        $this->cachedService = $cachedService;
        $this->connection = $connection;
    }

    protected function configure(): void
    {
        $this
            ->setName('learning:cache:benchmark')
            ->setDescription('Benchmark cache performance for product view counts');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();
        
        // Get a real product ID from the database
        $productId = $this->connection->fetchOne('SELECT LOWER(HEX(id)) FROM product LIMIT 1');
        
        if (!$productId) {
            $io->error('No products found in database. Please create a product first.');
            return Command::FAILURE;
        }
        
        $io->info(sprintf('Testing cache with product ID: %s', $productId));

        // First call (cache miss)
        $start = microtime(true);
        $this->cachedService->getProductViewCount($productId, $context);
        $firstCallTime = (microtime(true) - $start) * 1000;

        // Second call (cache hit)
        $start = microtime(true);
        $this->cachedService->getProductViewCount($productId, $context);
        $secondCallTime = (microtime(true) - $start) * 1000;

        $io->table(
            ['Call', 'Time (ms)', 'Status'],
            [
                ['First (miss)', number_format($firstCallTime, 2), '❌ Cache Miss'],
                ['Second (hit)', number_format($secondCallTime, 2), '✅ Cache Hit'],
                ['Improvement', number_format($firstCallTime - $secondCallTime, 2), sprintf('%.1fx faster', $firstCallTime / $secondCallTime)],
            ]
        );

        return Command::SUCCESS;
    }
}
```

### Step 3: Run Cache Tests

**Run the cache unit tests:**
```bash
cd custom/plugins/LearningBundle

# Run only the cache service test
../../../vendor/bin/phpunit -c phpunit.unit.xml tests/unit/Service/CachedProductViewServiceTest.php

# Run all unit tests including cache tests
../../../vendor/bin/phpunit -c phpunit.unit.xml
```

**Expected output:**
```
PHPUnit 9.5.x by Sebastian Bergmann and contributors.

..                                                                  2 / 2 (100%)

Time: 00:00.089, Memory: 14.00 MB

OK (2 tests, 4 assertions)
```

### Step 4: Run the Cache Benchmark

**Register the command in `services.xml`** (add inside `<services>` tag):
```xml
<service id="Learning\Bundle\Command\CacheBenchmarkCommand">
    <argument type="service" id="Learning\Bundle\Service\CachedProductViewService"/>
    <argument type="service" id="Doctrine\DBAL\Connection"/>
    <tag name="console.command"/>
</service>
```

**Run the benchmark:**
```bash
cd /path/to/shopware-tutorial-olli
bin/console cache:clear
bin/console learning:cache:benchmark
```

**Expected output:**
```
+------------------+----------+-------------+
| Call             | Time (ms)| Status      |
+------------------+----------+-------------+
| First (miss)     | 45.23    | ❌          |
| Second (hit)     | 0.12     | ✅          |
| Improvement      | 45.11    | 376.9x faster|
+------------------+----------+-------------+
```

✅ **Part 6 Complete!** You've learned how to:
- Write unit tests for cached services
- Mock cache interfaces for isolated testing
- Test cache hit and cache error scenarios
- Benchmark cache performance improvements

---

## Part 7: Exercises (60 minutes)

### Exercise 1: Test Coverage

Achieve 80%+ test coverage for your services. Run:

```bash
cd custom/plugins/LearningBundle
../../../vendor/bin/phpunit -c phpunit.unit.xml --coverage-html coverage/
open coverage/index.html
```

### Exercise 2: API Integration Test

Write an integration test that makes actual HTTP requests to your Store API endpoints.

### Exercise 3: Cache Warmup Command

Create a command that pre-warms the cache by loading popular products and their view counts.

---

## Key Takeaways

✅ **You've learned:**
- Writing unit tests with mocks
- Creating integration tests with real database
- Testing API endpoints
- Shopware's multi-layer caching system
- Implementing cache strategies
- Cache invalidation patterns
- Using cache tags
- Testing cache behavior
- Performance benchmarking

## Testing Best Practices

✅ **DO:**
- Write tests for critical business logic
- Use mocks for external dependencies
- Keep tests fast and isolated
- Test edge cases and error conditions
- Use descriptive test names

❌ **DON'T:**
- Test framework code
- Create brittle tests (too specific)
- Ignore failing tests
- Skip integration tests
- Forget to test cache invalidation

---

## Additional Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Symfony Testing](https://symfony.com/doc/current/testing.html)
- [Symfony Cache](https://symfony.com/doc/current/cache.html)
- [HTTP Caching Guide](https://developer.shopware.com/docs/guides/hosting/performance/caches)
- [Test-Driven Development](https://martinfowler.com/bliki/TestDrivenDevelopment.html)

---

**Estimated Completion Time:** 5-7 hours  
**Difficulty:** Intermediate to Advanced

🎉 Fantastic! Tomorrow is the final project day!
