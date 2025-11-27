<?php declare(strict_types= 1);

namespace Learning\Bundle\Service;

use Learning\Bundle\Core\Content\ProductView\ProductViewEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;

class ProductViewService
{
    private EntityRepository $productViewRepository;

    public function __construct(EntityRepository $productViewRepository)
    {
        $this->productViewRepository = $productViewRepository;
    }

    /**
     * Record a product view
     */
    public function recordView(
        string $productId,
        ?string $customerId,
        ?string $userAgent,
        Context $context
    ): void {
        // Check if a view record already exists for this product and customer
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productId', $productId));

        if($customerId) {
            $criteria->addFilter(new EqualsFilter('customerId', $customerId));
        }

        $existing = $this->productViewRepository->search($criteria, $context)->first();

        if ($existing) {
            // Update existing record
            $this->productViewRepository->update([[
                'id'=> $existing->getId(),
                'viewCount' => $existing->getViewCount() + 1,
                'lastViewedAt' => new \DateTime(),
                'userAgent' => $userAgent,
            ]], $context);
        } else {
            // Create new record
            $this->productViewRepository->create([[
                'id' => Uuid::randomHex(),
                'productId' => $productId,
                'customerId' => $customerId,
                'viewCount' => 1,
                'userAgent' => $userAgent,
                'lastViewedAt' => new \DateTime(),
            ]], $context);
        }
    }

    /**
     * Get view count for a product
     */
    public function getProductViewCount(string $productId, Context $context): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productId', $productId));
        
        $views = $this->productViewRepository->search($criteria, $context);

        $totalViews = 0;
        /** @var ProductViewEntity $view */
        foreach ($views as $view) {
            $totalViews += $view->getViewCount();
        }

        return $totalViews;
    }

    /**
     * Get most viewed products
     */
    public function getMostViewedProducts(int $limit, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addAssociation('product');
        $criteria->addSorting(new FieldSorting('viewCount', FieldSorting::DESCENDING));
        $criteria->setLimit($limit);

        $views = $this->productViewRepository->search($criteria, $context);

        $result = [];
        /** @var ProductViewEntity $view */
        foreach ($views as $view) {
            $product = $view->getProduct();
            $productName = $product?->getTranslated()['name'] 
                ?? $product?->getName() 
                ?? $product?->getProductNumber() 
                ?? 'N/A';
            
            $result[] = [
                'product_id' => $view->getProductId(),
                'product_name' => $productName,
                'view_count' => $view->getViewCount(),
                'last_viewed' => $view->getLastViewedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return $result;
    }

    /**
     * Get customer's viewed products
     */
    public function getCustomerViewedProducts(string $customerId, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));
        $criteria->addAssociation('product');
        $criteria->addSorting(new FieldSorting('lastViewedAt', FieldSorting::DESCENDING));

        $views = $this->productViewRepository->search($criteria, $context);

        $result = [];
        /** @var ProductViewEntity $view */
        foreach ($views as $view) {
            $product = $view->getProduct();
            $productName = $product?->getTranslated()['name'] 
                ?? $product?->getName() 
                ?? $product?->getProductNumber() 
                ?? 'N/A';
            
            $result[] = [
                'product_id' => $view->getProductId(),
                'product_name' => $productName,
                'view_count' => $view->getViewCount(),
                'last_viewed' => $view->getLastViewedAt()->format('Y-m-d H:i:s'),
            ];
        }
        return $result;
    }
}