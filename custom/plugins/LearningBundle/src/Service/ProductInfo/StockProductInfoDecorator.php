<?php declare(strict_types=1);

namespace Learning\Bundle\Service\ProductInfo;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class StockProductInfoDecorator implements ProductInfoServiceInterface
{
    private ProductInfoServiceInterface $decoratedService;
    private EntityRepository $productRepository;
    private LoggerInterface $logger;

    public function __construct(
        ProductInfoServiceInterface $decoratedService,
        EntityRepository $productRepository,
        LoggerInterface $logger
    ) {
        $this->decoratedService = $decoratedService;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    public function getInfo(string $productId): string
    {
        // Get info from decorated service
        $baseInfo = $this->decoratedService->getInfo($productId);

        // Add stock information
        $product = $this->loadProduct($productId);

        if (!$product) {
            return $baseInfo . ' - Stock: N/A';
        }

        $stock = $product->getStock() ?? 0;
        $available = $product->getAvailable() ?? false;

        $stockInfo = sprintf(
            ' - Stock: %d (%s)', 
            $stock,
            $available ? 'Available' : 'Not Available'
        );

        $this->logger->debug('Stock info added to product info.', [
            'product_id' => $productId,
            'stock' => $stock,
            'available' => $available,
        ]);

        return $baseInfo . $stockInfo;
    }

    private function loadProduct(string $productId): ?ProductEntity
    {
        $criteria = new Criteria([$productId]);
        
        $result = $this->productRepository->search($criteria, Context::createDefaultContext());
        return $result->first();
    }
}       