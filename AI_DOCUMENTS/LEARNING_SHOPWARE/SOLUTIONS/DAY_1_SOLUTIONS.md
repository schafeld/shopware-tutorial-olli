# Day 1: Complete Exercise Solutions

> **Note:** These are complete, working solutions. Try to solve the exercises yourself first before looking at these!

🤓 **Developer Note:** The AI time estimates are very inaccurate. There's no way someone without any Symfony or Shopware experience solves theses exercises that quickly. Even copying the solution (typing to learn, changing tiny details, not just tabbing through code completion) and debugging typos easily takes two or three times longer than in these estimates.

---

## Exercise 1: Add Multilingual Configuration (30-45 min)

### Step 1: Update config.xml

Update `custom/plugins/LearningBundle/src/Resources/config/config.xml` to add language field:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/shopware/platform/trunk/src/Core/System/SystemConfig/Schema/config.xsd">

    <card>
        <title>Basic Configuration</title>
        <title lang="de-DE">Basis Konfiguration</title>

        <input-field>
            <name>welcomePrefix</name>
            <label>Welcome Message Prefix</label>
            <label lang="de-DE">Willkommensnachricht Präfix</label>
            <defaultValue>Welcome to Shopware Development</defaultValue>
            <helpText>This prefix will be used in welcome messages</helpText>
        </input-field>

        <!-- NEW: Language Selection -->
        <input-field type="single-select">
            <name>greetingLanguage</name>
            <label>Greeting Language</label>
            <label lang="de-DE">Begrüßungssprache</label>
            <options>
                <option>
                    <id>en</id>
                    <name>English</name>
                </option>
                <option>
                    <id>de</id>
                    <name>German (Deutsch)</name>
                </option>
                <option>
                    <id>es</id>
                    <name>Spanish (Español)</name>
                </option>
            </options>
            <defaultValue>en</defaultValue>
            <helpText>Select the language for greeting messages</helpText>
        </input-field>

        <input-field type="bool">
            <name>enableLogging</name>
            <label>Enable Logging</label>
            <label lang="de-DE">Logging aktivieren</label>
            <defaultValue>true</defaultValue>
        </input-field>

        <input-field type="int">
            <name>maxMessages</name>
            <label>Maximum Messages per Day</label>
            <label lang="de-DE">Maximale Nachrichten pro Tag</label>
            <defaultValue>100</defaultValue>
        </input-field>
    </card>

    <card>
        <title>Advanced Settings</title>
        <title lang="de-DE">Erweiterte Einstellungen</title>

        <input-field type="single-select">
            <name>messageFormat</name>
            <label>Message Format</label>
            <label lang="de-DE">Nachrichtenformat</label>
            <options>
                <option>
                    <id>simple</id>
                    <name>Simple</name>
                    <name lang="de-DE">Einfach</name>
                </option>
                <option>
                    <id>detailed</id>
                    <name>Detailed</name>
                    <name lang="de-DE">Detailliert</name>
                </option>
                <option>
                    <id>custom</id>
                    <name>Custom</name>
                    <name lang="de-DE">Benutzerdefiniert</name>
                </option>
            </options>
            <defaultValue>simple</defaultValue>
        </input-field>
    </card>
