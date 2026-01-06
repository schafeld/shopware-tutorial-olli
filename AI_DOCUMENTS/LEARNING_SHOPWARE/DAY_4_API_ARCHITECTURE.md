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
  2. Verify namespace matches composer.json (e.g., `Learning\Bundle`, not `LearningBundle`)

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

> **Important:** Use HTTPS (not HTTP) - Shopware 6.7+ redirects HTTP to HTTPS for all API requests, including OAuth token endpoint.

```bash
# Get OAuth token (note: use HTTPS with -k flag)
curl -k -X POST "https://localhost:8000/api/oauth/token" \
  -H "Content-Type: application/json" \
  -d '{
    "client_id": "administration",
    "grant_type": "password",
    "scopes": "write",
    "username": "admin",
    "password": "shopware"
  }'

# Expected response:
# {
#   "token_type": "Bearer",
#   "expires_in": 600,
#   "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJI...",
#   "refresh_token": "def5020051f6305bd25ef..."
# }

# Save the access token from the response
export SW_ACCESS_TOKEN="your-access-token-here"

# Test analytics overview
curl -X GET "https://localhost:8000/api/_action/learning/product-view/analytics/overview?days=7" \
  -H "Authorization: Bearer $SW_ACCESS_TOKEN" \
  -H "Content-Type: application/json"

# Test product-specific analytics
curl -X GET "https://localhost:8000/api/_action/learning/product-view/analytics/product/YOUR_PRODUCT_ID" \
  -H "Authorization: Bearer $SW_ACCESS_TOKEN"

# Test popular products
curl -X GET "https://localhost:8000/api/_action/learning/product-view/analytics/popular?limit=10" \
  -H "Authorization: Bearer $SW_ACCESS_TOKEN"
```

---

## Part 4: API Best Practices (60 minutes)

### Practice 1: Error Handling

**File Structure:**
```
custom/plugins/LearningBundle/src/
├── Core/
│   ├── Api/
│   │   ├── Exception/
│   │   │   ├── ProductViewNotFoundException.php
│   │   │   ├── InvalidAnalyticsRequestException.php
│   │   │   └── ProductViewLimitExceededException.php
│   │   ├── ProductViewAnalyticsController.php  (update this)
│   │   └── ...
```

**Step 1: Create Exception Classes**

Create `custom/plugins/LearningBundle/src/Core/Api/Exception/ProductViewNotFoundException.php`:

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

Create `custom/plugins/LearningBundle/src/Core/Api/Exception/InvalidAnalyticsRequestException.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Api\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class InvalidAnalyticsRequestException extends ShopwareHttpException
{
    public function __construct(array $errors)
    {
        parent::__construct(
            'Invalid request parameters: {{ errors }}',
            ['errors' => json_encode($errors)]
        );
    }

    public function getErrorCode(): string
    {
        return 'LEARNING__INVALID_ANALYTICS_REQUEST';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
```

Create `custom/plugins/LearningBundle/src/Core/Api/Exception/ProductViewLimitExceededException.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Api\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class ProductViewLimitExceededException extends ShopwareHttpException
{
    public function __construct(int $limit, int $requested)
    {
        parent::__construct(
            'Requested limit {{ requested }} exceeds maximum allowed {{ limit }}',
            ['limit' => $limit, 'requested' => $requested]
        );
    }

    public function getErrorCode(): string
    {
        return 'LEARNING__PRODUCT_VIEW_LIMIT_EXCEEDED';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
```

**Step 2: Update Controller with Error Handling**

Update `custom/plugins/LearningBundle/src/Core/Api/ProductViewAnalyticsController.php`:

```php
use Learning\Bundle\Core\Api\Exception\ProductViewNotFoundException;
use Learning\Bundle\Core\Api\Exception\ProductViewLimitExceededException;

public function getProductAnalytics(string $productId, Request $request, Context $context): JsonResponse
{
    $viewCount = $this->productViewService->getProductViewCount($productId, $context);
    
    // Throw exception if product has no views
    if ($viewCount === 0) {
        throw new ProductViewNotFoundException($productId);
    }

    return new JsonResponse(
        ApiResponse::success([
            'product_id' => $productId,
            'total_views' => $viewCount,
        ])
    );
}

public function getPopularProducts(Request $request, Context $context): JsonResponse
{
    $limit = (int) $request->query->get('limit', 10);
    
    // Validate limit (max 100)
    if ($limit > 100) {
        throw new ProductViewLimitExceededException(100, $limit);
    }
    
    $popularProducts = $this->productViewService->getMostViewedProducts($limit, $context);

    return new JsonResponse(
        ApiResponse::paginated($popularProducts, count($popularProducts), $page, $limit)
    );
}

public function resetProductViews(string $productId, Context $context): JsonResponse
{
    // Reset logic would go here
    
    return new JsonResponse(
        ApiResponse::success([
            'product_id' => $productId,
            'reset' => true,
        ], [
            'message' => "View count for product {$productId} has been reset",
        ])
    );
}
```

