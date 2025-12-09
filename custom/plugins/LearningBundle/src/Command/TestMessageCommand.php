<?php declare(strict_types=1);

namespace Learning\Bundle\Command;

use Learning\Bundle\Exception\ValidationException;
use Learning\Bundle\Service\MessageService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TestMessageCommand extends Command
{
    private MessageService $messageService;

    public function __construct(MessageService $messageService)
    {
        parent::__construct();
        $this->messageService = $messageService;
    }

    protected function configure(): void
    {
        $this
            ->setName('learning:test-message')
            ->setDescription('Test the MessageService')
            ->addArgument('name', InputArgument::OPTIONAL, 'Your name', 'Developer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = (string) $input->getArgument('name');

        try {
            // Test message generation
            $message = $this->messageService->generateWelcomeMessage($name);
            $io->success($message);

            // Display plugin info
            $info = $this->messageService->getPluginInfo();
            $io->section('Plugin Information');
            $io->listing($info['features']);
            $io -> text(sprintf('Total messages generated: %d', $info['total_messages_generated']));

            return Command::SUCCESS;

        } catch (ValidationException $e) {
            $io->error('Validation error: ' . $e->getMessage());
            $io -> note('Please provide a valid name consisting of alphabetic characters only.');
            return Command::FAILURE;
        } catch (\Throwable $e) {
            $io->error('An unexpected error occurred: ' . $e->getMessage());
            return Command::FAILURE;
        }   
    }
}