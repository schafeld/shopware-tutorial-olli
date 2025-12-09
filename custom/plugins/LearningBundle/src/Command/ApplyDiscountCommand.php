<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Learning\Bundle\Service\DiscountService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ApplyDiscountCommand extends Command
{
    // protected static $defaultName = 'learning:apply-discount'; // deprecated

    private DiscountService $discountService;

    public function __construct(DiscountService $discountService)
    {
        parent::__construct();
        $this->discountService = $discountService;
    }

    protected function configure(): void
    {
        $this
            ->setName('learning:apply-discount') // this is how to set the command name
            ->setDescription('Simulate applying a discount')
            ->addArgument('code', InputOption::VALUE_REQUIRED, 'Discount code')
            ->addArgument('amount', InputOption::VALUE_REQUIRED, 'Discount amount')
            ->addOption('customer-id', 'c', InputOption::VALUE_REQUIRED,'Customer ID')
            ->addOption('stats','s', InputOption::VALUE_NONE,'Show discount statistics')
            ->addOption('reset','r', InputOption::VALUE_NONE,'Reset all discounts');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('reset')) {
            $this->discountService->reset();
            $io->success('All discount records have been reset.');
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
                    ['Amount', '€' . number_format((float)$result['discount_amount'], 2)],
                    ['Order ID', $orderId],
                    ['Customer ID', $customerId ?? 'guest'],
                ]
            );

            if (!empty($result['metadata'])) {
                $io->section('Event Metadata');
                foreach ($result['metadata'] as $key => $value) {
                    $io->text(sprintf('%s: %s', $key, is_bool($value) ? ($value ? 'true' : 'false') : $value));
                }
            }
            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $io->error(sprintf('Failed to apply discount: %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }

    private function showStatistics(SymfonyStyle $io): void
    {
        $stats = $this->discountService->getStatistics();

        $io->title('Discount Statistics');
        $io->table(['Metric','Value'],
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