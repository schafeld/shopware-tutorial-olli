<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Learning\Bundle\Event\CustomWelcomeEvent;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Learning\Bundle\Exception\ValidationException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MessageService
{
    private LoggerInterface $logger;
    private SystemConfigService $systemConfigService;
    private CounterService $counterService;
    private ValidationService $validationService;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        LoggerInterface $logger, 
        SystemConfigService $systemConfigService,
        CounterService $counterService,
        ValidationService $validationService,
        EventDispatcherInterface $eventDispatcher
        )
    {
        $this->logger = $logger;
        $this->systemConfigService = $systemConfigService;
        $this -> counterService = $counterService;
        $this->validationService = $validationService;
        $this->eventDispatcher = $eventDispatcher;
    }

        // translations array for welcome messages
    private const TRANSLATIONS = [
        'en' => [
            'welcome' => 'Welcome to the Shopware Development department',
            'goodbye' => 'Goodbye',
            'hello' => 'Hello',
            'today_is' => 'Today is',
        ],
        'de' => [
            'welcome' => 'Willkommen zur Shopware-Entwicklungsabteilung',
            'goodbye' => 'Auf Wiedersehen',
            'hello' => 'Hallo',
            'today_is' => 'Heute ist',
        ],
        'es' => [
            'welcome' => 'Bienvenido al departamento de desarrollo de Shopware',
            'goodbye' => 'Adiós',
            'hello' => 'Hola',
            'today_is' => 'Hoy es',
        ],
    ];

    /**
     * @throws ValidationException
     */    
    public function generateWelcomeMessage(string $name, Context $context): string
    {

        // Validate and sanitize the name input
        try {
            $name = $this -> validationService -> processName($name);
        } catch (ValidationException $e) {
            $this -> logger -> warning('Validation failed for name: {name}', [
                'name' => $name,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        // Increment the the counter each time a message is generated
        $messageNumber = $this -> counterService -> incrementCount();

        // Get the selected language from configuration, default: en
        $language = $this->systemConfigService->get('LearningBundle.config.greetingLanguage') ?? 'en';

        // Get the welcome prefix based on selected language, with fallback to config or hard-coded default
        $prefix = self::TRANSLATIONS[$language]['welcome'] ?? $this->systemConfigService->getString('LearningBundle.config.welcomePrefix') ?? 'Welcome to Shopware development';

        $format = $this->systemConfigService->getString('LearningBundle.config.messageFormat') ??'simple';

        // Use the custom message that can be entered in the dashboard as 'custom'.
        $customMessage = $prefix = $this->systemConfigService->getString('LearningBundle.config.welcomePrefix') ?? 'Welcome to Shopware development';

        $message = match($format) {
            'simple'    => sprintf(' %s, %s! ' . $this->getGreeting('goodbye') . '.', $this->getGreeting('welcome'), $name),
            'detailed'  => sprintf('%s. ' . $this->getGreeting('hello') . ' %s! ' . $this->getGreeting('today_is') . ' %s. ' . $this->getGreeting('goodbye') . '.' , $this->getGreeting('welcome'), $name, date('Y-m-d')),
            'custom'    => sprintf('%s! ' . $customMessage , $name),
            default     => sprintf($prefix. ', %s! ', $name),
        };

        // Dispatch a custom event - other plugins can modify the message
        $event = new CustomWelcomeEvent($name, $message, $context);
        $this->eventDispatcher->dispatch($event);

        // Get potentially modified message from event
        $finalMessage = $event->getMessage();

        // Check if logging is enabled
        $enableLogging = $this->systemConfigService->getBool('LearningBundle.config.enableLogging') ?? true;

        if ($enableLogging) {
            $this->logger->info('Generated welcome message for {name}', [
                'name' => $name,
                'message' => $message,
                'format' => $format,
                'language' => $language,
                'message_number' => $messageNumber,
            ]);
        }   
        
        return $finalMessage;
    }

    /**
     * Get a greeting in the configured language
     */
    public function getGreeting(string $type = 'hello'): string
    {
        $language = $this->systemConfigService->get('LearningBundle.config.greetingLanguage') ?? 'en';
        return self::TRANSLATIONS[$language][$type] ?? self::TRANSLATIONS['en'][$type];
    }

    public function getPluginInfo(): array
    {

        $stats = $this->counterService->getStatistics();
        
        return [
            'name' => 'LearningBundle',
            'version' => '1.0.0',
            'author' => 'Learning Developer',
            'features' => [
                'Message Generation',
                'Logging',
                'Service Container',
                'Configuration Management',
                'Multi-language Support',
                'Message Counter',
            ],
            'total_messages_generated' => $stats['count'],
        ];
    }
}