</config>
```

### Step 2: Update MessageService.php

Update the service to use the language setting:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class MessageService
{
    private LoggerInterface $logger;
    private SystemConfigService $systemConfigService;

    // Translation arrays for different languages
    private const TRANSLATIONS = [
        'en' => [
            'welcome' => 'Welcome to Shopware Development',
            'goodbye' => 'Goodbye',
            'hello' => 'Hello',
        ],
        'de' => [
            'welcome' => 'Willkommen zur Shopware-Entwicklung',
            'goodbye' => 'Auf Wiedersehen',
            'hello' => 'Hallo',
        ],
        'es' => [
            'welcome' => 'Bienvenido al desarrollo de Shopware',
            'goodbye' => 'Adiós',
            'hello' => 'Hola',
        ],
    ];

    public function __construct(
        LoggerInterface $logger,
        SystemConfigService $systemConfigService
    ) {
        $this->logger = $logger;
        $this->systemConfigService = $systemConfigService;
    }

    public function generateWelcomeMessage(string $name): string
    {
        // Get configured language (default: en)
        $language = $this->systemConfigService->get('LearningBundle.config.greetingLanguage') ?? 'en';
        
        // Get the prefix based on language
        $prefix = self::TRANSLATIONS[$language]['welcome'] ?? self::TRANSLATIONS['en']['welcome'];
        
        // Get message format
        $format = $this->systemConfigService->get('LearningBundle.config.messageFormat') ?? 'simple';

        $message = match($format) {
            'detailed' => sprintf('%s, %s! Today is %s', $prefix, $name, date('Y-m-d')),
            'custom' => sprintf('[%s] %s - %s', strtoupper($language), $prefix, $name),
            default => sprintf('%s, %s!', $prefix, $name),
        };
        
        // Check if logging is enabled
        $enableLogging = $this->systemConfigService->get('LearningBundle.config.enableLogging') ?? true;
        
        if ($enableLogging) {
            $this->logger->info('Welcome message generated', [
                'name' => $name,
                'message' => $message,
                'format' => $format,
                'language' => $language,
            ]);
        }

        return $message;
    }

    public function getPluginInfo(): array
    {
        return [
            'name' => 'LearningBundle',
            'version' => '1.0.0',
            'author' => 'Learning Developer',
            'features' => [
                'Message Generation',
                'Logging',
                'Service Container',
                'Configuration Management',
                'Multi-language Support'
            ]
        ];
    }

    /**
     * Get a greeting in the configured language
     */
    public function getGreeting(string $type = 'hello'): string
    {
        $language = $this->systemConfigService->get('LearningBundle.config.greetingLanguage') ?? 'en';
        return self::TRANSLATIONS[$language][$type] ?? self::TRANSLATIONS['en'][$type];
    }
}
```

### Step 3: Test the Solution

```bash
# Clear cache and reinstall plugin
bin/console cache:clear
bin/console plugin:uninstall LearningBundle
bin/console plugin:install --activate LearningBundle

# Test with default (English)
bin/console learning:test-message "Olli"
# Output: Welcome to Shopware Development, Olli!

# Now change language in Administration:
# Settings > System > Plugins > LearningBundle
# Set "Greeting Language" to "German"

# Test again
bin/console cache:clear
bin/console learning:test-message "Olli"
# Output: Willkommen zur Shopware-Entwicklung, Olli!
```

---

## Exercise 2: Create Counter Service (45-60 min)

### Step 1: Create CounterService.php

Create `custom/plugins/LearningBundle/src/Service/CounterService.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Psr\Log\LoggerInterface;

class CounterService
{
    private const COUNTER_FILE = 'var/learning_counter.txt';
    
    private LoggerInterface $logger;
    private string $counterFilePath;

    public function __construct(LoggerInterface $logger, string $projectDir)
    {
        $this->logger = $logger;
        // Use absolute path based on project directory
        $this->counterFilePath = $projectDir . '/' . self::COUNTER_FILE;
    }

    /**
     * Increment the counter and return the new value
     */
    public function increment(): int
    {
        $currentCount = $this->getCount();
        $newCount = $currentCount + 1;
        
        $this->saveCount($newCount);
        
        $this->logger->info('Counter incremented', [
            'old_value' => $currentCount,
            'new_value' => $newCount,
        ]);

        return $newCount;
    }

    /**
     * Get the current counter value
     */
    public function getCount(): int
    {
        if (!file_exists($this->counterFilePath)) {
            return 0;
        }

        $content = file_get_contents($this->counterFilePath);
        
        if ($content === false) {
            $this->logger->error('Failed to read counter file', [
                'file_path' => $this->counterFilePath,
            ]);
            return 0;
        }

        return (int) $content;
    }

    /**
     * Reset the counter to zero
     */
    public function reset(): void
    {
        $this->saveCount(0);
        $this->logger->info('Counter reset to zero');
    }

    /**
     * Save the counter value to file
     */
    private function saveCount(int $count): void
    {
        // Ensure directory exists
        $directory = dirname($this->counterFilePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $result = file_put_contents($this->counterFilePath, (string) $count);
        
        if ($result === false) {
            $this->logger->error('Failed to write counter file', [
                'file_path' => $this->counterFilePath,
                'count' => $count,
            ]);
        }
    }

    /**
     * Get statistics about the counter
     */
    public function getStatistics(): array
    {
        $count = $this->getCount();
        $fileExists = file_exists($this->counterFilePath);
        $lastModified = $fileExists ? filemtime($this->counterFilePath) : null;

        return [
            'count' => $count,
            'file_exists' => $fileExists,
            'file_path' => $this->counterFilePath,
            'last_modified' => $lastModified ? date('Y-m-d H:i:s', $lastModified) : null,
        ];
    }
}
```

