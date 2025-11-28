# Day 7-10: Final Project - GoTo Webinar Integration

**Duration:** 3-4 days (24-32 hours with breaks)  
**Goal:** Build a complete Shopware App that integrates with GoTo Webinar API to sell webinar tickets

> **Note for Beginners:** This is a real-world third-party integration! Budget 3-4 full days. This project demonstrates how to build a Shopware **App** (not a plugin) that communicates with external APIs. Perfect for your portfolio!

> **💡 Why This Matters:** This is exactly the type of feature businesses need. You'll learn OAuth, API integration, webhooks, error handling, and customer communication - all essential skills for professional Shopware development.

---

## Project Overview

### What You're Building

A **Shopware App** that enables selling webinar tickets through your shop. When customers purchase a webinar ticket:

1. ✅ They complete checkout like any normal product
2. ✅ After payment, their data is automatically sent to GoTo Webinar
3. ✅ They receive a webinar registration with join link
4. ✅ Admin can view registration status and manage webinars
5. ✅ Failed registrations are logged and retryable

**Real Business Value:**
- Turn your Shopware shop into a webinar booking platform
- Automate the entire registration process
- Track sales and attendance in one place
- Provide seamless customer experience

### App vs Plugin

**Why we're building an App (not a Plugin):**

```
┌─────────────────────────────────────────────────────┐
│  SHOPWARE APP                                       │
├─────────────────────────────────────────────────────┤
│  ✅ Perfect for third-party API integrations        │
│  ✅ OAuth built-in (Shopware handles it)            │
│  ✅ Webhook support out of the box                  │
│  ✅ Isolated - doesn't touch Shopware core          │
│  ✅ Can be hosted separately                        │
│  ✅ Marketplace-ready                               │
│  ✅ Easier to maintain and update                   │
└─────────────────────────────────────────────────────┘

vs

┌─────────────────────────────────────────────────────┐
│  SHOPWARE PLUGIN                                    │
├─────────────────────────────────────────────────────┤
│  ❌ Requires core access (unnecessary for this)     │
│  ❌ Manual OAuth implementation needed              │
│  ❌ Tightly coupled to Shopware version             │
│  ❌ More complex for API integrations               │
└─────────────────────────────────────────────────────┘
```

**Apps are the modern way to extend Shopware!**

---

## Part 1: Understanding Shopware Apps (45 minutes)

### Key Concepts

**1. What is a Shopware App?**

A Shopware App is a **standalone application** that communicates with Shopware via:
- **REST API** - To read/write Shopware data
- **Webhooks** - To receive events (order placed, etc.)
- **App System** - Shopware's app management framework

**Think of it like this:**
```
Your Shopware Shop  ←→  Your App  ←→  GoTo Webinar API
     (triggers)      (processes)    (registers customer)
```

**2. App Structure**

```
WebinarIntegration/          # Your app root directory
├── manifest.xml             # App metadata & permissions
├── Resources/
│   ├── config/
│   │   └── config.xml      # Admin settings UI
│   └── views/
│       └── storefront/     # Optional template extensions
└── src/
    ├── Controller/         # HTTP endpoints for webhooks
    ├── Service/            # Business logic
    └── Command/            # CLI commands
```

**3. manifest.xml - The Heart of Your App**

This file tells Shopware:
- What your app is called
- What permissions it needs
- Which webhooks it wants to receive
- OAuth configuration
- Admin UI modules

### Required Reading

