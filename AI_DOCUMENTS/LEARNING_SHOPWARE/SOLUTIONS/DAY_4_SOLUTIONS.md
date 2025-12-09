# Day 4 Solutions: API Architecture

Complete solutions for all exercises in Day 4.

## Exercise 1: Wishlist API

### Abstract Route Class

**File:** `custom/plugins/LearningBundle/src/Core/Content/Wishlist/SalesChannel/AbstractWishlistRoute.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\Wishlist\SalesChannel;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class AbstractWishlistRoute
{
    abstract public function getDecorated(): AbstractWishlistRoute;

    abstract public function add(Request $request, SalesChannelContext $context): JsonResponse;

    abstract public function remove(string $productId, SalesChannelContext $context): JsonResponse;

    abstract public function get(SalesChannelContext $context): JsonResponse;
}
```

### Wishlist Route Implementation

**File:** `custom/plugins/LearningBundle/src/Core/Content/Wishlist/SalesChannel/WishlistRoute.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\Wishlist\SalesChannel;

use Learning\Bundle\Service\WishlistService;
use OpenApi\Annotations as OA;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"store-api"}})
 */
class WishlistRoute extends AbstractWishlistRoute
{
    private WishlistService $wishlistService;

    public function __construct(WishlistService $wishlistService)
    {
        $this->wishlistService = $wishlistService;
    }

    public function getDecorated(): AbstractWishlistRoute
    {
        throw new \RuntimeException('This route cannot be decorated');
    }

    /**
     * @OA\Post(
     *     path="/store-api/learning/wishlist/add",
     *     summary="Add product to wishlist",
     *     operationId="addToWishlist",
     *     tags={"Store API", "Learning", "Wishlist"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"productId"},
     *             @OA\Property(property="productId", type="string", format="uuid")
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Product added to wishlist",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="wishlistId", type="string")
     *         )
     *     ),
     *     @OA\Response(response="400", description="Invalid product ID or already in wishlist")
     * )
     * @Route("/store-api/learning/wishlist/add", name="store-api.learning.wishlist.add", methods={"POST"})
     */
    public function add(Request $request, SalesChannelContext $context): JsonResponse
    {
        $productId = $request->request->get('productId');

        if (!$productId) {
            return new JsonResponse(
                ['success' => false, 'message' => 'Product ID is required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $customerId = $context->getCustomer()?->getId();
        if (!$customerId) {
            return new JsonResponse(
                ['success' => false, 'message' => 'Customer not logged in'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        try {
            $wishlistId = $this->wishlistService->addToWishlist(
                $customerId,
                $productId,
                $context->getContext()
            );

            return new JsonResponse([
                'success' => true,
                'wishlistId' => $wishlistId,
                'message' => 'Product added to wishlist',
            ]);
        } catch (\RuntimeException $e) {
            return new JsonResponse(
                ['success' => false, 'message' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * @OA\Delete(
     *     path="/store-api/learning/wishlist/remove/{productId}",
     *     summary="Remove product from wishlist",
     *     operationId="removeFromWishlist",
     *     tags={"Store API", "Learning", "Wishlist"},
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Product removed from wishlist",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean")
     *         )
     *     )
     * )
     * @Route("/store-api/learning/wishlist/remove/{productId}", name="store-api.learning.wishlist.remove", methods={"DELETE"})
     */
    public function remove(string $productId, SalesChannelContext $context): JsonResponse
    {
        $customerId = $context->getCustomer()?->getId();
        if (!$customerId) {
            return new JsonResponse(
                ['success' => false, 'message' => 'Customer not logged in'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        try {
            $this->wishlistService->removeFromWishlist(
                $customerId,
                $productId,
                $context->getContext()
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Product removed from wishlist',
            ]);
        } catch (\RuntimeException $e) {
            return new JsonResponse(
                ['success' => false, 'message' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/store-api/learning/wishlist",
     *     summary="Get customer's wishlist",
     *     operationId="getWishlist",
     *     tags={"Store API", "Learning", "Wishlist"},
     *     @OA\Response(
     *         response="200",
     *         description="Customer's wishlist",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="items", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     )
     * )
     * @Route("/store-api/learning/wishlist", name="store-api.learning.wishlist.get", methods={"GET"})
     */
    public function get(SalesChannelContext $context): JsonResponse
    {
        $customerId = $context->getCustomer()?->getId();
        if (!$customerId) {
            return new JsonResponse(
                ['success' => false, 'message' => 'Customer not logged in'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $wishlist = $this->wishlistService->getWishlist($customerId, $context->getContext());

        return new JsonResponse([
            'success' => true,
            'items' => $wishlist,
            'total' => count($wishlist),
        ]);
    }
}
```

