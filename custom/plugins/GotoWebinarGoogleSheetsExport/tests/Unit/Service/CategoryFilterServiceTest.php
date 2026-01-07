<?php declare(strict_types=1);

namespace GotoWebinarGoogleSheetsExport\Tests\Unit\Service;

use GotoWebinarGoogleSheetsExport\Service\CategoryFilterService;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;

/**
 * Unit tests for CategoryFilterService
 */
class CategoryFilterServiceTest extends TestCase
{
    private CategoryFilterService $service;
    private EntityRepository $categoryRepository;
    private EntityRepository $productRepository;

    protected function setUp(): void
    {
        $this->categoryRepository = $this->createMock(EntityRepository::class);
        $this->productRepository = $this->createMock(EntityRepository::class);
        
        $this->service = new CategoryFilterService(
            $this->categoryRepository,
            $this->productRepository
        );
    }

    public function testProductMatchesCategoryDirectMatch(): void
    {
        $categoryId = 'category-123';
        $context = Context::createDefaultContext();

        $product = new ProductEntity();
        $product->setCategoryIds([$categoryId, 'other-category']);

        $result = $this->service->productMatchesCategory($product, $categoryId, $context);

        $this->assertTrue($result);
    }

    public function testProductMatchesCategoryNoMatch(): void
    {
        $categoryId = 'category-123';
        $context = Context::createDefaultContext();

        $product = new ProductEntity();
        $product->setCategoryIds(['other-category-1', 'other-category-2']);

        // Mock empty category search result
        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('getIterator')->willReturn(new \ArrayIterator([]));
        
        $this->categoryRepository
            ->method('search')
            ->willReturn($searchResult);

        $result = $this->service->productMatchesCategory($product, $categoryId, $context);

        $this->assertFalse($result);
    }

    public function testProductMatchesCategoryEmptyCategories(): void
    {
        $categoryId = 'category-123';
        $context = Context::createDefaultContext();

        $product = new ProductEntity();
        $product->setCategoryIds([]);

        $result = $this->service->productMatchesCategory($product, $categoryId, $context);

        $this->assertFalse($result);
    }

    public function testGetCategoryByName(): void
    {
        $categoryName = 'GotoWebinar';
        $context = Context::createDefaultContext();

        $category = new CategoryEntity();
        $category->setId('category-123');
        $category->setName($categoryName);

        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('first')->willReturn($category);

        $this->categoryRepository
            ->expects($this->once())
            ->method('search')
            ->willReturn($searchResult);

        $result = $this->service->getCategoryByName($categoryName, $context);

        $this->assertInstanceOf(CategoryEntity::class, $result);
        $this->assertEquals('category-123', $result->getId());
    }

    public function testGetCategoryByNameNotFound(): void
    {
        $categoryName = 'NonExistent';
        $context = Context::createDefaultContext();

        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('first')->willReturn(null);

        $this->categoryRepository
            ->method('search')
            ->willReturn($searchResult);

        $result = $this->service->getCategoryByName($categoryName, $context);

        $this->assertNull($result);
    }
}
