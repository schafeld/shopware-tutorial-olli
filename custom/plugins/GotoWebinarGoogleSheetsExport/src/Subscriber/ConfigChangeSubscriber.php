<?php declare(strict_types=1);

namespace GotoWebinarGoogleSheetsExport\Subscriber;

use GotoWebinarGoogleSheetsExport\ScheduledTask\ExportOrdersTask;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SystemConfig\Event\SystemConfigChangedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens for plugin configuration changes and updates the scheduled task interval
 */
class ConfigChangeSubscriber implements EventSubscriberInterface
{
    private const CONFIG_KEY = 'GotoWebinarGoogleSheetsExport.config.exportInterval';

    /**
     * Map configuration values to seconds
     */
    private const INTERVAL_MAP = [
        'disabled' => 86400,         // 24 hours (still runs but exits early)
        'every_15_minutes' => 900,   // 15 minutes
        'hourly' => 3600,            // 1 hour
        'every_4_hours' => 14400,    // 4 hours
        'daily' => 86400,            // 24 hours
        'weekly' => 604800,          // 7 days
    ];

    public function __construct(
        private readonly EntityRepository $scheduledTaskRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SystemConfigChangedEvent::class => 'onConfigChanged',
        ];
    }

    public function onConfigChanged(SystemConfigChangedEvent $event): void
    {
        if ($event->getKey() !== self::CONFIG_KEY) {
            return;
        }

        $intervalKey = $event->getValue();
        $intervalSeconds = self::INTERVAL_MAP[$intervalKey] ?? 3600;

        $this->updateTaskInterval($intervalSeconds);
    }

    private function updateTaskInterval(int $intervalSeconds): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', ExportOrdersTask::getTaskName()));

        $context = \Shopware\Core\Framework\Context::createDefaultContext();
        $result = $this->scheduledTaskRepository->search($criteria, $context);

        if ($result->getTotal() === 0) {
            $this->logger->warning('Could not find scheduled task to update interval', [
                'taskName' => ExportOrdersTask::getTaskName(),
            ]);
            return;
        }

        $task = $result->first();
        
        $this->scheduledTaskRepository->update([
            [
                'id' => $task->getId(),
                'runInterval' => $intervalSeconds,
            ]
        ], $context);

        $this->logger->info('Updated scheduled task interval', [
            'taskName' => ExportOrdersTask::getTaskName(),
            'intervalSeconds' => $intervalSeconds,
        ]);
    }
}
