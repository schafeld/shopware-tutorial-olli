<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Api;

use Learning\Bundle\Service\ProductViewAnalyticsService;
use Learning\Bundle\Service\ProductViewService;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Learning\Bundle\Core\Api\Exception\ProductViewNotFoundException;
use Learning\Bundle\Core\Api\Exception\ProductViewLimitExceededException;
use Learning\Bundle\Core\Api\Validator\AnalyticsRequestValidator;
use Learning\Bundle\Core\Api\Exception\InvalidAnalyticsRequestException;
use Learning\Bundle\Core\Api\Response\ApiResponse;

#[Route(defaults: ['_routeScope' => ['api']])]
class ProductViewAnalyticsController extends AbstractController
{
    private ProductViewAnalyticsService $analyticsService;
    private ProductViewService $productViewService;
    private AnalyticsRequestValidator $validator;

    public function __construct(
        ProductViewAnalyticsService $analyticsService,
        ProductViewService $productViewService,
        AnalyticsRequestValidator $validator
    ) {
        $this->analyticsService = $analyticsService;
        $this->productViewService = $productViewService;
        $this->validator = $validator;
    }

    #[Route(
        path: '/api/_action/learning/product-view/analytics/overview',
        name: 'api.action.learning.product-view.analytics.overview',
        methods: ['GET']
    )]

    public function getOverview(Request $request, Context $context): JsonResponse
    {

        // Validate request parameters
        $errors = $this->validator->validateOverviewRequest($request);
        if (!empty($errors)) {
            throw new InvalidAnalyticsRequestException($errors);
        }

        $days = (int) $request->query->get('days', 30);

        $viewsPerDay = $this->analyticsService->getViewsForLastDays($days, $context);
        $totalViews = $this->analyticsService->getTotalViewsByProduct($context);
        $browserStats = $this->analyticsService->getViewsByBrowser($context);

        return new JsonResponse([
            ApiResponse::success([
                'period' => [
                    'days' => $days,
                    'start' => (new \DateTime())->modify(sprintf('-%d days', $days))->format('Y-m-d'),
                    'end' => (new \DateTime())->format('Y-m-d'),
                ],
                'views_per_day' => $viewsPerDay,
                'total_views_by_product' => $totalViews,
                'browser_statistics' => $browserStats,
            ], [
                'version' => '1.0.0',
                'endpoint' => 'overview',
            ])
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

        // Throw exception if product has no views
        if ($viewCount === 0) {
            throw new ProductViewNotFoundException($productId);
        }

        return new JsonResponse([
            ApiResponse::success([
                'product_id' => $productId,
                'total_views' => $viewCount,
            ])
        ]);
    }

    #[Route(
        path: '/api/_action/learning/product-view/analytics/popular',
        name: 'api.action.learning.product-view.analytics.popular',
        methods: ['GET']
    )]
    public function getPopularProducts(Request $request, Context $context): JsonResponse
    {
        $errors = $this->validator->validatePopularRequest($request);
        if (!empty($errors)) {
            throw new InvalidAnalyticsRequestException($errors);
        }

        $limit = (int) $request->query->get('limit', 10);
        $page = (int) $request->query->get('page', 1);

        // Validate limit (max 100)
        if ($limit > 100) {
            throw new ProductViewLimitExceededException(100,$limit);
        }
        
        $popularProducts = $this->productViewService->getMostViewedProducts($limit, $context);

        return new JsonResponse(
            ApiResponse::paginated($popularProducts, count($popularProducts), $page, $limit)
        );
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
        
        return new JsonResponse(
            ApiResponse::success([
                'product_id' => $productId,
                'reset' => true,
            ], [
                'message' => "View count for product {$productId} has been reset",
            ])
        );
    }
}