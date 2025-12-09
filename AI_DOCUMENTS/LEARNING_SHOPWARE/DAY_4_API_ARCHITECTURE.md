# Day 4: API Architecture - Sales Channel & Admin APIs

**Duration:** 1.5-2 days (10-14 hours with breaks)  
**Goal:** Master Shopware's API architecture and create custom API endpoints

> **Note for Beginners:** API development builds on Days 1-3. Make sure you're comfortable with services and repositories before starting. Testing APIs takes time!

## Learning Objectives

- Understand Store API vs Admin API
- Create custom Store API routes
- Create custom Admin API routes
- Work with API authentication and authorization
- Handle API requests and responses
- Implement API versioning
- Test APIs with tools

## Prerequisites

- Completed Days 1-3
- Understanding of RESTful APIs
- Basic knowledge of HTTP methods and status codes
- Postman or similar API testing tool

---

## Part 1: Understanding Shopware APIs (45 minutes)

### Theory: API Architecture

Shopware 6 provides two main API systems:

**1. Store API (Sales Channel API)**
- Public-facing API for storefronts
- Context-aware (sales channel, language, currency)
- Used by: PWA, mobile apps, custom frontends
- Authentication: Context token
- Base path: `/store-api/`

**2. Admin API**
- Management API for backend operations
- Full access to all entities
- Used by: Administration, integrations, automation
- Authentication: OAuth 2.0
- Base path: `/api/`

**API Flow:**
```
Request → Authentication → Route → Controller → Service → Response
```

### Official Documentation

