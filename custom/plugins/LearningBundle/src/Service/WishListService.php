<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class WishListService
{
    private EntityRepository $customerWishlistRepository;
    private EntityRepository $productRepository;

    public function __construct(
        EntityRepository $customerWishlistRepository,
        EntityRepository $productRepository
    ) {
        $this->customerWishlistRepository = $customerWishlistRepository;
        $this->productRepository = $productRepository;
    }

    public function addProduct(string $customerId, string $productId, Context $context): void
    {
        $criteria = new Criteria([$productId]);
        $product = $this->productRepository->search($criteria, $context)->first();

        if (!$product) {
            throw new \InvalidArgumentException('Product ' . $productId . ' not found');
        }
        
        // Check if the product is already in the wishlist
        $wishlistCriteria = new Criteria();
        $wishlistCriteria->addFilter(new EqualsFilter('customerId', $customerId));
        $wishlistCriteria->addFilter(new EqualsFilter('productId', $productId));

        $existing = $this->customerWishlistRepository->search($criteria, $context)->first();
        if ($existing) {
            return; // Product already in wishlist
        }

        // Add to wishlist
        $this->customerWishlistRepository->create([[
            [
                'id' => Uuid::randomHex(),
                'customerId' => $customerId,
                'productId' => $productId,
                'createdAt' => new \DateTime(),
            ]
        ]], $context);
    }

    public function removeProduct(string $customerId, string $productId, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));
        $criteria->addFilter(new EqualsFilter('productId', $productId));

        $wishlistItem = $this->customerWishlistRepository->search($criteria, $context)->first();

        if (!$wishlistItem) {
            throw new \InvalidArgumentException('Product ' . $productId . ' not found in wishlist');
        }

        $this->customerWishlistRepository->delete([['id' => $wishlistItem->getId()]], $context);
    }

    public function getWishlist(string $customerId, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));
        $criteria->addAssociation('product');
        $criteria->addAssociation('product.cover');
        
        $wishlistItems = $this->customerWishlistRepository->search($criteria, $context);
        
        $products = [];
        foreach ($wishlistItems as $item) {
            $product = $item->getProduct();
            $products[] = [
                'wishlist_item_id' => $item->getId(),
                'product_id' => $product->getId(),
                'product_number' => $product->getProductNumber(),
                'name' => $product->getTranslation('name'),
                'price' => $product->getPrice(),
                'added_at' => $item->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }
        
        return $products;
    }
}