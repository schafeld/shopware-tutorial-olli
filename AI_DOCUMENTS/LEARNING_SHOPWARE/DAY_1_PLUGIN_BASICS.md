# Day 1: Plugin Basics and Structure

**Duration:** 4-6 hours  
**Goal:** Understand Shopware plugin structure and create your first functional plugin

## Learning Objectives

- Understand Shopware plugin architecture
- Create a basic plugin from scratch
- Learn plugin lifecycle and activation
- Implement a simple service
- Add configuration to your plugin

## Prerequisites

- Working Shopware 6 installation
- Basic understanding of PHP and Symfony
- Composer installed
- IDE setup (PHPStorm recommended)

---

## Part 1: Understanding Plugin Architecture (45 minutes)

### Theory: Plugin Structure

Shopware 6 plugins are Symfony bundles with a specific structure:

```
custom/plugins/YourPlugin/
├── src/
│   ├── YourPlugin.php              # Main plugin class
│   ├── Resources/
│   │   └── config/
│   │       └── services.xml        # Service definitions
│   └── Service/
│       └── YourService.php         # Your services
└── composer.json                    # Plugin metadata
```

**Key Concepts:**
- Plugins extend `Shopware\Core\Framework\Plugin`
- Follow PSR-4 autoloading standards
- Use Symfony's Dependency Injection
- Lifecycle hooks: install, activate, deactivate, uninstall

### Official Documentation

