<?php declare(strict_types=1);

namespace Learning\Bundle\Subscriber;

use Shopware\Core\Content\Product\Events\ProductListingCriteriaEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ProductListingSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestStack $requestStack
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductListingCriteriaEvent::class => 'handlePriceFilter'
        ];
    }

    public function handlePriceFilter(ProductListingCriteriaEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        
        if (!$request) {
            return;
        }

        $minPrice = $request->query->get('min-price');
        $maxPrice = $request->query->get('max-price');

        if ($minPrice !== null || $maxPrice !== null) {
            $criteria = $event->getCriteria();
            
            // Build range filter parameters
            $range = [];
            if ($minPrice !== null && $minPrice >= 0) {
                $range[RangeFilter::GTE] = (float) $minPrice;
            }
            if ($maxPrice !== null && $maxPrice >= 0) {
                $range[RangeFilter::LTE] = (float) $maxPrice;
            }
            
            // Use 'product.cheapestPrice' field - this is the correct field for price filtering
            if (!empty($range)) {
                $criteria->addFilter(
                    new RangeFilter('product.cheapestPrice', $range)
                );
            }
        }
    }
}