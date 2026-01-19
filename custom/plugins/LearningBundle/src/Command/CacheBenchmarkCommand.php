<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Doctrine\DBAL\Connection;
use Learning\Bundle\Service\CachedProductViewService;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CacheBenchmarkCommand extends Command
{
    protected static $defaultName = 'learning:cache:benchmark';

    private CachedProductViewService $cachedService;
    private Connection $connection;

    public function __construct(
        CachedProductViewService $cachedService,
        Connection $connection
    ) {
        parent::__construct();
        $this->cachedService = $cachedService;
        $this->connection = $connection;
    }

    protected function configure(): void
    {
        $this
            ->setName('learning:cache:benchmark')
            ->setDescription('Benchmark cache performance for product view counts');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();
        
        // Get a real product ID from the database
        $productId = $this->connection->fetchOne('SELECT LOWER(HEX(id)) FROM product LIMIT 1');
        
        if (!$productId) {
            $io->error('No products found in database. Please create a product first.');
            return Command::FAILURE;
        }
        
        $io->info(sprintf('Testing cache with product ID: %s', $productId));

        // First call (cache miss)
        $start = microtime(true);
        $this->cachedService->getProductViewCount($productId, $context);
        $firstCallTime = (microtime(true) - $start) * 1000;

        // Second call (cache hit)
        $start = microtime(true);
        $this->cachedService->getProductViewCount($productId, $context);
        $secondCallTime = (microtime(true) - $start) * 1000;

        $io->table(
            ['Call', 'Time (ms)', 'Status'],
            [
                ['First (miss)', number_format($firstCallTime, 2), '❌ Cache Miss'],
                ['Second (hit)', number_format($secondCallTime, 2), '✅ Cache Hit'],
                ['Improvement', number_format($firstCallTime - $secondCallTime, 2), sprintf('%.1fx ms faster', $firstCallTime / $secondCallTime)],
            ]
        );

        return Command::SUCCESS;
    }
}