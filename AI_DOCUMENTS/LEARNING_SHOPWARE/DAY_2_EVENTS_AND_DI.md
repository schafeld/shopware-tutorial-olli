# Day 2: Event System and Dependency Injection

**Duration:** 1.5-2 days (10-14 hours with breaks)  
**Goal:** Master Shopware's event-driven architecture, create subscribers, and understand advanced DI patterns

> **Note for Beginners:** Event systems can be tricky at first. Expect to spend extra time understanding how events flow through the system. Use logging extensively to see what's happening!

## Learning Objectives

- Understand Shopware's event-driven architecture
- Create event subscribers and listeners
- Work with business events and lifecycle hooks
- Master dependency injection patterns
- Decorate existing services
- Create custom events

## Prerequisites

- Completed Day 1 (Plugin basics)
- Understanding of Observer pattern
- Familiarity with Symfony EventDispatcher

---

## Part 1: Understanding the Event System (45 minutes)

### Theory: Event-Driven Architecture

Shopware 6 uses Symfony's EventDispatcher component extensively. Events are dispatched at key points in the application lifecycle.

**Event Types:**
1. **Business Events** - Domain logic (order placed, customer registered)
2. **Hook Events** - Template rendering, data loading
3. **Lifecycle Events** - Entity CRUD operations
4. **System Events** - Cache clearing, plugin installation

**Event Flow:**
```
Action → Event Dispatched → Subscribers Execute → Result
```

### Official Documentation

