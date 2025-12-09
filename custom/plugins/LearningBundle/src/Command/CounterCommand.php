<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Learning\Bundle\Service\CounterService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CounterCommand extends Command
{
    private CounterService $counterService;

    public function __construct(CounterService $counterService)
    {
        parent::__construct();
        $this->counterService = $counterService;
    }

    protected function configure(): void
    {
        $this
            ->setName('learning:counter')
            ->setDescription('Manage the message counter')
            -> addOption('show', 's', InputOption::VALUE_NONE, 'Show counter statistics')
            -> addOption('reset', 'r', InputOption::VALUE_NONE, 'Reset the counter to zero')
            -> addOption('increment', 'i', InputOption::VALUE_NONE, 'Increment the counter by one');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        if ($input->getOption('show')) {
            $count = $this->counterService->getCount();
            $io->info("Current counter value: $count");
        } elseif ($input->getOption('reset')) {
            $this->counterService->resetCount();
            $io->success('Counter has been reset to zero.');
        } elseif ($input->getOption('increment')) {
            $newCount = $this->counterService->incrementCount();
            $io->success("Counter has been incremented to: $newCount.");
        } 

        // Default: show statistics
        $stats = $this->counterService->getStatistics();

        $io->title("Message Counter statistics:");
        $io->table(
            ['Metric', 'Value'],
            [
                ['Total Messages', $stats['count']],
                ['File Path', $stats['file_path']],
                ['File exists', $stats['file_exists'] ? 'Yes' : 'No'],
                ['Last Modified', $stats['file_modified'] ?? 'Never']
            ]
        );

        return Command::SUCCESS;
    }
}