### Step 2: Register CounterService in services.xml

Update `custom/plugins/LearningBundle/src/Resources/config/services.xml`:

```xml
<?xml version="1.0" ?>
<container xmlns="http://symfony.com/schema/dic/services"
           xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
           xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd">

    <services>
        <!-- Register CounterService -->
        <service id="Learning\Bundle\Service\CounterService">
            <argument type="service" id="logger"/>
            <argument type="string">%kernel.project_dir%</argument>
        </service>

        <!-- Register MessageService with CounterService -->
        <service id="Learning\Bundle\Service\MessageService">
            <argument type="service" id="logger"/>
            <argument type="service" id="Shopware\Core\System\SystemConfig\SystemConfigService"/>
            <argument type="service" id="Learning\Bundle\Service\CounterService"/>
        </service>

        <!-- Register Command -->
        <service id="Learning\Bundle\Command\TestMessageCommand">
            <argument type="service" id="Learning\Bundle\Service\MessageService"/>
            <tag name="console.command"/>
        </service>
    </services>
</container>
```

### Step 3: Update MessageService to Use CounterService

Update `custom/plugins/LearningBundle/src/Service/MessageService.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class MessageService
{
    private LoggerInterface $logger;
    private SystemConfigService $systemConfigService;
    private CounterService $counterService;

    private const TRANSLATIONS = [
        'en' => [
            'welcome' => 'Welcome to Shopware Development',
            'goodbye' => 'Goodbye',
            'hello' => 'Hello',
        ],
        'de' => [
            'welcome' => 'Willkommen zur Shopware-Entwicklung',
            'goodbye' => 'Auf Wiedersehen',
            'hello' => 'Hallo',
        ],
        'es' => [
            'welcome' => 'Bienvenido al desarrollo de Shopware',
            'goodbye' => 'Adiós',
            'hello' => 'Hola',
        ],
    ];

    public function __construct(
        LoggerInterface $logger,
        SystemConfigService $systemConfigService,
        CounterService $counterService
    ) {
        $this->logger = $logger;
        $this->systemConfigService = $systemConfigService;
        $this->counterService = $counterService;
    }

    public function generateWelcomeMessage(string $name): string
    {
        // Increment the counter each time a message is generated
        $messageNumber = $this->counterService->increment();

        $language = $this->systemConfigService->get('LearningBundle.config.greetingLanguage') ?? 'en';
        $prefix = self::TRANSLATIONS[$language]['welcome'] ?? self::TRANSLATIONS['en']['welcome'];
        $format = $this->systemConfigService->get('LearningBundle.config.messageFormat') ?? 'simple';

        $message = match($format) {
            'detailed' => sprintf('%s, %s! Today is %s (Message #%d)', $prefix, $name, date('Y-m-d'), $messageNumber),
            'custom' => sprintf('[%s] %s - %s [#%d]', strtoupper($language), $prefix, $name, $messageNumber),
            default => sprintf('%s, %s! (Message #%d)', $prefix, $name, $messageNumber),
        };
        
        $enableLogging = $this->systemConfigService->get('LearningBundle.config.enableLogging') ?? true;
        
        if ($enableLogging) {
            $this->logger->info('Welcome message generated', [
                'name' => $name,
                'message' => $message,
                'format' => $format,
                'language' => $language,
                'message_number' => $messageNumber,
            ]);
        }

        return $message;
    }

    public function getPluginInfo(): array
    {
        $stats = $this->counterService->getStatistics();

        return [
            'name' => 'LearningBundle',
            'version' => '1.0.0',
            'author' => 'Learning Developer',
            'features' => [
                'Message Generation',
                'Logging',
                'Service Container',
                'Configuration Management',
                'Multi-language Support',
                'Message Counter'
            ],
            'total_messages_generated' => $stats['count'],
        ];
    }

    public function getGreeting(string $type = 'hello'): string
    {
        $language = $this->systemConfigService->get('LearningBundle.config.greetingLanguage') ?? 'en';
        return self::TRANSLATIONS[$language][$type] ?? self::TRANSLATIONS['en'][$type];
    }
}
```