📖 **Read these resources:**
- [Add Subscriber](https://developer.shopware.com/docs/guides/plugins/plugins/plugin-fundamentals/listening-to-events.html#creating-your-own-subscriber)
- [Listening to Events](https://developer.shopware.com/docs/guides/plugins/plugins/plugin-fundamentals/listening-to-events)
- [Business Events](https://docs.shopware.com/en/shopware-6-en/settings/Business-Events)
- [Symfony Event Dispatcher](https://symfony.com/doc/current/components/event_dispatcher.html)

---

## Part 2: Create Event Subscribers (90 minutes)

### Step 1: Simple Event Subscriber

Create `custom/plugins/LearningBundle/src/Subscriber/CustomerSubscriber.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Subscriber;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Customer\Event\CustomerLoginEvent;
use Shopware\Core\Checkout\Customer\Event\CustomerLogoutEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CustomerSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Returns an array of events this subscriber wants to listen to
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CustomerLoginEvent::class => 'onCustomerLogin',
            CustomerLogoutEvent::class => 'onCustomerLogout',
        ];
    }

    /**
     * Called when a customer logs in
     */
    public function onCustomerLogin(CustomerLoginEvent $event): void
    {
        $customer = $event->getCustomer();
        
        $this->logger->info('Customer logged in', [
            'customer_id' => $customer->getId(),
            'email' => $customer->getEmail(),
            'first_name' => $customer->getFirstName(),
            'last_name' => $customer->getLastName(),
        ]);

        // You can add custom logic here:
        // - Track login times
        // - Send notifications
        // - Update customer data
        // - Trigger third-party integrations
    }

    /**
     * Called when a customer logs out
     */
    public function onCustomerLogout(CustomerLogoutEvent $event): void
    {
        $this->logger->info('Customer logged out', [
            // `getContextToken` doesn't exist
            // 'context_token' => $event->getContextToken(),
            // function isn't used anyway?
            'customer_id' => $customer->getId(),
            'email' => $customer->getEmail(),
        ]);
    }
}
```

### Step 2: Register Subscriber

Update `custom/plugins/LearningBundle/src/Resources/config/services.xml`:

```xml
<!-- Customer Event Subscriber -->
<service id="Learning\Bundle\Subscriber\CustomerSubscriber">
    <argument type="service" id="logger"/>
    <tag name="kernel.event_subscriber"/>
</service>
```

### Step 3: Test Customer Events

```bash
# Clear cache
bin/console cache:clear

# Watch logs in another terminal
tail -f var/log/dev.log | grep "Customer logged"

# Now log in/out in the storefront and watch the logs!
```

### Step 4: Product Event Subscriber

Create `custom/plugins/LearningBundle/src/Subscriber/ProductSubscriber.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Subscriber;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
            ProductEvents::PRODUCT_WRITTEN_EVENT => 'onProductWritten',
        ];
    }

    /**
     * Called when a product is created or updated
     */
    public function onProductWritten(EntityWrittenEvent $event): void
    {
        foreach ($event->getWriteResults() as $writeResult) {
            $payload = $writeResult->getPayload();
            
            $this->logger->info('Product modified', [
                'product_id' => $writeResult->getPrimaryKey(),
                'operation' => $writeResult->getOperation(),
                'product_name' => $payload['name'] ?? 'N/A',
            ]);

            // Custom logic examples:
            // - Sync with external systems
            // - Update search indices
            // - Trigger cache invalidation
            // - Send notifications to admins
        }
    }
}
```

Register in `services.xml`:

```xml
<service id="Learning\Bundle\Subscriber\ProductSubscriber">
    <argument type="service" id="logger"/>
    <tag name="kernel.event_subscriber"/>
</service>
```

---

## Part 3: Advanced Event Handling (90 minutes)

> **Real-World Use Cases:**
>
> - **Order Events:** Send confirmation emails, notify warehouse systems, update ERP, trigger fulfillment processes
> - **Cart Events:** Apply automatic discounts, enforce purchase limits, add free gift products, track abandoned carts
> - **State Machine Events:** Integrate with shipping providers, update accounting systems, send SMS notifications

### Step 1: Order State Change Subscriber

Create `custom/plugins/LearningBundle/src/Subscriber/OrderSubscriber.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Subscriber;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Shopware\Core\System\StateMachine\Event\StateMachineStateChangeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'state_machine.order.state_changed' => 'onOrderStateChange',
        ];
    }

    public function onOrderStateChange(StateMachineStateChangeEvent $event): void
    {
        $fromState = $event->getTransition()->getFromPlace()->getName();
        $toState = $event->getTransition()->getToPlace()->getName();
        
        $this->logger->info('Order state changed', [
            'order_id' => $event->getTransition()->getEntityId(),
            'from_state' => $fromState,
            'to_state' => $toState,
        ]);

        // Implement custom logic based on state transitions
        if ($toState === 'completed') {
            $this->handleOrderCompleted($event);
        } elseif ($toState === 'cancelled') {
            $this->handleOrderCancelled($event);
        }
    }

    private function handleOrderCompleted(StateMachineStateChangeEvent $event): void
    {
        // Example: Send thank you email, update inventory, notify warehouse
        $this->logger->info('Order completed - triggering completion workflow', [
            'order_id' => $event->getTransition()->getEntityId(),
        ]);
    }

    private function handleOrderCancelled(StateMachineStateChangeEvent $event): void
    {
        // Example: Restore inventory, send cancellation email
        $this->logger->info('Order cancelled - triggering cancellation workflow', [
            'order_id' => $event->getTransition()->getEntityId(),
        ]);
    }
}
```

### Step 2: Cart Events

Create `custom/plugins/LearningBundle/src/Subscriber/CartSubscriber.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Subscriber;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Event\BeforeLineItemAddedEvent;
use Shopware\Core\Checkout\Cart\Event\CartCreatedEvent;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CartSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CartCreatedEvent::class => 'onCartCreated',
            BeforeLineItemAddedEvent::class => 'onBeforeLineItemAdded',
        ];
    }

    public function onCartCreated(CartCreatedEvent $event): void
    {
        $this->logger->info('New cart created', [
            'cart_token' => $event->getCart()->getToken(),
        ]);
    }

    public function onBeforeLineItemAdded(BeforeLineItemAddedEvent $event): void
    {
        $lineItem = $event->getLineItem();
        
        $this->logger->info('Item being added to cart', [
            'product_id' => $lineItem->getReferencedId(),
            'quantity' => $lineItem->getQuantity(),
            'type' => $lineItem->getType(),
        ]);

        // You can modify or validate the line item here
        // Example: Apply business rules, check stock, add free items
        
        if ($lineItem->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE) {
            // Add custom payload or modify quantity based on rules
            $lineItem->setPayloadValue('added_by_plugin', true);
        }
    }
}
```

Register both subscribers in `services.xml`:

```xml
<!-- Order State Subscriber -->
<service id="Learning\Bundle\Subscriber\OrderSubscriber">
    <argument type="service" id="logger"/>
    <tag name="kernel.event_subscriber"/>
</service>

<!-- Cart Subscriber -->
<service id="Learning\Bundle\Subscriber\CartSubscriber">
    <argument type="service" id="logger"/>
    <tag name="kernel.event_subscriber"/>
</service>
```

### Testing Part 3: Advanced Event Handling

```bash
# Clear cache first
bin/console cache:clear

# Verify subscribers are registered
bin/console debug:event-dispatcher | grep -E "(OrderSubscriber|CartSubscriber)"

# Watch logs in real-time
# too verbose:
tail -f var/log/dev.log | grep -E "(Order|Cart|Item being added)"
# this triggers only when an item is added to cart:
tail -f var/log/dev.log | grep -E "Item being added)"
```

**Manual Testing Steps:**

1. **Test Cart Events:**
   - Open your storefront in a browser
   - Browse to any product page
   - Add a product to cart (triggers `BeforeLineItemAddedEvent`)
   - Check logs: `grep "Item being added" var/log/dev.log`
   - First cart creation also triggers `CartCreatedEvent`

2. **Test Order State Events:**
   - Log into Administration (usually `http://localhost/admin`)
   - Go to Orders → Overview
   - Create a test order or select existing one
   - Change order state (Open → In Progress → Completed)
   - Check logs: `grep "Order state changed" var/log/dev.log`
   - Alternative: Use demo data if available

3. **Programmatic Testing with CLI:**

   ```bash
   # Create a test command to trigger events
   bin/console debug:event-dispatcher state_machine.order.state_changed
   ```

> **💡 Testing Tip:** If you don't have test orders, install demo data:
>
> ```bash
> bin/console framework:demodata --products=10 --orders=5
> ```

---

## Part 4: Service Decoration (75 minutes)

> **Real-World Use Cases:**
>
> - **Price Calculators:** Add custom pricing logic (B2B discounts, loyalty points, regional pricing)
> - **Product Loaders:** Enrich product data from external sources (reviews, stock from ERP)
> - **Email Services:** Add custom headers, tracking pixels, or modify templates
> - **Search Services:** Add custom filters, boost certain products, integrate AI recommendations

### Theory: Service Decoration

Service decoration allows you to extend or modify existing Shopware services without changing core code.

**Pattern:**

```text
Original Service → Your Decorator → Rest of System
```

### Step 1: Decorate Price Calculator

Create `custom/plugins/LearningBundle/src/Service/Decorator/CustomPriceCalculator.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service\Decorator;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * This is an example decorator - use with caution in production!
 * 
 * IMPORTANT: Decorators MUST extend the class they decorate to maintain type compatibility.
 * This ensures that any service expecting QuantityPriceCalculator will accept our decorator.
 */
class CustomPriceCalculator extends QuantityPriceCalculator
{
    private QuantityPriceCalculator $decoratedService;
    private LoggerInterface $logger;

    public function __construct(
        QuantityPriceCalculator $decoratedService,
        LoggerInterface $logger
    ) {
        $this->decoratedService = $decoratedService;
        $this->logger = $logger;
    }

    /**
     * Example: Add logging to price calculations
     */
    public function calculate(
        QuantityPriceDefinition $definition,
        SalesChannelContext $context
    ): CalculatedPrice {
        $this->logger->debug('Calculating price', [
            'quantity' => $definition->getQuantity(),
            'price' => $definition->getPrice(),
        ]);

        // Call the original service
        $calculatedPrice = $this->decoratedService->calculate($definition, $context);

        $this->logger->debug('Price calculated', [
            'total_price' => $calculatedPrice->getTotalPrice(),
            'unit_price' => $calculatedPrice->getUnitPrice(),
        ]);

        return $calculatedPrice;
    }
}
```

### Step 2: Register Decorator

In `services.xml`:

```xml
<!-- Example of service decoration -->
<service id="Learning\Bundle\Service\Decorator\CustomPriceCalculator" 
         decorates="Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator">
    <argument type="service" id=".inner"/>
    <argument type="service" id="logger"/>
</service>
```

### Testing Part 4: Service Decoration

```bash
# Clear cache
bin/console cache:clear

# Verify decoration is active
bin/console debug:container Shopware\\Core\\Checkout\\Cart\\Price\\QuantityPriceCalculator --show-arguments

# Look for your decorator in the output
# You should see Learning\Bundle\Service\Decorator\CustomPriceCalculator

# Watch price calculation logs
tail -f var/log/dev.log | grep "Calculating price"
```

**Manual Testing Steps:**

1. **Trigger Price Calculations:**
   - Open storefront
   - Add any product to cart
   - Change quantity in cart
   - Proceed to checkout
   - Each action triggers price calculations

2. **Check Logs:**

   ```bash
   # See all price calculations
   grep -A 2 "Calculating price" var/log/dev.log
   
   # Count how many times prices were calculated
   grep -c "Price calculated" var/log/dev.log
   ```

3. **Verify Decorator Chain:**

   ```bash
   # If you have multiple decorators, verify the order
   bin/console debug:container --show-arguments QuantityPriceCalculator
   ```

> **⚠️ Important:** Service decoration affects ALL price calculations. In production:
>
> - Add feature flags to enable/disable decoration
> - Include performance monitoring
> - Add comprehensive error handling
> - Consider caching decorated results

---

## Part 5: Create Custom Events (60 minutes)

> **Real-World Use Cases:**
>
> - **Business Events:** Trigger workflows when business milestones occur (customer VIP status reached, inventory low)
> - **Integration Events:** Notify external systems (CRM updates, analytics tracking, webhook dispatching)
> - **Workflow Events:** Chain multiple plugin actions (after product import → update search → clear cache)
> - **Audit Events:** Track custom business activities for compliance and reporting

### Step 1: Create Custom Event Class

Create `custom/plugins/LearningBundle/src/Event/CustomWelcomeEvent.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Event;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\ShopwareEvent;
use Symfony\Contracts\EventDispatcher\Event;

class CustomWelcomeEvent extends Event implements ShopwareEvent
{
    private string $customerName;
    private string $message;
    private Context $context;

    public function __construct(string $customerName, string $message, Context $context)
    {
        $this->customerName = $customerName;
        $this->message = $message;
        $this->context = $context;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}
```

### Step 2: Dispatch Custom Event

Update `MessageService.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Learning\Bundle\Event\CustomWelcomeEvent;
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
        $prefix = $this->systemConfigService->get('LearningBundle.config.welcomePrefix') 
            ?? 'Welcome to Shopware Development';
        
        $message = sprintf('%s, %s!', $prefix, $name);

        // Dispatch custom event - other plugins can modify the message!
        $event = new CustomWelcomeEvent($name, $message, $context);
        $this->eventDispatcher->dispatch($event);

        // Get potentially modified message
        $finalMessage = $event->getMessage();
        
        $enableLogging = $this->systemConfigService->get('LearningBundle.config.enableLogging') 
            ?? true;
        
        if ($enableLogging) {
            $this->logger->info('Welcome message generated', [
                'name' => $name,
                'message' => $finalMessage,
            ]);
        }

        return $finalMessage;
    }

    // ... rest of the methods
}
```

### Step 3: Create Subscriber for Custom Event

Create `custom/plugins/LearningBundle/src/Subscriber/WelcomeMessageSubscriber.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Subscriber;

use Learning\Bundle\Event\CustomWelcomeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WelcomeMessageSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CustomWelcomeEvent::class => 'onWelcomeMessage',
        ];
    }

    public function onWelcomeMessage(CustomWelcomeEvent $event): void
    {
        // Modify the message - add timestamp
        $originalMessage = $event->getMessage();
        $timestamp = date('H:i:s');
        
        $modifiedMessage = sprintf('%s [Generated at %s]', $originalMessage, $timestamp);
        $event->setMessage($modifiedMessage);
    }
}
```

Register the subscriber:

```xml
<service id="Learning\Bundle\Subscriber\WelcomeMessageSubscriber">
    <tag name="kernel.event_subscriber"/>
</service>
```

Update service registration for MessageService:

```xml
<service id="Learning\Bundle\Service\MessageService">
    <argument type="service" id="logger"/>
    <argument type="service" id="Shopware\Core\System\SystemConfig\SystemConfigService"/>
    <argument type="service" id="event_dispatcher"/>
</service>
```

### Testing Part 5: Custom Events

```bash
# Clear cache
bin/console cache:clear

# Verify custom event subscriber is registered
bin/console debug:event-dispatcher Learning\\Bundle\\Event\\CustomWelcomeEvent

# Test the event flow
tail -f var/log/dev.log | grep -E "(Welcome message|Generated at)"
```

**Testing with CLI Command:**

Create a test command: `custom/plugins/LearningBundle/src/Command/TestWelcomeCommand.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Learning\Bundle\Service\MessageService;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestWelcomeCommand extends Command
{
    protected static $defaultName = 'learning:test-welcome';
    private MessageService $messageService;

    public function __construct(MessageService $messageService)
    {
        parent::__construct();
        $this->messageService = $messageService;
    }

    protected function configure(): void
    {
        $this->setDescription('Test custom welcome event')
            ->addArgument('name', InputArgument::OPTIONAL, 'Customer name', 'Test User');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $message = $this->messageService->generateWelcomeMessage($name, Context::createDefaultContext());
        
        $output->writeln('<info>Generated message:</info>');
        $output->writeln($message);
        
        return Command::SUCCESS;
    }
}
```

Register the command in `services.xml`:

```xml
<service id="Learning\Bundle\Command\TestWelcomeCommand">
    <argument type="service" id="Learning\Bundle\Service\MessageService"/>
    <tag name="console.command"/>
</service>
```

**Run Tests:**

```bash
# Clear cache
bin/console cache:clear

# Test the custom event
bin/console learning:test-welcome "John Doe"

# Expected output: "Welcome to Shopware Development, John Doe! [Generated at HH:MM:SS]"

# Verify in logs
grep "Welcome message generated" var/log/dev.log | tail -1
```

**What to Verify:**

1. ✅ Original message is generated by `MessageService`
2. ✅ `CustomWelcomeEvent` is dispatched
3. ✅ `WelcomeMessageSubscriber` catches event and modifies message
4. ✅ Final message includes timestamp
5. ✅ All activity is logged

> **💡 Pro Tip:** Use custom events to make your plugin extensible! Other developers can subscribe to your events and add their own logic without modifying your code.

**Example Use Case Chain:**

```text
Your Plugin dispatches CustomWelcomeEvent
    → Plugin A: Adds customer loyalty points info
    → Plugin B: Adds personalized product recommendations
    → Plugin C: Tracks event for analytics
    → Final enriched message returned
```

---

## Part 6: Dependency Injection Patterns (45 minutes)

### Pattern 1: Constructor Injection (Recommended)

```php
class MyService
{
    private LoggerInterface $logger;
    private EntityRepository $productRepository;

    // All dependencies injected via constructor
    public function __construct(
        LoggerInterface $logger,
        EntityRepository $productRepository
    ) {
        $this->logger = $logger;
        $this->productRepository = $productRepository;
    }
}
```

### Pattern 2: Setter Injection (Less Common)

```php
class MyService
{
    private ?LoggerInterface $logger = null;

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
```

In `services.xml`:

```xml
<service id="MyService">
    <call method="setLogger">
        <argument type="service" id="logger"/>
    </call>
</service>
```

### Pattern 3: Service Locator (Avoid if possible)

```php
use Psr\Container\ContainerInterface;

class MyService
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function doSomething(): void
    {
        // Fetch service when needed (not recommended for most cases)
        $logger = $this->container->get('logger');
    }
}
```

### Best Practices

✅ **DO:**

- Use constructor injection for required dependencies
- Type-hint interfaces, not concrete classes
- Keep constructors focused (max 5-7 dependencies)
- Make services stateless when possible

❌ **DON'T:**

- Use service locator pattern unless absolutely necessary
- Create circular dependencies
- Inject the entire container
- Make services stateful without good reason

---

## Part 7: Practical Exercises (3-4 hours)

> **💡 Tip:** Complete solutions available in `SOLUTIONS/DAY_2_SOLUTIONS.md`.

### Exercise 1: Product View Counter (60-75 min)

Create a subscriber that counts how many times each product page is viewed.

**Requirements:**

- Subscribe to `ProductPageLoadedEvent`
- Log product ID and timestamp
- Store count in a file in `var/` directory (format: `productId => count`)
- Create a command to display top 10 viewed products

**Hints:**

- Event class: `Shopware\Storefront\Page\Product\ProductPageLoadedEvent`
- Use JSON file to store data: `var/product_views.json`
- Remember to serialize/deserialize the array

### Exercise 2: Discount Event System (75-90 min)

Create a custom event system for discounts.

**Requirements:**

- Create `DiscountAppliedEvent` with discount details (code, amount, customerId)
- Create subscriber that logs all discount applications
- Create a service that simulates applying a discount
- Dispatch the custom event when discount is applied
- Create a command to test: `learning:apply-discount {code} {amount}`

**Hints:**

- Event should extend `Symfony\Contracts\EventDispatcher\Event`
- Include `ShopwareEvent` interface if you need Context
- Subscriber should implement `EventSubscriberInterface`

### Exercise 3: Service Chain with Decoration (90-120 min)

Create a chain of decorated services that build product information.

**Requirements:**

1. Create `ProductInfoService` interface with `getInfo(string $productId): string`
2. Base service `BaseProductInfoService`: Returns "Product: {name}"
3. First decorator `PriceProductInfoDecorator`: Adds " - Price: {price}"
4. Second decorator `StockProductInfoDecorator`: Adds " - Stock: {stock}"
5. Create command to test: `learning:product-info {productId}`

**Hints:**

- Each decorator should call the decorated service first
- Register decorators in order in `services.xml`
- Use `product.repository` to fetch product data
- Remember the `.inner` argument for decorated services

---

## Testing Your Work

### Test Event Subscribers

```bash
# Clear cache
bin/console cache:clear

# Check registered subscribers
bin/console debug:event-dispatcher

# Search for your events
bin/console debug:event-dispatcher | grep Learning

# Test with logs
tail -f var/log/dev.log | grep "Learning"
```

### Debug Services

```bash
# List all services
bin/console debug:container | grep Learning

# Get details about specific service
bin/console debug:container Learning\\Bundle\\Service\\MessageService

# Check service decoration
bin/console debug:container --show-arguments Shopware\\Core\\Checkout\\Cart\\Price\\QuantityPriceCalculator
```

---

## Key Takeaways

✅ **You've learned:**

- Event-driven architecture in Shopware
- Creating and registering event subscribers
- Listening to business, lifecycle, and cart events
- Service decoration pattern
- Creating and dispatching custom events
- Advanced dependency injection patterns
- Debugging events and services

## Common Issues

**Problem:** Event subscriber not firing

- Check `<tag name="kernel.event_subscriber"/>`
- Verify event class name
- Clear cache
- Check if event is actually dispatched

**Problem:** Circular dependency error

- Review service dependencies
- Consider using setter injection
- Break circular reference with event system

**Problem:** Decorated service not working

- Check `decorates` attribute in services.xml
- Ensure `.inner` argument is first
- Clear cache completely

---

## Additional Resources

- [Symfony Event Dispatcher](https://symfony.com/doc/current/components/event_dispatcher.html)
- [Shopware Events Reference](https://developer.shopware.com/docs/resources/guidelines/code/events.html#events)
- [Service Decoration](https://symfony.com/doc/current/service_container/service_decoration.html)
- [Dependency Injection Best Practices](https://symfony.com/doc/current/service_container/injection_types.html)

---

**Estimated Completion Time:** 5-7 hours  
**Difficulty:** Intermediate

🎉 Great job! Tomorrow we'll work with databases and migrations.
