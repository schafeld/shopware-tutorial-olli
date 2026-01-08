<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\Wishlist\SalesChannel;

use Learning\Bundle\Service\WishListService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['store-api'], '_loginRequired' => true])]
class WishlistRoute
{
    private WishListService $wishlistService;

    public function __construct(WishListService $wishlistService)
    {
        $this->wishlistService = $wishlistService;
    }

    #[Route(
        path: '/store-api/learning/wishlist',
        name: 'store-api.learning.wishlist.get',
        methods: ['GET']
    )]
    public function load(
        Request $request,
        SalesChannelContext $context
    ): JsonResponse {
        $customer = $context->getCustomer();

        if (!$customer) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Customer not logged in',
            ], 401);
        }

        $wishlist = $this->wishlistService->getWishlist($customer->getId(), $context->getContext());

        return new JsonResponse([
            'success' => true,
            'data' => $wishlist,
            'total' => count($wishlist),
        ]);
    }

    #[Route(
        path: '/store-api/learning/wishlist/add',
        name: 'store-api.learning.wishlist.add',
        methods: ['POST']
    )]
    public function add(
        Request $request,
        SalesChannelContext $context
    ): JsonResponse {
        $customer = $context->getCustomer();

        if (!$customer) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Customer not logged in',
            ], 401);
        }

        $productId = $request->request->get('productId');

        if (!$productId) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Product ID is required',
            ], 400);
        }

        try {
            $this->wishlistService->addProduct(
                $customer->getId(), 
                $productId, 
                $context->getContext()
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Product added to wishlist',
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 400);
        }
    }

    #[Route(
        path: '/store-api/learning/wishlist/remove/{productId}',
        name: 'store-api.learning.wishlist.remove',
        methods: ['DELETE']
    )]
    public function remove(
        string $productId,
        Request $request,
        SalesChannelContext $context
    ): JsonResponse {
        $customer = $context->getCustomer();
        if (!$customer) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Customer not logged in',
            ], 401);
        }

        try {
            $this->wishlistService->removeProduct(
                $customer->getId(),
                $productId,
                $context->getContext()
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Product removed from wishlist',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}