📖 **Read these resources:**
- [Plugin Base Guide](https://developer.shopware.com/docs/guides/plugins/plugins/plugin-base-guide)
- [Plugin Fundamentals](https://developer.shopware.com/docs/guides/plugins/plugins/plugin-fundamentals/plugin-fundamentals)
- [Plugin Lifecycle](https://developer.shopware.com/docs/guides/plugins/plugins/plugin-fundamentals/plugin-lifecycle)

---

## Part 2: Create Your First Plugin (90 minutes)

### Step 1: Generate Plugin Structure

We'll create a "Learning Bundle" plugin that demonstrates basic concepts.

```bash
# Navigate to your Shopware installation
cd /Users/oliverschafeld/workspace/shopware-experiments/shopware-tutorial-olli

# Create plugin directory
mkdir -p custom/plugins/LearningBundle/src
```

### Step 2: Create composer.json

Create `custom/plugins/LearningBundle/composer.json`:

```json
{
    "name": "learning/bundle",
    "description": "Learning plugin for Shopware 6 development",
    "version": "1.0.0",
    "type": "shopware-platform-plugin",
    "license": "MIT",
    "authors": [
        {
            "name": "Your Name"
        }
    ],
    "require": {
        "shopware/core": "~6.5.0"
    },
    "extra": {
        "shopware-plugin-class": "Learning\\Bundle\\LearningBundle",
        "label": {
            "de-DE": "Lern-Plugin",
            "en-GB": "Learning Bundle"
        },
        "description": {
            "de-DE": "Ein Plugin zum Lernen der Shopware 6 Entwicklung",
            "en-GB": "A plugin for learning Shopware 6 development"
        }
    },
    "autoload": {
        "psr-4": {
            "Learning\\Bundle\\": "src/"
        }
    }
}
```

### Step 3: Create Main Plugin Class

Create `custom/plugins/LearningBundle/src/LearningBundle.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class LearningBundle extends Plugin
{
    /**
     * Called when the plugin is installed
     */
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
        // Add installation logic here
    }

    /**
     * Called when the plugin is activated
     */
    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);
        // Add activation logic here
    }

    /**
     * Called when the plugin is deactivated
     */
    public function deactivate(DeactivateContext $deactivateContext): void
    {
        parent::deactivate($deactivateContext);
        // Add deactivation logic here
    }

    /**
     * Called when the plugin is uninstalled
     */
    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);
        
        // Keep user data by default
        if ($uninstallContext->keepUserData()) {
            return;
        }

        // Remove data only if user explicitly wants to
        // We'll implement this in Day 3
    }
}
```

### Step 4: Install and Activate

```bash
# Refresh plugin list
bin/console plugin:refresh

# Install plugin
bin/console plugin:install --activate LearningBundle

# Clear cache
bin/console cache:clear
```

**Expected Output:**
```
Plugin "LearningBundle" has been installed successfully.
Plugin "LearningBundle" has been activated successfully.
```

---

## Part 3: Create a Simple Service (60 minutes)

### Step 1: Create Service Directory and File

Create `custom/plugins/LearningBundle/src/Service/MessageService.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Psr\Log\LoggerInterface;

class MessageService
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function generateWelcomeMessage(string $name): string
    {
        $message = sprintf('Welcome to Shopware Development, %s!', $name);
        
        // Log the message
        $this->logger->info('Welcome message generated', [
            'name' => $name,
            'message' => $message
        ]);

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
                'Service Container'
            ]
        ];
    }
}
```

### Step 2: Register Service

Create `custom/plugins/LearningBundle/src/Resources/config/services.xml`:

```xml
<?xml version="1.0" ?>
<container xmlns="http://symfony.com/schema/dic/services"
           xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
           xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd">

    <services>
        <!-- Register our MessageService -->
        <service id="Learning\Bundle\Service\MessageService">
            <argument type="service" id="logger"/>
        </service>
    </services>
</container>
```

### Step 3: Create a Test Command

Create `custom/plugins/LearningBundle/src/Command/TestMessageCommand.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Learning\Bundle\Service\MessageService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TestMessageCommand extends Command
{
    protected static $defaultName = 'learning:test-message';

    private MessageService $messageService;

    public function __construct(MessageService $messageService)
    {
        parent::__construct();
        $this->messageService = $messageService;
    }

    protected function configure(): void
    {
        $this->setDescription('Test the MessageService')
            ->addArgument('name', InputArgument::OPTIONAL, 'Your name', 'Developer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');

        // Test message generation
        $message = $this->messageService->generateWelcomeMessage($name);
        $io->success($message);

        // Display plugin info
        $info = $this->messageService->getPluginInfo();
        $io->section('Plugin Information');
        $io->listing($info['features']);

        return Command::SUCCESS;
    }
}
```

### Step 4: Register Command

Update `services.xml` to include the command:

```xml
<?xml version="1.0" ?>
<container xmlns="http://symfony.com/schema/dic/services"
           xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
           xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd">

    <services>
        <!-- Register our MessageService -->
        <service id="Learning\Bundle\Service\MessageService">
            <argument type="service" id="logger"/>
        </service>

        <!-- Register our Command -->
        <service id="Learning\Bundle\Command\TestMessageCommand">
            <argument type="service" id="Learning\Bundle\Service\MessageService"/>
            <tag name="console.command"/>
        </service>
    </services>
</container>
```

### Step 5: Test Your Service

```bash
# Clear cache
bin/console cache:clear

# Run the command
bin/console learning:test-message "Your Name"
```

**Expected Output:**
```
[OK] Welcome to Shopware Development, Your Name!

Plugin Information
==================
* Message Generation
* Logging
* Service Container
```

---

## Part 4: Add Plugin Configuration (60 minutes)

### Step 1: Create Configuration Schema

Create `custom/plugins/LearningBundle/src/Resources/config/config.xml`:

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

### Step 2: Use Configuration in Service

Update `MessageService.php` to use system config:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class MessageService
{
    private LoggerInterface $logger;
    private SystemConfigService $systemConfigService;

    public function __construct(
        LoggerInterface $logger,
        SystemConfigService $systemConfigService
    ) {
        $this->logger = $logger;
        $this->systemConfigService = $systemConfigService;
    }

    public function generateWelcomeMessage(string $name): string
    {
        $prefix = $this->systemConfigService->get('LearningBundle.config.welcomePrefix') 
            ?? 'Welcome to Shopware Development';
        
        $format = $this->systemConfigService->get('LearningBundle.config.messageFormat') 
            ?? 'simple';

        $message = match($format) {
            'detailed' => sprintf('%s, %s! Today is %s', $prefix, $name, date('Y-m-d')),
            'custom' => sprintf('[CUSTOM] %s - %s', $prefix, $name),
            default => sprintf('%s, %s!', $prefix, $name),
        };
        
        // Check if logging is enabled
        $enableLogging = $this->systemConfigService->get('LearningBundle.config.enableLogging') 
            ?? true;
        
        if ($enableLogging) {
            $this->logger->info('Welcome message generated', [
                'name' => $name,
                'message' => $message,
                'format' => $format
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
                'Configuration Management'
            ]
        ];
    }
}
```

### Step 3: Update Service Registration

Update `services.xml`:

```xml
<service id="Learning\Bundle\Service\MessageService">
    <argument type="service" id="logger"/>
    <argument type="service" id="Shopware\Core\System\SystemConfig\SystemConfigService"/>
</service>
```

### Step 4: Test Configuration

```bash
# Clear cache
bin/console cache:clear

# Reinstall plugin to register configuration
bin/console plugin:uninstall LearningBundle
bin/console plugin:install --activate LearningBundle

# Test with default config
bin/console learning:test-message "Olli"
```

Now go to **Administration > Settings > System > Plugins > LearningBundle** and modify the configuration values, then test again!

---

## Part 5: Exercises (60 minutes)

### Exercise 1: Add New Configuration
Add a new configuration field for "Greeting Language" (English, German, Spanish) and implement multilingual greetings.

### Exercise 2: Create a Counter Service
Create a new service that counts how many messages have been generated and stores the count in a file.

### Exercise 3: Add Validation
Add validation to ensure the name parameter is not empty and contains only letters.

---

## Key Takeaways

✅ **You've learned:**
- Shopware plugin structure and file organization
- Plugin lifecycle methods (install, activate, deactivate, uninstall)
- Service creation and dependency injection
- Console command development
- Plugin configuration system
- PSR-4 autoloading and namespacing

## Troubleshooting

**Problem:** Plugin doesn't appear in plugin list
- Run `bin/console plugin:refresh`
- Check `composer.json` for correct `shopware-plugin-class`

**Problem:** Service not found
- Verify `services.xml` syntax
- Clear cache: `bin/console cache:clear`
- Check service ID matches class namespace

**Problem:** Configuration not showing
- Ensure `config.xml` is in correct directory
- Reinstall plugin to register configuration
- Check XML syntax

## Next Steps

Tomorrow we'll dive into:
- Event System and Subscribers
- Dependency Injection patterns
- Decorating existing services
- Creating custom business events

## Additional Resources

- [Shopware Plugin Development Documentation](https://developer.shopware.com/docs/guides/plugins/plugins/)
- [Symfony Service Container](https://symfony.com/doc/current/service_container.html)
- [Symfony Console Commands](https://symfony.com/doc/current/console.html)
- [PSR-4 Autoloading](https://www.php-fig.org/psr/psr-4/)

---

**Estimated Completion Time:** 4-6 hours  
**Difficulty:** Beginner to Intermediate

🎉 Congratulations on completing Day 1!