### Practice 2: Request Validation

**File Structure:**
```
custom/plugins/LearningBundle/src/
├── Core/
│   ├── Api/
│   │   ├── Validator/
│   │   │   └── AnalyticsRequestValidator.php  (create this)
│   │   ├── ProductViewAnalyticsController.php  (update this)
```

**Step 1: Create Validator Class**

Create `custom/plugins/LearningBundle/src/Core/Api/Validator/AnalyticsRequestValidator.php`:

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
                new Assert\Type(['type' => 'integer', 'message' => 'Days must be an integer']),
                new Assert\Range([
                    'min' => 1,
                    'max' => 365,
                    'notInRangeMessage' => 'Days must be between {{ min }} and {{ max }}',
                ]),
            ],
        ]);

        $violations = $this->validator->validate(['days' => (int)$days], $constraints);

        $errors = [];
        foreach ($violations as $violation) {
            $errors[] = [
                'property' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];
        }

        return $errors;
    }

    public function validatePopularRequest(Request $request): array
    {
        $limit = $request->query->get('limit', 10);

        $constraints = new Assert\Collection([
            'limit' => [
                new Assert\Type(['type' => 'integer', 'message' => 'Limit must be an integer']),
                new Assert\Range([
                    'min' => 1,
                    'max' => 100,
                    'notInRangeMessage' => 'Limit must be between {{ min }} and {{ max }}',
                ]),
            ],
        ]);

        $violations = $this->validator->validate(['limit' => (int)$limit], $constraints);

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

**Step 2: Register Validator in services.xml**

Update `custom/plugins/LearningBundle/src/Resources/config/services.xml`:

```xml
<!-- Request Validator -->
<service id="Learning\Bundle\Core\Api\Validator\AnalyticsRequestValidator">
    <argument type="service" id="validator"/>
</service>
```

**Step 3: Use Validator in Controller**

Update `custom/plugins/LearningBundle/src/Core/Api/ProductViewAnalyticsController.php`:

```php
use Learning\Bundle\Core\Api\Validator\AnalyticsRequestValidator;
use Learning\Bundle\Core\Api\Exception\InvalidAnalyticsRequestException;

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
        // Validate request
        $errors = $this->validator->validateOverviewRequest($request);
        if (!empty($errors)) {
            throw new InvalidAnalyticsRequestException($errors);
        }

        $days = (int) $request->query->get('days', 30);

        $viewsPerDay = $this->analyticsService->getViewsForLastDays($days, $context);
        $totalViews = $this->analyticsService->getTotalViewsByProduct($context);
        $browserStats = $this->analyticsService->getViewsByBrowser($context);

        return new JsonResponse(
            ApiResponse::success([
                'period' => [
                    'days' => $days,
                    'start' => (new \DateTime())->modify("-{$days} days")->format('Y-m-d'),
                    'end' => (new \DateTime())->format('Y-m-d'),
                ],
                'views_per_day' => $viewsPerDay,
                'total_views_by_product' => $totalViews,
                'browser_statistics' => $browserStats,
            ], [
                'version' => '1.0',
                'endpoint' => 'overview',
            ])
        );
    }
}
```

**Step 4: Update Controller Service Definition**

Update the controller service in `services.xml` to inject the validator:

```xml
<!-- Admin API Controller -->
<service id="Learning\Bundle\Core\Api\ProductViewAnalyticsController" public="true">
    <argument type="service" id="Learning\Bundle\Service\ProductViewAnalyticsService"/>
    <argument type="service" id="Learning\Bundle\Service\ProductViewService"/>
    <argument type="service" id="Learning\Bundle\Core\Api\Validator\AnalyticsRequestValidator"/>
    <call method="setContainer">
        <argument type="service" id="service_container"/>
    </call>
    <tag name="controller.service_arguments"/>
</service>
```

### Practice 3: Response Formatting

**File Structure:**
```
custom/plugins/LearningBundle/src/
├── Core/
│   ├── Api/
│   │   ├── Response/
│   │   │   └── ApiResponse.php  (create this)
│   │   ├── ProductViewAnalyticsController.php  (update this)
```

**Step 1: Create Response Helper Class**

Create `custom/plugins/LearningBundle/src/Core/Api/Response/ApiResponse.php`:

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

    public static function collection($data, array $meta = []): array
    {
        return [
            'success' => true,
            'data' => $data,
            'meta' => array_merge([
                'total' => count($data),
                'timestamp' => (new \DateTime())->format(\DateTime::ATOM),
            ], $meta),
        ];
    }
}
```

**Step 2: Apply to All Controller Methods**

Update `custom/plugins/LearningBundle/src/Core/Api/ProductViewAnalyticsController.php`:

```php
use Learning\Bundle\Core\Api\Response\ApiResponse;

