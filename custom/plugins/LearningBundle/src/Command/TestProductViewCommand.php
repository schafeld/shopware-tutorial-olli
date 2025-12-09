<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Learning\Bundle\Service\ProductViewService;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TestProductViewCommand extends Command
{
    private ProductViewService $productViewService;

    public function __construct(ProductViewService $productViewService)
    {
        parent::__construct();
        $this->productViewService = $productViewService;
    }

    protected function configure(): void
    {
        $this->setName('learning:test-product-view')
            ->setDescription('Test the Product View Service');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();

        // Test data: You'll need to replace this ID with an actual product ID from your Shopware instance
        // Example https://127.0.0.1:8000/Main-product-with-properties/SWDEMO10007.1
        $productId = '019a9b8a832373f8ad9218fb79e6a4d7'; // 'INSERT_EXISTING_PRODUCT_ID_HERE';

        // Record some test views
        $io->section('Recording test views...');
        for ($i = 0; $i < 5; $i++) {
            $this->productViewService->recordView(
                $productId,
                null, // No customer ID for guest views
                'TestUserAgent/1.0',
                $context
            );
        }
        $io->success('Recorded 5 views');

        // Get view count
        $viewCount = $this->productViewService->getProductViewCount($productId, $context);
        $io->info(sprintf('Product has %d views', $viewCount));

        // Get most viewed products
        $mostViewed = $this->productViewService->getMostViewedProducts(5, $context);
        $io->section('Most viewed products:');
        $io->table(
            ['Product ID', 'Name', 'Views', 'Last Viewed'],
            array_map(fn ($item) => [
                $item['product_id'],
                $item['product_name'] ?? 'N/A',
                $item['view_count'],
                (new \DateTime($item['last_viewed']))->format('Y-m-d H:i:s'),
            ], $mostViewed)
        );

        return Command::SUCCESS;
    }
}