<?php declare(strict_types=1);

namespace GotoWebinarGoogleSheetsExport\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

/**
 * Scheduled task definition for automatic order exports
 */
class ExportOrdersTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'gotowebinar.google_sheets_export';
    }

    public static function getDefaultInterval(): int
    {
        // Default: 1 hour (3600 seconds)
        return 3600;
    }
}