public function getOverview(Request $request, Context $context): JsonResponse
{
    $errors = $this->validator->validateOverviewRequest($request);
    if (!empty($errors)) {
        throw new InvalidAnalyticsRequestException($errors);
    }

    $days = (int) $request->query->get('days', 30);

    $viewsPerDay = $this->analyticsService->getViewsForLastDays($days, $context);
    $totalViews = $this->analyticsService->getTotalViewsByProduct($context);
    $browserStats = $this->analyticsService->getViewsByBrowser($context);

    return new JsonResponse(
        ApiResponse::success([
            'period' => [
                'days' => $days,
                'start' => (new \DateTime())->modify("-{$days} days")->format('Y-m-d'),
                'end' => (new \DateTime())->format('Y-m-d'),
            ],
            'views_per_day' => $viewsPerDay,
            'total_views_by_product' => $totalViews,
            'browser_statistics' => $browserStats,
        ], [
            'version' => '1.0',
            'endpoint' => 'overview',
        ])
    );
}

public function getProductAnalytics(string $productId, Request $request, Context $context): JsonResponse
{
    $viewCount = $this->productViewService->getProductViewCount($productId, $context);
    
    if ($viewCount === 0) {
        throw new ProductViewNotFoundException($productId);
    }

    return new JsonResponse(
        ApiResponse::success([
            'product_id' => $productId,
            'total_views' => $viewCount,
        ])
    );
}

public function getPopularProducts(Request $request, Context $context): JsonResponse
{
    $errors = $this->validator->validatePopularRequest($request);
    if (!empty($errors)) {
        throw new InvalidAnalyticsRequestException($errors);
    }

    $limit = (int) $request->query->get('limit', 10);
    $page = (int) $request->query->get('page', 1);
    
    $popularProducts = $this->productViewService->getMostViewedProducts($limit, $context);

    return new JsonResponse(
        ApiResponse::paginated($popularProducts, count($popularProducts), $page, $limit)
    );
}

public function resetProductViews(string $productId, Context $context): JsonResponse
{
    // Reset logic would go here
    
    return new JsonResponse(
        ApiResponse::success([
            'product_id' => $productId,
            'reset' => true,
        ], [
            'message' => "View count for product {$productId} has been reset",
        ])
    );
}
```

---

## Part 5: API Documentation with OpenAPI (45 minutes)

### Generate API Documentation

Shopware uses OpenAPI (Swagger) for API documentation. You can generate the schema in multiple ways:

**Method 1: Generate File Using CLI (Recommended for Import)**

```bash
# Generate OpenAPI schema for Admin API
bin/console framework:schema --schema-format=openapi3 openapi-admin.json

# Or using short form
bin/console framework:schema -s openapi3 openapi-admin.json

# Pretty-printed JSON (more readable)
bin/console framework:schema -s openapi3 --pretty openapi-admin.json

# For Store API specifically
bin/console framework:schema -s openapi3 --store-api openapi-store.json
```

> **Note:** You may see a warning: `Warning: Failed to load plugins. Message: An exception occurred in the driver: SQLSTATE[HY000] [2002] Connection refused`
> 
> This warning is **harmless** and can be safely ignored. It occurs because the command tries to connect to the database during plugin loading, but the schema generation doesn't actually need database data - it reads from your route attributes and service definitions.
>
> **To verify it worked:** Check that the file was created: `ls -lh openapi-admin.json` (should be 50KB+)

**Suppress the Warning (Optional):**

```bash
# Option 1: Redirect stderr to suppress warnings
bin/console framework:schema -s openapi3 openapi-admin.json 2>/dev/null

# Option 2: Ensure database is fully started before running
docker compose up -d database
sleep 5
bin/console framework:schema -s openapi3 openapi-admin.json
```

**Method 2: Access Live API Endpoints (Best for Testing)**

```bash
# Admin API schema
curl -k https://localhost:8000/api/_info/openapi3.json | jq . > openapi-admin.json

# Store API schema (requires access key)
curl -k https://localhost:8000/store-api/_info/openapi3.json \
  -H "sw-access-key: YOUR_ACCESS_KEY" | jq . > openapi-store.json
```

> **Note:** Use `-k` flag to ignore self-signed SSL certificate warnings in development.

### Verify Your Routes Appear in the Schema

```bash
# Search for your custom routes
grep -A 5 "learning" openapi-admin.json

