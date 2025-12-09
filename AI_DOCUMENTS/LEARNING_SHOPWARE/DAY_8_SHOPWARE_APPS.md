# Day 8: Understanding Shopware Apps (The Alternative to Plugins)

**Duration:** 4-6 hours  
**Level:** Intermediate  
**Prerequisites:** Basic understanding of plugins (Day 2), REST APIs, and webhooks

---

## Overview

Shopware offers two extension mechanisms: **Plugins** and **Apps**. While plugins are great for tightly-coupled features that need direct access to Shopware's core, **Apps** are perfect for loosely-coupled integrations with external services.

### Why This Matters

As a frontend-focused developer, understanding Apps is crucial because:
- **Modern architecture**: Apps use webhooks and APIs (REST patterns you already know)
- **Cleaner separation**: No deep PHP/Symfony knowledge needed for basic apps
- **Marketplace ready**: Apps are easier to distribute and update
- **Third-party integrations**: Perfect for connecting payment providers, shipping services, marketing tools, etc.

### What You'll Learn

By the end of this tutorial, you'll understand:
1. ✅ The fundamental differences between Apps and Plugins
2. ✅ When to choose Apps over Plugins
3. ✅ How to create a simple Shopware App
4. ✅ The App registration and authentication flow
5. ✅ How to handle webhooks from Shopware
6. ✅ How to call Shopware's Admin API from your app

---

## Part 1: Apps vs Plugins - The Key Differences (30 minutes)

### Architectural Comparison

| Aspect | **Plugin** | **App** |
|--------|----------|-------|
| **Location** | `custom/plugins/` | `custom/apps/` |
| **Language** | PHP (must run on Shopware server) | Any language (runs on your server) |
| **Core Access** | Direct access to Shopware core | API-only access |
| **Installation** | Composer + database migrations | Manifest file + OAuth registration |
| **Updates** | Requires reinstall/update command | Update manifest version |
| **Distribution** | Needs PHP source code | Can be closed-source service |
| **Communication** | Event system, DI container | Webhooks + REST API |
| **Best For** | Core functionality, custom entities | External services, SaaS integrations |

### When to Use Apps

**Choose Apps when:**
- ✅ Integrating with external services (payment gateways, shipping providers)
- ✅ Building SaaS products that serve multiple shops
- ✅ You want to keep your business logic private
- ✅ Your team prefers non-PHP languages (Node.js, Python, Go, etc.)
- ✅ You need to update your service without updating shops
- ✅ You're distributing via Shopware Store

**Choose Plugins when:**
- ✅ You need direct database access
- ✅ You're extending core Shopware entities deeply
- ✅ Performance is critical (no API overhead)
- ✅ You need custom Symfony services
- ✅ You're building shop-specific customizations

### Real-World Examples

**Good App Use Cases:**
- Payment provider integration (Stripe, PayPal)
- Email marketing (Mailchimp, SendGrid)
- Analytics dashboard (external BI tool)
- Product recommendation engine
- **Our Day 7 project**: GoTo Webinar integration

**Good Plugin Use Cases:**
- Custom product types with special logic
- Advanced discount rules
- Performance-critical features
- Custom admin modules with complex database queries
- **Our Day 2-3 projects**: Product view tracking

---

## Part 2: Your First Shopware App - "Order Logger" (90 minutes)

Let's create a simple app that logs order details whenever an order is placed. This demonstrates the core app concepts without external dependencies.

### Step 1: Create App Structure

```bash
# Navigate to your Shopware installation
cd /path/to/shopware

# Create app directory
mkdir -p custom/apps/OrderLogger
cd custom/apps/OrderLogger
```

Your app needs just one file to start: `manifest.xml`

### Step 2: Create the Manifest File

