<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Learning\Bundle\Service\MessageService;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestWelcomeCommand extends Command
{
    private MessageService $messageService;

    public function __construct(MessageService $messageService)
    {
        parent::__construct();
        $this->messageService = $messageService;
    }

    protected function configure(): void
    {
        $this->setName('learning:test-welcome')
            ->setDescription('Test custom welcome event')
            ->addArgument('name', InputArgument::OPTIONAL, 'Customer name', 'Test User');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $message = $this->messageService->generateWelcomeMessage($name, Context::createDefaultContext());
        
        $output->writeln('<info>Generated message:</info>');
        $output->writeln($message);
        
        return Command::SUCCESS;
    }
}
