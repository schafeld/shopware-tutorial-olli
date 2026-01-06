<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Api;

use Learning\Bundle\Service\ProductComparisonService;
use Learning\Bundle\Core\Api\Response\ApiResponse;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class ProductComparisonController extends AbstractController
{
    private ProductComparisonService $comparisonService;

    public function __construct(ProductComparisonService $comparisonService)
    {
        $this->comparisonService = $comparisonService;
    }

    #[Route(
        path: '/api/_action/learning/comparison/stats',
        name: 'api.action.learning.comparison.stats',
        methods: ['GET']
    )]
    public function getStats(Request $request, Context $context): JsonResponse
    {
        $stats = $this->comparisonService->getComparisonStats($context);

        return new JsonResponse(
            ApiResponse::success($stats, [
                'endpoint' => 'comparison-stats',
            ])
        );
    }

    #[Route(
        path: '/api/_action/learning/comparison/popular-combinations',
        name: 'api.action.learning.comparison.popular',
        methods: ['GET']
    )]
    public function getPopularCombinations(Request $request, Context $context): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 20);
        
        $combinations = $this->comparisonService->getPopularCombinations($limit, $context);

        return new JsonResponse(
            ApiResponse::collection($combinations, [
                'endpoint' => 'popular-combinations',
                'limit' => $limit,
            ])
        );
    }
}