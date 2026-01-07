<?php declare(strict_types=1);

namespace GotoWebinarGoogleSheetsExport\Command;

use GotoWebinarGoogleSheetsExport\Service\GoogleSheetsService;
use GotoWebinarGoogleSheetsExport\Service\OrderExportService;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Console command for manual export of orders
 */
#[AsCommand(
    name: 'gotowebinar:export-orders',
    description: 'Manually export pending orders to Google Sheets'
)]
class ExportOrdersCommand extends Command
{
    private const CONFIG_PREFIX = 'GotoWebinarGoogleSheetsExport.config.';

    public function __construct(
        private readonly OrderExportService $orderExportService,
        private readonly GoogleSheetsService $googleSheetsService,
        private readonly SystemConfigService $systemConfigService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Maximum number of exports to process',
                50
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force export even if plugin is disabled'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();

        $io->title('GoTo Webinar Google Sheets Export');

        // Check if plugin is enabled
        $enabled = $this->systemConfigService->get(self::CONFIG_PREFIX . 'enabled');
        if (!$enabled && !$input->getOption('force')) {
            $io->error('Plugin is disabled. Use --force to export anyway.');
            return Command::FAILURE;
        }

        // Check if Google Sheets is configured
        if (!$this->googleSheetsService->isConfigured()) {
            $io->error('Google Sheets integration is not configured. Please configure OAuth credentials first.');
            return Command::FAILURE;
        }

        $sheetId = $this->systemConfigService->get(self::CONFIG_PREFIX . 'googleSheetId');
        $worksheetName = $this->systemConfigService->get(self::CONFIG_PREFIX . 'worksheetName') ?? 'Bestellungen';

        if (!$sheetId) {
            $io->error('Google Sheet ID is not configured.');
            return Command::FAILURE;
        }

        $limit = (int) $input->getOption('limit');

        $io->section('Configuration');
        $io->table(
            ['Setting', 'Value'],
            [
                ['Sheet ID', $sheetId],
                ['Worksheet', $worksheetName],
                ['Batch Size', $limit],
            ]
        );

        // Get pending exports
        $io->section('Fetching pending exports');
        $pendingExports = $this->orderExportService->getPendingExports($context, $limit);

        if (empty($pendingExports)) {
            $io->success('No pending exports found.');
            return Command::SUCCESS;
        }

        $io->writeln(sprintf('Found %d pending export(s)', count($pendingExports)));

        // Prepare rows for export
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

        // Export to Google Sheets
        $io->section('Exporting to Google Sheets');
        
        try {
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

            $io->success(sprintf('Successfully exported %d row(s) to Google Sheets', count($rows)));
            
            return Command::SUCCESS;
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

            $io->error('Export failed: ' . $e->getMessage());
            
            return Command::FAILURE;
        }
    }
}