Create `custom/apps/OrderLogger/manifest.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<manifest xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/shopware/shopware/trunk/src/Core/Framework/App/Manifest/Schema/manifest-3.0.xsd">
    
    <!-- Meta Information -->
    <meta>
        <name>OrderLogger</name>
        <label>Order Logger App</label>
        <label lang="de-DE">Bestellprotokoll-App</label>
        
        <description>Logs order details to an external service via webhook</description>
        <description lang="de-DE">Protokolliert Bestelldetails über Webhook an einen externen Dienst</description>
        
        <author>Your Name</author>
        <copyright>(c) 2025 Your Company</copyright>
        <version>1.0.0</version>
        <license>MIT</license>
        
        <!-- Optional: Icon for the app -->
        <!-- <icon>Resources/app-icon.png</icon> -->
    </meta>
    
    <!-- Setup: Registration with external server (optional for now) -->
    <!-- We'll add this in Part 3 -->
    
    <!-- Permissions: What data can the app access? -->
    <permissions>
        <!-- Read order data -->
        <read>order</read>
        <read>customer</read>
        
        <!-- Read product data from orders -->
        <read>product</read>
    </permissions>
    
    <!-- Webhooks: Which events should trigger notifications? -->
    <webhooks>
        <!-- Trigger when order is placed -->
        <webhook name="order-placed" url="https://your-server.com/webhooks/order-placed" event="checkout.order.placed"/>
        
        <!-- Trigger when payment is completed -->
        <webhook name="order-paid" url="https://your-server.com/webhooks/order-paid" event="state_enter.order_transaction.state.paid"/>
    </webhooks>
</manifest>
```

### Understanding the Manifest

**Key Sections:**

1. **`<meta>`**: Basic app information (name, version, description)
2. **`<permissions>`**: What data your app can read/write via API
3. **`<webhooks>`**: Events that trigger HTTP calls to your server

**Important Notes:**
- ⚠️ The `<name>` must match your folder name: `OrderLogger`
- ⚠️ Webhook URLs must be publicly accessible (HTTPS in production)
- ⚠️ You need `read` permissions for any data included in webhooks

### Step 3: Install the App

```bash
# Validate the app structure
bin/console app:validate OrderLogger

# Install the app
bin/console app:install --activate OrderLogger

# Clear cache
bin/console cache:clear
```

**Expected Output:**
```
[OK] App OrderLogger has been installed successfully.
[OK] App OrderLogger has been activated successfully.
```

### Step 4: Verify Installation

Check in Admin:
1. Go to **Settings** → **System** → **Apps**
2. Find "Order Logger App"
3. Status should be "Active" with a green checkmark

---

## Part 3: Handling Webhooks (Local Testing) (60 minutes)

Now we need a server to receive webhooks. For learning purposes, we'll create a simple local webhook receiver.

### Option A: Quick Test with RequestBin (Easiest)

1. Go to https://requestbin.com/ (or https://webhook.site/)
2. Create a new bin - you'll get a URL like `https://requestbin.com/r/abc123`
3. Update your `manifest.xml` webhook URLs:

```xml
<webhooks>
    <webhook name="order-placed" 
             url="https://requestbin.com/r/YOUR_BIN_ID" 
             event="checkout.order.placed"/>
</webhooks>
```

4. Reinstall the app:
```bash
bin/console app:install --activate --force OrderLogger
bin/console cache:clear
```

5. Create a test order in your shop
6. Check RequestBin - you should see the webhook payload!

### Option B: Local PHP Webhook Server (For Development)

Create a simple PHP webhook receiver for local testing:

**File:** `custom/apps/OrderLogger/webhook-server.php`

```php
<?php
/**
 * Simple webhook receiver for local testing
 * 
 * Run with: php -S localhost:8888 webhook-server.php
 * Then use ngrok to expose: ngrok http 8888
 */

// Get webhook data
$payload = file_get_contents('php://input');
$headers = getallheaders();

// Log timestamp
$timestamp = date('Y-m-d H:i:s');

// Extract event type from headers
$eventType = $headers['sw-webhook-event-name'] ?? 'unknown';

// Decode the payload
$data = json_decode($payload, true);

// Log the webhook
$logEntry = [
    'timestamp' => $timestamp,
    'event' => $eventType,
    'headers' => $headers,
    'payload' => $data,
];

// Save to file
$logFile = __DIR__ . '/webhook-log.json';
$logs = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];
$logs[] = $logEntry;
file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));

// Pretty print for terminal
echo "\n=== Webhook Received at {$timestamp} ===\n";
echo "Event: {$eventType}\n";
echo "Payload:\n";
echo json_encode($data, JSON_PRETTY_PRINT);
echo "\n\n";

// Return 200 OK
http_response_code(200);
echo json_encode(['success' => true, 'message' => 'Webhook received']);
```

**Run the server:**

```bash
cd custom/apps/OrderLogger
php -S localhost:8888 webhook-server.php
```

**Expose it with ngrok (required for Shopware to reach it):**