### Services Configuration

Add to `custom/plugins/LearningBundle/src/Resources/config/services.xml`:

```xml
<service id="Learning\Bundle\Core\Content\Wishlist\SalesChannel\WishlistRoute" public="true">
    <argument type="service" id="Learning\Bundle\Service\WishlistService"/>
    <tag name="controller.service_arguments"/>
</service>
```

### Test Script

**File:** `test-wishlist-api.sh`

```bash
#!/bin/bash

# Configuration
BASE_URL="http://localhost:8000"
CONTEXT_TOKEN="your-context-token-here"

echo "=== Testing Wishlist API ==="

# Get a product ID first
PRODUCT_ID=$(curl -s "${BASE_URL}/store-api/product" \
  -H "sw-context-token: ${CONTEXT_TOKEN}" \
  | jq -r '.elements[0].id')

echo "Using Product ID: ${PRODUCT_ID}"

# Add to wishlist
echo -e "\n1. Adding product to wishlist..."
curl -X POST "${BASE_URL}/store-api/learning/wishlist/add" \
  -H "Content-Type: application/json" \
  -H "sw-context-token: ${CONTEXT_TOKEN}" \
  -d "{\"productId\": \"${PRODUCT_ID}\"}" | jq

# Get wishlist
echo -e "\n2. Getting wishlist..."
curl "${BASE_URL}/store-api/learning/wishlist" \
  -H "sw-context-token: ${CONTEXT_TOKEN}" | jq

# Remove from wishlist
echo -e "\n3. Removing product from wishlist..."
curl -X DELETE "${BASE_URL}/store-api/learning/wishlist/remove/${PRODUCT_ID}" \
  -H "sw-context-token: ${CONTEXT_TOKEN}" | jq

# Get wishlist again
echo -e "\n4. Getting wishlist after removal..."
curl "${BASE_URL}/store-api/learning/wishlist" \
  -H "sw-context-token: ${CONTEXT_TOKEN}" | jq
```

---

## Exercise 2: Product Comparison API

### Admin API Controller

**File:** `custom/plugins/LearningBundle/src/Controller/Admin/ComparisonController.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Controller\Admin;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"api"}})
 */
class ComparisonController extends AbstractController
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @Route("/api/_action/learning/comparison/stats", name="api.action.learning.comparison.stats", methods={"GET"})
     */
    public function getStats(Context $context): JsonResponse
    {
        // Total comparisons
        $totalComparisons = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM learning_product_comparison'
        );

        // Unique sessions
        $uniqueSessions = (int) $this->connection->fetchOne(
            'SELECT COUNT(DISTINCT session_id) FROM learning_product_comparison'
        );

        // Comparisons per day (last 30 days)
        $dailyStats = $this->connection->fetchAllAssociative(
            'SELECT 
                DATE(created_at) as date,
                COUNT(*) as count
            FROM learning_product_comparison
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC'
        );

        // Most compared products
        $mostCompared = $this->connection->fetchAllAssociative(
            'SELECT 
                LOWER(HEX(pc.product_id)) as product_id,
                p.product_number,
                JSON_UNQUOTE(JSON_EXTRACT(p.name, "$.en-GB")) as product_name,
                COUNT(*) as comparison_count
            FROM learning_product_comparison pc
            LEFT JOIN product p ON pc.product_id = p.id AND p.version_id = 0x0FA91CE3E96A4BC2BE4BD9CE752C3425
            GROUP BY pc.product_id, p.product_number, product_name
            ORDER BY comparison_count DESC
            LIMIT 10'
        );

        return new JsonResponse([
            'success' => true,
            'data' => [
                'totalComparisons' => $totalComparisons,
                'uniqueSessions' => $uniqueSessions,
                'averageComparisonsPerSession' => $uniqueSessions > 0 
                    ? round($totalComparisons / $uniqueSessions, 2) 
                    : 0,
                'dailyStats' => $dailyStats,
                'mostComparedProducts' => $mostCompared,
            ],
        ]);
    }

    /**
     * @Route("/api/_action/learning/comparison/popular-combinations", name="api.action.learning.comparison.popular_combinations", methods={"GET"})
     */
    public function getPopularCombinations(Context $context): JsonResponse
    {
        // Find products that are often compared together
        $combinations = $this->connection->fetchAllAssociative(
            'SELECT 
                LOWER(HEX(pc1.product_id)) as product1_id,
                LOWER(HEX(pc2.product_id)) as product2_id,
                JSON_UNQUOTE(JSON_EXTRACT(p1.name, "$.en-GB")) as product1_name,
                JSON_UNQUOTE(JSON_EXTRACT(p2.name, "$.en-GB")) as product2_name,
                COUNT(*) as combination_count
            FROM learning_product_comparison pc1
            JOIN learning_product_comparison pc2 
                ON pc1.session_id = pc2.session_id 
                AND pc1.product_id < pc2.product_id
            LEFT JOIN product p1 
                ON pc1.product_id = p1.id 
                AND p1.version_id = 0x0FA91CE3E96A4BC2BE4BD9CE752C3425
            LEFT JOIN product p2 
                ON pc2.product_id = p2.id 
                AND p2.version_id = 0x0FA91CE3E96A4BC2BE4BD9CE752C3425
            GROUP BY pc1.product_id, pc2.product_id, product1_name, product2_name
            HAVING combination_count > 1
            ORDER BY combination_count DESC
            LIMIT 20'
        );

        return new JsonResponse([
            'success' => true,
            'data' => [
                'combinations' => $combinations,
                'total' => count($combinations),
            ],
        ]);
    }
}
```