📖 **Before coding, read these:**
- [Shopware App Base Guide](https://developer.shopware.com/docs/guides/plugins/apps/app-base-guide.html) (30 min)
- [GoTo Webinar API Overview](https://developer.goto.com/GoToWebinarV2#section/GoTo-Webinar-API-Overview) (15 min)

---

## Part 2: Setup & Prerequisites (60 minutes)

### Step 1: Create GoTo Webinar Developer Account

1. Go to [GoTo Developer Portal](https://developer.goto.com/)
2. Sign up / Sign in
3. Navigate to "My Apps" → "Create New App"
4. Fill in app details:
   - **App Name:** "Shopware Webinar Integration"
   - **Description:** "Integrates Shopware shop with GoTo Webinar"
   - **OAuth Redirect URL:** `https://your-shop.com/api/oauth/callback`

5. Save and note down:
   - **Consumer Key** (Client ID)
   - **Consumer Secret** (Client Secret)

**Important:** Keep these credentials secure!

### Step 2: Create Shopware App Folder Structure

```bash
cd custom/apps
mkdir -p WebinarIntegration/Resources/config
mkdir -p WebinarIntegration/Resources/views/storefront
mkdir -p WebinarIntegration/src/{Controller,Service,Command}
```

### Step 3: Create manifest.xml

Create `custom/apps/WebinarIntegration/manifest.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<manifest xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/shopware/platform/trunk/src/Core/Framework/App/Manifest/Schema/manifest-2.0.xsd">
    
    <!-- Basic App Information -->
    <meta>
        <name>WebinarIntegration</name>
        <label>GoTo Webinar Integration</label>
        <label lang="de-DE">GoTo Webinar Integration</label>
        <description>Sell webinar tickets and automatically register customers via GoTo Webinar API</description>
        <description lang="de-DE">Verkaufen Sie Webinar-Tickets und registrieren Sie Kunden automatisch über die GoTo Webinar API</description>
        <author>Your Company Name</author>
        <copyright>(c) by Your Company Name</copyright>
        <version>1.0.0</version>
        <license>MIT</license>
    </meta>

    <!-- 
        Setup: App registration process
        - Shopware calls this URL to register the app
        - Your app responds with confirmation
    -->
    <setup>
        <registrationUrl>https://your-app-server.com/registration</registrationUrl>
        <secret>your-app-secret-key-change-in-production</secret>
    </setup>

    <!--
        Permissions: What data can the app access?
        This is crucial for security!
    -->
    <permissions>
        <!-- Read orders to get customer data -->
        <read>order</read>
        <read>order_line_item</read>
        <read>order_customer</read>
        
        <!-- Read products to sync webinar details -->
        <read>product</read>
        <update>product</update> <!-- To add webinar custom fields -->
        
        <!-- Send emails to customers -->
        <create>mail_template</create>
        
        <!-- Read/write custom fields -->
        <read>custom_field</read>
        <create>custom_field_set</create>
    </permissions>

    <!--
        Webhooks: Events we want to be notified about
        Shopware will send HTTP POST requests to our app
    -->
    <webhooks>
        <!-- Triggered when order is successfully placed -->
        <webhook name="order-placed" url="https://your-app-server.com/webhooks/order-placed" event="order.placed"/>
        
        <!-- Triggered when payment is completed -->
        <webhook name="order-paid" url="https://your-app-server.com/webhooks/order-paid" event="order_transaction.paid"/>
        
        <!-- Triggered when order is cancelled (for cleanup) -->
        <webhook name="order-cancelled" url="https://your-app-server.com/webhooks/order-cancelled" event="order_transaction.cancelled"/>
    </webhooks>

    <!--
        Admin UI: Add menu items and modules in Shopware Admin
    -->
    <admin>
        <module name="webinar-integration" parent="sw-marketing" source="https://your-app-server.com/admin"/>
        
        <!-- Or use action buttons on existing pages -->
        <action-button action="syncWebinars" entity="product" view="list" url="https://your-app-server.com/actions/sync">
            <label>Sync Webinars from GoTo</label>
        </action-button>
    </admin>
</manifest>
```

**Key Concepts Explained:**

- **`<setup>`** - How Shopware and your app "shake hands" during installation
- **`<permissions>`** - Security model: explicit permissions for data access
- **`<webhooks>`** - Real-time notifications from Shopware to your app
- **`<admin>`** - Adds UI elements to Shopware Admin panel

---

## Part 3: App Registration & OAuth (90 minutes)

### Understanding the Flow

```
1. Admin installs app in Shopware
2. Shopware calls your registrationUrl
3. Your app stores shop credentials
4. Your app confirms registration
5. Admin clicks "Connect GoTo Webinar"
6. OAuth flow starts
7. Store access tokens
8. Ready to use GoTo API!
```

### Step 1: Create Registration Endpoint

Create `custom/apps/WebinarIntegration/src/Controller/RegistrationController.php`:

```php
<?php declare(strict_types=1);

namespace WebinarIntegration\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handles app registration with Shopware
 * 
 * When the app is installed in a Shopware shop, Shopware will call
 * the registrationUrl from manifest.xml. This controller handles that request.
 */
#[Route(defaults: ['_routeScope' => ['api']])]
class RegistrationController
{
    /**
     * Registration endpoint
     * 
     * Shopware sends:
     * - shop-id: Unique identifier for the shop
     * - shop-url: URL of the shop
     * - shop-secret: Secret for verifying requests
     * - timestamp: When the request was made
     * - sw-version: Shopware version
     * 
     * @Route("/registration", name="app.registration", methods={"POST"})
     */
    public function register(Request $request): JsonResponse
    {
        // Step 1: Validate the request comes from Shopware
        $shopId = $request->get('shop-id');
        $shopUrl = $request->get('shop-url');
        $shopSecret = $request->get('shop-secret');
        $timestamp = $request->get('timestamp');
        
        if (!$shopId || !$shopUrl || !$shopSecret) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Missing required parameters'
            ], 400);
        }

        // Step 2: Verify the signature to ensure request authenticity
        $expectedSignature = $this->calculateSignature($request);
        $actualSignature = $request->headers->get('shopware-app-signature');
        
        if ($expectedSignature !== $actualSignature) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Invalid signature'
            ], 401);
        }

        // Step 3: Store shop credentials in your database
        // In production, save to database. For MVP, save to file:
        $this->storeShopCredentials([
            'shop_id' => $shopId,
            'shop_url' => $shopUrl,
            'shop_secret' => $shopSecret,
            'registered_at' => date('Y-m-d H:i:s'),
        ]);

        // Step 4: Generate confirmation token
        $confirmationUrl = $shopUrl . '/api/_action/extension/sw-app-registration/confirm';
        
        // Step 5: Return confirmation
        return new JsonResponse([
            'proof' => hash_hmac(
                'sha256',
                $shopId . $shopUrl . 'WebinarIntegration',
                $shopSecret
            ),
            'secret' => $shopSecret,
            'confirmation_url' => $confirmationUrl
        ]);
    }

    /**
     * Calculate expected signature for request validation
     * 
     * Shopware signs requests with HMAC-SHA256 using the app secret from manifest.xml
     */
    private function calculateSignature(Request $request): string
    {
        // The secret from manifest.xml
        $appSecret = 'your-app-secret-key-change-in-production';
        
        // Construct the message to sign
        $queryString = $request->getQueryString();
        $message = $queryString ?? '';
        
        return hash_hmac('sha256', $message, $appSecret);
    }

    /**
     * Store shop credentials
     * 
     * In production: Save to MySQL/PostgreSQL
     * For MVP: Save to JSON file
     */
    private function storeShopCredentials(array $data): void
    {
        $filePath = __DIR__ . '/../../var/shop_credentials.json';
        $dir = dirname($filePath);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
    }
}
```

### Step 2: Create OAuth Service for GoTo Webinar

Create `custom/apps/WebinarIntegration/src/Service/GoToOAuthService.php`:

```php
<?php declare(strict_types=1);

namespace WebinarIntegration\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Handles OAuth 2.0 flow with GoTo Webinar
 * 
 * OAuth Flow:
 * 1. User clicks "Connect" → Redirect to GoTo authorization page
 * 2. User authorizes → GoTo redirects back with code
 * 3. Exchange code for access token + refresh token
 * 4. Use access token for API calls
 * 5. Refresh token when it expires
 */
class GoToOAuthService
{
    private const AUTHORIZE_URL = 'https://authentication.logmeininc.com/oauth/authorize';
    private const TOKEN_URL = 'https://authentication.logmeininc.com/oauth/token';
    
    private Client $httpClient;
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    
    public function __construct()
    {
        $this->httpClient = new Client();
        
        // Load from configuration (stored during setup)
        $config = $this->loadConfig();
        $this->clientId = $config['client_id'] ?? '';
        $this->clientSecret = $config['client_secret'] ?? '';
        $this->redirectUri = $config['redirect_uri'] ?? '';
    }

    /**
     * Step 1: Get authorization URL
     * 
     * Redirect admin to this URL to start OAuth flow
     * 
     * @return string The URL to redirect user to
     */
    public function getAuthorizationUrl(): string
    {
        $params = http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri,
            // What we want access to
            'scope' => 'openid profile email', // Add GoTo specific scopes
        ]);
        
        return self::AUTHORIZE_URL . '?' . $params;
    }

    /**
     * Step 2: Exchange authorization code for access token
     * 
     * After user authorizes, GoTo redirects back with a code.
     * We exchange this code for actual access tokens.
     * 
     * @param string $authorizationCode The code from redirect
     * @return array Contains access_token, refresh_token, expires_in
     * @throws \RuntimeException If token exchange fails
     */
    public function exchangeCodeForToken(string $authorizationCode): array
    {
        try {
            $response = $this->httpClient->post(self::TOKEN_URL, [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $authorizationCode,
                    'redirect_uri' => $this->redirectUri,
                ],
                'auth' => [$this->clientId, $this->clientSecret], // HTTP Basic Auth
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            // Store tokens for future use
            $this->storeTokens($data);
            
            return $data;
            
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Failed to exchange code for token: ' . $e->getMessage());
        }
    }

    /**
     * Step 3: Refresh access token when it expires
     * 
     * Access tokens expire (usually after 1 hour).
     * Use the refresh token to get a new access token.
     * 
     * @return array New tokens
     * @throws \RuntimeException If refresh fails
     */
    public function refreshAccessToken(): array
    {
        $tokens = $this->loadTokens();
        
        if (!isset($tokens['refresh_token'])) {
            throw new \RuntimeException('No refresh token available');
        }

        try {
            $response = $this->httpClient->post(self::TOKEN_URL, [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $tokens['refresh_token'],
                ],
                'auth' => [$this->clientId, $this->clientSecret],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $this->storeTokens($data);
            
            return $data;
            
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Failed to refresh token: ' . $e->getMessage());
        }
    }

    /**
     * Get valid access token (refreshes if needed)
     * 
     * This is what you call before making API requests.
     * It automatically handles token refresh if needed.
     * 
     * @return string Valid access token
     */
    public function getValidAccessToken(): string
    {
        $tokens = $this->loadTokens();
        
        // Check if token is expired
        if (isset($tokens['expires_at']) && time() >= $tokens['expires_at']) {
            // Token expired, refresh it
            $tokens = $this->refreshAccessToken();
        }
        
        return $tokens['access_token'] ?? '';
    }

    /**
     * Store tokens securely
     * 
     * Production: Use encrypted database storage
     * MVP: Use JSON file
     */
    private function storeTokens(array $tokens): void
    {
        // Calculate expiration timestamp
        if (isset($tokens['expires_in'])) {
            $tokens['expires_at'] = time() + $tokens['expires_in'];
        }
        
        $filePath = __DIR__ . '/../../var/goto_tokens.json';
        file_put_contents($filePath, json_encode($tokens, JSON_PRETTY_PRINT));
    }

    /**
     * Load stored tokens
     */
    private function loadTokens(): array
    {
        $filePath = __DIR__ . '/../../var/goto_tokens.json';
        
        if (!file_exists($filePath)) {
            return [];
        }
        
        return json_decode(file_get_contents($filePath), true) ?? [];
    }

    /**
     * Load app configuration
     */
    private function loadConfig(): array
    {
        // In production, load from database or environment variables
        return [
            'client_id' => getenv('GOTO_CLIENT_ID') ?: 'your-client-id',
            'client_secret' => getenv('GOTO_CLIENT_SECRET') ?: 'your-client-secret',
            'redirect_uri' => getenv('GOTO_REDIRECT_URI') ?: 'https://your-shop.com/api/oauth/callback',
        ];
    }
}
```

---

## Part 4: GoTo Webinar API Client (120 minutes)

### Create API Client Service

Create `custom/apps/WebinarIntegration/src/Service/GoToWebinarClient.php`:

```php
<?php declare(strict_types=1);

namespace WebinarIntegration\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * GoTo Webinar API Client
 * 
 * This class handles all communication with GoTo Webinar API.
 * It wraps the API calls and provides simple methods for:
 * - Listing webinars
 * - Creating registrants
 * - Checking registration status
 * 
 * API Documentation: https://developer.goto.com/GoToWebinarV2
 */
class GoToWebinarClient
{
    private const API_BASE_URL = 'https://api.getgo.com/G2W/rest/v2';
    
    private Client $httpClient;
    private GoToOAuthService $oauthService;
    private LoggerInterface $logger;
    
    public function __construct(
        GoToOAuthService $oauthService,
        LoggerInterface $logger
    ) {
        $this->httpClient = new Client([
            'base_uri' => self::API_BASE_URL,
            'timeout' => 30, // 30 seconds timeout
        ]);
        $this->oauthService = $oauthService;
        $this->logger = $logger;
    }

    /**
     * Get list of all webinars for an organizer
     * 
     * Returns upcoming, past, and recurring webinars.
     * Useful for syncing webinars to Shopware products.
     * 
     * @param string $organizerKey Your GoTo organizer key
     * @return array List of webinars
     * 
     * Example response:
     * [
     *   {
     *     "webinarKey": "1234567890",
     *     "subject": "Blauwasser – Segeln für Anfänger",
     *     "description": "Learn ocean sailing basics",
     *     "times": [{"startTime": "2025-12-15T18:00:00Z", ...}],
     *     "registrationUrl": "https://...",
     *     ...
     *   }
     * ]
     */
    public function getWebinars(string $organizerKey): array
    {
        try {
            $accessToken = $this->oauthService->getValidAccessToken();
            
            $response = $this->httpClient->get(
                "/organizers/{$organizerKey}/webinars",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Accept' => 'application/json',
                    ],
                ]
            );

            $webinars = json_decode($response->getBody()->getContents(), true);
            
            $this->logger->info('Fetched webinars from GoTo', [
                'count' => count($webinars),
                'organizer_key' => $organizerKey,
            ]);
            
            return $webinars;
            
        } catch (GuzzleException $e) {
            $this->logger->error('Failed to fetch webinars', [
                'error' => $e->getMessage(),
                'organizer_key' => $organizerKey,
            ]);
            
            throw new \RuntimeException('Could not fetch webinars: ' . $e->getMessage());
        }
    }

    /**
     * Register a customer for a webinar
     * 
     * This is the core functionality! After a customer purchases a webinar
     * ticket, we call this to register them in GoTo Webinar.
     * 
     * @param string $organizerKey Your organizer key
     * @param string $webinarKey The specific webinar key
     * @param array $registrantData Customer data
     * 
     * $registrantData format:
     * [
     *   'firstName' => 'Max',
     *   'lastName' => 'Mustermann',
     *   'email' => 'max@example.com',
     *   'responses' => [ // Optional custom questions
     *     ['questionKey' => 12345, 'responseText' => 'Answer'],
     *   ]
     * ]
     * 
     * @return array Registration details with joinUrl
     * 
     * Example response:
     * [
     *   'registrantKey' => '5678901234567890123',
     *   'joinUrl' => 'https://attendee.gotowebinar.com/register/123456789',
     *   'confirmationUrl' => 'https://...'
     * ]
     */
    public function createRegistrant(
        string $organizerKey,
        string $webinarKey,
        array $registrantData
    ): array {
        try {
            $accessToken = $this->oauthService->getValidAccessToken();
            
            // Validate required fields
            $this->validateRegistrantData($registrantData);
            
            $response = $this->httpClient->post(
                "/organizers/{$organizerKey}/webinars/{$webinarKey}/registrants",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'json' => $registrantData,
                ]
            );

            $result = json_decode($response->getBody()->getContents(), true);
            
            $this->logger->info('Successfully registered customer for webinar', [
                'webinar_key' => $webinarKey,
                'registrant_key' => $result['registrantKey'] ?? 'unknown',
                'email' => $registrantData['email'],
            ]);
            
            return $result;
            
        } catch (GuzzleException $e) {
            $errorMessage = $this->parseApiError($e);
            
            $this->logger->error('Failed to register customer for webinar', [
                'webinar_key' => $webinarKey,
                'email' => $registrantData['email'] ?? 'unknown',
                'error' => $errorMessage,
            ]);
            
            throw new \RuntimeException($errorMessage);
        }
    }

    /**
     * Get details of a specific registrant
     * 
     * Useful for checking registration status or getting updated join URL.
     * 
     * @param string $organizerKey Your organizer key
     * @param string $webinarKey The webinar key
     * @param string $registrantKey The registrant key (from createRegistrant response)
     * @return array Registrant details
     */
    public function getRegistrant(
        string $organizerKey,
        string $webinarKey,
        string $registrantKey
    ): array {
        try {
            $accessToken = $this->oauthService->getValidAccessToken();
            
            $response = $this->httpClient->get(
                "/organizers/{$organizerKey}/webinars/{$webinarKey}/registrants/{$registrantKey}",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Accept' => 'application/json',
                    ],
                ]
            );

            return json_decode($response->getBody()->getContents(), true);
            
        } catch (GuzzleException $e) {
            $this->logger->error('Failed to fetch registrant details', [
                'webinar_key' => $webinarKey,
                'registrant_key' => $registrantKey,
                'error' => $e->getMessage(),
            ]);
            
            throw new \RuntimeException('Could not fetch registrant: ' . $e->getMessage());
        }
    }

    /**
     * Cancel/delete a registration
     * 
     * Called when an order is cancelled or refunded.
     * 
     * @param string $organizerKey Your organizer key
     * @param string $webinarKey The webinar key
     * @param string $registrantKey The registrant to cancel
     */
    public function deleteRegistrant(
        string $organizerKey,
        string $webinarKey,
        string $registrantKey
    ): void {
        try {
            $accessToken = $this->oauthService->getValidAccessToken();
            
            $this->httpClient->delete(
                "/organizers/{$organizerKey}/webinars/{$webinarKey}/registrants/{$registrantKey}",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                    ],
                ]
            );
            
            $this->logger->info('Successfully cancelled webinar registration', [
                'webinar_key' => $webinarKey,
                'registrant_key' => $registrantKey,
            ]);
            
        } catch (GuzzleException $e) {
            $this->logger->error('Failed to cancel registration', [
                'webinar_key' => $webinarKey,
                'registrant_key' => $registrantKey,
                'error' => $e->getMessage(),
            ]);
            
            // Don't throw - cancellation failure shouldn't block order cancellation
        }
    }

    /**
     * Validate registrant data before sending to API
     * 
     * @throws \InvalidArgumentException If data is invalid
     */
    private function validateRegistrantData(array $data): void
    {
        $required = ['firstName', 'lastName', 'email'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }
        
        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }
    }

    /**
     * Parse API error response into user-friendly message
     */
    private function parseApiError(GuzzleException $e): string
    {
        try {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $body = json_decode($response->getBody()->getContents(), true);
                
                // GoTo API returns errors in specific format
                if (isset($body['description'])) {
                    return $body['description'];
                }
                
                if (isset($body['errorCode'])) {
                    return "API Error ({$body['errorCode']}): " . ($body['msg'] ?? 'Unknown error');
                }
            }
        } catch (\Throwable $parseError) {
            // If we can't parse the error, fall through to generic message
        }
        
        return $e->getMessage();
    }
}
```

---

## Part 5: Webhook Handler - The Magic Happens Here! (120 minutes)

This is where the automatic registration happens when a customer places an order.

### Create Webhook Handler

Create `custom/apps/WebinarIntegration/src/Controller/WebhookController.php`:

```php
<?php declare(strict_types=1);

namespace WebinarIntegration\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use WebinarIntegration\Service\GoToWebinarClient;
use WebinarIntegration\Service\WebinarRegistrationService;

/**
 * Webhook Controller
 * 
 * Receives webhooks from Shopware when events occur.
 * Most important: order.placed and order_transaction.paid
 * 
 * When a customer completes an order with a webinar product,
 * this controller receives the webhook and triggers registration.
 */
#[Route(defaults: ['_routeScope' => ['api']])]
class WebhookController
{
    private WebinarRegistrationService $registrationService;
    private LoggerInterface $logger;
    
    public function __construct(
        WebinarRegistrationService $registrationService,
        LoggerInterface $logger
    ) {
        $this->registrationService = $registrationService;
        $this->logger = $logger;
    }

    /**
     * Handle order placed webhook
     * 
     * Triggered when customer completes checkout (before payment).
     * We'll actually register on payment confirmation for safety.
     * 
     * @Route("/webhooks/order-placed", name="app.webhook.order_placed", methods={"POST"})
     */
    public function handleOrderPlaced(Request $request): JsonResponse
    {
        try {
            // Step 1: Validate webhook came from Shopware
            $this->validateWebhook($request);
            
            // Step 2: Parse webhook payload
            $payload = json_decode($request->getContent(), true);
            
            $this->logger->info('Received order.placed webhook', [
                'order_id' => $payload['data']['payload']['id'] ?? 'unknown',
            ]);
            
            // Log but don't process yet - wait for payment confirmation
            return new JsonResponse(['success' => true, 'message' => 'Webhook received']);
            
        } catch (\Throwable $e) {
            $this->logger->error('Error handling order.placed webhook', [
                'error' => $e->getMessage(),
            ]);
            
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle order paid webhook - THE MAIN EVENT!
     * 
     * This is where we actually register customers for webinars.
     * Only process after payment is confirmed to avoid fake registrations.
     * 
     * @Route("/webhooks/order-paid", name="app.webhook.order_paid", methods={"POST"})
     */
    public function handleOrderPaid(Request $request): JsonResponse
    {
        try {
            // Step 1: Validate webhook
            $this->validateWebhook($request);
            
            // Step 2: Parse payload
            $payload = json_decode($request->getContent(), true);
            $orderId = $payload['data']['payload']['orderId'] ?? null;
            
            if (!$orderId) {
                throw new \RuntimeException('No order ID in webhook payload');
            }
            
            $this->logger->info('Processing order payment for webinar registration', [
                'order_id' => $orderId,
            ]);
            
            // Step 3: Process the order and register for webinars
            $result = $this->registrationService->processOrder($orderId, $payload);
            
            $this->logger->info('Webinar registration processing complete', [
                'order_id' => $orderId,
                'registrations_created' => $result['success_count'],
                'failures' => $result['error_count'],
            ]);
            
            return new JsonResponse([
                'success' => true,
                'result' => $result,
            ]);
            
        } catch (\Throwable $e) {
            $this->logger->error('Error handling order.paid webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle order cancelled webhook
     * 
     * When an order is cancelled, we should unregister the customer
     * from the webinar (if possible).
     * 
     * @Route("/webhooks/order-cancelled", name="app.webhook.order_cancelled", methods={"POST"})
     */
    public function handleOrderCancelled(Request $request): JsonResponse
    {
        try {
            $this->validateWebhook($request);
            
            $payload = json_decode($request->getContent(), true);
            $orderId = $payload['data']['payload']['orderId'] ?? null;
            
            if (!$orderId) {
                return new JsonResponse(['success' => true, 'message' => 'No order ID']);
            }
            
            $this->logger->info('Cancelling webinar registrations for order', [
                'order_id' => $orderId,
            ]);
            
            // Cancel registrations
            $this->registrationService->cancelRegistrations($orderId);
            
            return new JsonResponse(['success' => true]);
            
        } catch (\Throwable $e) {
            $this->logger->error('Error handling order.cancelled webhook', [
                'error' => $e->getMessage(),
            ]);
            
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Validate webhook request
     * 
     * Security: Ensure webhook actually came from Shopware
     * Shopware signs webhooks with HMAC signature
     * 
     * @throws \RuntimeException If validation fails
     */
    private function validateWebhook(Request $request): void
    {
        // Get signature from header
        $signature = $request->headers->get('shopware-shop-signature');
        
        if (!$signature) {
            throw new \RuntimeException('Missing webhook signature');
        }
        
        // Calculate expected signature
        $shopSecret = $this->getShopSecret();
        $expectedSignature = hash_hmac('sha256', $request->getContent(), $shopSecret);
        
        if (!hash_equals($expectedSignature, $signature)) {
            throw new \RuntimeException('Invalid webhook signature');
        }
    }

    /**
     * Get shop secret for signature validation
     * 
     * This was stored during app registration
     */
    private function getShopSecret(): string
    {
        $credentials = json_decode(
            file_get_contents(__DIR__ . '/../../var/shop_credentials.json'),
            true
        );
        
        return $credentials['shop_secret'] ?? '';
    }
}
```

### Create Registration Service

Create `custom/apps/WebinarIntegration/src/Service/WebinarRegistrationService.php`:

```php
<?php declare(strict_types=1);

namespace WebinarIntegration\Service;

use Psr\Log\LoggerInterface;

/**
 * Webinar Registration Service
 * 
 * Orchestrates the registration process:
 * 1. Check which order items are webinars
 * 2. Extract customer data
 * 3. Call GoTo API to register
 * 4. Store registration details
 * 5. Send confirmation email
 * 
 * This is the "business logic" layer that coordinates everything.
 */
class WebinarRegistrationService
{
    private GoToWebinarClient $webinarClient;
    private ShopwareApiClient $shopwareClient;
    private EmailService $emailService;
    private LoggerInterface $logger;
    
    public function __construct(
        GoToWebinarClient $webinarClient,
        ShopwareApiClient $shopwareClient,
        EmailService $emailService,
        LoggerInterface $logger
    ) {
        $this->webinarClient = $webinarClient;
        $this->shopwareClient = $shopwareClient;
        $this->emailService = $emailService;
        $this->logger = $logger;
    }

    /**
     * Process an order for webinar registrations
     * 
     * Main workflow:
     * 1. Fetch full order details from Shopware
     * 2. Find webinar products in order
     * 3. Register customer for each webinar
     * 4. Update order with registration details
     * 5. Send confirmation email
     * 
     * @param string $orderId Shopware order ID
     * @param array $webhookPayload Original webhook data
     * @return array Processing result with counts
     */
    public function processOrder(string $orderId, array $webhookPayload): array
    {
        $result = [
            'order_id' => $orderId,
            'registrations' => [],
            'success_count' => 0,
            'error_count' => 0,
            'errors' => [],
        ];
        
        try {
            // Step 1: Fetch complete order data from Shopware
            $order = $this->shopwareClient->getOrder($orderId);
            
            if (!$order) {
                throw new \RuntimeException('Order not found: ' . $orderId);
            }
            
            // Step 2: Get customer details
            $customer = $this->extractCustomerData($order);
            
            // Step 3: Find webinar products in order
            $webinarItems = $this->findWebinarItems($order);
            
            if (empty($webinarItems)) {
                $this->logger->info('No webinar products in order', ['order_id' => $orderId]);
                return $result;
            }
            
            $this->logger->info('Found webinar items in order', [
                'order_id' => $orderId,
                'webinar_count' => count($webinarItems),
            ]);
            
            // Step 4: Register for each webinar
            foreach ($webinarItems as $item) {
                try {
                    $registration = $this->registerForWebinar($item, $customer);
                    
                    // Store registration details on order line item
                    $this->shopwareClient->updateOrderLineItem(
                        $item['id'],
                        [
                            'customFields' => [
                                'webinar_registration' => [
                                    'registrantKey' => $registration['registrantKey'],
                                    'joinUrl' => $registration['joinUrl'],
                                    'registrationStatus' => 'confirmed',
                                    'registeredAt' => date('c'),
                                ],
                            ],
                        ]
                    );
                    
                    $result['registrations'][] = $registration;
                    $result['success_count']++;
                    
                } catch (\Throwable $e) {
                    $this->logger->error('Failed to register for webinar', [
                        'item_id' => $item['id'],
                        'product_id' => $item['productId'],
                        'error' => $e->getMessage(),
                    ]);
                    
                    // Store error on order line item for admin to see
                    $this->shopwareClient->updateOrderLineItem(
                        $item['id'],
                        [
                            'customFields' => [
                                'webinar_registration' => [
                                    'registrationStatus' => 'failed',
                                    'error' => $e->getMessage(),
                                    'failedAt' => date('c'),
                                ],
                            ],
                        ]
                    );
                    
                    $result['errors'][] = [
                        'item_id' => $item['id'],
                        'error' => $e->getMessage(),
                    ];
                    $result['error_count']++;
                }
            }
            
            // Step 5: Send confirmation email with all registrations
            if ($result['success_count'] > 0) {
                $this->emailService->sendWebinarConfirmation(
                    $customer,
                    $result['registrations']
                );
            }
            
        } catch (\Throwable $e) {
            $this->logger->error('Error processing order for webinars', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            
            $result['errors'][] = ['general' => $e->getMessage()];
        }
        
        return $result;
    }

    /**
     * Register customer for a specific webinar
     * 
     * @param array $orderLineItem The webinar product from order
     * @param array $customerData Customer information
     * @return array Registration result with joinUrl
     */
    private function registerForWebinar(array $orderLineItem, array $customerData): array
    {
        // Get webinar details from product custom fields
        $product = $this->shopwareClient->getProduct($orderLineItem['productId']);
        $webinarDetails = $product['customFields']['webinar_details'] ?? null;
        
        if (!$webinarDetails) {
            throw new \RuntimeException('Product is not properly configured as webinar');
        }
        
        $organizerKey = $webinarDetails['organizerKey'];
        $webinarKey = $webinarDetails['webinarKey'];
        
        // Prepare registrant data
        $registrantData = [
            'firstName' => $customerData['firstName'],
            'lastName' => $customerData['lastName'],
            'email' => $customerData['email'],
            // Optional: Add custom question responses
            'responses' => [],
        ];
        
        // Call GoTo API
        $registration = $this->webinarClient->createRegistrant(
            $organizerKey,
            $webinarKey,
            $registrantData
        );
        
        // Add context for email
        $registration['webinarTitle'] = $webinarDetails['title'] ?? 'Webinar';
        $registration['webinarStartTime'] = $webinarDetails['startTime'] ?? null;
        
        return $registration;
    }

    /**
     * Extract customer data from order
     */
    private function extractCustomerData(array $order): array
    {
        $orderCustomer = $order['orderCustomer'] ?? [];
        $billingAddress = $order['billingAddress'] ?? [];
        
        return [
            'firstName' => $billingAddress['firstName'] ?? '',
            'lastName' => $billingAddress['lastName'] ?? '',
            'email' => $orderCustomer['email'] ?? '',
            'customerId' => $orderCustomer['customerId'] ?? null,
        ];
    }

    /**
     * Find webinar products in order
     * 
     * Checks if product has webinar_details custom field
     */
    private function findWebinarItems(array $order): array
    {
        $webinarItems = [];
        
        foreach ($order['lineItems'] ?? [] as $item) {
            // Check if this is a webinar product
            $product = $this->shopwareClient->getProduct($item['productId']);
            
            if (isset($product['customFields']['webinar_details'])) {
                $webinarItems[] = $item;
            }
        }
        
        return $webinarItems;
    }

    /**
     * Cancel registrations for an order
     * 
     * Called when order is cancelled or refunded
     */
    public function cancelRegistrations(string $orderId): void
    {
        try {
            $order = $this->shopwareClient->getOrder($orderId);
            
            foreach ($order['lineItems'] ?? [] as $item) {
                $registration = $item['customFields']['webinar_registration'] ?? null;
                
                if (!$registration || $registration['registrationStatus'] !== 'confirmed') {
                    continue;
                }
                
                // Get webinar details
                $product = $this->shopwareClient->getProduct($item['productId']);
                $webinarDetails = $product['customFields']['webinar_details'] ?? null;
                
                if (!$webinarDetails) {
                    continue;
                }
                
                // Cancel in GoTo
                $this->webinarClient->deleteRegistrant(
                    $webinarDetails['organizerKey'],
                    $webinarDetails['webinarKey'],
                    $registration['registrantKey']
                );
                
                // Update status
                $this->shopwareClient->updateOrderLineItem(
                    $item['id'],
                    [
                        'customFields' => [
                            'webinar_registration' => array_merge(
                                $registration,
                                [
                                    'registrationStatus' => 'cancelled',
                                    'cancelledAt' => date('c'),
                                ]
                            ),
                        ],
                    ]
                );
            }
            
        } catch (\Throwable $e) {
            $this->logger->error('Error cancelling registrations', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

---

## Part 6: Shopware API Client (90 minutes)

Now we need a way to communicate back with Shopware - to fetch order data, update line items, etc.

### Create Shopware API Client

Create `custom/apps/WebinarIntegration/src/Service/ShopwareApiClient.php`:

```php
<?php declare(strict_types=1);

namespace WebinarIntegration\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Shopware API Client
 * 
 * This client communicates with your Shopware shop's API.
 * During app registration, Shopware gives us:
 * - API credentials
 * - Shop URL
 * - Secret for authentication
 * 
 * We use these to read orders, update products, etc.
 */
class ShopwareApiClient
{
    private Client $httpClient;
    private string $shopUrl;
    private string $apiToken;
    private LoggerInterface $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        
        // Load shop credentials from registration
        $credentials = $this->loadShopCredentials();
        $this->shopUrl = $credentials['shop_url'];
        $this->apiToken = $credentials['api_token'] ?? '';
        
        $this->httpClient = new Client([
            'base_uri' => $this->shopUrl,
            'timeout' => 30,
        ]);
    }

    /**
     * Get complete order data including line items and customer
     * 
     * @param string $orderId Shopware order ID
     * @return array Complete order data
     * @throws \RuntimeException If order not found
     */
    public function getOrder(string $orderId): array
    {
        try {
            $response = $this->httpClient->post(
                '/api/search/order',
                [
                    'headers' => $this->getHeaders(),
                    'json' => [
                        'ids' => [$orderId],
                        'associations' => [
                            'lineItems' => [], // Include line items
                            'orderCustomer' => [], // Include customer
                            'billingAddress' => [], // Include billing address
                            'transactions' => [], // Include payment info
                        ],
                    ],
                ]
            );

            $result = json_decode($response->getBody()->getContents(), true);
            
            if (empty($result['data'])) {
                throw new \RuntimeException('Order not found: ' . $orderId);
            }
            
            return $result['data'][0]; // Return first (and only) order
            
        } catch (GuzzleException $e) {
            $this->logger->error('Failed to fetch order from Shopware', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            
            throw new \RuntimeException('Could not fetch order: ' . $e->getMessage());
        }
    }

    /**
     * Get product data including custom fields
     * 
     * @param string $productId Shopware product ID
     * @return array Product data
     */
    public function getProduct(string $productId): array
    {
        try {
            $response = $this->httpClient->post(
                '/api/search/product',
                [
                    'headers' => $this->getHeaders(),
                    'json' => [
                        'ids' => [$productId],
                        'associations' => [
                            'customFields' => [], // Include custom fields
                        ],
                    ],
                ]
            );

            $result = json_decode($response->getBody()->getContents(), true);
            
            return $result['data'][0] ?? [];
            
        } catch (GuzzleException $e) {
            $this->logger->error('Failed to fetch product', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }

    /**
     * Update order line item with custom fields
     * 
     * Used to store webinar registration details on the order.
     * 
     * @param string $lineItemId Order line item ID
     * @param array $data Data to update
     */
    public function updateOrderLineItem(string $lineItemId, array $data): void
    {
        try {
            $this->httpClient->patch(
                "/api/order-line-item/{$lineItemId}",
                [
                    'headers' => $this->getHeaders(),
                    'json' => $data,
                ]
            );
            
            $this->logger->info('Updated order line item', [
                'line_item_id' => $lineItemId,
            ]);
            
        } catch (GuzzleException $e) {
            $this->logger->error('Failed to update order line item', [
                'line_item_id' => $lineItemId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create or update a product
     * 
     * Used when syncing webinars from GoTo to Shopware.
     * 
     * @param array $productData Product data
     * @return string Product ID
     */
    public function upsertProduct(array $productData): string
    {
        try {
            $response = $this->httpClient->post(
                '/api/_action/sync',
                [
                    'headers' => $this->getHeaders(),
                    'json' => [
                        'write-product' => [
                            'entity' => 'product',
                            'action' => 'upsert',
                            'payload' => [$productData],
                        ],
                    ],
                ]
            );

            $result = json_decode($response->getBody()->getContents(), true);
            
            $this->logger->info('Product upserted successfully', [
                'product_number' => $productData['productNumber'] ?? 'unknown',
            ]);
            
            return $productData['id'];
            
        } catch (GuzzleException $e) {
            $this->logger->error('Failed to upsert product', [
                'error' => $e->getMessage(),
            ]);
            
            throw new \RuntimeException('Could not upsert product: ' . $e->getMessage());
        }
    }

    /**
     * Get request headers for API authentication
     * 
     * Shopware API uses bearer token authentication
     */
    private function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Load shop credentials from storage
     */
    private function loadShopCredentials(): array
    {
        $filePath = __DIR__ . '/../../var/shop_credentials.json';
        
        if (!file_exists($filePath)) {
            throw new \RuntimeException('Shop credentials not found. App not registered?');
        }
        
        return json_decode(file_get_contents($filePath), true);
    }
}
```

---

## Part 7: Email Service (60 minutes)

Customers need to receive their webinar join links! Let's create a beautiful email.

### Create Email Service

Create `custom/apps/WebinarIntegration/src/Service/EmailService.php`:

```php
<?php declare(strict_types=1);

namespace WebinarIntegration\Service;

use Psr\Log\LoggerInterface;

/**
 * Email Service
 * 
 * Sends confirmation emails to customers with webinar details.
 * Includes join URLs and calendar invites.
 * 
 * In production, you might use:
 * - Shopware's mail system
 * - SendGrid
 * - Mailchimp
 * - Custom SMTP
 */
class EmailService
{
    private ShopwareApiClient $shopwareClient;
    private LoggerInterface $logger;
    
    public function __construct(
        ShopwareApiClient $shopwareClient,
        LoggerInterface $logger
    ) {
        $this->shopwareClient = $shopwareClient;
        $this->logger = $logger;
    }

    /**
     * Send webinar confirmation email to customer
     * 
     * @param array $customerData Customer info (email, name)
     * @param array $registrations List of webinar registrations
     */
    public function sendWebinarConfirmation(array $customerData, array $registrations): void
    {
        try {
            $emailContent = $this->buildEmailContent($customerData, $registrations);
            
            // Method 1: Use Shopware's mail system (recommended)
            $this->sendViaShopware($customerData, $emailContent);
            
            // Method 2: Use external service (alternative)
            // $this->sendViaExternal($customerData, $emailContent);
            
            $this->logger->info('Webinar confirmation email sent', [
                'recipient' => $customerData['email'],
                'webinar_count' => count($registrations),
            ]);
            
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send confirmation email', [
                'recipient' => $customerData['email'],
                'error' => $e->getMessage(),
            ]);
            
            // Don't throw - email failure shouldn't block registration
        }
    }

    /**
     * Build email HTML content
     * 
     * Creates a nice-looking email with all webinar details
     */
    private function buildEmailContent(array $customerData, array $registrations): string
    {
        $webinarList = '';
        foreach ($registrations as $registration) {
            $title = htmlspecialchars($registration['webinarTitle']);
            $joinUrl = htmlspecialchars($registration['joinUrl']);
            $startTime = $registration['webinarStartTime'] 
                ? date('l, F j, Y \a\t g:i A', strtotime($registration['webinarStartTime']))
                : 'TBA';
            
            $webinarList .= <<<HTML
                <div style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <h3 style="margin: 0 0 10px 0; color: #333;">{$title}</h3>
                    <p style="margin: 5px 0; color: #666;">
                        <strong>Date & Time:</strong> {$startTime}
                    </p>
                    <p style="margin: 15px 0;">
                        <a href="{$joinUrl}" 
                           style="display: inline-block; padding: 12px 24px; 
                                  background: #007bff; color: white; text-decoration: none; 
                                  border-radius: 4px; font-weight: bold;">
                            Join Webinar
                        </a>
                    </p>
                    <p style="margin: 5px 0; font-size: 12px; color: #999;">
                        Link: <a href="{$joinUrl}">{$joinUrl}</a>
                    </p>
                </div>
HTML;
        }
        
        $firstName = htmlspecialchars($customerData['firstName']);
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Webinar Registration Confirmation</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #007bff; margin-bottom: 10px;">🎉 Registration Confirmed!</h1>
        <p style="font-size: 18px; color: #666;">You're all set for your webinar(s)</p>
    </div>
    
    <p>Hi {$firstName},</p>
    
    <p>Thank you for registering! We're excited to have you join us. Here are your webinar details:</p>
    
    {$webinarList}
    
    <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107;">
        <h4 style="margin: 0 0 10px 0; color: #856404;">📅 Save the Date</h4>
        <p style="margin: 0; color: #856404;">
            We recommend adding these events to your calendar. You'll receive reminder emails before each webinar.
        </p>
    </div>
    
    <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
        <h4 style="margin: 0 0 10px 0;">Need Help?</h4>
        <p style="margin: 0;">
            If you have any questions or need technical support, please contact us at 
            <a href="mailto:support@example.com">support@example.com</a>
        </p>
    </div>
    
    <div style="margin-top: 40px; text-align: center; font-size: 12px; color: #999;">
        <p>This email was sent because you purchased a webinar ticket on our shop.</p>
        <p>© 2025 Your Company. All rights reserved.</p>
    </div>
    
</body>
</html>
HTML;
    }

    /**
     * Send email via Shopware's mail system
     * 
     * Uses Shopware's built-in email infrastructure
     */
    private function sendViaShopware(array $customerData, string $content): void
    {
        // In production, you would:
        // 1. Create a mail template in Shopware Admin
        // 2. Trigger it via API
        // 3. Or use Shopware's MailService directly
        
        // For now, we'll log it
        // TODO: Implement actual Shopware mail sending
        $this->logger->info('Email would be sent via Shopware', [
            'recipient' => $customerData['email'],
            'content_length' => strlen($content),
        ]);
    }

    /**
     * Send email via external service (e.g., SendGrid)
     * 
     * Alternative method using third-party email service
     */
    private function sendViaExternal(array $customerData, string $content): void
    {
        // Example with SendGrid API
        // $apiKey = getenv('SENDGRID_API_KEY');
        // 
        // $client = new \GuzzleHttp\Client();
        // $client->post('https://api.sendgrid.com/v3/mail/send', [
        //     'headers' => [
        //         'Authorization' => 'Bearer ' . $apiKey,
        //         'Content-Type' => 'application/json',
        //     ],
        //     'json' => [
        //         'personalizations' => [
        //             ['to' => [['email' => $customerData['email']]]]
        //         ],
        //         'from' => ['email' => 'noreply@yourshop.com'],
        //         'subject' => 'Your Webinar Registration Confirmation',
        //         'content' => [['type' => 'text/html', 'value' => $content]]
        //     ]
        // ]);
    }
}
```

---

## Part 8: CLI Commands for Testing (75 minutes)

Let's create console commands for testing and manual operations.

### Command 1: Test Connection

Create `custom/apps/WebinarIntegration/src/Command/TestConnectionCommand.php`:

```php
<?php declare(strict_types=1);

namespace WebinarIntegration\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WebinarIntegration\Service\GoToWebinarClient;
use WebinarIntegration\Service\GoToOAuthService;

/**
 * Test GoTo Webinar connection
 * 
 * Usage: bin/console webinar:test-connection
 */
class TestConnectionCommand extends Command
{
    protected static $defaultName = 'webinar:test-connection';
    
    private GoToOAuthService $oauthService;
    private GoToWebinarClient $webinarClient;
    
    public function __construct(
        GoToOAuthService $oauthService,
        GoToWebinarClient $webinarClient
    ) {
        parent::__construct();
        $this->oauthService = $oauthService;
        $this->webinarClient = $webinarClient;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Test connection to GoTo Webinar API')
            ->setHelp('Verifies OAuth tokens are valid and API is accessible');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('GoTo Webinar Connection Test');
        
        try {
            // Test 1: Check if we have tokens
            $io->section('Step 1: Checking OAuth tokens');
            $accessToken = $this->oauthService->getValidAccessToken();
            
            if (empty($accessToken)) {
                $io->error('No access token found. Please connect your GoTo Webinar account first.');
                return Command::FAILURE;
            }
            
            $io->success('✓ Access token found');
            
            // Test 2: Try to fetch webinars
            $io->section('Step 2: Testing API connection');
            
            // You'll need to provide your organizer key
            $io->note('Note: You need to configure your organizer key in the app settings');
            $organizerKey = getenv('GOTO_ORGANIZER_KEY') ?: 'CONFIGURE_ME';
            
            if ($organizerKey === 'CONFIGURE_ME') {
                $io->warning('Organizer key not configured. Set GOTO_ORGANIZER_KEY environment variable.');
                return Command::FAILURE;
            }
            
            $webinars = $this->webinarClient->getWebinars($organizerKey);
            
            $io->success(sprintf('✓ Successfully fetched %d webinars', count($webinars)));
            
            // Display webinar list
            if (!empty($webinars)) {
                $io->section('Available Webinars:');
                
                $rows = [];
                foreach (array_slice($webinars, 0, 5) as $webinar) {
                    $rows[] = [
                        $webinar['webinarKey'],
                        $webinar['subject'],
                        $webinar['times'][0]['startTime'] ?? 'N/A',
                    ];
                }
                
                $io->table(['Webinar Key', 'Subject', 'Start Time'], $rows);
                
                if (count($webinars) > 5) {
                    $io->note(sprintf('Showing 5 of %d webinars', count($webinars)));
                }
            }
            
            $io->success('Connection test passed! Your integration is working correctly.');
            
            return Command::SUCCESS;
            
        } catch (\Throwable $e) {
            $io->error('Connection test failed: ' . $e->getMessage());
            $io->note('Trace: ' . $e->getTraceAsString());
            
            return Command::FAILURE;
        }
    }
}
```

### Command 2: Sync Webinars

Create `custom/apps/WebinarIntegration/src/Command/SyncWebinarsCommand.php`:

```php
<?php declare(strict_types=1);

namespace WebinarIntegration\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WebinarIntegration\Service\GoToWebinarClient;
use WebinarIntegration\Service\ShopwareApiClient;

/**
 * Sync webinars from GoTo to Shopware products
 * 
 * This command:
 * 1. Fetches all webinars from GoTo Webinar
 * 2. Creates/updates products in Shopware
 * 3. Stores webinar details in product custom fields
 * 
 * Usage: bin/console webinar:sync --organizer-key=YOUR_KEY
 */
class SyncWebinarsCommand extends Command
{
    protected static $defaultName = 'webinar:sync';
    
    private GoToWebinarClient $webinarClient;
    private ShopwareApiClient $shopwareClient;
    
    public function __construct(
        GoToWebinarClient $webinarClient,
        ShopwareApiClient $shopwareClient
    ) {
        parent::__construct();
        $this->webinarClient = $webinarClient;
        $this->shopwareClient = $shopwareClient;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Sync webinars from GoTo Webinar to Shopware products')
            ->addOption(
                'organizer-key',
                'o',
                InputOption::VALUE_REQUIRED,
                'Your GoTo Webinar organizer key'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Show what would be synced without actually creating products'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $organizerKey = $input->getOption('organizer-key') ?: getenv('GOTO_ORGANIZER_KEY');
        $dryRun = $input->getOption('dry-run');
        
        if (!$organizerKey) {
            $io->error('Organizer key required. Use --organizer-key option or set GOTO_ORGANIZER_KEY env var.');
            return Command::FAILURE;
        }
        
        $io->title('Syncing Webinars from GoTo Webinar');
        
        if ($dryRun) {
            $io->warning('DRY RUN MODE - No products will be created');
        }
        
        try {
            // Fetch webinars from GoTo
            $io->section('Fetching webinars from GoTo Webinar...');
            $webinars = $this->webinarClient->getWebinars($organizerKey);
            
            $io->success(sprintf('Found %d webinars', count($webinars)));
            
            if (empty($webinars)) {
                $io->note('No webinars to sync');
                return Command::SUCCESS;
            }
            
            // Process each webinar
            $io->section('Processing webinars...');
            $io->progressStart(count($webinars));
            
            $created = 0;
            $updated = 0;
            $errors = 0;
            
            foreach ($webinars as $webinar) {
                try {
                    $productData = $this->convertWebinarToProduct($webinar, $organizerKey);
                    
                    if (!$dryRun) {
                        $this->shopwareClient->upsertProduct($productData);
                        $created++;
                    } else {
                        $io->writeln('');
                        $io->writeln(sprintf(
                            'Would create/update: %s (Key: %s)',
                            $webinar['subject'],
                            $webinar['webinarKey']
                        ));
                    }
                    
                } catch (\Throwable $e) {
                    $errors++;
                    $io->error(sprintf(
                        'Failed to sync webinar %s: %s',
                        $webinar['subject'],
                        $e->getMessage()
                    ));
                }
                
                $io->progressAdvance();
            }
            
            $io->progressFinish();
            
            // Summary
            $io->section('Sync Summary');
            $io->table(
                ['Metric', 'Count'],
                [
                    ['Webinars found', count($webinars)],
                    ['Products created/updated', $created],
                    ['Errors', $errors],
                ]
            );
            
            if ($errors === 0) {
                $io->success('Sync completed successfully!');
                return Command::SUCCESS;
            } else {
                $io->warning(sprintf('Sync completed with %d errors', $errors));
                return Command::FAILURE;
            }
            
        } catch (\Throwable $e) {
            $io->error('Sync failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Convert GoTo webinar data to Shopware product format
     * 
     * This is where you map webinar fields to product fields
     */
    private function convertWebinarToProduct(array $webinar, string $organizerKey): array
    {
        $webinarKey = $webinar['webinarKey'];
        $productNumber = 'WEBINAR-' . $webinarKey;
        
        // Get first session time (for single-session webinars)
        $startTime = $webinar['times'][0]['startTime'] ?? null;
        $endTime = $webinar['times'][0]['endTime'] ?? null;
        
        return [
            'id' => $this->generateProductId($webinarKey), // Consistent ID for upserts
            'productNumber' => $productNumber,
            'name' => $webinar['subject'],
            'description' => $webinar['description'] ?? '',
            'active' => true,
            'taxId' => $this->getDefaultTaxId(), // You'll need to provide this
            'price' => [
                [
                    'currencyId' => $this->getDefaultCurrencyId(), // You'll need this too
                    'gross' => 99.00, // Default price - configure as needed
                    'net' => 83.19,
                    'linked' => true,
                ]
            ],
            'stock' => 999, // Virtual product - high stock
            'customFields' => [
                'webinar_details' => [
                    'webinarKey' => $webinarKey,
                    'organizerKey' => $organizerKey,
                    'webinarType' => $webinar['type'] ?? 'single_session',
                    'startTime' => $startTime,
                    'endTime' => $endTime,
                    'timezone' => $webinar['timezone'] ?? 'UTC',
                    'registrationUrl' => $webinar['registrationUrl'] ?? '',
                    'syncedAt' => date('c'),
                ],
            ],
        ];
    }

    /**
     * Generate consistent product ID from webinar key
     * 
     * This ensures we update the same product each sync
     */
    private function generateProductId(string $webinarKey): string
    {
        // Create deterministic UUID from webinar key
        return substr(md5('webinar-' . $webinarKey), 0, 32);
    }

    /**
     * Get default tax ID
     * 
     * In production, fetch from Shopware or configure
     */
    private function getDefaultTaxId(): string
    {
        // TODO: Fetch actual default tax ID from Shopware
        return '7a4eb5f7c3cd40b7b4de36f573b4e0c2'; // Example - replace with real ID
    }

    /**
     * Get default currency ID
     * 
     * In production, fetch from Shopware or configure
     */
    private function getDefaultCurrencyId(): string
    {
        // TODO: Fetch actual currency ID from Shopware
        return 'b7d2554b0ce847cd82f3ac9bd1c0dfca'; // Example - replace with real ID
    }
}
```

### Command 3: Manual Registration

Create `custom/apps/WebinarIntegration/src/Command/RegisterCustomerCommand.php`:

```php
<?php declare(strict_types=1);

namespace WebinarIntegration\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use WebinarIntegration\Service\GoToWebinarClient;

/**
 * Manually register a customer for testing
 * 
 * Usage: 
 * bin/console webinar:register ORG_KEY WEBINAR_KEY \
 *   --first-name="Max" --last-name="Mustermann" --email="max@example.com"
 */
class RegisterCustomerCommand extends Command
{
    protected static $defaultName = 'webinar:register';
    
    private GoToWebinarClient $webinarClient;
    
    public function __construct(GoToWebinarClient $webinarClient)
    {
        parent::__construct();
        $this->webinarClient = $webinarClient;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Manually register a customer for a webinar (for testing)')
            ->addArgument('organizer-key', InputArgument::REQUIRED, 'Organizer key')
            ->addArgument('webinar-key', InputArgument::REQUIRED, 'Webinar key')
            ->addOption('first-name', null, InputArgument::OPTIONAL, 'First name')
            ->addOption('last-name', null, InputArgument::OPTIONAL, 'Last name')
            ->addOption('email', null, InputArgument::OPTIONAL, 'Email address');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $organizerKey = $input->getArgument('organizer-key');
        $webinarKey = $input->getArgument('webinar-key');
        
        $firstName = $input->getOption('first-name') ?: 'Test';
        $lastName = $input->getOption('last-name') ?: 'Customer';
        $email = $input->getOption('email') ?: 'test@example.com';
        
        $io->title('Manual Webinar Registration');
        
        $io->table(
            ['Field', 'Value'],
            [
                ['Organizer Key', $organizerKey],
                ['Webinar Key', $webinarKey],
                ['Name', "$firstName $lastName"],
                ['Email', $email],
            ]
        );
        
        if (!$io->confirm('Proceed with registration?', false)) {
            $io->note('Registration cancelled');
            return Command::SUCCESS;
        }
        
        try {
            $io->section('Registering customer...');
            
            $result = $this->webinarClient->createRegistrant(
                $organizerKey,
                $webinarKey,
                [
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'email' => $email,
                ]
            );
            
            $io->success('Registration successful!');
            
            $io->section('Registration Details:');
            $io->table(
                ['Field', 'Value'],
                [
                    ['Registrant Key', $result['registrantKey']],
                    ['Join URL', $result['joinUrl']],
                    ['Confirmation URL', $result['confirmationUrl'] ?? 'N/A'],
                ]
            );
            
            $io->note('Customer can use the Join URL to access the webinar');
            
            return Command::SUCCESS;
            
        } catch (\Throwable $e) {
            $io->error('Registration failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
```

---

## Part 9: Admin Interface & Configuration (60 minutes)

### Create Configuration UI

Create `custom/apps/WebinarIntegration/Resources/config/config.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/shopware/platform/trunk/src/Core/System/SystemConfig/Schema/config.xsd">
    
    <!-- GoTo Webinar Connection Settings -->
    <card>
        <title>GoTo Webinar Connection</title>
        <title lang="de-DE">GoTo Webinar Verbindung</title>
        
        <component name="sw-field">
            <name>organizerKey</name>
            <type>text</type>
            <label>Organizer Key</label>
            <label lang="de-DE">Organizer-Schlüssel</label>
            <placeholder>Enter your GoTo Webinar organizer key</placeholder>
            <helpText>Find this in your GoTo Webinar account settings</helpText>
            <helpText lang="de-DE">Finden Sie dies in Ihren GoTo Webinar Kontoeinstellungen</helpText>
        </component>
        
        <component name="sw-button">
            <name>connectButton</name>
            <label>Connect GoTo Webinar Account</label>
            <label lang="de-DE">GoTo Webinar Konto verbinden</label>
        </component>
    </card>
    
    <!-- Sync Settings -->
    <card>
        <title>Webinar Sync Settings</title>
        <title lang="de-DE">Webinar-Synchronisationseinstellungen</title>
        
        <component name="sw-switch-field">
            <name>autoSync</name>
            <type>bool</type>
            <label>Enable automatic webinar sync</label>
            <label lang="de-DE">Automatische Webinar-Synchronisation aktivieren</label>
            <defaultValue>true</defaultValue>
        </component>
        
        <component name="sw-number-field">
            <name>syncInterval</name>
            <type>int</type>
            <label>Sync interval (hours)</label>
            <label lang="de-DE">Synchronisationsintervall (Stunden)</label>
            <defaultValue>6</defaultValue>
            <min>1</min>
            <max>168</max>
        </component>
        
        <component name="sw-number-field">
            <name>defaultPrice</name>
            <type>float</type>
            <label>Default webinar price (EUR)</label>
            <label lang="de-DE">Standard-Webinarpreis (EUR)</label>
            <defaultValue>99.00</defaultValue>
            <min>0</min>
        </component>
    </card>
    
    <!-- Registration Settings -->
    <card>
        <title>Registration Settings</title>
        <title lang="de-DE">Registrierungseinstellungen</title>
        
        <component name="sw-switch-field">
            <name>sendCustomEmails</name>
            <type>bool</type>
            <label>Send custom confirmation emails</label>
            <label lang="de-DE">Benutzerdefinierte Bestätigungse-Mails senden</label>
            <defaultValue>true</defaultValue>
            <helpText>If disabled, only GoTo Webinar's default emails will be sent</helpText>
        </component>
        
        <component name="sw-switch-field">
            <name>registerOnPayment</name>
            <type>bool</type>
            <label>Register only after payment confirmation</label>
            <label lang="de-DE">Nur nach Zahlungsbestätigung registrieren</label>
            <defaultValue>true</defaultValue>
            <helpText>Recommended: Prevents fake registrations</helpText>
        </component>
        
        <component name="sw-number-field">
            <name>maxRetries</name>
            <type>int</type>
            <label>Maximum registration retry attempts</label>
            <label lang="de-DE">Maximale Wiederholungsversuche</label>
            <defaultValue>3</defaultValue>
            <min>0</min>
            <max>10</max>
        </component>
    </card>
    
    <!-- Error Handling -->
    <card>
        <title>Error Handling</title>
        <title lang="de-DE">Fehlerbehandlung</title>
        
        <component name="sw-text-field">
            <name>adminNotificationEmail</name>
            <type>email</type>
            <label>Admin notification email</label>
            <label lang="de-DE">Admin-Benachrichtigungs-E-Mail</label>
            <placeholder>admin@example.com</placeholder>
            <helpText>Receive notifications when registrations fail</helpText>
        </component>
        
        <component name="sw-switch-field">
            <name>logAllRequests</name>
            <type>bool</type>
            <label>Log all API requests (for debugging)</label>
            <label lang="de-DE">Alle API-Anfragen protokollieren</label>
            <defaultValue>false</defaultValue>
        </component>
    </card>
</config>
```

### Admin Dashboard Widget

Create a simple admin widget to show registration status.

Create `custom/apps/WebinarIntegration/Resources/app/administration/src/module/webinar-dashboard/index.js`:

```javascript
/**
 * Admin Dashboard Module for Webinar Integration
 * 
 * Shows:
 * - Connection status
 * - Recent registrations
 * - Failed registrations requiring attention
 * - Quick actions (sync, retry failed)
 */

import './page/webinar-dashboard';
import './component/webinar-stats-card';

Shopware.Module.register('webinar-dashboard', {
    type: 'plugin',
    name: 'WebinarIntegration',
    title: 'webinar-dashboard.general.mainMenuItemGeneral',
    description: 'webinar-dashboard.general.description',
    color: '#ff3d58',
    icon: 'default-action-share',

    routes: {
        index: {
            component: 'webinar-dashboard-index',
            path: 'index'
        }
    },

    navigation: [{
        label: 'webinar-dashboard.general.mainMenuItemGeneral',
        color: '#ff3d58',
        path: 'webinar.dashboard.index',
        icon: 'default-action-share',
        parent: 'sw-marketing',
        position: 100
    }]
});
```

---

## Part 10: Testing, Documentation & Deployment (90 minutes)

### Step 1: Write Integration Tests

Create `custom/apps/WebinarIntegration/tests/Integration/WebhookTest.php`:

```php
<?php declare(strict_types=1);

namespace WebinarIntegration\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use WebinarIntegration\Controller\WebhookController;

/**
 * Integration tests for webhook handling
 * 
 * Tests the complete flow from webhook receipt to registration
 */
class WebhookTest extends TestCase
{
    /**
     * Test that order.paid webhook triggers registration
     */
    public function testOrderPaidWebhookTriggersRegistration(): void
    {
        // Create mock order with webinar product
        $order = $this->createTestOrder();
        
        // Create webhook payload
        $payload = [
            'data' => [
                'payload' => [
                    'orderId' => $order['id'],
                ],
            ],
        ];
        
        // Create request
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [
                'REQUEST_METHOD' => 'POST',
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode($payload)
        );
        
        // Add signature header (calculated with shop secret)
        $signature = hash_hmac('sha256', json_encode($payload), 'test-secret');
        $request->headers->set('shopware-shop-signature', $signature);
        
        // Handle webhook
        $controller = $this->getWebhookController();
        $response = $controller->handleOrderPaid($request);
        
        // Assert registration was successful
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertGreaterThan(0, $data['result']['success_count']);
    }

    /**
     * Test that invalid signature is rejected
     */
    public function testInvalidSignatureIsRejected(): void
    {
        $payload = ['data' => ['payload' => ['orderId' => 'test']]];
        
        $request = new Request([], [], [], [], [], [], json_encode($payload));
        $request->headers->set('shopware-shop-signature', 'invalid-signature');
        
        $controller = $this->getWebhookController();
        $response = $controller->handleOrderPaid($request);
        
        $this->assertEquals(500, $response->getStatusCode());
    }

    private function createTestOrder(): array
    {
        // Create test order with webinar product
        // Implementation depends on your test setup
        return [
            'id' => 'test-order-id',
            'lineItems' => [
                [
                    'id' => 'test-line-item',
                    'productId' => 'test-product',
                    'customFields' => [
                        'webinar_details' => [
                            'webinarKey' => 'test-webinar',
                            'organizerKey' => 'test-organizer',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getWebhookController(): WebhookController
    {
        // Create controller with mocked dependencies
        // Implementation depends on your DI setup
    }
}
```

### Step 2: Create README

Create `custom/apps/WebinarIntegration/README.md`:

```markdown
# GoTo Webinar Integration for Shopware 6

Sell webinar tickets through your Shopware shop and automatically register customers via GoTo Webinar API.

## Features

✅ **Automatic Registration** - Customers are registered immediately after payment  
✅ **Webinar Sync** - Import webinars from GoTo as products  
✅ **Email Confirmations** - Send beautiful confirmation emails with join links  
✅ **Error Handling** - Automatic retries and admin notifications  
✅ **Admin Dashboard** - View registration status and analytics  
✅ **Cancellation Support** - Unregister when orders are cancelled  

## Requirements

- Shopware 6.5 or higher
- GoTo Webinar account with API access
- PHP 8.1 or higher
- HTTPS-enabled shop (required for webhooks)

## Installation

### 1. Get GoTo Webinar API Credentials

1. Go to [GoTo Developer Portal](https://developer.goto.com/)
2. Create a new app
3. Note your Consumer Key and Consumer Secret
4. Set OAuth redirect URL to: `https://your-shop.com/api/oauth/callback`

### 2. Install the App

```bash
# Upload app to custom/apps/WebinarIntegration
cd custom/apps
git clone [your-repo] WebinarIntegration

# Or manually create directory structure as documented
```

### 3. Register the App

```bash
# Install app through Shopware Admin
Admin → Extensions → My Extensions → WebinarIntegration → Install
```

### 4. Configure OAuth

1. Go to Settings → Extensions → WebinarIntegration
2. Enter your Organizer Key
3. Click "Connect GoTo Webinar Account"
4. Authorize the app in GoTo's OAuth flow

### 5. Sync Webinars

```bash
# Via CLI
bin/console webinar:sync --organizer-key=YOUR_KEY

# Or via Admin
Marketing → Webinar Integration → Sync Webinars
```

## Usage

### Selling Webinars

1. Webinars are synced as regular products
2. Customers add to cart and checkout normally
3. After payment: automatic registration
4. Customer receives email with join link

### Manual Operations

```bash
# Test connection
bin/console webinar:test-connection

# Sync webinars
bin/console webinar:sync -o YOUR_ORGANIZER_KEY

# Manual registration (for testing)
bin/console webinar:register ORG_KEY WEBINAR_KEY \
  --first-name="Max" --last-name="Test" --email="test@example.com"
```

### Viewing Registrations

1. Admin → Orders → [Select Order]
2. View line items → Custom Fields
3. See `webinar_registration` with join URL and status

## Configuration

Settings available in Admin → Settings → Extensions:

| Setting | Description | Default |
|---------|-------------|---------|
| **Organizer Key** | Your GoTo organizer ID | Required |
| **Auto Sync** | Automatically sync webinars | Yes |
| **Sync Interval** | Hours between syncs | 6 |
| **Default Price** | Price for synced webinars | 99.00 |
| **Send Custom Emails** | Use custom confirmation emails | Yes |
| **Register on Payment** | Wait for payment before registering | Yes (recommended) |
| **Max Retries** | Registration retry attempts | 3 |
| **Admin Email** | Notification email for failures | - |

## Troubleshooting

### Registration Fails

1. Check logs: `var/log/webinar_integration.log`
2. Verify OAuth tokens are valid
3. Test API connection: `bin/console webinar:test-connection`
4. Manually retry: Admin → Failed Registrations → Retry

### Webinars Not Syncing

1. Check organizer key is correct
2. Verify API credentials
3. Check GoTo account has active webinars
4. Manual sync: `bin/console webinar:sync`

### Email Not Sending

1. Check "Send Custom Emails" is enabled
2. Verify Shopware mail configuration
3. Check logs for email errors
4. Test with manual registration command

## API Documentation

### Webhook Endpoints

**POST** `/api/webhooks/order-paid`  
Triggered when payment is confirmed. Registers customer for webinars.

**POST** `/api/webhooks/order-cancelled`  
Triggered when order is cancelled. Unregisters customer.

### Admin API

**GET** `/api/_action/webinar-integration/status`  
Get connection and sync status

**POST** `/api/_action/webinar-integration/sync`  
Manually trigger webinar sync

**POST** `/api/_action/webinar-integration/registrations/{id}/retry`  
Retry failed registration

## Development

### Running Tests

```bash
cd custom/apps/WebinarIntegration
composer install
vendor/bin/phpunit
```

### Debug Mode

Enable in config:

```xml
<component name="sw-switch-field">
    <name>logAllRequests</name>
    <defaultValue>true</defaultValue>
</component>
```

This logs all API requests to help debug issues.

## Support

- **Documentation**: See `docs/` folder
- **Issues**: GitHub Issues
- **Email**: support@example.com

## License

MIT License - See LICENSE file

## Credits

Developed by [Your Company]  
GoTo Webinar API: https://developer.goto.com/

---

**Version:** 1.0.0  
**Last Updated:** November 28, 2025
```

### Step 3: Deployment Checklist

Create `custom/apps/WebinarIntegration/DEPLOYMENT.md`:

```markdown
# Deployment Checklist

## Pre-Deployment

- [ ] All tests passing
- [ ] OAuth credentials configured for production
- [ ] Shop has HTTPS (required for webhooks)
- [ ] Email service configured
- [ ] Admin notification email set
- [ ] Default tax and currency IDs updated in sync command
- [ ] Error logging configured (Sentry/Datadog)
- [ ] Backup current shop before deployment

## Deployment Steps

### 1. Upload App

```bash
# Via Git
cd custom/apps
git clone [production-repo] WebinarIntegration

# Or via SFTP
# Upload entire WebinarIntegration directory
```

### 2. Install Dependencies

```bash
cd custom/apps/WebinarIntegration
composer install --no-dev --optimize-autoloader
```

### 3. Install App in Shopware

```bash
# Via Admin UI
Admin → Extensions → My Extensions → Install

# Or via CLI
bin/console plugin:refresh
bin/console plugin:install --activate WebinarIntegration
```

### 4. Configure OAuth

1. Admin → Settings → Extensions → WebinarIntegration
2. Enter production organizer key
3. Connect GoTo Webinar account
4. Verify connection: `bin/console webinar:test-connection`

### 5. Initial Webinar Sync

```bash
# Sync all webinars
bin/console webinar:sync --organizer-key=PROD_ORG_KEY

# Check results in Admin
```

### 6. Test Registration Flow

1. Create test order with webinar product
2. Complete payment
3. Verify registration in GoTo Webinar
4. Check confirmation email received
5. Verify join URL works

### 7. Configure Monitoring

Set up monitoring for:
- Failed registrations
- API errors
- Webhook delivery failures
- Email delivery issues

### 8. Update Documentation

- [ ] Document production URLs
- [ ] Update support contact info
- [ ] Document backup procedures
- [ ] Train support team

## Post-Deployment

- [ ] Monitor logs for 24 hours
- [ ] Check error rate dashboard
- [ ] Verify webhooks are being received
- [ ] Test with real customer order
- [ ] Set up automated backups
- [ ] Schedule regular sync checks

## Rollback Plan

If issues occur:

```bash
# Disable app
bin/console plugin:deactivate WebinarIntegration

# Or uninstall (preserves data)
bin/console plugin:uninstall --keep-user-data WebinarIntegration

# Restore from backup if needed
```

## Monitoring

Check these regularly:

```bash
# View logs
tail -f var/log/webinar_integration.log

# Check failed registrations
Admin → Marketing → Webinar Integration → Failed Registrations

# Test API health
bin/console webinar:test-connection
```

## Performance Considerations

- Webhook processing should complete in < 5 seconds
- API calls cached where possible
- Failed registrations retried automatically
- Rate limiting: max 60 requests/minute to GoTo API

## Security

- [ ] OAuth tokens encrypted at rest
- [ ] Webhook signatures validated
- [ ] API credentials in environment variables (not code)
- [ ] HTTPS enforced for all communications
- [ ] Admin access restricted to authorized users

---

**Emergency Contact:** support@example.com  
**On-Call:** [Phone Number]
```

---

## Final Review & Summary

### What You've Built

A complete, production-ready Shopware App featuring:

**✅ Core Integration:**
- OAuth 2.0 authentication with GoTo Webinar
- Automatic token refresh
- Webhook-based order processing
- API client for registration and sync
- Error handling and retry logic

**✅ Admin Features:**
- Configuration UI
- Connection testing
- Manual webinar sync
- Registration status dashboard
- Failed registration retry

**✅ Customer Experience:**
- Seamless checkout process
- Automatic registration after payment
- Beautiful confirmation emails
- Join links and calendar invites

**✅ Development Tools:**
- CLI commands for testing
- Comprehensive logging
- Integration tests
- Documentation

**✅ Production Ready:**
- Security (webhook signatures, OAuth)
- Error handling and retries
- Monitoring and alerts
- Deployment procedures

### What You've Learned

**Technical Skills:**
- ✅ Shopware App development (vs plugins)
- ✅ OAuth 2.0 implementation
- ✅ REST API integration
- ✅ Webhook handling and security
- ✅ Event-driven architecture
- ✅ Error handling patterns
- ✅ Email templating
- ✅ CLI command development
- ✅ Admin UI configuration
- ✅ Testing strategies

**Business Skills:**
- ✅ Third-party service integration
- ✅ Customer experience design
- ✅ Error recovery strategies
- ✅ Production deployment
- ✅ Support documentation

### Comparison: App vs Plugin vs Recommendation Engine

| Aspect | This Webinar App | Day 7 Recommendation | Plugin (Day 2) |
|--------|------------------|----------------------|----------------|
| **Type** | Shopware App | Plugin | Plugin |
| **Use Case** | 3rd party API | Internal feature | Core extension |
| **Installation** | `custom/apps/` | `custom/plugins/` | `custom/plugins/` |
| **OAuth** | Built-in | Manual | Manual |
| **Webhooks** | Native support | Event subscribers | Event subscribers |
| **Core Access** | API only | Full access | Full access |
| **Distribution** | Marketplace ready | Marketplace ready | Private use |
| **Updates** | Independent | Coupled to Shopware | Coupled to Shopware |

### Time Breakdown

| Phase | Hours | Percentage |
|-------|-------|------------|
| OAuth & Registration | 6-8 | 25% |
| API Client Development | 4-6 | 20% |
| Webhook Processing | 4-5 | 18% |
| Email & Communication | 2-3 | 10% |
| CLI Commands | 3-4 | 12% |
| Admin Interface | 3-4 | 12% |
| Testing & Docs | 2-3 | 8% |
| **Total** | **24-33 hours** | **100%** |

### Next Steps

**Immediate (Week 2):**
1. Deploy to staging environment
2. Test with real webinars
3. Gather feedback from test users
4. Fix any bugs

**Short Term (Month 1):**
1. Add recurring webinar support
2. Implement attendance tracking
3. Add capacity management
4. Multi-language support

**Long Term (Quarter 1):**
1. Analytics dashboard
2. Automated reminder emails
3. Certificate generation
4. Zoom integration option

### Portfolio Value

This project demonstrates:

✅ **API Integration Expertise** - OAuth, REST, webhooks  
✅ **E-commerce Knowledge** - Order processing, payments  
✅ **Production Skills** - Error handling, monitoring, deployment  
✅ **Business Understanding** - Customer experience, support  
✅ **Documentation Skills** - Clear, comprehensive docs  

**Perfect for showing to potential clients or employers!**

---

## Congratulations! 🎉

You've completed an advanced Shopware App that solves a real business problem. You now have:

- ✅ A working third-party API integration
- ✅ Production-ready code with error handling
- ✅ Complete documentation
- ✅ Testing and deployment procedures
- ✅ A portfolio piece that demonstrates advanced skills

**This is exactly the type of project that gets you hired or wins clients!**

### Resources for Continued Learning

**Shopware App Development:**
- [Official App Guide](https://developer.shopware.com/docs/guides/plugins/apps/)
- [App Examples](https://github.com/shopware/app-php-sdk)
- [Community Forum](https://forum.shopware.com/)

**GoTo Webinar API:**
- [API Documentation](https://developer.goto.com/GoToWebinarV2)
- [OAuth Guide](https://developer.goto.com/guides/Get%20Started/)
- [Code Examples](https://github.com/citrix/sample-applications)

**API Integration Best Practices:**
- [REST API Design](https://restfulapi.net/)
- [OAuth 2.0 Spec](https://oauth.net/2/)
- [Webhook Security](https://webhooks.fyi/)

---

**You're now ready to build professional Shopware integrations!** 🚀

Whether you choose to build this webinar integration or the recommendation engine from the original Day 7 project, you have the skills to create production-quality Shopware extensions.

**Keep building, keep learning, and welcome to the world of professional Shopware development!**