```bash
# Install ngrok: https://ngrok.com/download
ngrok http 8888
```

You'll get a public URL like `https://abc123.ngrok.io` - use this in your manifest!

### Understanding Webhook Payloads

When Shopware sends a webhook, it includes:

**Headers:**
```
sw-webhook-event-name: checkout.order.placed
sw-version: 6.5.7.0
sw-context-language: 2fbb5fe2e29a4d70aa5854ce7ce3e20b
shopware-shop-signature: <hmac-signature>
```

**Payload Example (order placed):**

```json
{
  "data": {
    "payload": {
      "orderId": "018c1234-5678-7890-abcd-ef1234567890",
      "orderNumber": "10001",
      "price": {
        "totalPrice": 99.99
      },
      "orderCustomer": {
        "email": "customer@example.com",
        "firstName": "Max",
        "lastName": "Mustermann"
      },
      "lineItems": [
        {
          "id": "...",
          "productId": "...",
          "label": "Product Name",
          "quantity": 1,
          "price": 99.99
        }
      ]
    }
  },
  "source": {
    "url": "http://localhost:8000",
    "shopId": "shop-id-here"
  },
  "timestamp": 1234567890
}
```

---

## Part 4: App Registration & API Authentication (90 minutes)

For apps that need to call Shopware's API, you need the **registration flow**. This establishes trust between Shopware and your app.

### The Registration Handshake

When you add `<setup>` to your manifest, Shopware initiates a 3-step handshake:

```
┌──────────┐                ┌──────────────┐
│ Shopware │                │  Your App    │
└────┬─────┘                └──────┬───────┘
     │                              │
     │ 1. Registration Request      │
     │ GET /register?shop-id=...    │
     ├─────────────────────────────>│
     │                              │
     │ 2. Registration Response     │
     │    {proof, secret, confirm}  │
     │<─────────────────────────────┤
     │                              │
     │ 3. Confirmation Request      │
     │ POST /confirm {apiKey, ...}  │
     ├─────────────────────────────>│
     │                              │
     │ 4. Ready! ✅                 │
     │                              │
```

### Step 1: Add Setup to Manifest

Update `custom/apps/OrderLogger/manifest.xml`:

```xml
<manifest xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/shopware/shopware/trunk/src/Core/Framework/App/Manifest/Schema/manifest-3.0.xsd">
    
    <meta>
        <!-- ... meta info from before ... -->
    </meta>
    
    <!-- NEW: Setup section for registration -->
    <setup>
        <registrationUrl>https://your-server.com/register</registrationUrl>
        
        <!-- ONLY FOR LOCAL DEVELOPMENT - Remove before publishing! -->
        <secret>my-development-secret-key</secret>
    </setup>
    
    <permissions>
        <read>order</read>
        <read>customer</read>
        <read>product</read>
    </permissions>
    
    <webhooks>
        <!-- ... webhooks from before ... -->
    </webhooks>
</manifest>
```

⚠️ **Important:** The `<secret>` is ONLY for local development. For production apps, Shopware provides the secret via the Store.

### Step 2: Create Registration Endpoint

Create a simple registration handler. For this tutorial, we'll use PHP, but you can use any language.

**File:** `custom/apps/OrderLogger/register.php`