📖 **Read these resources:**
- [Store API Guide](https://developer.shopware.com/docs/guides/plugins/plugins/framework/store-api/)
- [Admin API Guide](https://developer.shopware.com/docs/concepts/api/)
- [API Authentication](https://developer.shopware.com/docs/guides/integrations-api/authentication-authorisation)
- [API Reference](https://shopware.stoplight.io/docs/store-api/)

---

## Part 2: Create Store API Endpoint (90 minutes)

### Step 1: Create Route Definition

Create `custom/plugins/LearningBundle/src/Core/Content/ProductView/SalesChannel/ProductViewRoute.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\ProductView\SalesChannel;

use Learning\Bundle\Service\ProductViewService;
use OpenApi\Annotations as OA;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"store-api"}})
 */
class ProductViewRoute extends AbstractProductViewRoute
{
    private ProductViewService $productViewService;

    public function __construct(ProductViewService $productViewService)
    {
        $this->productViewService = $productViewService;
    }

    public function getDecorated(): AbstractProductViewRoute
    {
        throw new \Exception('This route is not decorated');
    }

    /**
     * @OA\Get(
     *     path="/store-api/learning/product-view/{productId}",
     *     summary="Get product view statistics",
     *     operationId="getProductViews",
     *     tags={"Store API", "Learning", "Product View"},
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Product view statistics"
     *     )
     * )
     * @Route(
     *     "/store-api/learning/product-view/{productId}",
     *     name="store-api.learning.product-view.detail",
     *     methods={"GET"}
     * )
     */
    public function load(string $productId, Request $request, SalesChannelContext $context): ProductViewRouteResponse
    {
        $viewCount = $this->productViewService->getProductViewCount($productId, $context->getContext());

        return new ProductViewRouteResponse(new ProductViewResult($productId, $viewCount));
    }

    /**
     * @OA\Post(
     *     path="/store-api/learning/product-view/{productId}",
     *     summary="Record a product view",
     *     operationId="recordProductView",
     *     tags={"Store API", "Learning", "Product View"},
     *     @OA\Parameter(
     *         name="productId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="View recorded successfully"
     *     )
     * )
     * @Route(
     *     "/store-api/learning/product-view/{productId}",
     *     name="store-api.learning.product-view.record",
     *     methods={"POST"}
     * )
     */
    public function record(string $productId, Request $request, SalesChannelContext $context): JsonResponse
    {
        $customerId = $context->getCustomer()?->getId();
        $userAgent = $request->headers->get('User-Agent');

        $this->productViewService->recordView(
            $productId,
            $customerId,
            $userAgent,
            $context->getContext()
        );

        return new JsonResponse([
            'success' => true,
            'message' => 'View recorded successfully',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/store-api/learning/product-view/popular",
     *     summary="Get most viewed products",
     *     operationId="getMostViewedProducts",
     *     tags={"Store API", "Learning", "Product View"},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="List of most viewed products"
     *     )
     * )
     * @Route(
     *     "/store-api/learning/product-view/popular",
     *     name="store-api.learning.product-view.popular",
     *     methods={"GET"}
     * )
     */
    public function popular(Request $request, SalesChannelContext $context): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 10);
        
        $popularProducts = $this->productViewService->getMostViewedProducts($limit, $context->getContext());

        return new JsonResponse([
            'success' => true,
            'data' => $popularProducts,
            'total' => count($popularProducts),
        ]);
    }
}
```

### Step 2: Create Abstract Route

Create `custom/plugins/LearningBundle/src/Core/Content/ProductView/SalesChannel/AbstractProductViewRoute.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\ProductView\SalesChannel;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractProductViewRoute
{
    abstract public function getDecorated(): AbstractProductViewRoute;

    abstract public function load(string $productId, Request $request, SalesChannelContext $context): ProductViewRouteResponse;
}
```

### Step 3: Create Response Objects

Create `custom/plugins/LearningBundle/src/Core/Content/ProductView/SalesChannel/ProductViewResult.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\ProductView\SalesChannel;

use Shopware\Core\Framework\Struct\Struct;

class ProductViewResult extends Struct
{
    protected string $productId;
    protected int $viewCount;

    public function __construct(string $productId, int $viewCount)
    {
        $this->productId = $productId;
        $this->viewCount = $viewCount;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getViewCount(): int
    {
        return $this->viewCount;
    }
}
```

Create `custom/plugins/LearningBundle/src/Core/Content/ProductView/SalesChannel/ProductViewRouteResponse.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\ProductView\SalesChannel;

use Shopware\Core\System\SalesChannel\StoreApiResponse;

class ProductViewRouteResponse extends StoreApiResponse
{
    protected ProductViewResult $result;

    public function __construct(ProductViewResult $result)
    {
        parent::__construct($result);
        $this->result = $result;
    }

    public function getResult(): ProductViewResult
    {
        return $this->result;
    }
}
```

### Step 4: Register Route

Update `services.xml`:

```xml
<!-- Store API Route -->
<service id="Learning\Bundle\Core\Content\ProductView\SalesChannel\ProductViewRoute" public="true">
    <argument type="service" id="Learning\Bundle\Service\ProductViewService"/>
</service>
```

### Step 5: Test Store API

```bash
# Clear cache
bin/console cache:clear

# Get context token (needed for Store API)
curl -X POST "http://localhost:8000/store-api/context" \
  -H "sw-access-key: SWSCMDVXUEVWCVD1S0FXSXHBSQ"

# Use the returned token in subsequent requests
export SW_CONTEXT_TOKEN="your-token-here"

# Record a view
curl -X POST "http://localhost:8000/store-api/learning/product-view/YOUR_PRODUCT_ID" \
  -H "sw-access-key: SWSCMDVXUEVWCVD1S0FXSXHBSQ" \
  -H "sw-context-token: $SW_CONTEXT_TOKEN"

# Get view count
curl -X GET "http://localhost:8000/store-api/learning/product-view/YOUR_PRODUCT_ID" \
  -H "sw-access-key: SWSCMDVXUEVWCVD1S0FXSXHBSQ" \
  -H "sw-context-token: $SW_CONTEXT_TOKEN"

# Get popular products
curl -X GET "http://localhost:8000/store-api/learning/product-view/popular?limit=5" \
  -H "sw-access-key: SWSCMDVXUEVWCVD1S0FXSXHBSQ" \
  -H "sw-context-token: $SW_CONTEXT_TOKEN"
```

---

## Part 3: Create Admin API Endpoint (90 minutes)

### Step 1: Create Admin Controller

Create `custom/plugins/LearningBundle/src/Core/Api/ProductViewAnalyticsController.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Api;

use Learning\Bundle\Service\ProductViewAnalyticsService;
use Learning\Bundle\Service\ProductViewService;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"api"}})
 */
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

    /**
     * @Route(
     *     "/api/_action/learning/product-view/analytics/overview",
     *     name="api.action.learning.product-view.analytics.overview",
     *     methods={"GET"}
     * )
     */
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
                    'start' => (new \DateTime())->modify("-{$days} days")->format('Y-m-d'),
                    'end' => (new \DateTime())->format('Y-m-d'),
                ],
                'views_per_day' => $viewsPerDay,
                'total_views_by_product' => $totalViews,
                'browser_statistics' => $browserStats,
            ],
        ]);
    }

    /**
     * @Route(
     *     "/api/_action/learning/product-view/analytics/product/{productId}",
     *     name="api.action.learning.product-view.analytics.product",
     *     methods={"GET"}
     * )
     */
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

    /**
     * @Route(
     *     "/api/_action/learning/product-view/analytics/popular",
     *     name="api.action.learning.product-view.analytics.popular",
     *     methods={"GET"}
     * )
     */
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

    /**
     * @Route(
     *     "/api/_action/learning/product-view/reset/{productId}",
     *     name="api.action.learning.product-view.reset",
     *     methods={"POST"}
     * )
     */
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
```

### Step 2: Register Controller

Update `services.xml`:

```xml
<!-- Admin API Controller -->
<service id="Learning\Bundle\Core\Api\ProductViewAnalyticsController" public="true">
    <argument type="service" id="Learning\Bundle\Service\ProductViewAnalyticsService"/>
    <argument type="service" id="Learning\Bundle\Service\ProductViewService"/>
    <call method="setContainer">
        <argument type="service" id="service_container"/>
    </call>
</service>

<!-- Analytics Service -->
<service id="Learning\Bundle\Service\ProductViewAnalyticsService">
    <argument type="service" id="learning_product_view.repository"/>
</service>
```

### Step 3: Get Admin API Credentials

```bash
# Create integration in Administration or via CLI
# Go to Settings > System > Integrations > Add Integration
# Or use SQL:

bin/console dbal:run-sql "
INSERT INTO integration (id, label, access_key, secret_access_key, created_at)
VALUES (
    UNHEX(REPLACE(UUID(), '-', '')),
    'Learning Plugin API',
    'LEARNINGACCESSKEY',
    'LEARNINGSECRETKEY',
    NOW()
);
"
```

### Step 4: Authenticate and Test Admin API

```bash
# Get OAuth token
curl -X POST "http://localhost:8000/api/oauth/token" \
  -H "Content-Type: application/json" \
  -d '{
    "client_id": "administration",
    "grant_type": "password",
    "scopes": "write",
    "username": "admin",
    "password": "shopware"
  }'

# Save the access token
export SW_ACCESS_TOKEN="your-access-token-here"

# Test analytics overview
curl -X GET "http://localhost:8000/api/_action/learning/product-view/analytics/overview?days=7" \
  -H "Authorization: Bearer $SW_ACCESS_TOKEN" \
  -H "Content-Type: application/json"

# Test product-specific analytics
curl -X GET "http://localhost:8000/api/_action/learning/product-view/analytics/product/YOUR_PRODUCT_ID" \
  -H "Authorization: Bearer $SW_ACCESS_TOKEN"

# Test popular products
curl -X GET "http://localhost:8000/api/_action/learning/product-view/analytics/popular?limit=10" \
  -H "Authorization: Bearer $SW_ACCESS_TOKEN"
```

---

## Part 4: API Best Practices (60 minutes)

### Practice 1: Error Handling

Create proper error responses:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Api\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class ProductViewNotFoundException extends ShopwareHttpException
{
    public function __construct(string $productId)
    {
        parent::__construct(
            'Product view data for product "{{ productId }}" not found.',
            ['productId' => $productId]
        );
    }

    public function getErrorCode(): string
    {
        return 'LEARNING__PRODUCT_VIEW_NOT_FOUND';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
```

Usage in controller:

```php
use Learning\Bundle\Core\Api\Exception\ProductViewNotFoundException;

public function getProductAnalytics(string $productId, Request $request, Context $context): JsonResponse
{
    $viewCount = $this->productViewService->getProductViewCount($productId, $context);
    
    if ($viewCount === 0) {
        throw new ProductViewNotFoundException($productId);
    }

    return new JsonResponse([
        'success' => true,
        'data' => [
            'product_id' => $productId,
            'total_views' => $viewCount,
        ],
    ]);
}
```

### Practice 2: Request Validation

Create request validators:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Api\Validator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AnalyticsRequestValidator
{
    private ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function validateOverviewRequest(Request $request): array
    {
        $days = $request->query->get('days', 30);

        $constraints = new Assert\Collection([
            'days' => [
                new Assert\Type('integer'),
                new Assert\Range(['min' => 1, 'max' => 365]),
            ],
        ]);

        $violations = $this->validator->validate(['days' => $days], $constraints);

        $errors = [];
        foreach ($violations as $violation) {
            $errors[] = [
                'property' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];
        }

        return $errors;
    }
}
```

### Practice 3: Response Formatting

Create consistent response structure:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Api\Response;

class ApiResponse
{
    public static function success($data, array $meta = []): array
    {
        return [
            'success' => true,
            'data' => $data,
            'meta' => array_merge([
                'timestamp' => (new \DateTime())->format(\DateTime::ATOM),
            ], $meta),
        ];
    }

    public static function error(string $message, int $code, array $details = []): array
    {
        return [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
            'meta' => [
                'timestamp' => (new \DateTime())->format(\DateTime::ATOM),
            ],
        ];
    }

    public static function paginated($data, int $total, int $page, int $limit): array
    {
        return [
            'success' => true,
            'data' => $data,
            'meta' => [
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => (int) ceil($total / $limit),
                ],
                'timestamp' => (new \DateTime())->format(\DateTime::ATOM),
            ],
        ];
    }
}
```

Usage:

```php
use Learning\Bundle\Core\Api\Response\ApiResponse;

public function getPopularProducts(Request $request, Context $context): JsonResponse
{
    $limit = (int) $request->query->get('limit', 10);
    $page = (int) $request->query->get('page', 1);
    
    $popularProducts = $this->productViewService->getMostViewedProducts($limit, $context);

    return new JsonResponse(
        ApiResponse::paginated($popularProducts, count($popularProducts), $page, $limit)
    );
}
```

---

## Part 5: API Documentation with OpenAPI (45 minutes)

### Generate API Documentation

Shopware uses OpenAPI (Swagger) annotations for API documentation.

### View Generated Documentation

```bash
# Generate OpenAPI schema
bin/console framework:schema -f openapi > openapi.json

# View in browser (if using API Platform)
# Navigate to: http://localhost:8000/api/_info/openapi3.json
```

### Test with Postman

1. Import OpenAPI schema into Postman
2. Create environment with variables:
   - `base_url`: http://localhost:8000
   - `access_token`: your-token
   - `context_token`: your-context-token
3. Test all endpoints

---

## Part 6: Exercises (90 minutes)

### Exercise 1: Wishlist API

Create complete Store API endpoints for wishlist:
- `POST /store-api/learning/wishlist/add` - Add product to wishlist
- `DELETE /store-api/learning/wishlist/remove/{productId}` - Remove from wishlist
- `GET /store-api/learning/wishlist` - Get customer's wishlist

### Exercise 2: Product Comparison API

Create Admin API for product comparisons:
- `GET /api/_action/learning/comparison/stats` - Get comparison statistics
- `GET /api/_action/learning/comparison/popular-combinations` - Most compared products together

### Exercise 3: Rate Limiting

Implement simple rate limiting for your API endpoints (track requests per IP/customer).

---

## Testing Your Work

### Using cURL

```bash
# Test Store API
./test-store-api.sh

# Test Admin API
./test-admin-api.sh
```

### Using Postman Collection

Create and export a Postman collection with all your endpoints.

### Unit Tests (Preview for Day 6)

```php
public function testProductViewApi(): void
{
    $response = $this->request('GET', '/store-api/learning/product-view/popular');
    
    static::assertEquals(200, $response->getStatusCode());
    $content = json_decode($response->getContent(), true);
    static::assertTrue($content['success']);
}
```

---

## Key Takeaways

✅ **You've learned:**
- Difference between Store API and Admin API
- Creating custom Store API routes
- Creating custom Admin API endpoints
- API authentication flows
- Request validation and error handling
- Response formatting best practices
- OpenAPI documentation
- Testing APIs with cURL and Postman

## Common Issues

**Problem:** Route not found (404)
- Clear cache: `bin/console cache:clear`
- Check route annotation and path
- Verify `_routeScope` is correct

**Problem:** Authentication fails
- Check access key for Store API
- Verify OAuth token for Admin API
- Ensure permissions are correct

**Problem:** CORS errors
- Configure CORS in `.env`: `CORS_ALLOW_ORIGIN=*`
- Or in `config/packages/framework.yaml`

---

## Additional Resources

- [Store API Concept](https://developer.shopware.com/docs/concepts/api/store-api)
- [Admin API Concept](https://developer.shopware.com/docs/concepts/api/admin-api)
- [API Authentication](https://developer.shopware.com/docs/guides/integrations-api/authentication-authorisation)
- [OpenAPI Specification](https://swagger.io/specification/)
- [Postman Learning Center](https://learning.postman.com/)

---

**Estimated Completion Time:** 5-7 hours  
**Difficulty:** Intermediate to Advanced

🎉 Great progress! Tomorrow we'll master debugging and error analysis.
