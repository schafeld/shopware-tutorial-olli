<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;

class ProductRecommendationTrackingService
{
    private const SESSION_WINDOW_MINUTES = 30;

    private EntityRepository $productSessionRepository;
    private EntityRepository $recommendationRepository;
    private LoggerInterface $logger;

    public function __construct(
        EntityRepository $productSessionRepository,
        EntityRepository $recommendationRepository,
        LoggerInterface $logger
    ) {
        $this->productSessionRepository = $productSessionRepository;
        $this->recommendationRepository = $recommendationRepository;
        $this->logger = $logger;
    }

    /**
     * Track a product view in a session
     */
    public function trackProductView(string $sessionId, string $productId, Context $context): void
    {
        try {
            // Record the view
            $this->productSessionRepository->create([
                [
                    'id' => Uuid::randomHex(),
                    'sessionId' => $sessionId,
                    'productId' => $productId,
                    'viewedAt' => new \DateTimeImmutable(),
                ],
            ], $context);

            // Update recommendations based on recent views in this session
            $this->updateRecommendationsForSession($sessionId, $productId, $context);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to track product view: ', [
                'session_id' => $sessionId,
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update recommendations based on recently viewed products in the session
     */
    private function updateRecommendationsForSession(string $sessionId, string $currentProductId, Context $context): void
    {
        // Get products viewed in this session within the time window
        $windowStart = new \DateTime();
        $windowStart->modify('-' . self::SESSION_WINDOW_MINUTES . ' minutes');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('sessionId', $sessionId));
        $criteria->addFilter(new Rangefilter('viewedAt', [
            RangeFilter::GTE => $windowStart->format('Y-m-d H:i:s'),
        ]));

        $recentViews = $this->productSessionRepository->search($criteria, $context);

        // Create/update recommendations between viewed products
        foreach ($recentViews as $view) {
            $otherProductId = $view->getProductId();

            // Don't create recommendation to itself
            if ($otherProductId === $currentProductId) {
                continue;
            }

            // Create bidirectional recommendations
            $this->upsertRecommendation($currentProductId, $otherProductId, $context);
            $this->upsertRecommendation($otherProductId, $currentProductId, $context);
        }
    }

    /**
     * Create or update a recommendation relationship
     */
    private function upsertRecommendation(string $sourceProductId, string $targetProductId, Context $context): void
    {
        // Check if recommendation already exists
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('sourceProductId', $sourceProductId));
        $criteria->addFilter(new EqualsFilter('recommendedProductId', $targetProductId));

        $existing = $this->recommendationRepository->search($criteria, $context)->first();

        if ($existing) {
            // Update existing
            $newViewCount = $existing->getViewCount() + 1;
            $newScore = $this -> calculateAffinityScore($newViewCount);

            $this->recommendationRepository->update([
                [
                    'id' => $existing->getId(),
                    'viewCount' => $newViewCount,
                    'affinityScore' => $newScore,
                    'lastUpdated' => new \DateTime(),
                ],
            ], $context);
        } else {
            // Create new
            $this->recommendationRepository->create([
                [
                    'id' => Uuid::randomHex(),
                    'sourceProductId' => $sourceProductId,
                    'recommendedProductId' => $targetProductId,
                    'viewCount' => 1,
                    'affinityScore' => $this->calculateAffinityScore(1),
                    'lastUpdated' => new \DateTime(),
                ],
            ], $context);
        }
    }

    /**
     * Calculate affinity score based on view count
     * This is a simple algorithm – you can enhance it as needed
     */
    private function calculateAffinityScore(int $viewCount): float
    {
        // Logarithmic scale
        return log10($viewCount + 1) * 10.0;
    }

    /**
     * Get recommendations for a product
     */
    public function getRecommendations(string $productId, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('sourceProductId', $productId));
        $criteria->addAssociation('recommendedProduct');
        $criteria->addSorting( new FieldSorting('affinityScore', FieldSorting::DESCENDING));
        $criteria->setLimit(5);

        $recommendations = $this->recommendationRepository->search($criteria, $context);

        $result = [];
        foreach ($recommendations as $recommendation) {
            $result[] = [
                'product_id' => $recommendation->getRecommendedProductId(),
                'product_name' => $recommendation->getRecommendedProduct()?->getName(),
                'affinity_score' => $recommendation->getAffinityScore(),
                'view_count' => $recommendation->getViewCount(),
            ];
        }

        return $result;
    }
    /**
     * Returns top recommended product pairs and statistics for analytics.
     */
    public function getRecommendationsStats(int $limit, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addSorting(new FieldSorting('viewCount', FieldSorting::DESCENDING));
        $criteria->setLimit($limit);

        $recommendations = $this->recommendationRepository->search($criteria, $context);
        $result = [];
        foreach ($recommendations as $recommendation) {
            $result[] = [
                'productA' => $recommendation->getSourceProductId(),
                'productB' => $recommendation->getRecommendedProductId(),
                'count' => $recommendation->getViewCount(),
                'affinityScore' => $recommendation->getAffinityScore(),
            ];
        }
        return $result;
    }
}