```php
<?php
/**
 * App Registration Handler
 * 
 * This endpoint handles the OAuth-like registration flow between
 * Shopware and your app server.
 * 
 * Run with: php -S localhost:8889 register.php
 * Expose with: ngrok http 8889
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log file for debugging
$logFile = __DIR__ . '/registration-log.txt';

function logMessage($message) {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

// Step 1: Handle Registration Request (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['shop-id'])) {
    logMessage('Registration request received');
    
    $shopId = $_GET['shop-id'];
    $shopUrl = $_GET['shop-url'];
    $timestamp = $_GET['timestamp'];
    
    // Get the signature from headers
    $providedSignature = $_SERVER['HTTP_SHOPWARE_APP_SIGNATURE'] ?? '';
    
    // Verify signature (in production, verify this!)
    $appSecret = 'my-development-secret-key'; // Same as in manifest.xml
    $queryString = $_SERVER['QUERY_STRING'];
    $expectedSignature = hash_hmac('sha256', $queryString, $appSecret);
    
    logMessage("Shop ID: {$shopId}");
    logMessage("Shop URL: {$shopUrl}");
    logMessage("Signature valid: " . ($providedSignature === $expectedSignature ? 'YES' : 'NO'));
    
    // Generate a random shop-specific secret
    $shopSecret = bin2hex(random_bytes(32)); // 64 characters
    
    // Save shop data for later use
    $shopData = [
        'shopId' => $shopId,
        'shopUrl' => $shopUrl,
        'shopSecret' => $shopSecret,
        'timestamp' => time(),
    ];
    file_put_contents(__DIR__ . "/shop-{$shopId}.json", json_encode($shopData, JSON_PRETTY_PRINT));
    
    // Calculate proof: sha256(shopId + shopUrl + appName)
    $appName = 'OrderLogger';
    $proof = hash_hmac('sha256', $shopId . $shopUrl . $appName, $appSecret);
    
    // Return registration response
    header('Content-Type: application/json');
    echo json_encode([
        'proof' => $proof,
        'secret' => $shopSecret,
        'confirmation_url' => 'https://your-ngrok-url.ngrok.io/register', // Same URL for confirmation
    ]);
    
    logMessage('Registration response sent with proof and secret');
    exit;
}

// Step 2: Handle Confirmation Request (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    logMessage('Confirmation request received');
    
    $payload = file_get_contents('php://input');
    $data = json_decode($payload, true);
    
    $shopId = $data['shopId'];
    $apiKey = $data['apiKey'];
    $secretKey = $data['secretKey'];
    $shopUrl = $data['shopUrl'];
    
    // Load shop data
    $shopDataFile = __DIR__ . "/shop-{$shopId}.json";
    if (!file_exists($shopDataFile)) {
        logMessage('ERROR: Shop data not found for ' . $shopId);
        http_response_code(400);
        exit;
    }
    
    $shopData = json_decode(file_get_contents($shopDataFile), true);
    $shopSecret = $shopData['shopSecret'];
    
    // Verify signature
    $providedSignature = $_SERVER['HTTP_SHOPWARE_SHOP_SIGNATURE'] ?? '';
    $expectedSignature = hash_hmac('sha256', $payload, $shopSecret);
    
    logMessage("Confirmation signature valid: " . ($providedSignature === $expectedSignature ? 'YES' : 'NO'));
    
    // Save API credentials
    $shopData['apiKey'] = $apiKey;
    $shopData['secretKey'] = $secretKey;
    $shopData['confirmed'] = true;
    $shopData['confirmedAt'] = time();
    
    file_put_contents($shopDataFile, json_encode($shopData, JSON_PRETTY_PRINT));
    
    logMessage('Registration completed! API credentials saved.');
    logMessage("API Key: {$apiKey}");
    
    // Return success
    header('Content-Type: application/json');
    http_response_code(200);
    echo json_encode(['success' => true]);
    exit;
}

// Default response
http_response_code(404);
echo 'Registration endpoint ready. Use GET with shop-id parameter or POST for confirmation.';
```

### Step 3: Run Registration Server

```bash
# Terminal 1: Run the server
cd custom/apps/OrderLogger
php -S localhost:8889 register.php

# Terminal 2: Expose with ngrok
ngrok http 8889
# Copy the HTTPS URL (e.g., https://xyz123.ngrok.io)
```

Update your manifest with the ngrok URL:
```xml
<setup>
    <registrationUrl>https://xyz123.ngrok.io/register</registrationUrl>
    <secret>my-development-secret-key</secret>
</setup>
```

### Step 4: Reinstall and Register

```bash
# Uninstall first (to trigger fresh registration)
bin/console app:uninstall OrderLogger

# Install and activate
bin/console app:install --activate OrderLogger

# Check logs
cat custom/apps/OrderLogger/registration-log.txt
```

**Expected Log Output:**
```
2025-11-28 10:30:15: Registration request received
2025-11-28 10:30:15: Shop ID: abc123
2025-11-28 10:30:15: Shop URL: http://localhost:8000
2025-11-28 10:30:15: Signature valid: YES
2025-11-28 10:30:15: Registration response sent with proof and secret
2025-11-28 10:30:16: Confirmation request received
2025-11-28 10:30:16: Confirmation signature valid: YES
2025-11-28 10:30:16: Registration completed! API credentials saved.
```

### Step 5: Verify API Credentials

Check the saved shop data:
```bash
cat custom/apps/OrderLogger/shop-*.json
```

