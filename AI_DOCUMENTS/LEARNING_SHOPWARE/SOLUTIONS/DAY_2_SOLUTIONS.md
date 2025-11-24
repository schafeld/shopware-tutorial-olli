# Day 2: Complete Exercise Solutions

> **Note:** These are complete, working solutions. Try to solve the exercises yourself first!

---

## Exercise 1: Product View Counter (60-75 min)

### Step 1: Create ProductViewCounterService

Create `custom/plugins/LearningBundle/src/Service/ProductViewCounterService.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Psr\Log\LoggerInterface;

class ProductViewCounterService
{
    private const STORAGE_FILE = 'var/product_views.json';
    
    private LoggerInterface $logger;
    private string $filePath;

    public function __construct(LoggerInterface $logger, string $projectDir)
    {
        $this->logger = $logger;
        $this->filePath = $projectDir . '/' . self::STORAGE_FILE;
    }

    /**
     * Record a product view
     */
    public function recordView(string $productId): void
    {
        $views = $this->loadViews();
        
        if (!isset($views[$productId])) {
            $views[$productId] = [
                'count' => 0,
                'first_viewed' => date('Y-m-d H:i:s'),
                'last_viewed' => null,
            ];
        }

        $views[$productId]['count']++;
        $views[$productId]['last_viewed'] = date('Y-m-d H:i:s');

        $this->saveViews($views);

        $this->logger->info('Product view recorded', [
            'product_id' => $productId,
            'total_views' => $views[$productId]['count'],
        ]);
    }

    /**
     * Get view count for a specific product
     */
    public function getViewCount(string $productId): int
    {
        $views = $this->loadViews();
        return $views[$productId]['count'] ?? 0;
    }

    /**
     * Get all product views
     */
    public function getAllViews(): array
    {
        return $this->loadViews();
    }

    /**
     * Get top N most viewed products
     */
    public function getTopViewedProducts(int $limit = 10): array
    {
        $views = $this->loadViews();

        // Sort by view count descending
        uasort($views, function($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return array_slice($views, 0, $limit, true);
    }

    /**
     * Load views from file
     */
    private function loadViews(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }

        $content = file_get_contents($this->filePath);
        if ($content === false) {
            $this->logger->error('Failed to read product views file');
            return [];
        }

        $views = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('Failed to decode product views JSON', [
                'error' => json_last_error_msg(),
            ]);
            return [];
        }

        return $views;
    }

    /**
     * Save views to file
     */
    private function saveViews(array $views): void
    {
        $directory = dirname($this->filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $json = json_encode($views, JSON_PRETTY_PRINT);
        $result = file_put_contents($this->filePath, $json);

        if ($result === false) {
            $this->logger->error('Failed to write product views file');
        }
    }

    /**
     * Reset all views
     */
    public function reset(): void
    {
        $this->saveViews([]);
        $this->logger->info('Product views reset');
    }
}
```

### Step 2: Create Product View Subscriber

