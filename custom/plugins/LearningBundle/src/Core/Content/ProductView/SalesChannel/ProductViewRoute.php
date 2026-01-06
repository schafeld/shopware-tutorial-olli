<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\ProductView\SalesChannel;

use Learning\Bundle\Service\ProductViewService;
use OpenApi\Annotations as OA;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Learning\Bundle\Core\Content\ProductView\SalesChannel\ProductViewRouteResponse;
use Learning\Bundle\Core\Content\ProductView\SalesChannel\ProductViewResult;
use Learning\Bundle\Core\Api\RateLimiter\SimpleRateLimiter;
use Learning\Bundle\Core\Api\Exception\RateLimitExceededException;

#[Route(defaults: ['_routeScope' => ['store-api']])]
class ProductViewRoute extends AbstractProductViewRoute
{
    private ProductViewService $productViewService;
    private SimpleRateLimiter $rateLimiter;

    public function __construct(
        ProductViewService $productViewService,
        SimpleRateLimiter $rateLimiter
    ) {
        $this->productViewService = $productViewService;
        $this->rateLimiter = $rateLimiter;
    }

    public function getDecorated(): AbstractProductViewRoute
    {
        throw new \Exception('This route is not decorated.');
    }

    #[Route(path: '/store-api/learning/product-view/popular', name: 'store-api.learning.product-view.popular', methods: ['GET'])]
    public function popular(
        Request $request,
        SalesChannelContext $context
    ) : JsonResponse {
        $limit = (int) $request->query->get('limit', 10);
        $popularProducts = $this->productViewService->getMostViewedProducts($limit, $context->getContext());

        return new JsonResponse([
            'success' => true,
            'data' => $popularProducts,
            'total' => count($popularProducts)
        ]);
    }

    #[Route(path: '/store-api/learning/product-view/{productId}', name: 'store-api.learning.product-view.detail', methods: ['GET'])]
    public function load(
        string $productId,
        Request $request,
        SalesChannelContext $context
    ): ProductViewRouteResponse {
        $viewCount = $this->productViewService->getProductViewCount($productId, $context -> getContext());
        return new ProductViewRouteResponse(new ProductViewResult($productId, $viewCount));
    }

    #[Route(path: '/store-api/learning/product-view/{productId}', name: 'store-api.learning.product-view.record', methods: ['POST'])]
    public function record(
        string $productId,
        Request $request,
        SalesChannelContext $context
    ) : JsonResponse {
        // Check rate limit
        if (!$this->rateLimiter->check($request)) {
            throw new RateLimitExceededException(60);
        }

        $customerId = $context->getCustomer()?->getId();
        $userAgent = $request->headers->get('User-Agent');

        $this->productViewService->recordView(
            $productId,
            $customerId,
            $userAgent,
            $context->getContext()
        );
        return new JsonResponse([
            'success' => true,
            'message' => 'Product view recorded successfully',
            'rate_limit' => [
                'remaining' => $this->rateLimiter->getRemainingRequests($request),
            ],
        ]);
    }
}