You should see:
```json
{
    "shopId": "abc123",
    "shopUrl": "http://localhost:8000",
    "shopSecret": "...",
    "apiKey": "SWIARXBSDJRWEMJONFK2OHBNWA",
    "secretKey": "Q1QyaUg3ZHpnZURPeDV3...",
    "confirmed": true
}
```

🎉 **Success!** Your app can now call Shopware's Admin API using these credentials.

---

## Part 5: Calling Shopware's Admin API (60 minutes)

Now that you have API credentials, let's fetch order data from Shopware.

### Getting an Access Token

Shopware uses OAuth2 Client Credentials flow. You exchange your API key/secret for a temporary access token.

**File:** `custom/apps/OrderLogger/api-client.php`

```php
<?php
/**
 * Shopware Admin API Client
 * 
 * Demonstrates how to authenticate and call Shopware's API
 */

class ShopwareApiClient
{
    private string $shopUrl;
    private string $apiKey;
    private string $secretKey;
    private ?string $accessToken = null;
    
    public function __construct(string $shopId)
    {
        // Load credentials from registration
        $shopDataFile = __DIR__ . "/shop-{$shopId}.json";
        if (!file_exists($shopDataFile)) {
            throw new \Exception("Shop data not found for {$shopId}");
        }
        
        $shopData = json_decode(file_get_contents($shopDataFile), true);
        $this->shopUrl = rtrim($shopData['shopUrl'], '/');
        $this->apiKey = $shopData['apiKey'];
        $this->secretKey = $shopData['secretKey'];
    }
    
    /**
     * Get OAuth access token
     */
    public function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }
        
        $ch = curl_init($this->shopUrl . '/api/oauth/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'grant_type' => 'client_credentials',
                'client_id' => $this->apiKey,
                'client_secret' => $this->secretKey,
            ]),
        ]);
        
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($statusCode !== 200) {
            throw new \Exception("Failed to get access token: {$response}");
        }
        
        $data = json_decode($response, true);
        $this->accessToken = $data['access_token'];
        
        return $this->accessToken;
    }
    
    /**
     * Get order details by ID
     */
    public function getOrder(string $orderId): array
    {
        $token = $this->getAccessToken();
        
        $ch = curl_init($this->shopUrl . '/api/search/order');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'ids' => [$orderId],
                'associations' => [
                    'lineItems' => [],
                    'orderCustomer' => [],
                ],
            ]),
        ]);
        
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($statusCode !== 200) {
            throw new \Exception("Failed to get order: {$response}");
        }
        
        $data = json_decode($response, true);
        return $data['data'][0] ?? [];
    }
    
    /**
     * Search orders with criteria
     */
    public function searchOrders(array $criteria = []): array
    {
        $token = $this->getAccessToken();
        
        $ch = curl_init($this->shopUrl . '/api/search/order');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_POSTFIELDS => json_encode($criteria),
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        return $data['data'] ?? [];
    }
}

// Example usage
if (php_sapi_name() === 'cli') {
    $shopId = $argv[1] ?? null;
    
    if (!$shopId) {
        echo "Usage: php api-client.php <shop-id>\n";
        exit(1);
    }
    
    try {
        $client = new ShopwareApiClient($shopId);
        
        echo "Getting access token...\n";
        $token = $client->getAccessToken();
        echo "✓ Got token: " . substr($token, 0, 20) . "...\n\n";
        
        echo "Searching for recent orders...\n";
        $orders = $client->searchOrders([
            'limit' => 5,
            'sort' => [
                ['field' => 'orderDateTime', 'order' => 'DESC']
            ],
        ]);
        
        echo "Found " . count($orders) . " orders:\n";
        foreach ($orders as $order) {
            echo sprintf(
                "- Order #%s: %.2f EUR (%s)\n",
                $order['orderNumber'],
                $order['price']['totalPrice'],
                $order['orderDateTime']
            );
        }
        
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
```

### Test the API Client

```bash
# Find your shop ID
ls custom/apps/OrderLogger/shop-*.json

# Run the client (replace with your actual shop ID)
php custom/apps/OrderLogger/api-client.php abc123
```

**Expected Output:**
```
Getting access token...
✓ Got token: eyJ0eXAiOiJKV1QiLCJh...

Searching for recent orders...
Found 3 orders:
- Order #10003: 149.99 EUR (2025-11-28T10:45:00+00:00)
- Order #10002: 99.99 EUR (2025-11-28T09:30:00+00:00)
- Order #10001: 49.99 EUR (2025-11-28T08:15:00+00:00)
```

