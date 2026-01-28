<?php declare (strict_types=1);

namespace Learning\Bundle\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1700000010CreateRecommendationTables extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1700000010;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `learning_product_session` (
                `id` BINARY(16) NOT NULL,
                `session_id` VARCHAR(255) NOT NULL,
                `product_id` BINARY(16) NOT NULL,
                `product_version_id` BINARY(16) NOT NULL,
                `viewed_at` DATETIME(3) NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx.session_id` (`session_id`),
                KEY `idx.viewed_at` (`viewed_at`),
                CONSTRAINT `fk.learning_product_session.product_id` FOREIGN KEY (`product_id`, `product_version_id`)
                    REFERENCES `product` (`id`, `version_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        // Table for storing product recommendations
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `learning_product_recommendation` (
                `id` BINARY(16) NOT NULL,
                `source_product_id` BINARY(16) NOT NULL,
                `source_product_version_id` BINARY(16) NOT NULL,
                `recommended_product_id` BINARY(16) NOT NULL,
                `recommended_product_version_id` BINARY(16) NOT NULL,
                `affinity_score` FLOAT NOT NULL DEFAULT 0,
                `view_count` INT NOT NULL DEFAULT 0,
                `last_updated` DATETIME(3) NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq.source_recommended` (`source_product_id`, `recommended_product_id`),
                KEY `idx.affinity_score` (`affinity_score`),
                CONSTRAINT `fk.learning_recommendation.source_product_id`
                    FOREIGN KEY (`source_product_id`, `source_product_version_id`)
                    REFERENCES `product` (`id`, `version_id`) ON DELETE CASCADE,
                CONSTRAINT `fk.learning_recommendation.recommended_product_id`
                    FOREIGN KEY (`recommended_product_id`, `recommended_product_version_id`)
                    REFERENCES `product` (`id`, `version_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        $connection -> executeStatement('DROP TABLE IF EXISTS `learning_product_recommendation`');
        $connection -> executeStatement('DROP TABLE IF EXISTS `learning_product_session`');
    }
}
