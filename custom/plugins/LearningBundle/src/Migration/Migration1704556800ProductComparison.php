<?php declare(strict_types=1);

namespace Learning\Bundle\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1704556800ProductComparison extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1704556800;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `learning_product_comparison` (
    `id` BINARY(16) NOT NULL,
    `product_id_1` BINARY(16) NOT NULL,
    `product_id_2` BINARY(16) NOT NULL,
    `customer_id` BINARY(16) NULL,
    `comparison_count` INT NOT NULL DEFAULT 1,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    KEY `idx_product_pair` (`product_id_1`, `product_id_2`),
    CONSTRAINT `fk_learning_comparison_product_1` FOREIGN KEY (`product_id_1`) REFERENCES `product` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_learning_comparison_product_2` FOREIGN KEY (`product_id_2`) REFERENCES `product` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_learning_comparison_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // Implement if needed
    }
}