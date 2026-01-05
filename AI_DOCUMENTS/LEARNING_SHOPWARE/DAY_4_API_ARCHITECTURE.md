# Day 4: API Architecture - Sales Channel & Admin APIs

**Duration:** 1.5-2 days (10-14 hours with breaks)  
**Goal:** Master Shopware's API architecture and create custom API endpoints

> **Note for Beginners:** API development builds on Days 1-3. Make sure you're comfortable with services and repositories before starting. Testing APIs takes time!

> **⚠️ IMPORTANT UPDATES (Shopware 6.7+):**
> - Use **PHP 8 Attributes** (#[Route]) instead of @Route annotations
> - Must create **routes.xml** file for route discovery
> - Place **specific routes BEFORE parameterized routes** (e.g., `/popular` before `/{productId}`)
> - Routes require **HTTPS** in production (not HTTP)
> - Namespace must match composer.json autoload configuration

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

> **⚠️ Key Points:**
> - Use PHP 8 `#[Route]` attributes, not `@Route` annotations
> - Place specific routes (like `/popular`) BEFORE parameterized routes (like `/{productId}`)
> - Use namespace `Learning\Bundle` to match composer.json autoload

Create `custom/plugins/LearningBundle/src/Core/Content/ProductView/SalesChannel/ProductViewRoute.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\ProductView\SalesChannel;

use Learning\Bundle\Service\ProductViewService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Learning\Bundle\Core\Content\ProductView\SalesChannel\ProductViewRouteResponse;
use Learning\Bundle\Core\Content\ProductView\SalesChannel\ProductViewResult;

#[Route(defaults: ['_routeScope' => ['store-api']])]
class ProductViewRoute extends AbstractProductViewRoute
{
    private ProductViewService $productViewService;

    public function __construct(ProductViewService $productViewService)
    {
        $this->productViewService = $productViewService;
    }

    public function getDecorated(): AbstractProductViewRoute
    {
        throw new \Exception('This route is not decorated.');
    }

    // IMPORTANT: Place specific routes BEFORE parameterized routes
    #[Route(path: '/store-api/learning/product-view/popular', name: 'store-api.learning.product-view.popular', methods: ['GET'])]
    public function popular(
        Request $request,
        SalesChannelContext $context
    ): JsonResponse {
        $limit = (int) $request->query->get('limit', 10);
        $popularProducts = $this->productViewService->getMostViewedProducts($limit, $context->getContext());

        return new JsonResponse([
            'success' => true,
            'data' => $popularProducts,
            'total' => count($popularProducts)
        ]);
    }

    #[Route(path: '/store-api/learning/product-view/{productId}', name: 'store-api.learning.product-view.detail', methods: ['GET'])]
    public function load(
        string $productId,
        Request $request,
        SalesChannelContext $context
    ): ProductViewRouteResponse {
        $viewCount = $this->productViewService->getProductViewCount($productId, $context->getContext());
        return new ProductViewRouteResponse(new ProductViewResult($productId, $viewCount));
    }

    #[Route(path: '/store-api/learning/product-view/{productId}', name: 'store-api.learning.product-view.record', methods: ['POST'])]
    public function record(
        string $productId,
        Request $request,
        SalesChannelContext $context
    ): JsonResponse {
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
            'message' => 'Product view recorded successfully'
        ]);
    }
}
```

### Step 1b: Create Routes Configuration File

> **⚠️ REQUIRED:** Shopware 6.7+ needs this file for route discovery!

Create `custom/plugins/LearningBundle/src/Resources/config/routes.xml`:

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<routes xmlns="http://symfony.com/schema/routing"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://symfony.com/schema/routing
        https://symfony.com/schema/routing/routing-1.0.xsd">

    <import resource="../../Core/Content/ProductView/SalesChannel/*Route.php" type="attribute" />
</routes>
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

    public function getApiAlias(): string
    {
        return 'learning_bundle_core_content_product_view_sales_channel_product_view_result';
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
    protected $object;

    public function __construct(ProductViewResult $result)
    {
        parent::__construct($result);
        $this->object = $result;
    }

    public function getResult(): ProductViewResult
    {
        return $this->object;
    }
}
```
### Step 4: Register Service

Update `custom/plugins/LearningBundle/src/Resources/config/services.xml`:

```xml
<?xml version="1.0" ?>
<container xmlns="http://symfony.com/schema/dic/services"
           xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
           xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd">

    <services>
        <!-- Product View Route -->
        <service id="Learning\Bundle\Core\Content\ProductView\SalesChannel\ProductViewRoute" public="true">
            <argument type="service" id="Learning\Bundle\Service\ProductViewService"/>
            <call method="setContainer">
                <argument type="service" id="service_container"/>
            </call>
            <tag name="controller.service_arguments"/>
        </service>

        <!-- Other services... -->
    </services>
</container>
```

> **Note on `controller.service_arguments` tag:** This tag is required for Store API routes in Shopware 6.7+. It tells Symfony to properly inject dependencies and handle the route as a controller service.

### Step 5: Testing Your Store API

After clearing cache (`bin/console cache:clear`), test your endpoints:

**Understanding Store API Authentication:**

In Shopware 6.7, Store API requests require an `sw-access-key` header (the sales channel access key). 

**What is SW_ACCESS_KEY?**
- Every sales channel has a unique access key
- Acts as API identifier for that specific sales channel
- Determines which products, prices, currencies are available
- Required for ALL Store API requests
- Found in: Administration → Sales Channels → [Your Channel] → API access

**How to get SW_ACCESS_KEY:**

**Method 1: From Administration UI (Recommended)**
1. Login to Shopware Administration (http://localhost:8000/admin)
2. Go to: **Sales Channels** → Select your sales channel (e.g., "Storefront")
3. Scroll down to **API access** section
4. Copy the **Access key** value

**Method 2: From Database via Docker**

```bash
# Get the access key
docker compose exec database mariadb -u root -proot shopware -e "SELECT COALESCE(sct.name, sc.short_name, 'Unnamed') as name, sc.access_key FROM sales_channel sc LEFT JOIN sales_channel_translation sct ON sc.id = sct.sales_channel_id GROUP BY sc.id"
```

**Example Output:**
```
+------------+----------------------------+
| name       | access_key                 |
+------------+----------------------------+
| Storefront | SWSCQJDIU3D3SUDTDEHDNVH2UW |
| Headless   | SWSCSMNNEWDJCWPVOTJNCXUYEA |
+------------+----------------------------+
```

**Testing the Endpoints:**

> **Important:** Use HTTPS (not HTTP) - Shopware 6.7+ redirects HTTP to HTTPS. Use `-k` flag to ignore SSL certificate warnings in development.

**1. Get Popular Products:**
```bash
curl -X GET "https://localhost:8000/store-api/learning/product-view/popular?limit=5" \
  -H "sw-access-key: SWSCQJDIU3D3SUDTDEHDNVH2UW" -k
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "product_id": "019b45b39b297056be5b38f00b1c8aee",
      "product_name": "Variant product",
      "view_count": 172,
      "last_viewed": "2025-01-19 14:23:45"
    },
    {
      "product_id": "019b464c1b0e7098b82fef6a6bc95ced",
      "product_name": "Main product with properties",
      "view_count": 133,
      "last_viewed": "2025-01-19 14:23:44"
    }
  ],
  "total": 4
}
```

**2. Get Specific Product View Count:**
```bash
curl -X GET "https://localhost:8000/store-api/learning/product-view/019b4610a6697180b4fd97770223e1da" \
  -H "sw-access-key: SWSCQJDIU3D3SUDTDEHDNVH2UW" -k
