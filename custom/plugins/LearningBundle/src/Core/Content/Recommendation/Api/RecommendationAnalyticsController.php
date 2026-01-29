<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\Recommendation\Api;

use Learning\Bundle\Service\ProductRecommendationTrackingService;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"api"}})
 */
class RecommendationAnalyticsController extends AbstractController
{
    private ProductRecommendationTrackingService $trackingService;

    public function __construct(ProductRecommendationTrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * @Route(
     *   "/api/_action/learning/recommendations/analytics",
     *   name="api.learning.recommendations.analytics",
     *   methods={"POST"}
     * )
     */
    public function analytics(Request $request, Context $context): JsonResponse
    {
        // Example: Return top recommended product pairs and statistics
        $limit = (int) $request->query->get('limit', 10);
        $recommendations = $this->trackingService->getRecommendationsStats($limit, $context);
        
        return new JsonResponse([
            'success' => true,
            'data' => $recommendations,
        ]);
    }
}