### Step 4: Create Command to Manage Counter

Create `custom/plugins/LearningBundle/src/Command/CounterCommand.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Learning\Bundle\Service\CounterService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CounterCommand extends Command
{
    private CounterService $counterService;

    public function __construct(CounterService $counterService)
    {
        parent::__construct();
        $this->counterService = $counterService;
    }

    protected function configure(): void
    {
        $this
            ->setName('learning:counter')
            ->setDescription('Manage the message counter')
            ->addOption('show', 's', InputOption::VALUE_NONE, 'Show counter statistics')
            ->addOption('reset', 'r', InputOption::VALUE_NONE, 'Reset counter to zero')
            ->addOption('increment', 'i', InputOption::VALUE_NONE, 'Increment counter manually');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('reset')) {
            $this->counterService->reset();
            $io->success('Counter has been reset to zero');
            return Command::SUCCESS;
        }

        if ($input->getOption('increment')) {
            $newCount = $this->counterService->increment();
            $io->success(sprintf('Counter incremented to: %d', $newCount));
            return Command::SUCCESS;
        }

        // Default: show statistics
        $stats = $this->counterService->getStatistics();
        
        $io->title('Message Counter Statistics');
        $io->table(
            ['Metric', 'Value'],
            [
                ['Total Messages', $stats['count']],
                ['File Path', $stats['file_path']],
                ['File Exists', $stats['file_exists'] ? 'Yes' : 'No'],
                ['Last Modified', $stats['last_modified'] ?? 'Never'],
            ]
        );

        return Command::SUCCESS;
    }
}
```

### Step 5: Register CounterCommand

Update `services.xml`:

```xml
<!-- Register CounterCommand -->
<service id="Learning\Bundle\Command\CounterCommand">
    <argument type="service" id="Learning\Bundle\Service\CounterService"/>
    <tag name="console.command"/>
</service>
```

### Step 6: Test the Solution

```bash
# Clear cache
bin/console cache:clear

# Show counter statistics
bin/console learning:counter --show
# Output: Total Messages: 0

# Generate some messages
bin/console learning:test-message "Olli"
# Output: Welcome to Shopware Development, Olli! (Message #1)

bin/console learning:test-message "Max"
# Output: Welcome to Shopware Development, Max! (Message #2)

bin/console learning:test-message "Anna"
# Output: Welcome to Shopware Development, Anna! (Message #3)

# For the curious: The logging strings with the names should now be in the Symfony log:
tail ./var/log/dev.log

# Check counter again
bin/console learning:counter --show
# Output: Total Messages: 3

# Reset counter
bin/console learning:counter --reset

# Verify reset
bin/console learning:counter --show
# Output: Total Messages: 0
```