```

**Expected Response:**
```json
{
  "productId": "019b4610a6697180b4fd97770223e1da",
  "viewCount": 4
}
```

**3. Record a Product View (POST):**
```bash
curl -X POST "https://localhost:8000/store-api/learning/product-view/019b4610a6697180b4fd97770223e1da" \
  -H "sw-access-key: SWSCQJDIU3D3SUDTDEHDNVH2UW" -k
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Product view recorded successfully"
}
```

**4. Verify View Count Increased:**
```bash
# After recording, check count again - should increase by 1
curl -X GET "https://localhost:8000/store-api/learning/product-view/019b4610a6697180b4fd97770223e1da" \
  -H "sw-access-key: SWSCQJDIU3D3SUDTDEHDNVH2UW" -k
```

**Expected Response:**
```json
{
  "productId": "019b4610a6697180b4fd97770223e1da",
  "viewCount": 5
}
```

### Common Issues & Troubleshooting

**Issue: "No route found" error**
- **Cause:** Missing routes.xml or cache not cleared
- **Solution:** 
  1. Ensure routes.xml exists at `src/Resources/config/routes.xml`
  2. Run `bin/console cache:clear`
  3. Verify route is registered: `bin/console debug:router | grep product-view`

**Issue: Popular endpoint returns error about "productId"**
- **Cause:** Wrong route matched - Symfony matched `/{productId}` instead of `/popular`
- **Solution:** Ensure `popular()` method is defined BEFORE `load()` method in ProductViewRoute.php
- **Why:** Method order in PHP file determines Symfony route matching priority. Specific routes (no parameters) must come before parameterized routes.

**Issue: "Service not found" error**
- **Cause:** Missing `controller.service_arguments` tag or incorrect namespace
- **Solution:** 
  1. Add `<tag name="controller.service_arguments"/>` to your service definition
  2. Ensure namespace matches composer.json (e.g., `Learning\Bundle`, not `LearningBundle`)

**Issue: 401 Unauthorized**
- **Cause:** Missing or incorrect access key
- **Solution:** Get access key from Admin > Sales Channels > Your Channel > API Access

**Issue: HTTPS certificate errors**
- **Cause:** Self-signed SSL certificate in development
- **Solution:** Use `-k` flag with curl to ignore certificate warnings

Copy the `access_key` for the "Storefront" channel.

**What is SW_CONTEXT_TOKEN?**
A `sw-context-token` is **optional** for most read operations but **required** for:
- Cart operations (add to cart, checkout)
- Customer-specific data (wishlists, orders)
- Session-dependent features

For simple operations like viewing product statistics, the access key alone is sufficient.

```bash
# Clear cache with increased memory
php -d memory_limit=512M bin/console cache:clear

