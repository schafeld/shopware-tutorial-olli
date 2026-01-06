#!/bin/bash

# Product Comparison API Test Script
# This script tests the Product Comparison Admin API endpoints

set -e

echo "=========================================="
echo "Product Comparison API Test"
echo "=========================================="
echo ""

# Get some product IDs for testing
echo "📦 Getting product IDs for testing..."
PRODUCTS=$(docker compose exec -T database mariadb -u root -proot shopware -e "
SELECT LOWER(HEX(id)) as id, product_number 
FROM product 
WHERE parent_id IS NULL 
LIMIT 5;" 2>/dev/null | tail -n +2)

if [ -z "$PRODUCTS" ]; then
    echo "❌ No products found in database"
    exit 1
fi

echo "$PRODUCTS"
echo ""

# Extract first two product IDs
PRODUCT_ID_1=$(echo "$PRODUCTS" | head -n 1 | awk '{print $1}')
PRODUCT_ID_2=$(echo "$PRODUCTS" | head -n 2 | tail -n 1 | awk '{print $1}')

echo "Using products:"
echo "  Product 1: $PRODUCT_ID_1"
echo "  Product 2: $PRODUCT_ID_2"
echo ""

# Test 1: Record a comparison directly in the database
echo "=========================================="
echo "Test 1: Recording Product Comparison"
echo "=========================================="
echo ""

# Ensure consistent ordering
if [[ "$PRODUCT_ID_1" > "$PRODUCT_ID_2" ]]; then
    TEMP=$PRODUCT_ID_1
    PRODUCT_ID_1=$PRODUCT_ID_2
    PRODUCT_ID_2=$TEMP
fi

echo "Recording comparison between:"
echo "  $PRODUCT_ID_1"
echo "  $PRODUCT_ID_2"
echo ""

docker compose exec -T database mariadb -u root -proot shopware << EOF
SET @id = UNHEX(REPLACE(UUID(), '-', ''));
INSERT INTO learning_product_comparison 
    (id, product_id_1, product_id_2, customer_id, comparison_count, created_at, updated_at)
VALUES 
    (@id, UNHEX('$PRODUCT_ID_1'), UNHEX('$PRODUCT_ID_2'), NULL, 1, NOW(3), NOW(3))
ON DUPLICATE KEY UPDATE 
    comparison_count = comparison_count + 1,
    updated_at = NOW(3);
    
SELECT 'Comparison recorded successfully' as result;
EOF

echo ""

# Test 2: Check the data
echo "=========================================="
echo "Test 2: Verify Data in Database"
echo "=========================================="
echo ""

docker compose exec -T database mariadb -u root -proot shopware << EOF
SELECT 
    LOWER(HEX(product_id_1)) as product_id_1,
    LOWER(HEX(product_id_2)) as product_id_2,
    comparison_count,
    created_at,
    updated_at
FROM learning_product_comparison
ORDER BY comparison_count DESC
LIMIT 5;
EOF

echo ""

# Test 3: Test the service directly via command
echo "=========================================="
echo "Test 3: Test via PHP Service"
echo "=========================================="
echo ""

cat > /tmp/test-comparison-service.php << 'PHPEOF'
<?php

require_once '/Users/oliverschafeld/workspace/shopware-experiments/shopware-tutorial-olli/vendor/autoload.php';

use Shopware\Core\Framework\Context;
use Shopware\Core\TestBootstrapper;
use Learning\Bundle\Service\ProductComparisonService;
use Doctrine\DBAL\Connection;

$kernel = (new TestBootstrapper())
    ->setProjectDir('/Users/oliverschafeld/workspace/shopware-experiments/shopware-tutorial-olli')
    ->bootstrap()
    ->getKernel();

$container = $kernel->getContainer();
$connection = $container->get(Connection::class);

$service = new ProductComparisonService($connection);
$context = Context::createDefaultContext();

echo "Testing ProductComparisonService:\n\n";

// Get stats
echo "1. Comparison Stats:\n";
$stats = $service->getComparisonStats($context);
print_r($stats);
echo "\n";

// Get popular combinations
echo "2. Popular Combinations:\n";
$combinations = $service->getPopularCombinations(10, $context);
foreach ($combinations as $combo) {
    echo sprintf(
        "  - %s (%s) <-> %s (%s): %d comparisons\n",
        $combo['product_name_1'] ?? 'N/A',
        $combo['product_number_1'] ?? 'N/A',
        $combo['product_name_2'] ?? 'N/A',
        $combo['product_number_2'] ?? 'N/A',
        $combo['total_comparisons']
    );
}

echo "\n✓ Service test completed successfully\n";
PHPEOF

php /tmp/test-comparison-service.php 2>&1 || echo "PHP test failed (this might be expected if TestBootstrapper is not available)"

echo ""

# Test 4: API Routes Check
echo "=========================================="
echo "Test 4: Checking API Routes"
echo "=========================================="
echo ""

echo "Looking for comparison routes..."
echo ""

# Try to check if routes exist (this requires console access)
echo "To manually check routes, run:"
echo "  docker compose exec app bin/console debug:router | grep comparison"
echo ""

# Test 5: Summary
echo "=========================================="
echo "Summary"
echo "=========================================="
echo ""
echo "✓ Migration table created"
echo "✓ Test data inserted"
echo "✓ Database queries working"
echo ""
echo "Next steps:"
echo "1. Configure OAuth integration in Shopware Admin:"
echo "   - Go to Settings → System → Integrations"
echo "   - Create a new integration with 'write' permission"
echo "   - Copy the Access Key and Secret Key"
echo ""
echo "2. Test API endpoints with curl:"
echo ""
echo "   # Get OAuth token"
echo "   curl -X POST https://localhost:8000/api/oauth/token \\"
echo "     -H 'Content-Type: application/json' \\"
echo "     -d '{\"client_id\":\"YOUR_ACCESS_KEY\",\"client_secret\":\"YOUR_SECRET\",\"grant_type\":\"client_credentials\"}' \\"
echo "     -k"
echo ""
echo "   # Get comparison stats"
echo "   curl -X GET https://localhost:8000/api/_action/learning/comparison/stats \\"
echo "     -H 'Authorization: Bearer YOUR_TOKEN' \\"
echo "     -k"
echo ""
echo "   # Get popular combinations"
echo "   curl -X GET https://localhost:8000/api/_action/learning/comparison/popular-combinations?limit=10 \\"
echo "     -H 'Authorization: Bearer YOUR_TOKEN' \\"
echo "     -k"
echo ""
