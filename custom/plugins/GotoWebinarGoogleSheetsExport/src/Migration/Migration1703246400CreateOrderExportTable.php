<?php declare(strict_types=1);

namespace GotoWebinarGoogleSheetsExport\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Creates the database table for tracking order exports
 */
class Migration1703246400CreateOrderExportTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1703246400;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `gotowebinar_order_export` (
    `id` BINARY(16) NOT NULL,
    `order_id` BINARY(16) NOT NULL,
    `order_number` VARCHAR(255) NOT NULL,
    `product_id` BINARY(16) NOT NULL,
    `product_number` VARCHAR(255) NOT NULL,
    `customer_first_name` VARCHAR(255) NOT NULL,
    `customer_last_name` VARCHAR(255) NOT NULL,
    `customer_email` VARCHAR(255) NOT NULL,
    `sales_channel_name` VARCHAR(255) NOT NULL,
    `exported_at` DATETIME(3) NULL,
    `google_sheet_row_id` VARCHAR(255) NULL,
    `export_status` VARCHAR(50) NOT NULL DEFAULT 'pending',
    `error_message` TEXT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_exported_at` (`exported_at`),
    KEY `idx_export_status` (`export_status`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // No destructive changes
    }
}