# Note: You may see "Connection refused" warnings during cache clear - this is normal
# when Docker is managing the database. The commands below use Docker directly.

# ==========================================
# Step 1: Get a product ID
# ==========================================

docker compose exec database mariadb -u root -proot shopware -e "SELECT LOWER(HEX(id)) as id, product_number FROM product LIMIT 3"

# Example output:
# +----------------------------------+---------------------+
# | id                               | product_number      |
# +----------------------------------+---------------------+
# | 019b4610a6697180b4fd97770223e1da | 5807372378675640149 |
# +----------------------------------+---------------------+

# Set your product ID (copy one from above)
export PRODUCT_ID="019b4610a6697180b4fd97770223e1da"

# ==========================================
# Step 2: Get your sales channel access key
# ==========================================

docker compose exec database mariadb -u root -proot shopware -e "SELECT COALESCE(sct.name, sc.short_name, 'Unnamed') as name, sc.access_key FROM sales_channel sc LEFT JOIN sales_channel_translation sct ON sc.id = sct.sales_channel_id GROUP BY sc.id"

# Example output:
# +------------+----------------------------+
# | name       | access_key                 |
# +------------+----------------------------+
# | Storefront | SWSCQJDIU3D3SUDTDEHDNVH2UW |
# +------------+----------------------------+

# Set the access key (copy from above)
export SW_ACCESS_KEY="SWSCQJDIU3D3SUDTDEHDNVH2UW"

# ==========================================
# Method 1: Simple requests (no context token needed)
# ==========================================

# Record a view
curl -X POST "http://localhost:8000/store-api/learning/product-view/${PRODUCT_ID}" \
  -H "sw-access-key: ${SW_ACCESS_KEY}"

# Get view count
curl -X GET "http://localhost:8000/store-api/learning/product-view/${PRODUCT_ID}" \
  -H "sw-access-key: ${SW_ACCESS_KEY}"

# Get popular products
curl -X GET "http://localhost:8000/store-api/learning/product-view/popular?limit=5" \
  -H "sw-access-key: ${SW_ACCESS_KEY}"

# ==========================================
# Method 2: If you need a context token (for session/customer-specific operations)
# ==========================================

# Get a context token by calling the context endpoint
# This creates a new session and returns a context token
curl -X GET "http://localhost:8000/store-api/context" \
  -H "sw-access-key: ${SW_ACCESS_KEY}" | jq -r '.contextToken'

# Save the token (replace with actual token from response)
export SW_CONTEXT_TOKEN="your-context-token-here"

# Now use both headers for authenticated requests
curl -X POST "http://localhost:8000/store-api/learning/product-view/${PRODUCT_ID}" \
  -H "sw-access-key: ${SW_ACCESS_KEY}" \
  -H "sw-context-token: ${SW_CONTEXT_TOKEN}"

# ==========================================
# Debugging
# ==========================================

# List all routes to verify yours exist
bin/console debug:router | grep learning

# Check if your service is registered
bin/console debug:container ProductViewRoute
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
use Symfony\Component\Routing\Attribute\Route;

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
                    'start' => (new \DateTime())->modify("-{$days} days")->format('Y-m-d'),
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
```

> **Note:** Admin API controllers use the same PHP 8 #[Route] attribute syntax as Store API routes. The key difference is the `_routeScope` set to `['api']` instead of `['store-api']`.

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
    <tag name="controller.service_arguments"/>
</service>

<!-- Analytics Service -->
<service id="Learning\Bundle\Service\ProductViewAnalyticsService">
    <argument type="service" id="learning_product_view.repository"/>
</service>
```