### Services Configuration

Add to `custom/plugins/LearningBundle/src/Resources/config/services.xml`:

```xml
<service id="Learning\Bundle\Controller\Admin\ComparisonController" public="true">
    <argument type="service" id="Doctrine\DBAL\Connection"/>
    <tag name="controller.service_arguments"/>
</service>
```

### Test Script

**File:** `test-comparison-admin-api.sh`

```bash
#!/bin/bash

# Configuration
BASE_URL="http://localhost:8000"
ACCESS_TOKEN="your-admin-access-token-here"

echo "=== Testing Comparison Admin API ==="

# Get stats
echo -e "\n1. Getting comparison statistics..."
curl "${BASE_URL}/api/_action/learning/comparison/stats" \
  -H "Authorization: Bearer ${ACCESS_TOKEN}" \
  -H "Accept: application/json" | jq

# Get popular combinations
echo -e "\n2. Getting popular product combinations..."
curl "${BASE_URL}/api/_action/learning/comparison/popular-combinations" \
  -H "Authorization: Bearer ${ACCESS_TOKEN}" \
  -H "Accept: application/json" | jq
```

---

## Exercise 3: Rate Limiting

### Rate Limiter Service

**File:** `custom/plugins/LearningBundle/src/Service/RateLimiterService.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\CacheInterface;

class RateLimiterService
{
    private const DEFAULT_LIMIT = 60; // requests per minute
    private const DEFAULT_WINDOW = 60; // seconds

    private CacheInterface $cache;

    public function __construct()
    {
        $this->cache = new FilesystemAdapter('rate_limiter', 120);
    }

    public function isAllowed(string $identifier, int $limit = self::DEFAULT_LIMIT): bool
    {
        $key = 'rate_limit_' . md5($identifier);
        
        $requests = $this->cache->get($key, function () {
            return [
                'count' => 0,
                'reset_at' => time() + self::DEFAULT_WINDOW,
            ];
        });

        // Reset if window expired
        if (time() >= $requests['reset_at']) {
            $requests = [
                'count' => 0,
                'reset_at' => time() + self::DEFAULT_WINDOW,
            ];
        }

        // Check limit
        if ($requests['count'] >= $limit) {
            return false;
        }

        // Increment counter
        $requests['count']++;
        $this->cache->delete($key);
        $this->cache->get($key, fn() => $requests);

        return true;
    }

    public function getRemainingRequests(string $identifier, int $limit = self::DEFAULT_LIMIT): array
    {
        $key = 'rate_limit_' . md5($identifier);
        
        $requests = $this->cache->get($key, function () {
            return [
                'count' => 0,
                'reset_at' => time() + self::DEFAULT_WINDOW,
            ];
        });

        if (time() >= $requests['reset_at']) {
            return [
                'remaining' => $limit,
                'reset_at' => time() + self::DEFAULT_WINDOW,
            ];
        }

        return [
            'remaining' => max(0, $limit - $requests['count']),
            'reset_at' => $requests['reset_at'],
        ];
    }
}
```

