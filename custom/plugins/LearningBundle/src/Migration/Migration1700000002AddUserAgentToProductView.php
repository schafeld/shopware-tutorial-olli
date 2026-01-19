<?php declare(strict_types=1);

namespace Learning\Bundle\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1700000002AddUserAgentToProductView extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1700000002;
    }

    public function update(Connection $connection): void
    {
        // Check if column already exists
        $columnExists = $connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.COLUMNS 
             WHERE TABLE_SCHEMA = DATABASE() 
             AND TABLE_NAME = 'learning_product_view' 
             AND COLUMN_NAME = 'user_agent'"
        );

        if (!$columnExists) {
            $sql = <<<SQL
ALTER TABLE `learning_product_view`
ADD COLUMN `user_agent` VARCHAR(255) NULL AFTER `view_count`;
SQL;
            $connection->executeStatement($sql);
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // drop column
        $sql = <<<SQL
ALTER TABLE `learning_product_view`
DROP COLUMN `user_agent`;
SQL;
        $connection->executeStatement($sql);
    }
}