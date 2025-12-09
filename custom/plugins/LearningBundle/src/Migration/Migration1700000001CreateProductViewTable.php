<?php declare(strict_types=1);

namespace Learning\Bundle\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1700000001CreateProductViewTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1700000001;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `learning_product_view` (
    `id` BINARY(16) NOT NULL,
    `product_id` BINARY(16) NOT NULL,
    `product_version_id` BINARY(16) NOT NULL,
    `customer_id` BINARY(16) NULL,
    `view_count` INT NOT NULL DEFAULT 1,
    `last_viewed_at` DATETIME(3) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    KEY `fk.learning_product_view.product_id` (`product_id`,`product_version_id`),
    KEY `fk.learning_product_view.customer_id` (`customer_id`),
    KEY `idx.learning_product_view.last_viewed_at` (`last_viewed_at`),
    CONSTRAINT `fk.learning_product_view.product_id` 
        FOREIGN KEY (`product_id`,`product_version_id`) 
        REFERENCES `product` (`id`,`version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk.learning_product_view.customer_id` 
        FOREIGN KEY (`customer_id`) 
        REFERENCES `customer` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // Called when uninstalling - drop table
        $sql = <<<SQL
DROP TABLE IF EXISTS `learning_product_view`;
SQL;

        $connection->executeStatement($sql);
    }
}