---

## Part 6: Complete Working Example (30 minutes)

Let's tie it all together: a webhook that receives order notifications and fetches additional data via API.

**File:** `custom/apps/OrderLogger/complete-webhook.php`

```php
<?php
/**
 * Complete Webhook Handler with API Integration
 * 
 * This receives webhook notifications from Shopware
 * and enriches the data by calling the Admin API
 */

require_once __DIR__ . '/api-client.php';

// Get webhook payload
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

// Extract shop ID from source
$shopId = $data['source']['shopId'] ?? null;
if (!$shopId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing shop ID']);
    exit;
}

// Verify signature (important for security!)
$shopDataFile = __DIR__ . "/shop-{$shopId}.json";
if (file_exists($shopDataFile)) {
    $shopData = json_decode(file_get_contents($shopDataFile), true);
    $shopSecret = $shopData['shopSecret'];
    
    $providedSignature = $_SERVER['HTTP_SHOPWARE_SHOP_SIGNATURE'] ?? '';
    $expectedSignature = hash_hmac('sha256', $payload, $shopSecret);
    
    if ($providedSignature !== $expectedSignature) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }
}

// Get event type
$eventName = $_SERVER['HTTP_SW_WEBHOOK_EVENT_NAME'] ?? 'unknown';

// Process based on event
switch ($eventName) {
    case 'checkout.order.placed':
        handleOrderPlaced($shopId, $data);
        break;
        
    case 'state_enter.order_transaction.state.paid':
        handleOrderPaid($shopId, $data);
        break;
        
    default:
        echo json_encode(['message' => 'Event not handled: ' . $eventName]);
}

function handleOrderPlaced(string $shopId, array $data): void
{
    $orderId = $data['data']['payload']['orderId'] ?? null;
    
    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing order ID']);
        return;
    }
    
    // Fetch full order details from API
    $client = new ShopwareApiClient($shopId);
    $order = $client->getOrder($orderId);
    
    // Log the order (in production, save to database or external service)
    $logEntry = [
        'timestamp' => date('c'),
        'event' => 'order_placed',
        'orderId' => $orderId,
        'orderNumber' => $order['orderNumber'] ?? 'N/A',
        'customerEmail' => $order['orderCustomer']['email'] ?? 'N/A',
        'totalPrice' => $order['price']['totalPrice'] ?? 0,
        'itemCount' => count($order['lineItems'] ?? []),
    ];
    
    $logFile = __DIR__ . '/orders-log.json';
    $logs = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];
    $logs[] = $logEntry;
    file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));
    
    echo json_encode([
        'success' => true,
        'message' => 'Order logged successfully',
        'orderNumber' => $order['orderNumber'],
    ]);
}

function handleOrderPaid(string $shopId, array $data): void
{
    // Similar to above, but for payment confirmation
    echo json_encode([
        'success' => true,
        'message' => 'Payment confirmed and logged',
    ]);
}
```

### Test the Complete Flow

1. **Update manifest** to use the new webhook:
```xml
<webhooks>
    <webhook name="order-placed" 
             url="https://your-ngrok-url.ngrok.io/complete-webhook.php" 
             event="checkout.order.placed"/>
</webhooks>
```

2. **Reinstall app:**
```bash
bin/console app:install --activate --force OrderLogger
```

3. **Create test order** in your shop

4. **Check logs:**
```bash
cat custom/apps/OrderLogger/orders-log.json
```

**Example Output:**
```json
[
    {
        "timestamp": "2025-11-28T11:00:00+00:00",
        "event": "order_placed",
        "orderId": "018c1234567890abcdef123456789012",
        "orderNumber": "10004",
        "customerEmail": "test@example.com",
        "totalPrice": 99.99,
        "itemCount": 2
    }
]
```

🎉 **Congratulations!** You've built a complete Shopware App that:
- Registers with Shopware
- Receives webhooks
- Calls the Admin API
- Processes real order data

---

## Part 7: Comparison with Plugin Approach (Review)

Let's compare how we'd do the same thing with a Plugin vs App:

### Plugin Approach (from Day 2-3)