# Or view specific sections
cat openapi-admin.json | jq '.paths | keys[] | select(contains("learning"))'
```

### Import into API Testing Tools

**Option 1: Postman**
1. Open Postman
2. Click **Import** button
3. Select the generated `openapi-admin.json` file
4. Postman will create a collection with all endpoints

**Option 2: Swagger UI**
1. Install Swagger UI: `npm install -g swagger-ui-watcher`
2. Run: `swagger-ui-watcher openapi-admin.json`
3. Open browser to view interactive documentation

**Option 3: Use Shopware's Built-in Documentation**
- Navigate to: `https://localhost:8000/api/_info/swagger.html` (Admin API)
- Navigate to: `https://localhost:8000/store-api/_info/swagger.html` (Store API)

### Create Postman Environment

After importing the collection, create an environment with these variables:

```json
{
  "base_url": "https://localhost:8000",
  "access_key": "YOUR_SALES_CHANNEL_ACCESS_KEY",
  "context_token": "will_be_set_after_login",
  "admin_token": "will_be_set_after_oauth"
}
```

### Test All Endpoints

1. **Store API Endpoints:**
   - Get popular products
   - Record product view
   - Get specific product view count

2. **Admin API Endpoints:**
   - Get analytics overview
   - Get product-specific analytics
   - Get popular products (admin)

3. **Verify Response Format:**
   - All responses should follow your ApiResponse structure
   - Check timestamps, metadata, pagination where applicable
   - Verify error responses for invalid requests

### Common Issues & Troubleshooting

**Issue: "The `-f` option does not exist" error**
- **Cause:** Wrong command syntax - Shopware 6.7+ uses `--schema-format` not `-f`
- **Solution:** 
  ```bash
  # Correct syntax:
  bin/console framework:schema --schema-format=openapi3 openapi.json
  # Or short form:
  bin/console framework:schema -s openapi3 openapi.json
  ```

**Issue: "Connection refused" warning appears but file is created**
- **Cause:** Command tries to load plugins and connect to database, but doesn't actually need it for schema generation
- **Status:** ✅ **This is normal and harmless** - the file was created successfully
- **Verification:** 
  ```bash
  # Check file was created
  ls -lh openapi.json
  # Should show a file 50KB+ in size
  
  # Verify your routes are in the schema
  grep "learning" openapi.json
  ```
- **To suppress warning (optional):**
  ```bash
  bin/console framework:schema -s openapi3 openapi.json 2>/dev/null
  ```

**Issue: Generated file is empty or very small (<1KB)**
- **Cause:** Command failed but didn't show clear error message
- **Solution:** 
  1. Ensure routes.xml exists and is properly configured
  2. Clear cache: `php -d memory_limit=512M bin/console cache:clear`
  3. Verify routes are registered: `bin/console debug:router | grep learning`
  4. Try accessing the live endpoint instead: `curl -k https://localhost:8000/api/_info/openapi3.json`

**Issue: Schema doesn't include custom routes**
- **Cause:** Routes not properly discovered or cache issue
- **Solution:**
  1. Verify routes.xml exists at `src/Resources/config/routes.xml`
  2. Check route attributes use `#[Route]` syntax (not old `@Route` annotations)
  3. Clear cache thoroughly: `rm -rf var/cache/*`
  4. Regenerate: `bin/console framework:schema -s openapi3 openapi.json`

**Issue: Cannot access live OpenAPI endpoint (404)**
- **Cause:** Trying to use HTTP instead of HTTPS, or wrong path
- **Solution:**
  ```bash
  # Admin API - correct URL with HTTPS and -k flag
  curl -k https://localhost:8000/api/_info/openapi3.json
  
  # Store API - needs access key
  curl -k https://localhost:8000/store-api/_info/openapi3.json \
    -H "sw-access-key: YOUR_ACCESS_KEY"
  ```

---

## Part 6: Exercises (90-120 minutes)

### Exercise 1: Wishlist API (45 minutes)

**Goal:** Create a complete Store API for customer wishlists with proper authentication, validation, and error handling.

**File Structure to Create:**
```
custom/plugins/LearningBundle/src/
├── Core/
│   ├── Content/
│   │   ├── Wishlist/
│   │   │   ├── SalesChannel/
│   │   │   │   ├── AbstractWishlistRoute.php
│   │   │   │   ├── WishlistRoute.php
│   │   │   │   ├── WishlistAddRoute.php
│   │   │   │   ├── WishlistRemoveRoute.php
│   │   │   │   ├── WishlistResult.php
│   │   │   │   └── WishlistRouteResponse.php
│   │   │   └── WishlistEntity.php
├── Service/
│   └── WishlistService.php
```

**Step 1: Create Wishlist Service**

