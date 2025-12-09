<?php declare(strict_types=1);

namespace Learning\Bundle\Service\ProductInfo;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class PriceProductInfoDecorator implements ProductInfoServiceInterface
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

        // Add price information
        $product = $this->loadProduct($productId);

        if(!$product || !$product->getPrice()) {
            return $baseInfo . ' - Price: N/A';
        }

        $price = $product->getPrice()->first();
        if(!$price) {
            return $baseInfo . ' - Price: N/A';
        }

        $priceInfo = sprintf(
            ' - Price: €%.2f', 
            $price->getGross()
        );

        $this->logger->debug('Price info added to product info.', [
            'product_id' => $productId,
            'price_gross' => $price->getGross(),
        ]);

        return $baseInfo . $priceInfo;
    }

    private function loadProduct(string $productId): ?ProductEntity
    {
        $criteria = new Criteria([$productId]);
        $criteria->addAssociation('prices');

        $result = $this->productRepository->search($criteria, Context::createDefaultContext());

        return $result->first();
    }
}