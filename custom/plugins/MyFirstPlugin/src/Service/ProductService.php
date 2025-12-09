<?php declare(strict_types=1);

namespace OllisPlugin\Service;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class ProductService
{
    private EntityRepository $productRepository;

    public function __construct(EntityRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function getActiveProducts(Context $context, int $limit = 10): ProductCollection
    {
        $criteria = new Criteria();
        
        // Only get active products
        $criteria->addFilter(new EqualsFilter('active', true));
        
        // Load associations we need
        $criteria->addAssociation('manufacturer');
        $criteria->addAssociation('cover.media');
        
        // Sort by name
        $criteria->addSorting(new FieldSorting('name', FieldSorting::ASCENDING));
        
        // Limit results
        $criteria->setLimit($limit);

        $result = $this->productRepository->search($criteria, $context);
        
        return $result->getEntities();
    }

    public function getProductCount(Context $context): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        
        $result = $this->productRepository->search($criteria, $context);
        
        return $result->getTotal();
    }
}