Create `custom/plugins/LearningBundle/src/Service/WishlistService.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class WishlistService
{
    private EntityRepository $customerWishlistRepository;
    private EntityRepository $productRepository;

    public function __construct(
        EntityRepository $customerWishlistRepository,
        EntityRepository $productRepository
    ) {
        $this->customerWishlistRepository = $customerWishlistRepository;
        $this->productRepository = $productRepository;
    }

    public function addProduct(string $customerId, string $productId, Context $context): void
    {
        // Check if product exists
        $criteria = new Criteria([$productId]);
        $product = $this->productRepository->search($criteria, $context)->first();
        
        if (!$product) {
            throw new \InvalidArgumentException("Product {$productId} not found");
        }

        // Check if already in wishlist
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));
        $criteria->addFilter(new EqualsFilter('productId', $productId));
        
        $existing = $this->customerWishlistRepository->search($criteria, $context)->first();
        if ($existing) {
            return; // Already in wishlist
        }

        // Add to wishlist
        $this->customerWishlistRepository->create([[
            'id' => Uuid::randomHex(),
            'customerId' => $customerId,
            'productId' => $productId,
            'createdAt' => new \DateTime(),
        ]], $context);
    }

    public function removeProduct(string $customerId, string $productId, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));
        $criteria->addFilter(new EqualsFilter('productId', $productId));
        
        $wishlistItem = $this->customerWishlistRepository->search($criteria, $context)->first();
        
        if (!$wishlistItem) {
            throw new \InvalidArgumentException("Product {$productId} not in wishlist");
        }

        $this->customerWishlistRepository->delete([[
            'id' => $wishlistItem->getId(),
        ]], $context);
    }

    public function getWishlist(string $customerId, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));
        $criteria->addAssociation('product');
        $criteria->addAssociation('product.cover');
        
        $wishlistItems = $this->customerWishlistRepository->search($criteria, $context);
        
        $products = [];
        foreach ($wishlistItems as $item) {
            $product = $item->getProduct();
            $products[] = [
                'wishlist_item_id' => $item->getId(),
                'product_id' => $product->getId(),
                'product_number' => $product->getProductNumber(),
                'name' => $product->getTranslation('name'),
                'price' => $product->getPrice(),
                'added_at' => $item->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }
        
        return $products;
    }
}
```

**Step 2: Create Store API Routes**

Create `custom/plugins/LearningBundle/src/Core/Content/Wishlist/SalesChannel/WishlistRoute.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Content\Wishlist\SalesChannel;

use Learning\Bundle\Service\WishlistService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['store-api'], '_loginRequired' => true])]
class WishlistRoute
{
    private WishlistService $wishlistService;

    public function __construct(WishlistService $wishlistService)
    {
        $this->wishlistService = $wishlistService;
    }

    #[Route(
        path: '/store-api/learning/wishlist',
        name: 'store-api.learning.wishlist.get',
        methods: ['GET']
    )]
    public function load(
        Request $request,
        SalesChannelContext $context
    ): JsonResponse {
        $customer = $context->getCustomer();
        
        if (!$customer) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Customer not logged in',
            ], 401);
        }

        $wishlist = $this->wishlistService->getWishlist($customer->getId(), $context->getContext());

        return new JsonResponse([
            'success' => true,
            'data' => $wishlist,
            'total' => count($wishlist),
        ]);
    }

    #[Route(
        path: '/store-api/learning/wishlist/add',
        name: 'store-api.learning.wishlist.add',
        methods: ['POST']
    )]
    public function add(
        Request $request,
        SalesChannelContext $context
    ): JsonResponse {
        $customer = $context->getCustomer();
        
        if (!$customer) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Customer not logged in',
            ], 401);
        }

        $productId = $request->request->get('productId');
        
        if (!$productId) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Product ID is required',
            ], 400);
        }

        try {
            $this->wishlistService->addProduct(
                $customer->getId(),
                $productId,
                $context->getContext()
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Product added to wishlist',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    #[Route(
        path: '/store-api/learning/wishlist/remove/{productId}',
        name: 'store-api.learning.wishlist.remove',
        methods: ['DELETE']
    )]
    public function remove(
        string $productId,
        Request $request,
        SalesChannelContext $context
    ): JsonResponse {
        $customer = $context->getCustomer();
        
        if (!$customer) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Customer not logged in',
            ], 401);
        }

        try {
            $this->wishlistService->removeProduct(
                $customer->getId(),
                $productId,
                $context->getContext()
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Product removed from wishlist',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
```

**Step 3: Register Services**

Update `custom/plugins/LearningBundle/src/Resources/config/services.xml`:

```xml
<!-- Wishlist Service -->
<service id="Learning\Bundle\Service\WishlistService">
    <argument type="service" id="customer_wishlist_product.repository"/>
    <argument type="service" id="product.repository"/>
</service>

<!-- Wishlist Routes -->
<service id="Learning\Bundle\Core\Content\Wishlist\SalesChannel\WishlistRoute" public="true">
    <argument type="service" id="Learning\Bundle\Service\WishlistService"/>
    <call method="setContainer">
        <argument type="service" id="service_container"/>
    </call>
    <tag name="controller.service_arguments"/>
</service>
```