---

## Exercise 3: Add Validation (30-45 min)

### Step 1: Create ValidationService.php

Create `custom/plugins/LearningBundle/src/Service/ValidationService.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Learning\Bundle\Exception\ValidationException;

class ValidationService
{
    /**
     * Validate a name string
     * 
     * @throws ValidationException
     */
    public function validateName(string $name): void
    {
        // Check if empty
        if (empty(trim($name))) {
            throw new ValidationException('Name cannot be empty');
        }

        // Check minimum length
        if (strlen($name) < 2) {
            throw new ValidationException('Name must be at least 2 characters long');
        }

        // Check maximum length
        if (strlen($name) > 50) {
            throw new ValidationException('Name must not exceed 50 characters');
        }

        // Check if contains only letters, spaces, and common name characters
        if (!preg_match('/^[a-zA-ZÀ-ÿ\s\'-]+$/u', $name)) {
            throw new ValidationException(
                'Name can only contain letters, spaces, hyphens, and apostrophes'
            );
        }
    }

    /**
     * Sanitize a name string
     */
    public function sanitizeName(string $name): string
    {
        // Trim whitespace
        $name = trim($name);

        // Remove multiple spaces
        $name = preg_replace('/\s+/', ' ', $name);

        // Capitalize first letter of each word
        $name = ucwords(strtolower($name));

        return $name;
    }

    /**
     * Validate and sanitize a name in one step
     * 
     * @throws ValidationException
     */
    public function processName(string $name): string
    {
        $sanitized = $this->sanitizeName($name);
        $this->validateName($sanitized);
        return $sanitized;
    }
}
```

### Step 2: Create Custom Exception

Create `custom/plugins/LearningBundle/src/Exception/ValidationException.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Exception;

class ValidationException extends \Exception
{
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
```

This custom exception is actually required only in Exercise 3 (see below).

### Step 3: Register ValidationService

Update `services.xml`:

```xml
<!-- Register ValidationService -->
<service id="Learning\Bundle\Service\ValidationService">
    <!-- No dependencies -->
</service>
```

### Step 4: Update MessageService to Use Validation