```php
// Plugin: Direct event subscriber
class OrderPlacedSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutOrderPlacedEvent::class => 'onOrderPlaced',
        ];
    }
    
    public function onOrderPlaced(CheckoutOrderPlacedEvent $event): void
    {
        $order = $event->getOrder();
        // Direct access to all order data
        // No API calls needed
    }
}
```

**Pros:**
- ✅ Faster (no HTTP overhead)
- ✅ Simpler for shop-specific features
- ✅ Direct database access

**Cons:**
- ❌ Tied to Shopware's PHP environment
- ❌ Can't use external languages/services
- ❌ Harder to distribute as SaaS

### App Approach (What We Just Built)

```php
// App: Webhook endpoint
function handleOrderPlaced($shopId, $data) {
    $client = new ShopwareApiClient($shopId);
    $order = $client->getOrder($data['orderId']);
    // Process via API
}
```

**Pros:**
- ✅ Language-agnostic (use Node.js, Python, etc.)
- ✅ Serves multiple shops from one server
- ✅ Can keep business logic private
- ✅ Updates without touching shops

**Cons:**
- ❌ Network latency from API calls
- ❌ More complex architecture
- ❌ Requires external server

---

## Part 8: Best Practices & Production Considerations (30 minutes)

### Security

**1. Always Verify Signatures**
```php
$providedSignature = $_SERVER['HTTP_SHOPWARE_SHOP_SIGNATURE'];
$expectedSignature = hash_hmac('sha256', $payload, $shopSecret);

if (!hash_equals($providedSignature, $expectedSignature)) {
    http_response_code(401);
    exit('Invalid signature');
}
```

**2. Use HTTPS in Production**
- ⚠️ Webhooks MUST use HTTPS (not HTTP)
- Use Let's Encrypt for free SSL certificates
- Validate SSL certificates on API calls

**3. Store Secrets Securely**
- Don't commit secrets to Git
- Use environment variables or secret managers
- Rotate secrets periodically

### Performance

**1. Handle Webhooks Asynchronously**
```php
// Quick response to Shopware
http_response_code(200);
echo json_encode(['received' => true]);

// Process in background
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

// Now do heavy processing
processOrder($data);
```

**2. Implement Retry Logic**
```php
function callApiWithRetry($url, $data, $maxRetries = 3) {
    $attempt = 0;
    
    while ($attempt < $maxRetries) {
        try {
            return makeApiCall($url, $data);
        } catch (\Exception $e) {
            $attempt++;
            if ($attempt >= $maxRetries) throw $e;
            sleep(pow(2, $attempt)); // Exponential backoff
        }
    }
}
```

**3. Cache Access Tokens**
```php
// Don't request new token for every API call
$token = $cache->get("shopware_token_{$shopId}");
if (!$token) {
    $token = $this->getAccessToken();
    $cache->set("shopware_token_{$shopId}", $token, 600); // 10 minutes
}
```

### Monitoring & Logging

**1. Log All Webhook Events**
```php
$logger->info('Webhook received', [
    'shop_id' => $shopId,
    'event' => $eventName,
    'order_id' => $orderId,
]);
```

**2. Monitor Failed Webhooks**
- Shopware retries failed webhooks (5xx errors)
- Return 200 even if you'll process later
- Track retry counts

**3. Health Checks**
```php
// Add a health endpoint
if ($_GET['health']) {
    echo json_encode([
        'status' => 'ok',
        'version' => '1.0.0',
        'timestamp' => time(),
    ]);
    exit;
}
```

### Deployment Checklist

- [ ] Remove `<secret>` from manifest (use Shopware Store secret)
- [ ] Enable HTTPS
- [ ] Verify signature validation works
- [ ] Test with production webhook URLs
- [ ] Set up monitoring/alerting
- [ ] Document API rate limits
- [ ] Add error handling and logging
- [ ] Create rollback plan
- [ ] Test registration flow on fresh shop
- [ ] Validate manifest with `bin/console app:validate`

---

## Part 9: Real-World App Ideas (Inspiration)

Now that you understand apps, here are real-world examples:

### 1. **Email Marketing Integration**
- **Webhooks:** Customer registration, order placed
- **API Calls:** Fetch customer lists, update tags
- **Example:** Mailchimp, SendGrid, Klaviyo integrations

### 2. **Shipping Provider Integration**
- **Webhooks:** Order placed, order shipped
- **API Calls:** Create shipment labels, update tracking
- **Example:** DHL, UPS, FedEx apps

