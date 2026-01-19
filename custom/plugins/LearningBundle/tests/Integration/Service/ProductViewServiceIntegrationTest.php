<?php declare(strict_types=1);

namespace Learning\Bundle\Tests\Integration\Service;

use Learning\Bundle\Service\ProductViewService;
use Learning\Bundle\Tests\Integration\LearningIntegrationTestBehaviour;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;

class ProductViewServiceIntegrationTest extends TestCase
{
    use LearningIntegrationTestBehaviour;

    private ProductViewService $service;

    protected function setUp(): void
    {
        $this->service = $this->getContainer()->get(ProductViewService::class);
    }

    public function testRecordAndRetrieveView(): void
    {
        $context = Context::createDefaultContext();
        $productId = $this->getProductId();
        
        // Record a product view
        $this->service->recordView($productId, null, 'Test User Agent', $context);

        // Retrieve view count
        $count = $this->service->getProductViewCount($productId, $context);

        // Assert it was recorded
        $this->assertGreaterThanOrEqual(1, $count);
    }


    public function testMultipleViewsIncrement(): void
    {
        $context = Context::createDefaultContext();
        $productId = $this->getProductId();

        // Get initial count
        $initialCount = $this->service->getProductViewCount($productId, $context);

        // Record multiple views
        $this->service->recordView($productId, null, 'Test', $context);
        $this->service->recordView($productId, null, 'Test', $context);
        $this->service->recordView($productId, null, 'Test', $context);

        // Check updated count
        $newCount = $this->service->getProductViewCount($productId, $context);
        $this->assertEquals($initialCount + 3, $newCount);
    }


    public function testGetMostViewedProducts(): void
    {
        $context = Context::createDefaultContext();
        $productId = $this->getProductId();

        // Record multiple views for the same product
        for ($i = 0; $i < 10; $i++) {
            $this->service->recordView($productId, null, 'Test', $context);
        }

        // Get most viewed products
        $popular = $this->service->getMostViewedProducts(5, $context);

        // Assert we got results
        $this->assertIsArray($popular);
        $this->assertNotEmpty($popular);
    }
}