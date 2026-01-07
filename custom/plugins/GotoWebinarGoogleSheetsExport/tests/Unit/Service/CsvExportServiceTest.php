<?php declare(strict_types=1);

namespace GotoWebinarGoogleSheetsExport\Tests\Unit\Service;

use GotoWebinarGoogleSheetsExport\Service\CsvExportService;
use GotoWebinarGoogleSheetsExport\Core\Content\OrderExport\OrderExportEntity;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CsvExportService
 */
class CsvExportServiceTest extends TestCase
{
    private CsvExportService $service;

    protected function setUp(): void
    {
        $this->service = new CsvExportService();
    }

    public function testGetCsvHeaders(): void
    {
        $headers = $this->service->getCsvHeaders();

        $this->assertIsArray($headers);
        $this->assertCount(9, $headers);
        $this->assertContains('Export Date', $headers);
        $this->assertContains('Order Number', $headers);
        $this->assertContains('Email', $headers);
    }

    public function testGenerateCsvWithEmptyArray(): void
    {
        $csv = $this->service->generateCsv([]);

        // Should still contain headers
        $lines = explode("\n", trim($csv));
        $this->assertCount(1, $lines);
        $this->assertStringContainsString('Export Date', $lines[0]);
    }

    public function testGenerateCsvWithExportEntities(): void
    {
        // Create mock export entities
        $export1 = $this->createMockExport('10001', 'WEB-001', 'John', 'Doe', 'john@example.com');
        $export2 = $this->createMockExport('10002', 'WEB-002', 'Jane', 'Smith', 'jane@example.com');

        $csv = $this->service->generateCsv([$export1, $export2]);

        $lines = explode("\n", trim($csv));
        
        // Should have header + 2 data rows
        $this->assertCount(3, $lines);
        
        // Check data rows contain expected values
        $this->assertStringContainsString('10001', $csv);
        $this->assertStringContainsString('WEB-001', $csv);
        $this->assertStringContainsString('John', $csv);
        $this->assertStringContainsString('jane@example.com', $csv);
    }

    private function createMockExport(
        string $orderNumber,
        string $productNumber,
        string $firstName,
        string $lastName,
        string $email
    ): OrderExportEntity {
        $export = $this->createMock(OrderExportEntity::class);
        
        $export->method('getOrderNumber')->willReturn($orderNumber);
        $export->method('getProductNumber')->willReturn($productNumber);
        $export->method('getCustomerFirstName')->willReturn($firstName);
        $export->method('getCustomerLastName')->willReturn($lastName);
        $export->method('getCustomerEmail')->willReturn($email);
        $export->method('getSalesChannelName')->willReturn('Storefront');
        $export->method('getExportStatus')->willReturn('success');
        $export->method('getExportedAt')->willReturn(new \DateTime('2025-01-07 10:00:00'));
        $export->method('getErrorMessage')->willReturn(null);

        return $export;
    }
}
