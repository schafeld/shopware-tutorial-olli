<?php declare(strict_types=1);

namespace GotoWebinarGoogleSheetsExport\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Console command to reset/clear the export log table
 * Useful for development and testing to start fresh
 */
#[AsCommand(
    name: 'gotowebinar:reset-exports',
    description: 'Reset the export log table (delete all export entries)'
)]
class ResetExportsCommand extends Command
{
    public function __construct(
        private readonly EntityRepository $exportRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'status',
                's',
                InputOption::VALUE_OPTIONAL,
                'Only delete entries with specific status (pending, success, failed)',
                null
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Skip confirmation prompt'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();

        $io->title('Reset GoTo Webinar Export Logs');

        $status = $input->getOption('status');
        $force = $input->getOption('force');

        // Build criteria
        $criteria = new Criteria();
        
        if ($status !== null) {
            $validStatuses = ['pending', 'success', 'failed'];
            if (!in_array($status, $validStatuses, true)) {
                $io->error(sprintf('Invalid status "%s". Valid options: %s', $status, implode(', ', $validStatuses)));
                return Command::FAILURE;
            }
            $criteria->addFilter(new EqualsFilter('exportStatus', $status));
            $io->info(sprintf('Filtering by status: %s', $status));
        }

        // Count entries to delete
        $result = $this->exportRepository->search($criteria, $context);
        $count = $result->getTotal();

        if ($count === 0) {
            $io->success('No export entries found to delete.');
            return Command::SUCCESS;
        }

        $io->warning(sprintf('Found %d export entry/entries to delete.', $count));

        // Confirm deletion
        if (!$force) {
            $confirmed = $io->confirm('Are you sure you want to delete these entries? This cannot be undone.', false);
            if (!$confirmed) {
                $io->note('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        // Delete entries in batches
        $io->section('Deleting export entries');
        
        $deleted = 0;
        $batchSize = 100;

        do {
            $criteria = new Criteria();
            $criteria->setLimit($batchSize);
            
            if ($status !== null) {
                $criteria->addFilter(new EqualsFilter('exportStatus', $status));
            }

            $entries = $this->exportRepository->search($criteria, $context);
            
            if ($entries->count() === 0) {
                break;
            }

            $ids = [];
            foreach ($entries as $entry) {
                $ids[] = ['id' => $entry->getId()];
            }

            $this->exportRepository->delete($ids, $context);
            $deleted += count($ids);
            
            $io->writeln(sprintf('  Deleted %d entries (total: %d)', count($ids), $deleted));
            
        } while ($entries->count() === $batchSize);

        $io->success(sprintf('Successfully deleted %d export entry/entries.', $deleted));

        return Command::SUCCESS;
    }
}
