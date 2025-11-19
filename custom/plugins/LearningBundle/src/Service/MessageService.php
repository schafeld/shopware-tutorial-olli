<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Psr\Log\LoggerInterface;

class MessageService
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function generateWelcomeMessage(string $name): string
    {
        $message = sprintf('Welcome to Shopware development, %s!', $name);
        
        // Log the message
        $this -> logger -> info('Generated welcome message: ' ,[
            'name'=> $name,
            'message'=> $message,
        ]);
        
        return $message;
    }

    public function getPluginInfo(): array
    {
        return [
            'name' => 'LearningBundle',
            'version' => '1.0.0',
            'author' => 'Learning Developer',
            'features' => [
                'Message Generation',
                'Logging',
                'Service Container'
            ]
        ];
    }
}