### Step 3: Get Admin API Credentials

**Method 1: Via Administration UI (Recommended)**
1. Login to Administration (http://localhost:8000/admin)
2. Go to: **Settings** → **System** → **Integrations**
3. Click **Add integration**
4. Fill in:
   - Label: "Learning Plugin API"
   - Generate keys or set custom access/secret keys
5. Save and copy the keys

**Method 2: Via Database (CLI)**

> **Important:** When using heredoc (`<<EOF`) with `docker compose exec`, you must add the `-T` flag to disable TTY allocation, otherwise you'll get "the input device is not a TTY" error.

```bash
# Option A: Via Docker with inline SQL (recommended - simpler)
docker compose exec database mariadb -u root -proot shopware -e "INSERT INTO integration (id, label, access_key, secret_access_key, created_at) VALUES (UNHEX(REPLACE(UUID(), '-', '')), 'Learning Plugin API', 'LEARNINGACCESSKEY', 'LEARNINGSECRETKEY', NOW())"

# Option B: Via Docker with heredoc (requires -T flag)
docker compose exec -T database mariadb -u root -proot shopware <<'EOF'
INSERT INTO integration (id, label, access_key, secret_access_key, created_at)
VALUES (
    UNHEX(REPLACE(UUID(), '-', '')),
    'Learning Plugin API',
    'LEARNINGACCESSKEY',
    'LEARNINGSECRETKEY',
    NOW()
);
EOF

# Option C: Using MySQL client with port from .env (check your DATABASE_URL for the port)
# Note: Port may vary - check your .env file for DATABASE_URL
mysql -h localhost -P 50399 -u root -proot shopware -e "INSERT INTO integration (id, label, access_key, secret_access_key, created_at) VALUES (UNHEX(REPLACE(UUID(), '-', '')), 'Learning Plugin API', 'LEARNINGACCESSKEY', 'LEARNINGSECRETKEY', NOW())"

# View existing integrations
docker compose exec database mariadb -u root -proot shopware -e "SELECT label, access_key, secret_access_key FROM integration"
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
use LearningBundle\Core\Api\Exception\ProductViewNotFoundException;

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
use LearningBundle\Core\Api\Response\ApiResponse;

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
- **Shopware 6.7+ Requirements:**
  - Use PHP 8 `#[Route]` attributes instead of `@Route` annotations
  - Create `routes.xml` for route discovery
  - Namespace must match composer.json (e.g., `Learning\Bundle`)
  - Route method order matters (specific routes before parameterized)
  - Add `controller.service_arguments` tag to service definitions
- **API Fundamentals:**
  - Difference between Store API and Admin API
  - Creating custom Store API routes with proper response objects
  - Creating custom Admin API endpoints
  - API authentication flows (access keys vs OAuth tokens)
- **Best Practices:**
  - Request validation and error handling
  - Response formatting with StoreApiResponse
  - OpenAPI documentation (when needed)
  - Testing APIs with cURL and proper HTTPS handling

## Common Issues

**Problem:** Route not found (404)
- **Solution:** 
  - Ensure `routes.xml` exists at `src/Resources/config/routes.xml`
  - Clear cache: `php -d memory_limit=512M bin/console cache:clear` or `rm -rf var/cache/*`
  - Verify route is registered: `bin/console debug:router | grep learning`
  - Check PHP 8 attribute syntax is correct

**Problem:** Specific route (e.g., `/popular`) returns error about route parameter
- **Solution:** 
  - Reorder methods in controller - specific routes MUST come before parameterized routes
  - Example: `popular()` method before `load($productId)` method
  - Clear cache after reordering

**Problem:** "Service not found" or dependency injection errors
- **Solution:**
  - Add `<tag name="controller.service_arguments"/>` to service definition
  - Verify namespace matches composer.json autoload configuration
  - Ensure all dependencies are properly registered in services.xml

**Problem:** Authentication fails
- **Store API:** Check access key from Sales Channels > Your Channel > API Access
- **Admin API:** Verify OAuth token from Settings > System > Integrations
- Ensure proper headers: `sw-access-key` for Store API, `Authorization: Bearer` for Admin API

**Problem:** HTTPS/SSL certificate errors in development
- **Solution:** Use `-k` flag with curl to ignore self-signed certificates
- Or configure proper SSL certificates in Docker/development environment

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