### Rate Limit Event Subscriber

**File:** `custom/plugins/LearningBundle/src/Subscriber/RateLimitSubscriber.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Subscriber;

use Learning\Bundle\Service\RateLimiterService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RateLimitSubscriber implements EventSubscriberInterface
{
    private RateLimiterService $rateLimiter;

    public function __construct(RateLimiterService $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Only apply to our API routes
        $path = $request->getPathInfo();
        if (!str_starts_with($path, '/store-api/learning/')) {
            return;
        }

        // Identify by IP address or customer ID
        $identifier = $request->getClientIp();
        $contextToken = $request->headers->get('sw-context-token');
        if ($contextToken) {
            $identifier = $contextToken;
        }

        // Check rate limit
        if (!$this->rateLimiter->isAllowed($identifier, 30)) { // 30 requests per minute
            $remaining = $this->rateLimiter->getRemainingRequests($identifier, 30);
            
            $response = new JsonResponse([
                'success' => false,
                'message' => 'Rate limit exceeded. Please try again later.',
                'retryAfter' => $remaining['reset_at'] - time(),
            ], Response::HTTP_TOO_MANY_REQUESTS);

            $response->headers->set('X-RateLimit-Limit', '30');
            $response->headers->set('X-RateLimit-Remaining', (string) $remaining['remaining']);
            $response->headers->set('X-RateLimit-Reset', (string) $remaining['reset_at']);
            $response->headers->set('Retry-After', (string) ($remaining['reset_at'] - time()));

            $event->setResponse($response);
        } else {
            // Add rate limit headers to response
            $remaining = $this->rateLimiter->getRemainingRequests($identifier, 30);
            
            // We'll add headers in the response event
            $request->attributes->set('_rate_limit_remaining', $remaining['remaining']);
            $request->attributes->set('_rate_limit_reset', $remaining['reset_at']);
        }
    }
}
```

### Response Headers Subscriber

**File:** `custom/plugins/LearningBundle/src/Subscriber/RateLimitHeaderSubscriber.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RateLimitHeaderSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        // Only for our API routes
        $path = $request->getPathInfo();
        if (!str_starts_with($path, '/store-api/learning/')) {
            return;
        }

        // Add rate limit headers if available
        if ($request->attributes->has('_rate_limit_remaining')) {
            $response->headers->set('X-RateLimit-Limit', '30');
            $response->headers->set(
                'X-RateLimit-Remaining',
                (string) $request->attributes->get('_rate_limit_remaining')
            );
            $response->headers->set(
                'X-RateLimit-Reset',
                (string) $request->attributes->get('_rate_limit_reset')
            );
        }
    }
}
```

### Services Configuration

Add to `custom/plugins/LearningBundle/src/Resources/config/services.xml`:

```xml
<service id="Learning\Bundle\Service\RateLimiterService"/>

<service id="Learning\Bundle\Subscriber\RateLimitSubscriber">
    <argument type="service" id="Learning\Bundle\Service\RateLimiterService"/>
    <tag name="kernel.event_subscriber"/>
</service>

<service id="Learning\Bundle\Subscriber\RateLimitHeaderSubscriber">
    <tag name="kernel.event_subscriber"/>
</service>
```

### Test Script

**File:** `test-rate-limit.sh`

```bash
#!/bin/bash

# Configuration
BASE_URL="http://localhost:8000"
CONTEXT_TOKEN="your-context-token-here"

echo "=== Testing Rate Limiting ==="

# Make 35 requests (limit is 30)
for i in {1..35}; do
    echo -n "Request $i: "
    
    RESPONSE=$(curl -s -w "\n%{http_code}" "${BASE_URL}/store-api/learning/wishlist" \
        -H "sw-context-token: ${CONTEXT_TOKEN}")
    
    HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
    BODY=$(echo "$RESPONSE" | sed '$d')
    
    if [ "$HTTP_CODE" -eq 429 ]; then
        echo "❌ Rate limited"
        echo "$BODY" | jq
        break
    else
        REMAINING=$(curl -s -I "${BASE_URL}/store-api/learning/wishlist" \
            -H "sw-context-token: ${CONTEXT_TOKEN}" \
            | grep -i "x-ratelimit-remaining" \
            | cut -d' ' -f2 \
            | tr -d '\r')
        echo "✅ Success (Remaining: $REMAINING)"
    fi
    
    sleep 0.5
done
```

