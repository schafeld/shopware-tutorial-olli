<?php declare(strict_types=1);

namespace Learning\Bundle\Service\ProductInfo;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class BaseProductInfoService implements ProductInfoServiceInterface
{
    private EntityRepository $productRepository;
    private LoggerInterface $logger;

    public function __construct(
        EntityRepository $productRepository,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    public function getInfo(string $productId): string
    {
        $product = $this->loadProduct($productId);

        if (!$product) {
            $this->logger->error("Product with ID {$productId} not found.");
            return sprintf('Product: [Not Found: %s]', substr($productId, 0, 8));
        }

        $info = sprintf(
            'Product: %s', $product->getName()
        );

        $this->logger->debug('Base product info generated.', [
            'product_id' => $productId,
            'product_name' => $product->getName(),
        ]);

        return $info;
    }

    protected function loadProduct(string $productId): ?ProductEntity
    {
        $criteria = new Criteria([$productId]);
        $result = $this->productRepository->search($criteria, Context::createDefaultContext());

        return $result->first();
    }
}
    