**Step 4: Update routes.xml**

Update `custom/plugins/LearningBundle/src/Resources/config/routes.xml`:

```xml
<import resource="../../Core/Content/Wishlist/SalesChannel/*Route.php" type="attribute" />
```

**Step 5: Test the Wishlist API**

```bash
# Clear cache
php -d memory_limit=512M bin/console cache:clear

# Get context token (with customer login)
curl -X POST "https://localhost:8000/store-api/account/login" \
  -H "sw-access-key: ${SW_ACCESS_KEY}" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "customer@example.com",
    "password": "shopware"
  }' -k

# Save the context token from response
export SW_CONTEXT_TOKEN="your-context-token"

# Add product to wishlist
curl -X POST "https://localhost:8000/store-api/learning/wishlist/add" \
  -H "sw-access-key: ${SW_ACCESS_KEY}" \
  -H "sw-context-token: ${SW_CONTEXT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"productId": "YOUR_PRODUCT_ID"}' -k

# Get wishlist
curl -X GET "https://localhost:8000/store-api/learning/wishlist" \
  -H "sw-access-key: ${SW_ACCESS_KEY}" \
  -H "sw-context-token: ${SW_CONTEXT_TOKEN}" -k

# Remove from wishlist
curl -X DELETE "https://localhost:8000/store-api/learning/wishlist/remove/YOUR_PRODUCT_ID" \
  -H "sw-access-key: ${SW_ACCESS_KEY}" \
  -H "sw-context-token: ${SW_CONTEXT_TOKEN}" -k
```

---

### Exercise 2: Product Comparison API (45 minutes)

**Goal:** Create Admin API endpoints to track and analyze product comparisons.

**File Structure to Create:**
```
custom/plugins/LearningBundle/src/
├── Core/
│   ├── Api/
│   │   └── ProductComparisonController.php
├── Service/
│   └── ProductComparisonService.php
```

**Step 1: Create Database Migration**

Create `custom/plugins/LearningBundle/src/Migration/Migration1704556800ProductComparison.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1704556800ProductComparison extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1704556800;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `learning_product_comparison` (
    `id` BINARY(16) NOT NULL,
    `product_id_1` BINARY(16) NOT NULL,
    `product_id_2` BINARY(16) NOT NULL,
    `customer_id` BINARY(16) NULL,
    `comparison_count` INT NOT NULL DEFAULT 1,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    KEY `idx_product_pair` (`product_id_1`, `product_id_2`),
    CONSTRAINT `fk_learning_comparison_product_1` FOREIGN KEY (`product_id_1`) REFERENCES `product` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_learning_comparison_product_2` FOREIGN KEY (`product_id_2`) REFERENCES `product` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_learning_comparison_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // Implement if needed
    }
}
```

**Step 2: Create Comparison Service**

Create `custom/plugins/LearningBundle/src/Service/ProductComparisonService.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;

class ProductComparisonService
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function recordComparison(
        string $productId1,
        string $productId2,
        ?string $customerId,
        Context $context
    ): void {
        // Ensure consistent ordering (smaller ID first)
        if ($productId1 > $productId2) {
            [$productId1, $productId2] = [$productId2, $productId1];
        }

        $sql = <<<SQL
INSERT INTO learning_product_comparison 
    (id, product_id_1, product_id_2, customer_id, comparison_count, created_at, updated_at)
VALUES 
    (UNHEX(?), UNHEX(?), UNHEX(?), ?, 1, NOW(3), NOW(3))
ON DUPLICATE KEY UPDATE 
    comparison_count = comparison_count + 1,
    updated_at = NOW(3)
SQL;

        $this->connection->executeStatement($sql, [
            bin2hex(random_bytes(16)),
            $productId1,
            $productId2,
            $customerId ? hex2bin($customerId) : null,
        ]);
    }

    public function getComparisonStats(Context $context): array
    {
        $sql = <<<SQL
SELECT 
    COUNT(DISTINCT id) as total_comparisons,
    COUNT(DISTINCT customer_id) as unique_customers,
    AVG(comparison_count) as avg_comparisons_per_pair
FROM learning_product_comparison
SQL;

        return $this->connection->fetchAssociative($sql) ?: [];
    }

    public function getPopularCombinations(int $limit, Context $context): array
    {
        $sql = <<<SQL
SELECT 
    LOWER(HEX(lpc.product_id_1)) as product_id_1,
    LOWER(HEX(lpc.product_id_2)) as product_id_2,
    p1.product_number as product_number_1,
    p2.product_number as product_number_2,
    pt1.name as product_name_1,
    pt2.name as product_name_2,
    SUM(lpc.comparison_count) as total_comparisons
FROM learning_product_comparison lpc
LEFT JOIN product p1 ON lpc.product_id_1 = p1.id
LEFT JOIN product p2 ON lpc.product_id_2 = p2.id
LEFT JOIN product_translation pt1 ON p1.id = pt1.product_id AND pt1.language_id = UNHEX(?)
LEFT JOIN product_translation pt2 ON p2.id = pt2.product_id AND pt2.language_id = UNHEX(?)
GROUP BY lpc.product_id_1, lpc.product_id_2
ORDER BY total_comparisons DESC
LIMIT ?
SQL;

        $languageId = $context->getLanguageId();
        return $this->connection->fetchAllAssociative($sql, [
            $languageId,
            $languageId,
            $limit,
        ]);
    }
}
```

**Step 3: Create Admin API Controller**

Create `custom/plugins/LearningBundle/src/Core/Api/ProductComparisonController.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Api;

