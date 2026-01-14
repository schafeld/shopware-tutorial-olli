<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Learning\Bundle\Service\ProductViewService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DebugTestCommand extends Command
{
    private ProductViewService $productViewService;

    public function __construct(ProductViewService $productViewService)
    {
        parent::__construct();
        $this->productViewService = $productViewService;
    }

    protected function configure(): void
    {
        $this
            ->setName('learning:debug-test')
            ->setDescription('Test command for debugging with Xdebug');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $context = Context::createDefaultContext();

        // Set breakpoint here
        // Generate a valid UUID for testing
        $productId = Uuid::randomHex();

        // Step through this code
        $viewCount = $this->productViewService->getProductViewCount($productId, $context);

        $output->writeln("View count for product {$productId}: {$viewCount}");
        // check variables in debugger
        $data = [
            'productId' => $productId,
            'count' => $viewCount,
            'timestamp' => new \DateTime(),
        ];

        $output->writeln("Data: " . json_encode($data, JSON_PRETTY_PRINT));

        return Command::SUCCESS;
    }
}