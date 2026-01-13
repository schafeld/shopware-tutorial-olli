<?php declare(strict_types=1);

namespace GotoWebinarGoogleSheetsExport\Service;

use GotoWebinarGoogleSheetsExport\Core\Content\OrderExport\OrderExportEntity;

/**
 * Service for generating CSV exports from order export data
 */
class CsvExportService
{
    /**
     * Generate CSV content from export logs
     */
    public function generateCsv(array $exportLogs): string
    {
        $output = fopen('php://temp', 'r+');
        
        // Write headers
        fputcsv($output, $this->getCsvHeaders());
        
        // Write data rows
        foreach ($exportLogs as $log) {
            if ($log instanceof OrderExportEntity) {
                fputcsv($output, $this->formatRow($log));
            }
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * Get CSV column headers
     */
    public function getCsvHeaders(): array
    {
        return [
            'Export Date',
            'Order Number',
            'Product Number',
            'First Name',
            'Last Name',
            'Email',
            'Sales Channel',
            'Status',
            'Error Message'
        ];
    }

    /**
     * Format a single export log as a CSV row
     */
    private function formatRow(OrderExportEntity $log): array
    {
        return [
            $log->getExportedAt() ? $log->getExportedAt()->format('Y-m-d H:i:s') : '-',
            $log->getOrderNumber(),
            $log->getProductNumber(),
            $log->getCustomerFirstName(),
            $log->getCustomerLastName(),
            $log->getCustomerEmail(),
            $log->getSalesChannelName(),
            $log->getExportStatus(),
            $log->getErrorMessage() ?? ''
        ];
    }
}