use Learning\Bundle\Service\ProductComparisonService;
use Learning\Bundle\Core\Api\Response\ApiResponse;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class ProductComparisonController extends AbstractController
{
    private ProductComparisonService $comparisonService;

    public function __construct(ProductComparisonService $comparisonService)
    {
        $this->comparisonService = $comparisonService;
    }

    #[Route(
        path: '/api/_action/learning/comparison/stats',
        name: 'api.action.learning.comparison.stats',
        methods: ['GET']
    )]
    public function getStats(Request $request, Context $context): JsonResponse
    {
        $stats = $this->comparisonService->getComparisonStats($context);

        return new JsonResponse(
            ApiResponse::success($stats, [
                'endpoint' => 'comparison-stats',
            ])
        );
    }

    #[Route(
        path: '/api/_action/learning/comparison/popular-combinations',
        name: 'api.action.learning.comparison.popular',
        methods: ['GET']
    )]
    public function getPopularCombinations(Request $request, Context $context): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 20);
        
        $combinations = $this->comparisonService->getPopularCombinations($limit, $context);

        return new JsonResponse(
            ApiResponse::collection($combinations, [
                'endpoint' => 'popular-combinations',
                'limit' => $limit,
            ])
        );
    }
}
```

**Step 4: Register Services**

Update `services.xml`:

```xml
<!-- Product Comparison Service -->
<service id="Learning\Bundle\Service\ProductComparisonService">
    <argument type="service" id="Doctrine\DBAL\Connection"/>
</service>

<!-- Product Comparison Controller -->
<service id="Learning\Bundle\Core\Api\ProductComparisonController" public="true">
    <argument type="service" id="Learning\Bundle\Service\ProductComparisonService"/>
    <call method="setContainer">
        <argument type="service" id="service_container"/>
    </call>
    <tag name="controller.service_arguments"/>
</service>
```

**Step 5: Run Migration and Test**

```bash
# Run migration
bin/console database:migrate --all

# Clear cache
php -d memory_limit=512M bin/console cache:clear

# Get admin token
curl -k -X POST "https://localhost:8000/api/oauth/token" \
  -H "Content-Type: application/json" \
  -d '{
    "client_id": "administration",
    "grant_type": "password",
    "scopes": "write",
    "username": "admin",
    "password": "shopware"
  }'

export SW_ACCESS_TOKEN="your-token"

# Get comparison stats
curl -X GET "https://localhost:8000/api/_action/learning/comparison/stats" \
  -H "Authorization: Bearer $SW_ACCESS_TOKEN" \
  -H "Content-Type: application/json" -k

# Get popular combinations
curl -X GET "https://localhost:8000/api/_action/learning/comparison/popular-combinations?limit=10" \
  -H "Authorization: Bearer $SW_ACCESS_TOKEN" \
  -H "Content-Type: application/json" -k
```

---

### Exercise 3: Rate Limiting (30 minutes)

**Goal:** Implement simple rate limiting to prevent API abuse.

**File Structure to Create:**
```
custom/plugins/LearningBundle/src/
├── Core/
│   ├── Api/
│   │   ├── RateLimiter/
│   │   │   ├── RateLimiterInterface.php
│   │   │   └── SimpleRateLimiter.php
│   │   └── Exception/
│   │       └── RateLimitExceededException.php
```

**Step 1: Create Rate Limiter**

Create `custom/plugins/LearningBundle/src/Core/Api/RateLimiter/SimpleRateLimiter.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Api\RateLimiter;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\Request;

class SimpleRateLimiter
{
    private AdapterInterface $cache;
    private int $maxRequests;
    private int $windowSeconds;

    public function __construct(
        AdapterInterface $cache,
        int $maxRequests = 100,
        int $windowSeconds = 60
    ) {
        $this->cache = $cache;
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
    }

    public function check(Request $request): bool
    {
        $key = $this->getKey($request);
        $cacheItem = $this->cache->getItem($key);

        if (!$cacheItem->isHit()) {
            $cacheItem->set(1);
            $cacheItem->expiresAfter($this->windowSeconds);
            $this->cache->save($cacheItem);
            return true;
        }

        $count = $cacheItem->get();
        
        if ($count >= $this->maxRequests) {
            return false;
        }

        $cacheItem->set($count + 1);
        $this->cache->save($cacheItem);
        return true;
    }

