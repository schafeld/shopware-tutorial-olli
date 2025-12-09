<?php declare(strict_types=1);

namespace Academy\Command;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'academy:product:debug',
    description: 'Debug product loading with SQL logging'
)]
class ProductDebugCommand extends Command
{
    public function __construct(
        private readonly EntityRepository $productRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('productId', InputArgument::OPTIONAL, 'Product ID to debug');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $productId = $input->getArgument('productId');
        
        $io->title('Product Debug Command with SQL Logging');
        
        // SQL logging info
        $io->info('SQL queries for this operation will be visible in:');
        $io->listing([
            'Shopware profiler (when viewing pages in browser)',
            'Log file: tail -f var/log/dev.log',
            'Symfony debug toolbar in browser',
        ]);
        
        $context = Context::createDefaultContext();
        
        if ($productId) {
            $io->section("Loading specific product: $productId");
            
            $criteria = new Criteria([$productId]);
            $criteria->addAssociations(['manufacturer', 'categories', 'cover.media']);
            
            $result = $this->productRepository->search($criteria, $context);
            /** @var ProductEntity|null $product */
            $product = $result->first();
            
            if ($product) {
                $io->success("Product loaded: " . $product->getName());
                $io->table(
                    ['Property', 'Value'],
                    [
                        ['ID', $product->getId()],
                        ['Name', $product->getName()],
                        ['Product Number', $product->getProductNumber()],
                        ['Active', $product->getActive() ? 'Yes' : 'No'],
                        ['Manufacturer', $product->getManufacturer() ? $product->getManufacturer()->getName() : 'None'],
                    ]
                );
            } else {
                $io->error("Product not found: $productId");
                return Command::FAILURE;
            }
        } else {
            $io->section('Loading first 5 active products');
            
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('active', true));
            $criteria->setLimit(5);
            $criteria->addAssociations(['manufacturer']);
            
            $products = $this->productRepository->search($criteria, $context);
            
            $io->success("Found {$products->getTotal()} products");
            
            $tableRows = [];
            /** @var ProductEntity $product */
            foreach ($products as $product) {
                $tableRows[] = [
                    $product->getId(),
                    $product->getName(),
                    $product->getProductNumber(),
                    $product->getManufacturer() ? $product->getManufacturer()->getName() : 'None'
                ];
            }
            
            $io->table(['ID', 'Name', 'Product Number', 'Manufacturer'], $tableRows);
        }
        
        $io->note([
            'To see SQL queries in real-time:',
            '1. Run this command in one terminal',
            '2. In another terminal run: tail -f var/log/dev.log | grep -i sql',
            '3. Or check the Shopware profiler in your browser'
        ]);
        
        return Command::SUCCESS;
    }
}