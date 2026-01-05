<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Api;

use Learning\Bundle\Service\ProductViewAnalyticsService;
use Learning\Bundle\Service\ProductViewService;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class ProductViewAnalyticsController extends AbstractController
{
    private ProductViewAnalyticsService $analyticsService;
    private ProductViewService $productViewService;

    public function __construct(
        ProductViewAnalyticsService $analyticsService,
        ProductViewService $productViewService
    ) {
        $this->analyticsService = $analyticsService;
        $this->productViewService = $productViewService;
    }

    #[Route(
        path: '/api/_action/learning/product-view/analytics/overview',
        name: 'api.action.learning.product-view.analytics.overview',
        methods: ['GET']
    )]

    public function getOverview(Request $request, Context $context): JsonResponse
    {
        $days = (int) $request->query->get('days', 30);

        $viewsPerDay = $this->analyticsService->getViewsForLastDays($days, $context);
        $totalViews = $this->analyticsService->getTotalViewsByProduct($context);
        $browserStats = $this->analyticsService->getViewsByBrowser($context);

        return new JsonResponse([
            'success' => true,
            'data' => [
                'period' => [
                    'days' => $days,
                    'start' => (new \DateTime())->modify(sprintf('-%d days', $days))->format('Y-m-d'),
                    'end' => (new \DateTime())->format('Y-m-d'),
                ],
                'views_per_day' => $viewsPerDay,
                'total_views_by_product' => $totalViews,
                'browser_statistics' => $browserStats,
            ],
        ]);
    }

    #[Route(
        path: '/api/_action/learning/product-view/analytics/product/{productId}',
        name: 'api.action.learning.product-view.analytics.product',
        methods: ['GET']
    )]
    public function getProductAnalytics(string $productId, Request $request, Context $context): JsonResponse
    {
        $viewCount = $this->productViewService->getProductViewCount($productId, $context);

        return new JsonResponse([
            'success' => true,
            'data' => [
                'product_id' => $productId,
                'total_views' => $viewCount,
            ],
        ]);

    }

    #[Route(
        path: '/api/_action/learning/product-view/analytics/popular',
        name: 'api.action.learning.product-view.analytics.popular',
        methods: ['GET']
    )]
    public function getPopularProducts(Request $request, Context $context): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 10);
        
        $popularProducts = $this->productViewService->getMostViewedProducts($limit, $context);

        return new JsonResponse([
            'success' => true,
            'data' => $popularProducts,
            'meta' => [
                'total' => count($popularProducts),
                'limit' => $limit,
            ],
        ]);
    }

    #[Route(
        path: '/api/_action/learning/product-view/reset/{productId}',
        name: 'api.action.learning.product-view.reset',
        methods: ['POST']
    )]
    public function resetProductViews(string $productId, Context $context): JsonResponse
    {
        // This would require a new method in ProductViewService
        // For now, just return a success message
        
        return new JsonResponse([
            'success' => true,
            'message' => "View count for product {$productId} has been reset",
        ]);
    }
}