    public function getRemainingRequests(Request $request): int
    {
        $key = $this->getKey($request);
        $cacheItem = $this->cache->getItem($key);

        if (!$cacheItem->isHit()) {
            return $this->maxRequests;
        }

        $count = $cacheItem->get();
        return max(0, $this->maxRequests - $count);
    }

    private function getKey(Request $request): string
    {
        // Use IP address or customer ID as key
        $identifier = $request->getClientIp();
        return 'rate_limit_' . md5($identifier);
    }
}
```

**Step 2: Create Exception**

Create `custom/plugins/LearningBundle/src/Core/Api/Exception/RateLimitExceededException.php`:

```php
<?php declare(strict_types=1);

namespace Learning\Bundle\Core\Api\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class RateLimitExceededException extends ShopwareHttpException
{
    public function __construct(int $retryAfter)
    {
        parent::__construct(
            'Rate limit exceeded. Try again in {{ seconds }} seconds.',
            ['seconds' => $retryAfter]
        );
    }

    public function getErrorCode(): string
    {
        return 'LEARNING__RATE_LIMIT_EXCEEDED';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_TOO_MANY_REQUESTS;
    }
}
```

**Step 3: Apply to Controllers**

Update `ProductViewRoute.php` to use rate limiting:

```php
use Learning\Bundle\Core\Api\RateLimiter\SimpleRateLimiter;
use Learning\Bundle\Core\Api\Exception\RateLimitExceededException;

class ProductViewRoute extends AbstractProductViewRoute
{
    private ProductViewService $productViewService;
    private SimpleRateLimiter $rateLimiter;

    public function __construct(
        ProductViewService $productViewService,
        SimpleRateLimiter $rateLimiter
    ) {
        $this->productViewService = $productViewService;
        $this->rateLimiter = $rateLimiter;
    }

    #[Route(path: '/store-api/learning/product-view/{productId}', name: 'store-api.learning.product-view.record', methods: ['POST'])]
    public function record(
        string $productId,
        Request $request,
        SalesChannelContext $context
    ): JsonResponse {
        // Check rate limit
        if (!$this->rateLimiter->check($request)) {
            throw new RateLimitExceededException(60);
        }

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
            'message' => 'Product view recorded successfully',
            'rate_limit' => [
                'remaining' => $this->rateLimiter->getRemainingRequests($request),
            ],
        ]);
    }
}
```

**Step 4: Register Rate Limiter**

Update `services.xml`:

```xml
<!-- Rate Limiter -->
<service id="Learning\Bundle\Core\Api\RateLimiter\SimpleRateLimiter">
    <argument type="service" id="cache.app"/>
    <argument>100</argument> <!-- max requests -->
    <argument>60</argument>  <!-- window in seconds -->
</service>

<!-- Update ProductViewRoute service -->
<service id="Learning\Bundle\Core\Content\ProductView\SalesChannel\ProductViewRoute" public="true">
    <argument type="service" id="Learning\Bundle\Service\ProductViewService"/>
    <argument type="service" id="Learning\Bundle\Core\Api\RateLimiter\SimpleRateLimiter"/>
    <call method="setContainer">
        <argument type="service" id="service_container"/>
    </call>
    <tag name="controller.service_arguments"/>
</service>
```

**Step 5: Test Rate Limiting**

```bash
# Clear cache
php -d memory_limit=512M bin/console cache:clear

# Test by making many requests quickly
for i in {1..105}; do
  curl -X POST "https://localhost:8000/store-api/learning/product-view/${PRODUCT_ID}" \
    -H "sw-access-key: ${SW_ACCESS_KEY}" -k
  echo " - Request $i"
done

# After 100 requests, you should see rate limit errors
```

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

**Problem:** OAuth token request returns empty response or redirects
- **Cause:** Using HTTP instead of HTTPS - Shopware 6.7+ redirects all API requests to HTTPS
- **Solution:** Always use `https://localhost:8000` (not `http://`) with the `-k` flag for development

**Problem:** XML parsing errors when accessing any route (even OAuth)
- **Cause:** Syntax error in `services.xml` file (unclosed tags, extra closing tags, etc.)
- **Error message:** "Opening and ending tag mismatch" or "Extra content at the end of the document"
- **Solution:** 
  1. Check services.xml for XML syntax errors
  2. Common issue: Self-closing tags `/>` followed by an extra `</service>` tag
  3. Validate XML structure - every `<service>` must have exactly one matching `</service>` OR be self-closing `/>`
  4. Clear cache after fixing: `php -d memory_limit=512M bin/console cache:clear`

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
