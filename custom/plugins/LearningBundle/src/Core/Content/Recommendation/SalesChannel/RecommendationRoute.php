<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\Recommendation\SalesChannel;

use Learning\Bundle\Service\ProductRecommendationTrackingService;
use OpenApi\Annotations as OA;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['store-api']])]
class RecommendationRoute
{
    private ProductRecommendationTrackingService $trackingService;

    public function __construct(ProductRecommendationTrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * @OA\Get(
     *   path="/store-api/recommendation/{productId}",
     *   summary="Get product recommendations based on a product ID",
     *   operationId="getProductRecommendations",
     *   tags={"Store API", "Recommendation"},
     *   @OA\Parameter(
     *     name="productId",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="string")
     *  ),
     *  @OA\Parameter(
     *    name="limit",
     *    in="query",
     *    @OA\Schema(type="integer", default=5)
     *  ),
     *  @OA\Response(response="200", description="Product recommendations")
     * )
     */
    #[Route(
        path: '/store-api/recommendation/{productId}',
        name: 'store-api.learning.recommendations',
        methods: ['GET']
    )]
    public function getRecommendations(
        string $productId,
        Request $request,
        SalesChannelContext $context
    ): JsonResponse {
        $limit = (int) $request->query->get('limit', 5);

        $recommendations = $this->trackingService->getRecommendations(
            $productId,
            $limit,
            $context->getContext()
        );

        return new JsonResponse([
            'success' => true,
            'data' => $recommendations,
            'total' => count($recommendations),
        ]);
    }
}