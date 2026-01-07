<?php declare(strict_types=1);

namespace GotoWebinarGoogleSheetsExport\ScheduledTask;

use GotoWebinarGoogleSheetsExport\Service\GoogleSheetsService;
use GotoWebinarGoogleSheetsExport\Service\OrderExportService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * Handler for scheduled export task
 * Processes pending exports and sends them to Google Sheets
 */
class ExportOrdersTaskHandler extends ScheduledTaskHandler
{
    private const CONFIG_PREFIX = 'GotoWebinarGoogleSheetsExport.config.';

    public function __construct(
        EntityRepository $scheduledTaskRepository,
        private readonly OrderExportService $orderExportService,
        private readonly GoogleSheetsService $googleSheetsService,
        private readonly SystemConfigService $systemConfigService,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($scheduledTaskRepository);
    }

    public static function getHandledMessages(): iterable
    {
        return [ExportOrdersTask::class];
    }

    public function run(): void
    {
        // Check if plugin is enabled
        $enabled = $this->systemConfigService->get(self::CONFIG_PREFIX . 'enabled');
        if (!$enabled) {
            return;
        }

        // Check if export interval is not disabled
        $interval = $this->systemConfigService->get(self::CONFIG_PREFIX . 'exportInterval');
        if ($interval === 'disabled') {
            return;
        }

        // Check if Google Sheets is configured
        if (!$this->googleSheetsService->isConfigured()) {
            $this->logger->warning('Google Sheets not configured, skipping export');
            return;
        }

        try {
            $this->processExports();
        } catch (\Exception $e) {
            $this->logger->error('Scheduled export task failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Process pending exports
     */
    private function processExports(): void
    {
        $context = Context::createDefaultContext();
        $batchSize = (int) ($this->systemConfigService->get(self::CONFIG_PREFIX . 'batchSize') ?? 50);
        
        $sheetId = $this->systemConfigService->get(self::CONFIG_PREFIX . 'googleSheetId');
        $worksheetName = $this->systemConfigService->get(self::CONFIG_PREFIX . 'worksheetName') ?? 'Bestellungen';

        if (!$sheetId) {
            $this->logger->error('Google Sheet ID not configured');
            return;
        }

        // Get pending exports
        $pendingExports = $this->orderExportService->getPendingExports($context, $batchSize);

        if (empty($pendingExports)) {
            $this->logger->info('No pending exports found');
            return;
        }

        $this->logger->info('Processing pending exports', [
            'count' => count($pendingExports),
        ]);

        $rows = [];
        $exportMap = [];

        foreach ($pendingExports as $export) {
            $row = [
                $export->getCustomerFirstName(),
                $export->getCustomerLastName(),
                $export->getOrderNumber(),
                $export->getProductNumber(),
                $export->getSalesChannelName(),
                $export->getCustomerEmail(),
            ];

            $rows[] = $row;
            $exportMap[] = $export->getId();
        }

        try {
            // Export to Google Sheets
            $this->googleSheetsService->appendRows($sheetId, $worksheetName, $rows);

            // Mark all as successful
            foreach ($exportMap as $exportId) {
                $this->orderExportService->updateExportStatus(
                    $exportId,
                    'success',
                    $context
                );
            }

            // Update last export timestamp
            $this->systemConfigService->set(
                self::CONFIG_PREFIX . 'lastExportTimestamp',
                new \DateTime()
            );

            $this->logger->info('Successfully exported rows to Google Sheets', [
                'count' => count($rows),
            ]);
        } catch (\Exception $e) {
            // Mark all as failed
            foreach ($exportMap as $exportId) {
                $this->orderExportService->updateExportStatus(
                    $exportId,
                    'failed',
                    $context,
                    $e->getMessage()
                );
            }

            $this->logger->error('Failed to export to Google Sheets', [
                'error' => $e->getMessage(),
                'count' => count($rows),
            ]);
        }
    }
}
