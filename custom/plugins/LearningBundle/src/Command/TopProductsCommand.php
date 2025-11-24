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
            ->setName('learning:top-products')
            ->setDescription('Show top viewed products')
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Number of products to show',
                10
            )
            ->addOption(
                'reset',
                'r',
                InputOption::VALUE_NONE,
                'Reset all view counts'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('reset')) {
            $this->viewCounterService->reset();
            $io->success('All product view counts have been reset.');
            return Command::SUCCESS;
        }

        $limit = (int) $input->getOption('limit');
        $topProducts = $this->viewCounterService->getTopViewedProducts($limit);

        if (empty($topProducts)) {
            $io->warning('No product views recorded yet.');
            return Command::SUCCESS;
        }

        $io -> title(sprintf('Top %d Most Viewed Products', $limit));

        // Fetch product names
        $productIds = array_keys($topProducts);
        $criteria = new Criteria($productIds);
        $products = $this->productRepository->search($criteria, Context::createDefaultContext());

        $tableData = [];
        $rank = 1;

        foreach ($topProducts as $productId => $data) {
            $product = $products->get($productId);
            $productName = $product ? $product->getName() :'Unknown product';

            $tableData[] = [
                $rank++,
                substr($productId, 0, 8) . '...',
                $productName,
                $data['count'],
                $data['last_viewed'],
            ];
        }

        $io -> table(
            ['Rank', 'Product ID', 'Product Name', 'Views', 'Last Viewed'], 
            $tableData      
        );
        return Command::SUCCESS;
    }
}