### 3. **Analytics Dashboard**
- **Webhooks:** All order and product events
- **API Calls:** Fetch historical data
- **Example:** External BI tools, custom dashboards

### 4. **Product Recommendation Engine** (Our Day 7 project!)
- **Webhooks:** Product viewed, order placed
- **API Calls:** Update product recommendations
- **Example:** AI-powered recommendation service

### 5. **Inventory Sync**
- **Webhooks:** Order placed, stock updated
- **API Calls:** Update product stock levels
- **Example:** Sync with warehouse management systems

---

## Summary & Key Takeaways

### What You've Learned

✅ **App Architecture**: How Apps differ from Plugins  
✅ **Manifest Structure**: Meta info, permissions, webhooks, setup  
✅ **Registration Flow**: 3-step handshake for API access  
✅ **Webhook Handling**: Receiving and processing events  
✅ **API Authentication**: OAuth2 client credentials flow  
✅ **Admin API Usage**: Fetching and updating Shopware data  
✅ **Security**: Signature verification and HTTPS  
✅ **Best Practices**: Error handling, logging, performance  

### Decision Matrix: App vs Plugin

| Your Need | Choose... |
|-----------|-----------|
| External service integration | **App** |
| Serve multiple shops | **App** |
| Private business logic | **App** |
| Non-PHP language | **App** |
| Direct database access | **Plugin** |
| Performance-critical feature | **Plugin** |
| Complex Symfony services | **Plugin** |
| Shop-specific customization | **Plugin** |

### Next Steps

**Immediate Practice:**
1. ✅ Extend OrderLogger to log to a real database
2. ✅ Add more webhook events (customer.login, product.written)
3. ✅ Implement error handling and retry logic

**Week 2 Projects:**
1. Build a simple shipping tracker app
2. Create a product sync app (external catalog)
3. Implement our Day 7 GoTo Webinar App

**Advanced Topics:**
- App Scripts (Twig templates in apps)
- Custom fields via apps
- Admin modules (iframes in Shopware Admin)
- Payment provider apps

---

## Additional Resources

**Official Documentation:**
- [App Base Guide](https://developer.shopware.com/docs/guides/plugins/apps/app-base-guide.html)
- [App Concept](https://developer.shopware.com/docs/concepts/extensions/apps-concept.html)
- [Webhook Reference](https://developer.shopware.com/docs/guides/plugins/apps/webhook.html)
- [Admin API Docs](https://shopware.stoplight.io/docs/admin-api)

**Shopware Hub Learning:**
- [Understanding the App System](https://hub.shopware.com/learn/unit/understanding-the-app-system)
- [App Development Guide](https://www.shopware.com/en/app-development/)

**Community:**
- [Shopware Forum - Apps](https://forum.shopware.com/)
- [Store Apps Showcase](https://store.shopware.com/en/apps/)
- [GitHub Examples](https://github.com/shopware/app-php-sdk)

---

## Troubleshooting Common Issues

### App Not Installing
```bash
# Validate first
bin/console app:validate OrderLogger

# Check folder name matches manifest name
ls custom/apps/OrderLogger/manifest.xml

# Clear cache
bin/console cache:clear
```

### Webhooks Not Firing
- Check webhook URLs are publicly accessible (use ngrok)
- Verify permissions in manifest match webhook data
- Check Shopware logs: `var/log/shopware.log`
- Test with RequestBin first

### Registration Failing
- Verify `<secret>` matches in manifest and registration code
- Check ngrok/server is running
- Look at registration logs
- Ensure signature calculation is correct

### API Calls Failing
- Verify access token is valid (not expired)
- Check permissions in manifest
- Use correct API endpoints (`/api/search/order`, not `/api/order`)
- Validate JSON request format

### Signature Verification Issues
```php
// Debug signature mismatch
error_log("Provided: " . $providedSignature);
error_log("Expected: " . $expectedSignature);
error_log("Payload: " . $payload);
error_log("Secret: " . $shopSecret);
```

---

**Congratulations! You now understand Shopware Apps and when to use them instead of Plugins!** 🎉

This knowledge is crucial for modern Shopware development, especially when building integrations with external services or SaaS products. Apps are the future of the Shopware ecosystem, and you're now equipped to build them!

**Time to practice:** Pick one of the real-world app ideas and start building! 🚀
