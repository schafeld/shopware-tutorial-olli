<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Learning\Bundle\Exception\ProductViewException;
use Learning\Bundle\Service\ProductViewService;
// use Shopware\Core\Framework\Context;
// use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TestErrorHandlingCommand extends Command
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
            ->setName('learning:test-errors')
            ->setDescription('Test error handling and logging')
            ->addArgument('error-type', InputArgument::OPTIONAL, 'Type of error to test', 'all')
            ->addOption('throw', 't', InputOption::VALUE_NONE, 'Actually throw the exception');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $errorType = $input->getArgument('error-type');
        $shouldThrow = $input->getOption('throw');

        $io->title('Testing Error Handling');

        switch ($errorType) {
            case 'product-not-found':
                $this->testProductNotFound($io, $shouldThrow);
                break;
            case 'invalid-data':
                $this->testInvalidData($io, $shouldThrow);
                break;
            case 'database-error':
                $this->testDatabaseError($io, $shouldThrow);
                break;
            case 'all':
                $this->testProductNotFound($io, $shouldThrow);
                $this->testInvalidData($io, $shouldThrow);
                $this->testDatabaseError($io, $shouldThrow);
                break;
            default:
                $io->error("Unknown error type: {$errorType}");
                return Command::FAILURE;
        }

        $io->success('Error handling tests completed.');
        $io->note('Check var/log/dev.log for logged errors.');

        return Command::SUCCESS;
    }

    private function testProductNotFound(SymfonyStyle $io, bool $shouldThrow): void
    {
        $io->section('Testing: Product Not Found Exception');

        try {
            if ($shouldThrow) {
                throw ProductViewException::productNotFound('non-existent-id');
            } else {
                $io->text('Would throw: ProductViewException::productNotFound()');
            }
        } catch (ProductViewException $e) {
            $io->error("Caught exception: " . $e->getMessage());
            $io->text("Error Code: " . $e->getErrorCode());
            $io->text("HTTP Status: " . $e->getStatusCode());
        }
    }   

    private function testInvalidData(SymfonyStyle $io, bool $shouldThrow): void
    {
        $io->section('Testing: Invalid Data Exception');

        try {
            if ($shouldThrow) {
                throw ProductViewException::invalidViewData('Product ID cannot be empty');
            } else {
                $io->text('Would throw: ProductViewException::invalidViewData()');
            }
        } catch (ProductViewException $e) {
            $io->error("Caught exception: " . $e->getMessage());
        }
    }

    private function testDatabaseError(SymfonyStyle $io, bool $shouldThrow): void
    {
        $io->section('Testing: Database Error Exception');

        try {
            if ($shouldThrow) {
                $previous = new \PDOException('Connection failed');
                throw ProductViewException::databaseError($previous);
            } else {
                $io->text('Would throw: ProductViewException::databaseError()');
            }
        } catch (ProductViewException $e) {
            $io->error("Caught exception: " . $e->getMessage());
            if ($e->getPrevious()) {
                $io->text("Previous: " . $e->getPrevious()->getMessage());
            }
        }
    }
}