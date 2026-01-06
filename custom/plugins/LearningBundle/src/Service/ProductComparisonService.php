<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;

class ProductComparisonService
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function recordComparison(
        string $productId1,
        string $productId2,
        ?string $customerId,
        Context $context
    ): void {
        // Ensure consistent ordering (smaller ID first)
        if ($productId1 > $productId2) {
            [$productId1, $productId2] = [$productId2, $productId1];
        }

        $sql = <<<SQL
INSERT INTO learning_product_comparison 
    (id, product_id_1, product_id_2, customer_id, comparison_count, created_at, updated_at)
VALUES 
    (UNHEX(?), UNHEX(?), UNHEX(?), ?, 1, NOW(3), NOW(3))
ON DUPLICATE KEY UPDATE 
    comparison_count = comparison_count + 1,
    updated_at = NOW(3)
SQL;

        $this->connection->executeStatement($sql, [
            bin2hex(random_bytes(16)),
            $productId1,
            $productId2,
            $customerId ? hex2bin($customerId) : null,
        ]);
    }

    public function getComparisonStats(Context $context): array
    {
        $sql = <<<SQL
SELECT 
    COUNT(DISTINCT id) as total_comparisons,
    COUNT(DISTINCT customer_id) as unique_customers,
    AVG(comparison_count) as avg_comparisons_per_pair
FROM learning_product_comparison
SQL;

        return $this->connection->fetchAssociative($sql) ?: [];
    }

    public function getPopularCombinations(int $limit, Context $context): array
    {
        $sql = <<<SQL
SELECT 
    LOWER(HEX(lpc.product_id_1)) as product_id_1,
    LOWER(HEX(lpc.product_id_2)) as product_id_2,
    p1.product_number as product_number_1,
    p2.product_number as product_number_2,
    pt1.name as product_name_1,
    pt2.name as product_name_2,
    SUM(lpc.comparison_count) as total_comparisons
FROM learning_product_comparison lpc
LEFT JOIN product p1 ON lpc.product_id_1 = p1.id
LEFT JOIN product p2 ON lpc.product_id_2 = p2.id
LEFT JOIN product_translation pt1 ON p1.id = pt1.product_id AND pt1.language_id = UNHEX(?)
LEFT JOIN product_translation pt2 ON p2.id = pt2.product_id AND pt2.language_id = UNHEX(?)
GROUP BY lpc.product_id_1, lpc.product_id_2
ORDER BY total_comparisons DESC
LIMIT ?
SQL;

        $languageId = $context->getLanguageId();
        return $this->connection->fetchAllAssociative($sql, [
            $languageId,
            $languageId,
            $limit,
        ]);
    }
}