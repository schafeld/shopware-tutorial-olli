<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\ProductView\SalesChannel;

use Shopware\Core\Framework\Adapter\Cache\AbstractCacheTracer;
use Shopware\Core\Framework\Adapter\Cache\CacheValueCompressor;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\StoreApiResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CachedProductViewRoute extends AbstractProductViewRoute
{
    private AbstractProductViewRoute $decorated;
    private AbstractCacheTracer $tracer;
    private CacheInterface $cache;
    private array $states;

    public function __construct(
        AbstractProductViewRoute $decorated,
        AbstractCacheTracer $tracer,
        CacheInterface $cache,
        array $states
    ) {
        $this->decorated = $decorated;
        $this->tracer = $tracer;
        $this->cache = $cache;
        $this->states = $states;
    }

    public function getDecorated(): AbstractProductViewRoute
    {
        return $this->decorated;
    }

    public function load(string $productId, Request $request, SalesChannelContext $context): ProductViewRouteResponse
    {
        // Check if we can use cache
        if ($context->hasState(...$this->states)) {
            return $this->getDecorated()->load($productId, $request, $context);
        }

        // Generate cache key
        $key = $this->generateKey($productId, $context);

        // Try to get from cache
        $value = $this->cache->get($key, function (ItemInterface $item) use ($productId, $request, $context) {
            $response = $this->tracer->trace($key, function () use ($productId, $request, $context) {
                return $this->getDecorated()->load($productId, $request, $context);
            });

            $item->tag($this->generateTags($productId, $response));

            return CacheValueCompressor::compress($response);
        });

        return CacheValueCompressor::uncompress($value);
    }

    private function generateKey(string $productId, SalesChannelContext $context): string
    {
        return 'product-view-route-' . $productId . '-' . $context->getSalesChannelId();
    }

    private function generateTags(string $productId, StoreApiResponse $response): array
    {
        return [
            'learning-product-view',
            'product-' . $productId,
        ];
    }
}