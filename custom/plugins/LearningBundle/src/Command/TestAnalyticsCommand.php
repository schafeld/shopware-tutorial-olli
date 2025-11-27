<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Learning\Bundle\Service\ProductViewAnalyticsService;
use Learning\Bundle\Service\ProductViewService;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TestAnalyticsCommand extends Command
{
    private ProductViewAnalyticsService $analyticsService;
    private ProductViewService $viewService;

    public function __construct(
        ProductViewAnalyticsService $analyticsService,
        ProductViewService $viewService
    ) {
        parent::__construct();
        $this->analyticsService = $analyticsService;
        $this->viewService = $viewService;
    }

    protected function configure(): void
    {
        $this
            ->setName('learning:test-analytics')
            ->setDescription('Test the ProductViewAnalyticsService')
            ->addOption(
                'generate-data',
                'g',
                InputOption::VALUE_NONE,
                'Generate sample data first'
            )
            ->addOption(
                'product-id',
                'p',
                InputOption::VALUE_REQUIRED,
                'Product ID to generate views for'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();

        // Generate sample data if requested
        if ($input->getOption('generate-data')) {
            $productId = $input->getOption('product-id');
            if (!$productId) {
                $io->error('Please provide a product ID with --product-id when using --generate-data');
                return Command::FAILURE;
            }

            $io->section('Generating sample data...');
            $this->generateSampleData($productId, $context, $io);
        }

        // Test 1: Views for last N days
        $io->section('Views for Last 7 Days');
        $viewsByDay = $this->analyticsService->getViewsForLastDays(7, $context);
        
        if (empty($viewsByDay)) {
            $io->warning('No data found. Use --generate-data to create sample data.');
        } else {
            $io->table(
                ['Date', 'View Count'],
                array_map(fn($bucket) => [
                    $bucket->getKey(),
                    $bucket->getCount(),
                ], $viewsByDay)
            );
        }

        // Test 2: Total views by product
        $io->section('Total Views by Product');
        $viewsByProduct = $this->analyticsService->getTotalViewsByProduct($context);
        
        if (empty($viewsByProduct)) {
            $io->warning('No data found.');
        } else {
            $io->table(
                ['Product ID', 'Product Name', 'Total Views'],
                array_map(fn($item) => [
                    substr($item['product_id'], 0, 8) . '...',
                    $item['product_name'] ?? 'N/A',
                    $item['total_views'],
                ], array_slice($viewsByProduct, 0, 10)) // Show top 10
            );
        }

        // Test 3: Views by browser
        $io->section('Views by Browser');
        $viewsByBrowser = $this->analyticsService->getViewsByBrowser($context);
        
        if (empty($viewsByBrowser)) {
            $io->warning('No data found.');
        } else {
            $io->table(
                ['Browser', 'Total Views'],
                array_map(fn($browser, $count) => [$browser, $count], 
                    array_keys($viewsByBrowser), 
                    array_values($viewsByBrowser))
            );
        }

        $io->success('Analytics test completed!');
        
        return Command::SUCCESS;
    }

    private function generateSampleData(string $productId, Context $context, SymfonyStyle $io): void
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36 Edg/91.0.864.59',
        ];

        // Generate views for the last 7 days
        for ($day = 0; $day < 7; $day++) {
            $viewsPerDay = rand(5, 20);
            for ($i = 0; $i < $viewsPerDay; $i++) {
                $this->viewService->recordView(
                    $productId,
                    null,
                    $userAgents[array_rand($userAgents)],
                    $context
                );
            }
            $io->writeln("Generated {$viewsPerDay} views for day -{$day}");
        }

        $io->success('Sample data generated successfully!');
    }
}