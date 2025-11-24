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
    // protected static $defaultName = 'learning:product-info'; // deprecated

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
            ->setName('learning:product-info') // this is how to set the command name
            ->setDescription('Get decorated product information')
            ->addArgument('product-id', InputArgument::OPTIONAL, 'Product ID or product number')
            ->addOption('list', 'l', InputOption::VALUE_NONE, 'List first ten products');
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
            $io->error('Please provide a product ID or product number or use --list option to list products.');
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
            $io->success("Product Information (via decorated service chain):");
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
                $product->getProductNumber(),
                $product->getName(),
                $product->getId(),
            ];
        }
        $io->table(['Product Number', 'Name', 'ID'], $tableData);
        $io->note('Use: learning:product-info <product-number> to get detailed info about a product.');
    }

    private function resolveProductId(string $identifier): ?string
    {
        // First, check if identifier looks like a UUID (32 hex chars, optionally with dashes)
        if (preg_match('/^[0-9a-f]{32}$/i', str_replace('-', '', $identifier))) {
            // Try to find by ID
            $criteria = new Criteria([$identifier]);
            $result = $this->productRepository->search($criteria, Context::createDefaultContext());

            if ($result->first()) {
                return $identifier;
            }
        }

        // Try as product number
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productNumber', $identifier));
        $result = $this->productRepository->search($criteria, Context::createDefaultContext());

        return $result->first() ?->getId();
    }
}