Update `MessageService.php` constructor and method:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Learning\Bundle\Exception\ValidationException;
use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class MessageService
{
    private LoggerInterface $logger;
    private SystemConfigService $systemConfigService;
    private CounterService $counterService;
    private ValidationService $validationService;

    // ... translations array ...

    public function __construct(
        LoggerInterface $logger,
        SystemConfigService $systemConfigService,
        CounterService $counterService,
        ValidationService $validationService
    ) {
        $this->logger = $logger;
        $this->systemConfigService = $systemConfigService;
        $this->counterService = $counterService;
        $this->validationService = $validationService;
    }

    /**
     * @throws ValidationException
     */
    public function generateWelcomeMessage(string $name): string
    {
        // Validate and sanitize the name
        try {
            $name = $this->validationService->processName($name);
        } catch (ValidationException $e) {
            $this->logger->warning('Name validation failed', [
                'name' => $name,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        $messageNumber = $this->counterService->increment();
        $language = $this->systemConfigService->get('LearningBundle.config.greetingLanguage') ?? 'en';
        $prefix = self::TRANSLATIONS[$language]['welcome'] ?? self::TRANSLATIONS['en']['welcome'];
        $format = $this->systemConfigService->get('LearningBundle.config.messageFormat') ?? 'simple';

        $message = match($format) {
            'detailed' => sprintf('%s, %s! Today is %s (Message #%d)', $prefix, $name, date('Y-m-d'), $messageNumber),
            'custom' => sprintf('[%s] %s - %s [#%d]', strtoupper($language), $prefix, $name, $messageNumber),
            default => sprintf('%s, %s! (Message #%d)', $prefix, $name, $messageNumber),
        };
        
        $enableLogging = $this->systemConfigService->get('LearningBundle.config.enableLogging') ?? true;
        
        if ($enableLogging) {
            $this->logger->info('Welcome message generated', [
                'name' => $name,
                'message' => $message,
                'format' => $format,
                'language' => $language,
                'message_number' => $messageNumber,
            ]);
        }

        return $message;
    }

    // ... rest of methods ...
}
```

Update service registration in `services.xml`:

```xml
<service id="Learning\Bundle\Service\MessageService">
    <argument type="service" id="logger"/>
    <argument type="service" id="Shopware\Core\System\SystemConfig\SystemConfigService"/>
    <argument type="service" id="Learning\Bundle\Service\CounterService"/>
    <argument type="service" id="Learning\Bundle\Service\ValidationService"/>
</service>
```

### Step 5: Update Command to Handle Validation Errors

Update `TestMessageCommand.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Learning\Bundle\Exception\ValidationException;
use Learning\Bundle\Service\MessageService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TestMessageCommand extends Command
{
    // protected static $defaultName = 'learning:test-message'; // deprecated

    private MessageService $messageService;

    public function __construct(MessageService $messageService)
    {
        parent::__construct();
        $this->messageService = $messageService;
    }

    protected function configure(): void
    {
        $this
            ->setName('learning:test-message') // this is the way!
            ->setDescription('Test the MessageService')
            ->addArgument('name', InputArgument::OPTIONAL, 'Your name', 'Developer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');

        try {
            // Test message generation
            $message = $this->messageService->generateWelcomeMessage($name);
            $io->success($message);

            // Display plugin info
            $info = $this->messageService->getPluginInfo();
            $io->section('Plugin Information');
            $io->listing($info['features']);
            $io->text(sprintf('Total messages generated: %d', $info['total_messages_generated']));

            return Command::SUCCESS;

        } catch (ValidationException $e) {
            $io->error('Validation Error: ' . $e->getMessage());
            $io->note('Please provide a valid name containing only letters.');
            return Command::FAILURE;
        } catch (\Throwable $e) {
            $io->error('An unexpected error occurred: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
```

### Step 6: Test the Solution

```bash
# Clear cache
bin/console cache:clear

# Test with valid name
bin/console learning:test-message "Olli"
# ✓ Success

# Test with empty name
bin/console learning:test-message ""
# ✗ Error: Name cannot be empty

# Test with numbers
bin/console learning:test-message "User123"
# ✗ Error: Name can only contain letters, spaces, hyphens, and apostrophes

# Test with special characters
bin/console learning:test-message "User@#$"
# ✗ Error: Name can only contain letters, spaces, hyphens, and apostrophes

# Test with valid name containing hyphen
bin/console learning:test-message "Jean-Pierre"
# ✓ Success: Welcome to Shopware Development, Jean-Pierre!

# Test with lowercase name (should be capitalized)
bin/console learning:test-message "john doe"
# ✓ Success: Welcome to Shopware Development, John Doe!

# Test with very long name
bin/console learning:test-message "ThisIsAnExtremelyLongNameThatExceedsFiftyCharactersAndShouldBeRejected"
# ✗ Error: Name must not exceed 50 characters
```

---

## Summary

You've now completed all Day 1 exercises! You've learned:

✅ **Exercise 1:** Plugin configuration with multiple options and language support
✅ **Exercise 2:** File operations, service dependency injection, and state management
✅ **Exercise 3:** Input validation, custom exceptions, and error handling

### Key Concepts Mastered:

- **Configuration Management:** XML schema, config fields, reading system config
- **Service Architecture:** Multiple services working together via DI
- **File Operations:** Reading/writing files in Shopware
- **Validation:** Input sanitization and validation patterns
- **Error Handling:** Custom exceptions and graceful error recovery
- **Command Development:** Interactive CLI tools with options and error handling

### Next Steps:

Continue to Day 2 to learn about events and subscribers!