---

## Complete API Response Helper

**File:** `custom/plugins/LearningBundle/src/Core/Api/ApiResponse.php`

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Api;

class ApiResponse
{
    public static function success($data = null, string $message = 'Success'): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => (new \DateTime())->format(\DateTime::ATOM),
        ];
    }

    public static function error(string $message, $errors = null, int $code = 400): array
    {
        return [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'code' => $code,
            'timestamp' => (new \DateTime())->format(\DateTime::ATOM),
        ];
    }

    public static function paginated($items, int $total, int $page = 1, int $limit = 10): array
    {
        return [
            'success' => true,
            'data' => $items,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
            'timestamp' => (new \DateTime())->format(\DateTime::ATOM),
        ];
    }
}
```

---

## Testing All APIs

### Master Test Script

**File:** `test-all-apis.sh`

```bash
#!/bin/bash

echo "=== Running All API Tests ==="

# Run individual test scripts
./test-wishlist-api.sh
echo ""
./test-comparison-admin-api.sh
echo ""
./test-rate-limit.sh

echo ""
echo "=== All tests completed ==="
```

Make executable:
```bash
chmod +x test-all-apis.sh test-wishlist-api.sh test-comparison-admin-api.sh test-rate-limit.sh
```

---

## Postman Collection

Create a Postman collection with all endpoints:

```json
{
  "info": {
    "name": "Learning Bundle API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Wishlist",
      "item": [
        {
          "name": "Add to Wishlist",
          "request": {
            "method": "POST",
            "header": [
              {
                "key": "sw-context-token",
                "value": "{{context_token}}"
              }
            ],
            "body": {
              "mode": "raw",
              "raw": "{\"productId\": \"{{product_id}}\"}"
            },
            "url": "{{base_url}}/store-api/learning/wishlist/add"
          }
        },
        {
          "name": "Get Wishlist",
          "request": {
            "method": "GET",
            "header": [
              {
                "key": "sw-context-token",
                "value": "{{context_token}}"
              }
            ],
            "url": "{{base_url}}/store-api/learning/wishlist"
          }
        },
        {
          "name": "Remove from Wishlist",
          "request": {
            "method": "DELETE",
            "header": [
              {
                "key": "sw-context-token",
                "value": "{{context_token}}"
              }
            ],
            "url": "{{base_url}}/store-api/learning/wishlist/remove/{{product_id}}"
          }
        }
      ]
    },
    {
      "name": "Comparison (Admin)",
      "item": [
        {
          "name": "Get Stats",
          "request": {
            "method": "GET",
            "header": [
              {
                "key": "Authorization",
                "value": "Bearer {{access_token}}"
              }
            ],
            "url": "{{base_url}}/api/_action/learning/comparison/stats"
          }
        },
        {
          "name": "Get Popular Combinations",
          "request": {
            "method": "GET",
            "header": [
              {
                "key": "Authorization",
                "value": "Bearer {{access_token}}"
              }
            ],
            "url": "{{base_url}}/api/_action/learning/comparison/popular-combinations"
          }
        }
      ]
    }
  ]
}
```

---

## Key Takeaways

✅ **You've mastered:**
- Creating Store API routes with proper annotations
- Implementing Admin API controllers
- Request validation and error handling
- Rate limiting implementation
- OpenAPI documentation
- API testing with scripts and Postman
- Response formatting and pagination
- Authentication and authorization

## Common Issues

**Problem:** Route not found
- Clear cache: `bin/console cache:clear`
- Check `_routeScope` annotation
- Verify route is registered in services.xml

**Problem:** Rate limiting not working
- Check cache directory permissions
- Verify subscriber is registered
- Test with different identifiers

**Problem:** Authentication fails
- Verify context token for Store API
- Check OAuth token for Admin API
- Ensure customer is logged in

---

**Next:** Day 5 - Debugging and Error Analysis
