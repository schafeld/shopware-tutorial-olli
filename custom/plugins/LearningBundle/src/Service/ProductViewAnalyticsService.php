<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Learning\Bundle\Core\Content\ProductView\ProductViewEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\BucketResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;

class ProductViewAnalyticsService
{
    private EntityRepository $productViewRepository;

    public function __construct(EntityRepository $productViewRepository)
    {
        $this->productViewRepository = $productViewRepository;
    }

    /**
     * Get views for last N days
     */
    public function getViewsForLastDays(int $days, Context $context): array
    {
        $date = new \DateTime();
        $date->modify(sprintf('-%d days', $days));

        $criteria = new Criteria();
        $criteria->addFilter(
            new RangeFilter('lastViewedAt',[
            RangeFilter::GTE => $date->format('Y-m-d H:i:s')
            ])
        );

        $criteria->addAggregation(
            new DateHistogramAggregation(
                'views_per_day',
                'lastViewedAt',
                DateHistogramAggregation::PER_DAY,
                    )
        );

        $result = $this->productViewRepository->search($criteria, $context);
        $aggregations = $result->getAggregations();
        
        /** @var BucketResult|null $bucketResult */
        $bucketResult = $aggregations->get('views_per_day');

        return $bucketResult?->getBuckets() ?? [];
    }
    /**
     * Get total views by product
     */
    public function getTotalViewsByProduct(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addAssociation('product');
        $criteria->addAggregation(
            new SumAggregation('total_views', 'viewCount')
        );

        $result = $this->productViewRepository->search($criteria, $context);

        // Build Summary
        $summary = [];
        /** @var ProductViewEntity $view */
        foreach ($result as $view) {
            $productId = $view->getProductId();
            if (!isset($summary[$productId])) {
                $product = $view->getProduct();
                $productName = $product?->getTranslated()['name'] 
                    ?? $product?->getName() 
                    ?? $product?->getProductNumber() 
                    ?? 'N/A';
                
                $summary[$productId] = [
                    'product_id' => $productId,
                    'product_name' => $productName,
                    'total_views' => 0,
                ];
            }
            $summary[$productId]['total_views'] += $view->getViewCount();
        }
        return array_values($summary);
    }
    /**
     * Get views by user agent (browser analysis)
     */
    public function getViewsByBrowser(Context $context): array
    {
        $criteria = new Criteria();
        
        $result = $this->productViewRepository->search($criteria, $context);

        $browsers = [];
        /** @var ProductViewEntity $view */
        foreach ($result as $view) {
            $userAgent = $view->getUserAgent() ?? 'Unknown';

            // Simple browser detection (use a library for better results)
            $browser = 'Unknown';
            if (str_contains($userAgent,'Chrome')) {
                $browser = 'Chrome';
            } elseif (str_contains($userAgent,'Firefox')) {
                $browser = 'Firefox';
            } elseif (str_contains($userAgent,'Safari')) {
                $browser = 'Safari';
            } elseif (str_contains($userAgent,'Edge')) {
                $browser = 'Edge';
            }

            if (!isset($browsers[$browser])) {
                $browsers[$browser] = 0;
            }
            $browsers[$browser] += $view->getViewCount();
        }

        return $browsers;
    }
}