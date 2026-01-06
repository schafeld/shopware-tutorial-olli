<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Api\RateLimiter;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\ItemInterface;

class SimpleRateLimiter
{
    private CacheInterface $cache;
    private int $maxRequests;
    private int $windowSeconds;
    private ?LoggerInterface $logger;

    public function __construct(
        CacheInterface $cache,
        int $maxRequests = 100,
        int $windowSeconds = 60,
        LoggerInterface $logger = null
    ) {
        $this->cache = $cache;
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
        $this->logger = $logger;
    }

    public function check(Request $request): bool
    {
        $key = $this->getKey($request);
        
        try {
            // Get current count (or initialize to 0)
            $count = $this->cache->get($key, function (ItemInterface $item) {
                $item->expiresAfter($this->windowSeconds);
                
                if ($this->logger) {
                    $this->logger->info('Rate limiter: First request (cache miss)', [
                        'key' => $item->getKey(),
                    ]);
                }
                
                return 0;
            });
            
            if ($this->logger) {
                $this->logger->info('Rate limiter: Current count', [
                    'key' => $key,
                    'count' => $count,
                    'max' => $this->maxRequests,
                ]);
            }
            
            // Check if limit exceeded
            if ($count >= $this->maxRequests) {
                if ($this->logger) {
                    $this->logger->warning('Rate limit exceeded', [
                        'key' => $key,
                        'count' => $count,
                        'max' => $this->maxRequests
                    ]);
                }
                return false;
            }
            
            // Increment counter
            $this->cache->delete($key);
            $newCount = $this->cache->get($key, function (ItemInterface $item) use ($count) {
                $item->expiresAfter($this->windowSeconds);
                return $count + 1;
            });
            
            if ($this->logger) {
                $this->logger->info('Rate limiter: Incremented', [
                    'key' => $key,
                    'oldCount' => $count,
                    'newCount' => $newCount
                ]);
            }
            
            return true;
            
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Rate limiter error', [
                    'message' => $e->getMessage(),
                    'key' => $key
                ]);
            }
            // On error, allow the request
            return true;
        }
    }

    public function getRemainingRequests(Request $request): int
    {
        $key = $this->getKey($request);
        
        try {
            $count = $this->cache->get($key, fn() => 0);
            return max(0, $this->maxRequests - $count);
        } catch (\Exception $e) {
            return $this->maxRequests;
        }
    }

    private function getKey(Request $request): string
    {
        // Use IP address or customer ID as key
        $identifier = $request->getClientIp();
        return 'rate_limit_' . md5($identifier);
    }
}