Create `custom/plugins/LearningBundle/src/Subscriber/ProductViewSubscriber.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Subscriber;

use Learning\Bundle\Service\ProductViewCounterService;
use Psr\Log\LoggerInterface;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductViewSubscriber implements EventSubscriberInterface
{
    private ProductViewCounterService $viewCounterService;
    private LoggerInterface $logger;

    public function __construct(
        ProductViewCounterService $viewCounterService,
        LoggerInterface $logger
    ) {
        $this->viewCounterService = $viewCounterService;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => 'onProductPageLoaded',
        ];
    }

    public function onProductPageLoaded(ProductPageLoadedEvent $event): void
    {
        $product = $event->getPage()->getProduct();
        
        if (!$product) {
            return;
        }

        $productId = $product->getId();
        
        try {
            $this->viewCounterService->recordView($productId);
            
            $this->logger->info('Product page viewed', [
                'product_id' => $productId,
                'product_name' => $product->getName(),
                'customer_logged_in' => $event->getSalesChannelContext()->getCustomer() !== null,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to record product view', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

### Step 3: Create Command to Show Top Products

Create `custom/plugins/LearningBundle/src/Command/TopProductsCommand.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Learning\Bundle\Service\ProductViewCounterService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TopProductsCommand extends Command
{
    // protected static $defaultName = 'learning:top-products'; // deprecated

    private ProductViewCounterService $viewCounterService;
    private EntityRepository $productRepository;

    public function __construct(
        ProductViewCounterService $viewCounterService,
        EntityRepository $productRepository
    ) {
        parent::__construct();
        $this->viewCounterService = $viewCounterService;
        $this->productRepository = $productRepository;
    }

    protected function configure(): void
    {
        $this
            ->setName('learning:top-products') // this is the way to set a default name
            ->setDescription('Show top viewed products')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Number of products to show', 10)
            ->addOption('reset', 'r', InputOption::VALUE_NONE, 'Reset all view counts');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('reset')) {
            $this->viewCounterService->reset();
            $io->success('All product view counts have been reset');
            return Command::SUCCESS;
        }

        $limit = (int) $input->getOption('limit');
        $topProducts = $this->viewCounterService->getTopViewedProducts($limit);

        if (empty($topProducts)) {
            $io->warning('No product views recorded yet');
            return Command::SUCCESS;
        }

        $io->title(sprintf('Top %d Most Viewed Products', $limit));

        // Fetch product names
        $productIds = array_keys($topProducts);
        $criteria = new Criteria($productIds);
        $products = $this->productRepository->search($criteria, Context::createDefaultContext());

        $tableData = [];
        $rank = 1;

        foreach ($topProducts as $productId => $data) {
            $product = $products->get($productId);
            $productName = $product ? $product->getName() : 'Unknown Product';

            $tableData[] = [
                $rank++,
                substr($productId, 0, 8) . '...',
                $productName,
                $data['count'],
                $data['last_viewed'],
            ];
        }

        $io->table(
            ['Rank', 'Product ID', 'Product Name', 'Views', 'Last Viewed'],
            $tableData
        );

        return Command::SUCCESS;
    }
}
```

### Step 4: Register Services

Update `services.xml`:

```xml
<!-- Product View Counter Service -->
<service id="Learning\Bundle\Service\ProductViewCounterService">
    <argument type="service" id="logger"/>
    <argument type="string">%kernel.project_dir%</argument>
</service>

<!-- Product View Subscriber -->
<service id="Learning\Bundle\Subscriber\ProductViewSubscriber">
    <argument type="service" id="Learning\Bundle\Service\ProductViewCounterService"/>
    <argument type="service" id="logger"/>
    <tag name="kernel.event_subscriber"/>
</service>

<!-- Top Products Command -->
<service id="Learning\Bundle\Command\TopProductsCommand">
    <argument type="service" id="Learning\Bundle\Service\ProductViewCounterService"/>
    <argument type="service" id="product.repository"/>
    <tag name="console.command"/>
</service>
```

### Step 5: Test the Solution

```bash
# Clear cache
bin/console cache:clear

# View the storefront and browse some products
# Then check top products:

bin/console learning:top-products --limit=10

# Reset view counts
bin/console learning:top-products --reset
```

---

## Exercise 2: Discount Event System (75-90 min)

### Step 1: Create DiscountAppliedEvent

Create `custom/plugins/LearningBundle/src/Event/DiscountAppliedEvent.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Event;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\ShopwareEvent;
use Symfony\Contracts\EventDispatcher\Event;

class DiscountAppliedEvent extends Event implements ShopwareEvent
{
    private string $discountCode;
    private float $discountAmount;
    private ?string $customerId;
    private string $orderId;
    private Context $context;
    private array $metadata;

    public function __construct(
        string $discountCode,
        float $discountAmount,
        ?string $customerId,
        string $orderId,
        Context $context,
        array $metadata = []
    ) {
        $this->discountCode = $discountCode;
        $this->discountAmount = $discountAmount;
        $this->customerId = $customerId;
        $this->orderId = $orderId;
        $this->context = $context;
        $this->metadata = $metadata;
    }

