<?php declare(strict_types=1);

namespace Learning\Bundle\Tests\Unit\Service;

use Learning\Bundle\Core\Content\ProductView\ProductViewCollection;
use Learning\Bundle\Core\Content\ProductView\ProductViewEntity;
use Learning\Bundle\Service\ProductViewService;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class ProductViewServiceTest extends TestCase
{
    private ProductViewService $service;
    private EntityRepository $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EntityRepository::class);
        $this->service = new ProductViewService($this->repository);
    }

    public function testRecordViewCreatesNewRecord(): void
    {
        $productId = 'test-product-id';
        $customerId = 'test-customer-id';
        $userAgent = 'Test Browser';
        $context = Context::createDefaultContext();

        // Mock repository search returning no existing view
        $this->repository
            ->expects($this->once())
            ->method('search')
            ->willReturn($this->createEmptySearchResult());

        // Expect create to be called (not update)
        $this->repository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($data) use ($productId, $customerId) {
                $this->assertCount(1, $data);
                $this->assertEquals($productId, $data[0]['productId']);
                $this->assertEquals($customerId, $data[0]['customerId']);
                $this->assertEquals(1, $data[0]['viewCount']);
                return true;
            }));

        $this->service->recordView($productId, $customerId, $userAgent, $context);
    }


    public function testRecordViewUpdatesExistingEntry(): void
    {
        $productId = 'test-product-id';
        $context = Context::createDefaultContext();

        // Create existing view entry
        $existingView = new ProductViewEntity();
        $existingView->setId('existing-id');
        $existingView->setViewCount(5);

        // Mock repository search returning existing view
        $this->repository
            ->expects($this->once())
            ->method('search')
            ->willReturn($this->createSearchResultWithEntity($existingView));

        // Expect update to be called (not create)
        $this->repository
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(function ($data) {
                $this->assertCount(1, $data);
                $this->assertEquals(6, $data[0]['viewCount']);
                return true;
            }));

        $this->service->recordView($productId, null, null, $context);
    }
    
    public function testGetProductViewCountReturnsZeroForNoViews(): void
    {
        $productId = 'non-existent-product';
        $context = Context::createDefaultContext();

        $this->repository
            ->method('search')
            ->willReturn($this->createEmptySearchResult());
        
        $count = $this->service->getProductViewCount($productId, $context);
        $this->assertEquals(0, $count);
    }


    public function testGetProductViewCountSumsMultipleViews(): void
    {
        $productId = 'popular-product';
        $context = Context::createDefaultContext();

        // Create multiple view entries
        $view1 = new ProductViewEntity();
        $view1->setId('view-id-1');
        $view1->setViewCount(10);

        $view2 = new ProductViewEntity();
        $view2->setId('view-id-2');
        $view2->setViewCount(15);

        $collection = new ProductViewCollection([$view1, $view2]);

        $this->repository
            ->method('search')
            ->willReturn($this->createSearchResultWithCollection($collection));

        $count = $this->service->getProductViewCount($productId, $context);

        $this->assertEquals(25, $count);
    }


    private function createEmptySearchResult(): EntitySearchResult
    {
        return new EntitySearchResult(
            'learning_product_view',
            0,
            new ProductViewCollection(),
            null,
            new Criteria(),
            Context::createDefaultContext()
        );
    }


    private function createSearchResultWithEntity(ProductViewEntity $entity): EntitySearchResult
    {
        return new EntitySearchResult(
            'learning_product_view',
            1,
            new ProductViewCollection([$entity]),
            null,
            new Criteria(),
            Context::createDefaultContext()
        );
    }


    private function createSearchResultWithCollection(ProductViewCollection $collection): EntitySearchResult
    {
        return new EntitySearchResult(
            'learning_product_view',
            $collection->count(),
            $collection,
            null,
            new Criteria(),
            Context::createDefaultContext()
        );
    }
}