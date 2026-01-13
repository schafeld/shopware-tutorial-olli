<?php declare(strict_types=1);

namespace GotoWebinarGoogleSheetsExport\Command;

use GotoWebinarGoogleSheetsExport\Service\OrderExportService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Console command to scan existing orders and create export log entries
 * for products in the configured webinar category.
 * 
 * This is useful for:
 * - Initial setup to capture existing orders
 * - Orders where payment state didn't trigger the subscriber
 * - Manual bulk import of historical orders
 */
#[AsCommand(
    name: 'gotowebinar:scan-orders',
    description: 'Scan existing orders for webinar products and create export entries'
)]
class ScanOrdersCommand extends Command
{
    private const CONFIG_PREFIX = 'GotoWebinarGoogleSheetsExport.config.';

    public function __construct(
        private readonly EntityRepository $orderRepository,
        private readonly OrderExportService $orderExportService,
        private readonly SystemConfigService $systemConfigService,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Maximum number of orders to scan',
                100
            )
            ->addOption(
                'days',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Only scan orders from the last N days',
                30
            )
            ->addOption(
                'order-number',
                'o',
                InputOption::VALUE_OPTIONAL,
                'Scan a specific order by order number'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Show what would be created without actually creating entries'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();

        $io->title('Scan Orders for Webinar Products');

        // Check configuration
        $categoryId = $this->systemConfigService->get(self::CONFIG_PREFIX . 'categoryId');
        if (!$categoryId) {
            $io->error('Webinar category is not configured. Please set categoryId in plugin settings.');
            return Command::FAILURE;
        }

        $limit = (int) $input->getOption('limit');
        $days = (int) $input->getOption('days');
        $orderNumber = $input->getOption('order-number');
        $dryRun = $input->getOption('dry-run');

        if ($dryRun) {
            $io->warning('DRY RUN MODE - No entries will be created');
        }

        // Build criteria
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->addAssociation('lineItems.product.categories');
        $criteria->addAssociation('orderCustomer');
        $criteria->addAssociation('salesChannel');

        if ($orderNumber) {
            $criteria->addFilter(new EqualsFilter('orderNumber', $orderNumber));
            $io->info(sprintf('Scanning order #%s', $orderNumber));
        } else {
            // Filter by date
            $fromDate = (new \DateTime())->modify("-{$days} days");
            $criteria->addFilter(new RangeFilter('createdAt', [
                RangeFilter::GTE => $fromDate->format(\DATE_ATOM)
            ]));
            $io->info(sprintf('Scanning orders from the last %d days (limit: %d)', $days, $limit));
        }

        $orders = $this->orderRepository->search($criteria, $context);

        if ($orders->count() === 0) {
            $io->warning('No orders found matching the criteria.');
            return Command::SUCCESS;
        }

        $io->writeln(sprintf('Found %d order(s) to scan', $orders->count()));
        $io->newLine();

        $totalCreated = 0;
        $totalSkipped = 0;

        /** @var OrderEntity $order */
        foreach ($orders as $order) {
            $lineItems = $order->getLineItems();
            if (!$lineItems) {
                continue;
            }

            $customer = $order->getOrderCustomer();
            $salesChannel = $order->getSalesChannel();

            foreach ($lineItems as $lineItem) {
                if ($lineItem->getType() !== 'product') {
                    continue;
                }

                $product = $lineItem->getProduct();
                if (!$product) {
                    continue;
                }

                // Check if product is in the configured category
                $categories = $product->getCategories();
                if (!$categories) {
                    continue;
                }

                $isInCategory = false;
                foreach ($categories as $category) {
                    if ($category->getId() === $categoryId) {
                        $isInCategory = true;
                        break;
                    }
                }

                if (!$isInCategory) {
                    continue;
                }

                // Check if export entry already exists
                if ($this->orderExportService->exportExists($order->getId(), $product->getId(), $context)) {
                    $io->writeln(sprintf(
                        '  <comment>SKIP</comment> Order #%s / %s - Export entry already exists',
                        $order->getOrderNumber(),
                        $product->getProductNumber()
                    ));
                    $totalSkipped++;
                    continue;
                }

                $io->writeln(sprintf(
                    '  <info>%s</info> Order #%s / %s (%s)',
                    $dryRun ? 'WOULD CREATE' : 'CREATE',
                    $order->getOrderNumber(),
                    $product->getProductNumber(),
                    $lineItem->getLabel()
                ));

                if (!$dryRun) {
                    $customerData = [
                        'first_name' => $customer ? $customer->getFirstName() : '',
                        'last_name' => $customer ? $customer->getLastName() : '',
                        'email' => $customer ? $customer->getEmail() : '',
                        'sales_channel_name' => $salesChannel ? $salesChannel->getName() : 'Unknown',
                    ];

                    try {
                        $this->orderExportService->createExportLog(
                            $order->getId(),
                            $order->getOrderNumber(),
                            $product->getId(),
                            $product->getProductNumber(),
                            $customerData,
                            $context,
                            'pending'
                        );
                        $totalCreated++;
                    } catch (\Exception $e) {
                        $io->error(sprintf('Failed to create export entry: %s', $e->getMessage()));
                    }
                } else {
                    $totalCreated++;
                }
            }
        }

        $io->newLine();
        $io->success(sprintf(
            '%s %d export entries, skipped %d existing entries',
            $dryRun ? 'Would create' : 'Created',
            $totalCreated,
            $totalSkipped
        ));

        return Command::SUCCESS;
    }
}