    public function getDiscountCode(): string
    {
        return $this->discountCode;
    }

    public function getDiscountAmount(): float
    {
        return $this->discountAmount;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
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

### Step 2: Create Discount Service

Create `custom/plugins/LearningBundle/src/Service/DiscountService.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Learning\Bundle\Event\DiscountAppliedEvent;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DiscountService
{
    private const DISCOUNT_FILE = 'var/learning_discounts.json';

    private EventDispatcherInterface $eventDispatcher;
    private LoggerInterface $logger;
    private string $filePath;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger,
        string $projectDir
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
        $this->filePath = $projectDir . '/' . self::DISCOUNT_FILE;
    }

    /**
     * Apply a discount and dispatch event
     */
    public function applyDiscount(
        string $discountCode,
        float $discountAmount,
        ?string $customerId,
        string $orderId,
        Context $context
    ): array {
        // Record the discount
        $this->recordDiscount($discountCode, $discountAmount, $customerId, $orderId);

        // Dispatch event
        $event = new DiscountAppliedEvent(
            $discountCode,
            $discountAmount,
            $customerId,
            $orderId,
            $context,
            [
                'applied_at' => date('Y-m-d H:i:s'),
                'currency' => 'EUR',
            ]
        );

        $this->eventDispatcher->dispatch($event);

        $this->logger->info('Discount applied and event dispatched', [
            'discount_code' => $discountCode,
            'amount' => $discountAmount,
            'customer_id' => $customerId,
            'order_id' => $orderId,
        ]);

        return [
            'success' => true,
            'discount_code' => $discountCode,
            'discount_amount' => $discountAmount,
            'metadata' => $event->getMetadata(),
        ];
    }

    /**
     * Record discount to file
     */
    private function recordDiscount(
        string $code,
        float $amount,
        ?string $customerId,
        string $orderId
    ): void {
        $discounts = $this->loadDiscounts();

        $discounts[] = [
            'code' => $code,
            'amount' => $amount,
            'customer_id' => $customerId,
            'order_id' => $orderId,
            'applied_at' => date('Y-m-d H:i:s'),
        ];

        $this->saveDiscounts($discounts);
    }

    /**
     * Get all recorded discounts
     */
    public function getAllDiscounts(): array
    {
        return $this->loadDiscounts();
    }

    /**
     * Get discount statistics
     */
    public function getStatistics(): array
    {
        $discounts = $this->loadDiscounts();
        
        $stats = [
            'total_discounts' => count($discounts),
            'total_amount' => 0,
            'by_code' => [],
        ];

        foreach ($discounts as $discount) {
            $stats['total_amount'] += $discount['amount'];
            
            $code = $discount['code'];
            if (!isset($stats['by_code'][$code])) {
                $stats['by_code'][$code] = [
                    'count' => 0,
                    'total_amount' => 0,
                ];
            }
            
            $stats['by_code'][$code]['count']++;
            $stats['by_code'][$code]['total_amount'] += $discount['amount'];
        }

        return $stats;
    }

    private function loadDiscounts(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }

        $content = file_get_contents($this->filePath);
        if ($content === false) {
            return [];
        }

        return json_decode($content, true) ?? [];
    }

    private function saveDiscounts(array $discounts): void
    {
        $directory = dirname($this->filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($this->filePath, json_encode($discounts, JSON_PRETTY_PRINT));
    }

    public function reset(): void
    {
        $this->saveDiscounts([]);
    }
}
```

### Step 3: Create Discount Subscriber

Create `custom/plugins/LearningBundle/src/Subscriber/DiscountSubscriber.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Subscriber;

use Learning\Bundle\Event\DiscountAppliedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DiscountSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DiscountAppliedEvent::class => [
                ['logDiscountApplication', 100],
                ['checkDiscountThreshold', 50],
                ['enrichMetadata', 10],
            ],
        ];
    }

    /**
     * Log every discount application
     */
    public function logDiscountApplication(DiscountAppliedEvent $event): void
    {
        $this->logger->info('Discount application logged by subscriber', [
            'code' => $event->getDiscountCode(),
            'amount' => $event->getDiscountAmount(),
            'customer_id' => $event->getCustomerId() ?? 'guest',
            'order_id' => $event->getOrderId(),
        ]);
    }

    /**
     * Check if discount exceeds threshold and log warning
     */
    public function checkDiscountThreshold(DiscountAppliedEvent $event): void
    {
        $threshold = 100.0; // €100

        if ($event->getDiscountAmount() > $threshold) {
            $this->logger->warning('Large discount applied', [
                'code' => $event->getDiscountCode(),
                'amount' => $event->getDiscountAmount(),
                'threshold' => $threshold,
                'customer_id' => $event->getCustomerId(),
            ]);

            // Could trigger additional checks, notifications, etc.
        }
    }

    /**
     * Enrich event metadata with additional information
     */
    public function enrichMetadata(DiscountAppliedEvent $event): void
    {
        $metadata = $event->getMetadata();
        
        $metadata['processed_by_subscriber'] = true;
        $metadata['subscriber_timestamp'] = date('Y-m-d H:i:s');
        $metadata['is_large_discount'] = $event->getDiscountAmount() > 50.0;
        
        $event->setMetadata($metadata);

        $this->logger->debug('Discount metadata enriched', [
            'code' => $event->getDiscountCode(),
            'metadata' => $metadata,
        ]);
    }
}
```

### Step 4: Create Command to Test Discounts

Create `custom/plugins/LearningBundle/src/Command/ApplyDiscountCommand.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Learning\Bundle\Service\DiscountService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ApplyDiscountCommand extends Command
{
    protected static $defaultName = 'learning:apply-discount';

    private DiscountService $discountService;

    public function __construct(DiscountService $discountService)
    {
        parent::__construct();
        $this->discountService = $discountService;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Simulate applying a discount')
            ->addArgument('code', InputArgument::REQUIRED, 'Discount code')
            ->addArgument('amount', InputArgument::REQUIRED, 'Discount amount')
            ->addOption('customer-id', 'c', InputOption::VALUE_OPTIONAL, 'Customer ID')
            ->addOption('stats', 's', InputOption::VALUE_NONE, 'Show discount statistics')
            ->addOption('reset', 'r', InputOption::VALUE_NONE, 'Reset all discounts');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('reset')) {
            $this->discountService->reset();
            $io->success('All discount records have been reset');
            return Command::SUCCESS;
        }

        if ($input->getOption('stats')) {
            $this->showStatistics($io);
            return Command::SUCCESS;
        }

        $code = $input->getArgument('code');
        $amount = (float) $input->getArgument('amount');
        $customerId = $input->getOption('customer-id');
        $orderId = Uuid::randomHex();

        $context = Context::createDefaultContext();

        try {
            $result = $this->discountService->applyDiscount(
                $code,
                $amount,
                $customerId,
                $orderId,
                $context
            );

            $io->success(sprintf('Discount "%s" applied successfully!', $code));
            $io->table(
                ['Property', 'Value'],
                [
                    ['Code', $result['discount_code']],
                    ['Amount', '€' . number_format($result['discount_amount'], 2)],
                    ['Order ID', $orderId],
                    ['Customer ID', $customerId ?? 'Guest'],
                ]
            );

            if (!empty($result['metadata'])) {
                $io->section('Event Metadata');
                foreach ($result['metadata'] as $key => $value) {
                    $io->text(sprintf('  %s: %s', $key, is_bool($value) ? ($value ? 'true' : 'false') : $value));
                }
            }

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $io->error('Failed to apply discount: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function showStatistics(SymfonyStyle $io): void
    {
        $stats = $this->discountService->getStatistics();

        $io->title('Discount Statistics');
        $io->table(
            ['Metric', 'Value'],
            [
                ['Total Discounts Applied', $stats['total_discounts']],
                ['Total Amount', '€' . number_format($stats['total_amount'], 2)],
            ]
        );

        if (!empty($stats['by_code'])) {
            $io->section('By Discount Code');
            $tableData = [];
            foreach ($stats['by_code'] as $code => $data) {
                $tableData[] = [
                    $code,
                    $data['count'],
                    '€' . number_format($data['total_amount'], 2),
                ];
            }
            $io->table(['Code', 'Uses', 'Total Amount'], $tableData);
        }
    }
}
```

### Step 5: Register Services

Update `services.xml`:

```xml
<!-- Discount Service -->
<service id="Learning\Bundle\Service\DiscountService">
    <argument type="service" id="event_dispatcher"/>
    <argument type="service" id="logger"/>
    <argument type="string">%kernel.project_dir%</argument>
</service>

<!-- Discount Subscriber -->
<service id="Learning\Bundle\Subscriber\DiscountSubscriber">
    <argument type="service" id="logger"/>
    <tag name="kernel.event_subscriber"/>
</service>

<!-- Apply Discount Command -->
<service id="Learning\Bundle\Command\ApplyDiscountCommand">
    <argument type="service" id="Learning\Bundle\Service\DiscountService"/>
    <tag name="console.command"/>
</service>
```

### Step 6: Test the Solution

```bash
# Clear cache
bin/console cache:clear

# Apply a small discount
bin/console learning:apply-discount SAVE10 10.50

# Apply a large discount (triggers warning)
bin/console learning:apply-discount BIGSALE 150.00 --customer-id=abc123

# Show statistics
bin/console learning:apply-discount --stats

# Watch logs in another terminal
tail -f var/log/dev.log | grep -i discount

# Reset discounts
bin/console learning:apply-discount --reset
```

---

## Exercise 3: Service Chain with Decoration (90-120 min)

### Step 1: Create ProductInfoService Interface

Create `custom/plugins/LearningBundle/src/Service/ProductInfo/ProductInfoServiceInterface.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service\ProductInfo;

interface ProductInfoServiceInterface
{
    public function getInfo(string $productId): string;
}
```

### Step 2: Create Base Service

Create `custom/plugins/LearningBundle/src/Service/ProductInfo/BaseProductInfoService.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service\ProductInfo;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class BaseProductInfoService implements ProductInfoServiceInterface
{
    private EntityRepository $productRepository;
    private LoggerInterface $logger;

    public function __construct(
        EntityRepository $productRepository,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    public function getInfo(string $productId): string
    {
        $product = $this->loadProduct($productId);

        if (!$product) {
            return sprintf('Product: [Not Found: %s]', substr($productId, 0, 8));
        }

        $info = sprintf('Product: %s', $product->getName());

        $this->logger->debug('Base product info generated', [
            'product_id' => $productId,
            'product_name' => $product->getName(),
        ]);

        return $info;
    }

    protected function loadProduct(string $productId): ?ProductEntity
    {
        $criteria = new Criteria([$productId]);
        $criteria->addAssociation('prices');

        $result = $this->productRepository->search($criteria, Context::createDefaultContext());
        return $result->first();
    }
}
```

### Step 3: Create Price Decorator

Create `custom/plugins/LearningBundle/src/Service/ProductInfo/PriceProductInfoDecorator.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service\ProductInfo;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class PriceProductInfoDecorator implements ProductInfoServiceInterface
{
    private ProductInfoServiceInterface $decoratedService;
    private EntityRepository $productRepository;
    private LoggerInterface $logger;

    public function __construct(
        ProductInfoServiceInterface $decoratedService,
        EntityRepository $productRepository,
        LoggerInterface $logger
    ) {
        $this->decoratedService = $decoratedService;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    public function getInfo(string $productId): string
    {
        // Get info from decorated service
        $baseInfo = $this->decoratedService->getInfo($productId);

        // Add price information
        $product = $this->loadProduct($productId);

        if (!$product || !$product->getPrice()) {
            return $baseInfo . ' - Price: N/A';
        }

        $price = $product->getPrice()->first();
        if (!$price) {
            return $baseInfo . ' - Price: N/A';
        }

        $priceInfo = sprintf(
            ' - Price: €%.2f',
            $price->getGross()
        );

        $this->logger->debug('Price info added to product info', [
            'product_id' => $productId,
            'price' => $price->getGross(),
        ]);

        return $baseInfo . $priceInfo;
    }

    private function loadProduct(string $productId): ?ProductEntity
    {
        $criteria = new Criteria([$productId]);
        $criteria->addAssociation('prices');

        $result = $this->productRepository->search($criteria, Context::createDefaultContext());
        return $result->first();
    }
}
```

### Step 4: Create Stock Decorator

Create `custom/plugins/LearningBundle/src/Service/ProductInfo/StockProductInfoDecorator.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service\ProductInfo;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class StockProductInfoDecorator implements ProductInfoServiceInterface
{
    private ProductInfoServiceInterface $decoratedService;
    private EntityRepository $productRepository;
    private LoggerInterface $logger;

    public function __construct(
        ProductInfoServiceInterface $decoratedService,
        EntityRepository $productRepository,
        LoggerInterface $logger
    ) {
        $this->decoratedService = $decoratedService;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    public function getInfo(string $productId): string
    {
        // Get info from decorated service
        $baseInfo = $this->decoratedService->getInfo($productId);

        // Add stock information
        $product = $this->loadProduct($productId);

        if (!$product) {
            return $baseInfo . ' - Stock: N/A';
        }

        $stock = $product->getStock() ?? 0;
        $available = $product->getAvailable() ?? false;

        $stockInfo = sprintf(
            ' - Stock: %d (%s)',
            $stock,
            $available ? 'Available' : 'Not Available'
        );

        $this->logger->debug('Stock info added to product info', [
            'product_id' => $productId,
            'stock' => $stock,
            'available' => $available,
        ]);

        return $baseInfo . $stockInfo;
    }

    private function loadProduct(string $productId): ?ProductEntity
    {
        $criteria = new Criteria([$productId]);
        
        $result = $this->productRepository->search($criteria, Context::createDefaultContext());
        return $result->first();
    }
}
```

### Step 5: Register Services with Decoration Chain

Update `services.xml`:

```xml
<!-- Base Product Info Service -->
<service id="Learning\Bundle\Service\ProductInfo\BaseProductInfoService">
    <argument type="service" id="product.repository"/>
    <argument type="service" id="logger"/>
</service>

<!-- First Decorator: Add Price -->
<service id="Learning\Bundle\Service\ProductInfo\PriceProductInfoDecorator"
         decorates="Learning\Bundle\Service\ProductInfo\BaseProductInfoService"
         decoration-priority="100">
    <argument type="service" id=".inner"/>
    <argument type="service" id="product.repository"/>
    <argument type="service" id="logger"/>
</service>

<!-- Second Decorator: Add Stock -->
<service id="Learning\Bundle\Service\ProductInfo\StockProductInfoDecorator"
         decorates="Learning\Bundle\Service\ProductInfo\BaseProductInfoService"
         decoration-priority="50">
    <argument type="service" id=".inner"/>
    <argument type="service" id="product.repository"/>
    <argument type="service" id="logger"/>
</service>

<!-- Alias for easy injection -->
<service id="Learning\Bundle\Service\ProductInfo\ProductInfoServiceInterface"
         alias="Learning\Bundle\Service\ProductInfo\BaseProductInfoService"/>
```

### Step 6: Create Command to Test

Create `custom/plugins/LearningBundle/src/Command/ProductInfoCommand.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Learning\Bundle\Service\ProductInfo\ProductInfoServiceInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ProductInfoCommand extends Command
{
    protected static $defaultName = 'learning:product-info';

    private ProductInfoServiceInterface $productInfoService;
    private EntityRepository $productRepository;

    public function __construct(
        ProductInfoServiceInterface $productInfoService,
        EntityRepository $productRepository
    ) {
        parent::__construct();
        $this->productInfoService = $productInfoService;
        $this->productRepository = $productRepository;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Get decorated product information')
            ->addArgument('product-id', InputArgument::OPTIONAL, 'Product ID or product number')
            ->addOption('list', 'l', InputOption::VALUE_NONE, 'List first 10 products');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('list')) {
            $this->listProducts($io);
            return Command::SUCCESS;
        }

        $productIdentifier = $input->getArgument('product-id');

        if (!$productIdentifier) {
            $io->error('Please provide a product ID or use --list to see available products');
            return Command::FAILURE;
        }

        // Try to find product by ID or product number
        $productId = $this->resolveProductId($productIdentifier);

        if (!$productId) {
            $io->error('Product not found');
            return Command::FAILURE;
        }

        try {
            $info = $this->productInfoService->getInfo($productId);
            
            $io->success('Product Information (via decorated service chain):');
            $io->text($info);

            $io->note('This information was built using:');
            $io->listing([
                'BaseProductInfoService → Product name',
                'PriceProductInfoDecorator → + Price',
                'StockProductInfoDecorator → + Stock availability',
            ]);

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $io->error('Failed to get product info: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function listProducts(SymfonyStyle $io): void
    {
        $criteria = new Criteria();
        $criteria->setLimit(10);
        $criteria->addFilter(new EqualsFilter('active', true));

        $products = $this->productRepository->search($criteria, Context::createDefaultContext());

        $io->title('Available Products');

        $tableData = [];
        foreach ($products as $product) {
            $tableData[] = [
                substr($product->getId(), 0, 12) . '...',
                $product->getProductNumber(),
                $product->getName(),
            ];
        }

        $io->table(['ID', 'Product Number', 'Name'], $tableData);
        $io->note('Use: learning:product-info <product-id> to get detailed info');
    }

    private function resolveProductId(string $identifier): ?string
    {
        // First try as direct ID
        $criteria = new Criteria([$identifier]);
        $result = $this->productRepository->search($criteria, Context::createDefaultContext());
        
        if ($result->first()) {
            return $identifier;
        }

        // Try as product number
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productNumber', $identifier));
        $result = $this->productRepository->search($criteria, Context::createDefaultContext());

        return $result->first()?->getId();
    }
}
```

### Step 7: Register Command

Update `services.xml`:

```xml
<!-- Product Info Command -->
<service id="Learning\Bundle\Command\ProductInfoCommand">
    <argument type="service" id="Learning\Bundle\Service\ProductInfo\ProductInfoServiceInterface"/>
    <argument type="service" id="product.repository"/>
    <tag name="console.command"/>
</service>
```

### Step 8: Test the Solution

```bash
# Clear cache
bin/console cache:clear

# List available products
bin/console learning:product-info --list

# Get info for a specific product (use an ID from the list)
bin/console learning:product-info <product-id>

# Example output:
# Product: Example Product - Price: €29.99 - Stock: 100 (Available)

# Check that all decorators are working by watching logs
tail -f var/log/dev.log | grep "product info"
```

---

## Summary

You've completed all Day 2 exercises! You've learned:

✅ **Exercise 1:** Event subscription, file storage, data aggregation, and commands
✅ **Exercise 2:** Custom events, event dispatching, multiple subscribers with priorities
✅ **Exercise 3:** Service decoration chain, interface-based design, decorator pattern

### Key Concepts Mastered:

- **Event System:** Subscribing to events, creating custom events, event priorities
- **Service Decoration:** Chaining decorators, decorator priority, maintaining interfaces
- **File Operations:** JSON storage, data aggregation, statistics
- **Dependency Injection:** Complex service dependencies, interface injection
- **Command Development:** Interactive commands with options and table output

Continue to Day 3 for database and migrations!
