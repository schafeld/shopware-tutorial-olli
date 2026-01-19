<?php declare(strict_types=1);

namespace Learning\Bundle\Tests\Unit\Service;

use Learning\Bundle\Event\CustomWelcomeEvent;
use Learning\Bundle\Exception\ValidationException;
use Learning\Bundle\Service\CounterService;
use Learning\Bundle\Service\MessageService;
use Learning\Bundle\Service\ValidationService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MessageServiceTest extends TestCase
{
    private MessageService $messageService;
    private LoggerInterface $logger;
    private SystemConfigService $systemConfigService;
    private EventDispatcherInterface $eventDispatcher;
    private CounterService $counterService;
    private ValidationService $validationService;

    protected function setUp(): void
    {
        // Create mocks for all dependencies
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->systemConfigService = $this->createMock(SystemConfigService::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->counterService = $this->createMock(CounterService::class);
        $this->validationService = $this->createMock(ValidationService::class);

        // Create service with all mocked dependencies
        $this->messageService = new MessageService(
            $this->logger,
            $this->systemConfigService,
            $this->counterService,
            $this->validationService,
            $this->eventDispatcher
        );
    }

    public function testGenerateWelcomeMessageWithSimpleFormat(): void
    {
        // Mock validation service to return sanitized name
        $this->validationService
            ->expects($this->once())
            ->method('processName')
            ->with('Olli')
            ->willReturn('Olli');

        // Mock counter service
        $this->counterService
            ->expects($this->once())
            ->method('incrementCount')
            ->willReturn(1);

        // Mock system config for language
        $this->systemConfigService
            ->method('get')
            ->with('LearningBundle.config.greetingLanguage')
            ->willReturn('en');

        // Mock system config for format and other string values
        $this->systemConfigService
            ->method('getString')
            ->willReturnMap([
                ['LearningBundle.config.messageFormat', null, 'simple'],
                ['LearningBundle.config.welcomePrefix', null, 'Welcome to Shopware development'],
            ]);

        // Mock logging config (disabled for cleaner test)
        $this->systemConfigService
            ->method('getBool')
            ->with('LearningBundle.config.enableLogging')
            ->willReturn(false);

        // Mock event dispatcher
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(CustomWelcomeEvent::class))
            ->willReturnCallback(function ($event) {
                return $event; // Return unchanged event
            });

        // Test message generation
        $context = Context::createDefaultContext();
        $message = $this->messageService->generateWelcomeMessage('Olli', $context);

        // Assert the message contains expected parts
        $this->assertStringContainsString('Olli', $message);
        $this->assertStringContainsString('Welcome', $message);
    }

    public function testGenerateWelcomeMessageWithDetailedFormat(): void
    {
        $this->validationService
            ->method('processName')
            ->willReturn('Developer');

        $this->counterService
            ->method('incrementCount')
            ->willReturn(2);

        $this->systemConfigService
            ->method('get')
            ->willReturn('en');

        $this->systemConfigService
            ->method('getString')
            ->willReturnMap([
                ['LearningBundle.config.messageFormat', null, 'detailed'],
                ['LearningBundle.config.welcomePrefix', null, 'Welcome to Shopware development'],
            ]);

        $this->systemConfigService
            ->method('getBool')
            ->willReturn(false);

        $this->eventDispatcher
            ->method('dispatch')
            ->willReturnCallback(fn($event) => $event);

        $context = Context::createDefaultContext();
        $message = $this->messageService->generateWelcomeMessage('Developer', $context);

        // Detailed format includes name and date
        $this->assertStringContainsString('Developer', $message);
        $this->assertStringContainsString(date('Y-m-d'), $message);
    }

    public function testGenerateWelcomeMessageDispatchesEvent(): void
    {
        $this->validationService
            ->method('processName')
            ->willReturn('Test');

        $this->counterService
            ->method('incrementCount')
            ->willReturn(1);

        $this->systemConfigService
            ->method('getBool')
            ->willReturn(false);

        // Verify event is dispatched with correct type
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($event) {
                return $event instanceof CustomWelcomeEvent 
                    && $event->getCustomerName() === 'Test';
            }))
            ->willReturnCallback(fn($event) => $event);

        $context = Context::createDefaultContext();
        $this->messageService->generateWelcomeMessage('Test', $context);
    }

    public function testGenerateWelcomeMessageLogsWhenEnabled(): void
    {
        $this->validationService
            ->method('processName')
            ->willReturn('User');

        $this->counterService
            ->method('incrementCount')
            ->willReturn(5);

        // Enable logging
        $this->systemConfigService
            ->method('getBool')
            ->with('LearningBundle.config.enableLogging')
            ->willReturn(true);

        $this->eventDispatcher
            ->method('dispatch')
            ->willReturnCallback(fn($event) => $event);

        // Expect logger to be called with correct message
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Generated welcome message for {name}',
                $this->callback(function ($context) {
                    return isset($context['name']) 
                        && $context['name'] === 'User'
                        && isset($context['message_number'])
                        && $context['message_number'] === 5;
                })
            );

        $context = Context::createDefaultContext();
        $this->messageService->generateWelcomeMessage('User', $context);
    }

    public function testGenerateWelcomeMessageDoesNotLogWhenDisabled(): void
    {
        $this->validationService
            ->method('processName')
            ->willReturn('User');

        $this->counterService
            ->method('incrementCount')
            ->willReturn(1);

        // Disable logging
        $this->systemConfigService
            ->method('getBool')
            ->with('LearningBundle.config.enableLogging')
            ->willReturn(false);

        $this->eventDispatcher
            ->method('dispatch')
            ->willReturnCallback(fn($event) => $event);

        // Logger should NEVER be called
        $this->logger
            ->expects($this->never())
            ->method('info');

        $context = Context::createDefaultContext();
        $this->messageService->generateWelcomeMessage('User', $context);
    }

    public function testGenerateWelcomeMessageThrowsValidationException(): void
    {
        // Mock validation to throw exception
        $this->validationService
            ->expects($this->once())
            ->method('processName')
            ->with('')
            ->willThrowException(new ValidationException('Name cannot be empty'));

        // Expect logger warning
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Validation failed for name: {name}',
                $this->callback(function ($context) {
                    return isset($context['name']) && isset($context['error']);
                })
            );

        // Expect exception to be thrown
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Name cannot be empty');

        $context = Context::createDefaultContext();
        $this->messageService->generateWelcomeMessage('', $context);
    }

    public function testGetGreetingReturnsCorrectTranslation(): void
    {
        // Test German translation
        $this->systemConfigService
            ->method('get')
            ->with('LearningBundle.config.greetingLanguage')
            ->willReturn('de');

        $greeting = $this->messageService->getGreeting('hello');

        $this->assertEquals('Hallo', $greeting);
    }

    public function testGetGreetingFallsBackToEnglish(): void
    {
        // Test invalid language falls back to English
        $this->systemConfigService
            ->method('get')
            ->willReturn('invalid_lang');

        $greeting = $this->messageService->getGreeting('hello');

        $this->assertEquals('Hello', $greeting);
    }

    public function testGetGreetingReturnsAllTranslationTypes(): void
    {
        $this->systemConfigService
            ->method('get')
            ->willReturn('es'); // Spanish

        $this->assertEquals('Hola', $this->messageService->getGreeting('hello'));
        $this->assertEquals('Adiós', $this->messageService->getGreeting('goodbye'));
        $this->assertEquals('Bienvenido al departamento de desarrollo de Shopware', 
            $this->messageService->getGreeting('welcome'));
    }

    public function testGetPluginInfoReturnsCorrectStructure(): void
    {
        // Mock counter statistics
        $this->counterService
            ->expects($this->once())
            ->method('getStatistics')
            ->willReturn(['count' => 42]);

        $info = $this->messageService->getPluginInfo();

        // Assert structure
        $this->assertIsArray($info);
        $this->assertArrayHasKey('name', $info);
        $this->assertArrayHasKey('version', $info);
        $this->assertArrayHasKey('author', $info);
        $this->assertArrayHasKey('features', $info);
        $this->assertArrayHasKey('total_messages_generated', $info);
        
        // Assert values
        $this->assertEquals('LearningBundle', $info['name']);
        $this->assertEquals('1.0.0', $info['version']);
        $this->assertEquals(42, $info['total_messages_generated']);
        
        // Assert features is an array
        $this->assertIsArray($info['features']);
        $this->assertContains('Message Generation', $info['features']);
    }
}