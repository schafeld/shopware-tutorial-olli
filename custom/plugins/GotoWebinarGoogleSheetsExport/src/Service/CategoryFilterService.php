<?php declare(strict_types=1);

namespace GotoWebinarGoogleSheetsExport\Service;

use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

/**
 * Service for filtering products by category
 * Handles category tree traversal for nested categories
 */
class CategoryFilterService
{
    public function __construct(
        private readonly EntityRepository $categoryRepository,
        private readonly EntityRepository $productRepository
    ) {
    }

    /**
     * Check if a product belongs to the specified category or its subcategories
     */
    public function productMatchesCategory(ProductEntity $product, string $categoryId, Context $context): bool
    {
        $productCategoryIds = $product->getCategoryIds() ?? [];
        
        if (empty($productCategoryIds)) {
            return false;
        }

        // Direct match
        if (in_array($categoryId, $productCategoryIds, true)) {
            return true;
        }

        // Check if any product category is a child of the target category
        return $this->isInCategoryTree($productCategoryIds, $categoryId, $context);
    }

    /**
     * Get category entity by name
     */
    public function getCategoryByName(string $categoryName, Context $context): ?CategoryEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $categoryName));
        $criteria->setLimit(1);

        $result = $this->categoryRepository->search($criteria, $context);
        
        return $result->first();
    }

    /**
     * Check if any of the product's categories are in the target category tree
     */
    private function isInCategoryTree(array $productCategoryIds, string $targetCategoryId, Context $context): bool
    {
        $criteria = new Criteria($productCategoryIds);

        $categories = $this->categoryRepository->search($criteria, $context);

        foreach ($categories as $category) {
            // getPath() returns a string like "|uuid1|uuid2|" not an array
            $path = $category->getPath();
            if ($path !== null && str_contains($path, $targetCategoryId)) {
                return true;
            }
        }

        return false;
    }
}
