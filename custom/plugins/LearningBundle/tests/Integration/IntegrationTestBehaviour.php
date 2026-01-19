<?php declare(strict_types=1);

namespace Learning\Bundle\Tests\Integration;

use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Tax\TaxDefinition;

trait LearningIntegrationTestBehaviour
{
    use IntegrationTestBehaviour;
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    protected function getProductId(): string
    {
        // Get first product from database for testing
        $connection = $this->getContainer()->get('Doctrine\DBAL\Connection');
        $result = $connection->fetchOne('SELECT LOWER(HEX(id)) FROM product LIMIT 1');

        return $result ?: $this->createTestProduct();
    }

    protected function createTestProduct(): string
    {
        $productId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        
        /** @var EntityRepository $productRepository */
        $productRepository = $this->getContainer()->get('product.repository');
        
        // Get tax ID
        $connection = $this->getContainer()->get('Doctrine\DBAL\Connection');
        $taxId = $connection->fetchOne('SELECT LOWER(HEX(id)) FROM tax LIMIT 1');
        
        // Get sales channel ID  
        $salesChannelId = $connection->fetchOne('SELECT LOWER(HEX(id)) FROM sales_channel LIMIT 1');
        
        $productRepository->create([
            [
                'id' => $productId,
                'productNumber' => 'TEST-' . $productId,
                'name' => 'Test Product',
                'stock' => 10,
                'price' => [
                    [
                        'currencyId' => Defaults::CURRENCY,
                        'gross' => 99.99,
                        'net' => 84.03,
                        'linked' => false,
                    ],
                ],
                'taxId' => $taxId,
                'visibilities' => [
                    [
                        'salesChannelId' => $salesChannelId,
                        'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                    ],
                ],
            ],
        ], $context);
        
        return $productId;
    }
}

