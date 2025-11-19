<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class MessageService
{
    private LoggerInterface $logger;
    private SystemConfigService $systemConfigService;

    public function __construct(
        LoggerInterface $logger, 
        SystemConfigService $systemConfigService)
    {
        $this->logger = $logger;
        $this->systemConfigService = $systemConfigService;
    }

    public function generateWelcomeMessage(string $name): string
    {
        $prefix = $this->systemConfigService->getString('LearningBundle.config.welcomePrefix') ?? 'Welcome to Shopware development';

        $format = $this->systemConfigService->getString('LearningBundle.config.messageFormat') ??'simple';

        $message = match($format) {
            'detailed' => sprintf('%s, dear %s! We are thrilled to have you on board. Today is %s.', $prefix, $name, date('Y-m-d')),
            'custom' => sprintf('[CUSTOM] %s, %s! Enjoy your Shopware journey.', $prefix, $name),
            default => sprintf('%s, %s!', $prefix, $name),
        };

        // Check if logging is enabled
        $enableLogging = $this->systemConfigService->getBool('LearningBundle.config.enableLogging') ?? true;

        if ($enableLogging) {
            $this->logger->info('Generated welcome message for {name}', [
                'name' => $name,
                'message' => $message,
                'format' => $format,
            ]);
        }   
        
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
                'Service Container',
                'Configuration Management'
            ]
        ];
    }
}