# GotoWebinarGoogleSheetsExport - Technical Documentation

**Version:** 1.1.0  
**Last Updated:** January 7, 2026  
**Target Audience:** Developers, DevOps, Technical Administrators

---

## Table of Contents

1. [System Architecture](#1-system-architecture)
2. [Data Flow](#2-data-flow)
3. [Technical Decisions & Rationale](#3-technical-decisions--rationale)
4. [Database Schema](#4-database-schema)
5. [API Integration](#5-api-integration)
6. [Security Implementation](#6-security-implementation)
7. [Performance Considerations](#7-performance-considerations)
8. [Error Handling Strategy](#8-error-handling-strategy)
9. [Testing Approach](#9-testing-approach)
10. [Deployment & Operations](#10-deployment--operations)
11. [Extending the Plugin](#11-extending-the-plugin)

---

## 1. System Architecture

### 1.1 High-Level Architecture

```
┌──────────────────────────────────────────────────────────────┐
│                         Shopware 6                           │
│                                                              │
│  ┌────────────────┐        ┌─────────────────────────────┐   │
│  │  Storefront    │        │  Administration Panel       │   │
│  │  (Customer)    │        │  - Dashboard                │   │
│  └────────┬───────┘        │  - Configuration            │   │
│           │                │  - OAuth Setup              │   │
│           │ Place Order    │  - Manual Export            │   │
│           ▼                └───────────┬───────────-─────┘   │
│  ┌────────────────────────────────────┴──────────────-───┐   │
│  │         GotoWebinarGoogleSheetsExport Plugin           │   │
│  │                                                       │   │
│  │  ┌──────────────┐  ┌──────────────┐  ┌─────────────┐  │   │
│  │  │ OrderPlaced  │  │ Scheduled    │  │ Admin API   │  │   │
│  │  │ Subscriber   │  │ Task Handler │  │ Controller  │  │   │
│  │  └──────┬───────┘  └──────┬───────┘  └──────┬──────┘  │   │
│  │         │                  │                  │       │   │
│  │         │  Creates Pending Export Logs        │       │   │
│  │         ▼                  ▼                  ▼       │   │
│  │  ┌──────────────────────────────────────────────────┐ │   │
│  │  │         OrderExportService                       │ │   │
│  │  │  - Extract order data                            │ │   │
│  │  │  - Filter by category                            │ │   │
│  │  │  - Create export logs                            │ │   │
│  │  └──────────────────┬───────────────────────────────┘ │   │
│  │                     │                                 │   │
│  │                     ▼                                 │   │
│  │  ┌──────────────────────────────────────────────────┐ │   │
│  │  │         GoogleSheetsService                      │ │   │
│  │  │  - OAuth2 authentication                         │ │   │
│  │  │  - Token management                              │ │   │
│  │  │  - Batch API calls                               │ │   │
│  │  └──────────────────┬───────────────────────────────┘ │   │
│  │                     │                                 │   │
│  └─────────────────────┼─────────────────────────────────┘   │
│                        │                                     │
│  ┌─────────────────────┴─────────────────────────────────┐   │
│  │         Database (MySQL/MariaDB)                      │   │
│  │  - gotowebinar_order_export table                     │   │
│  │  - system_config (OAuth tokens)                       │   │
│  └──────────────────────┬────────────────────────────────┘   │
└─────────────────────────┼────────────────────────────────────┘
                          │ HTTPS
                          ▼
┌────────────────────────────────────────────────────────────────┐
│                     Google Cloud Platform                      │
│                                                                │
│  ┌──────────────────┐          ┌────────────────────────────┐  │
│  │  OAuth 2.0       │          │  Google Sheets API         │  │
│  │  Authorization   │◄─────────┤  - Append rows             │  │
│  │  Server          │          │  - Batch operations        │  │
│  └──────────────────┘          └────────────────────────────┘  │
└──────────────────────────────────┬─────────────────────────────┘
                                   │
                                   ▼
                        ┌──────────────────────┐
                        │  Google Sheets       │
                        │  User's Spreadsheet  │
                        └──────────────────────┘
```

### 1.2 Component Interaction Sequence

**Scenario: Order Placement**

```
Customer → Shopware Storefront: Place order with Webinar product
Shopware → OrderPlacedSubscriber: Trigger CheckoutOrderPlacedEvent
OrderPlacedSubscriber → CategoryFilterService: Is product in monitored category?
CategoryFilterService → OrderPlacedSubscriber: Yes
OrderPlacedSubscriber → OrderExportService: Create pending export log
OrderExportService → Database: INSERT into gotowebinar_order_export (status='pending')
```

**Scenario: Scheduled Export**

```
Cron → Shopware: Trigger scheduled tasks
Shopware → ExportOrdersTaskHandler: Execute export task
ExportOrdersTaskHandler → OrderExportService: Get pending exports (batch=50)
OrderExportService → Database: SELECT ... WHERE status='pending' LIMIT 50
OrderExportService → GoogleSheetsService: appendRows(data)
GoogleSheetsService → Google OAuth: Refresh access token (if expired)
GoogleSheetsService → Google Sheets API: batchUpdate(append rows)
Google Sheets API → GoogleSheetsService: Success response
GoogleSheetsService → OrderExportService: Return success
OrderExportService → Database: UPDATE ... SET status='success', exported_at=NOW()
```

---

## 2. Data Flow

### 2.1 Order Data Extraction Flow

```php
// Pseudocode representation of data flow

Order (OrderEntity)
  ├── orderCustomer (OrderCustomerEntity)
  │     ├── firstName: string
  │     ├── lastName: string
  │     └── email: string
  ├── orderNumber: string
  ├── salesChannel (SalesChannelEntity)
  │     └── name: string
  └── lineItems (OrderLineItemCollection)
        └── OrderLineItemEntity[]
              ├── product (ProductEntity)
              │     ├── productNumber: string
              │     └── categories (CategoryCollection)
              │           └── category ids...
              └── ...

// Extraction Process:
For each lineItem in order.lineItems:
    If lineItem.product.categories contains configuredCategoryId:
        Extract:
          - customer: order.orderCustomer.{firstName, lastName, email}
          - order: order.orderNumber
          - product: lineItem.product.productNumber
          - channel: order.salesChannel.name
        
        Create OrderExportEntity with status='pending'
```

### 2.2 Export Processing Flow

```
┌────────────────────────────────────────────────────────────┐
│ Step 1: Trigger (Scheduled or Manual)                      │
└──────────────────────┬─────────────────────────────────────┘
                       ▼
┌────────────────────────────────────────────────────────────┐
│ Step 2: Fetch Pending Exports                              │
│ Query: SELECT * FROM gotowebinar_order_export              │
│        WHERE export_status = 'pending'                     │
│        LIMIT [batchSize]                                   │
└──────────────────────┬─────────────────────────────────────┘
                       ▼
┌─────────────────────────────────────────────────────–───────┐
│ Step 3: Prepare Google Sheets Rows                          │
│ Transform: OrderExportEntity[] → array[]                    │
│ Format: [                                                   │
│   ['Max', 'Mustermann', '10001', 'BW-WEB-01', 'Store', ...],│
│   ['Anna', 'Schmidt', '10002', 'BW-WEB-02', 'Store', ...],  │
│ ]                                                           │
└──────────────────────┬────────────────────────────────────–─┘
                       ▼
┌────────────────────────────────────────────────────────────┐
│ Step 4: Check OAuth Token                                  │
│ If expired: Refresh using refresh_token                    │
└──────────────────────┬─────────────────────────────────────┘
                       ▼
┌────────────────────────────────────────────────────────────┐
│ Step 5: Call Google Sheets API                             │
│ Method: spreadsheets.values.append()                       │
│ Params: {                                                  │
│   spreadsheetId: 'configuredSheetId',                      │
│   range: 'Bestellungen!A:F',                               │
│   valueInputOption: 'RAW',                                 │
│   insertDataOption: 'INSERT_ROWS',                         │
│   values: [rows from step 3]                               │
│ }                                                          │
└──────────────────────┬─────────────────────────────────────┘
                       ▼
┌────────────────────────────────────────────────────────────┐
│ Step 6: Update Export Status                               │
│ On Success:                                                │
│   UPDATE gotowebinar_order_export                          │
│   SET export_status='success',                             │
│       exported_at=NOW()                                    │
│   WHERE id IN (exported_ids)                               │
│                                                            │
│ On Error:                                                  │
│   UPDATE gotowebinar_order_export                          │
│   SET export_status='failed',                              │
│       error_message='[error details]'                      │
│   WHERE id IN (failed_ids)                                 │
└────────────────────────────────────────────────────────────┘
```

---

## 3. Technical Decisions & Rationale

### 3.1 Why Scheduled Export Instead of Real-Time?

**Decision:** Export happens via scheduled tasks (intervals) rather than immediately on order placement.

**Rationale:**
1. **Performance:** Prevents blocking the checkout process
   - Order placement is time-critical for customer experience
   - Google API calls can take 500ms-2s per request
   - Asynchronous processing doesn't delay order confirmation

2. **Reliability:** Decouples order processing from external API
   - Order succeeds even if Google Sheets is temporarily unavailable
   - Failed exports can be retried without affecting completed orders

3. **API Rate Limiting:** Batch processing reduces API calls
   - Google Sheets API: 100 requests per 100 seconds per user
   - Batching 50 rows into 1 API call vs 50 separate calls
   - Reduces likelihood of hitting rate limits

4. **Error Recovery:** Easier to manage and retry failures
   - Failed exports remain in "pending" state
   - Next scheduled run automatically retries
   - Manual trigger option for immediate resolution

**Trade-off:** Small delay (up to interval duration) between order and export
- Mitigation: Configurable intervals (as low as 15 minutes)
- Acceptable for reporting/analytics use cases

### 3.2 Why OAuth2 Instead of Service Account?

**Decision:** Use OAuth2 user authentication instead of Google Service Account.

**Rationale:**
1. **Simpler Setup:** No JSON key file management
   - OAuth requires only Client ID and Secret (visible in UI)
   - Service Account requires downloading and securely storing JSON key file
   - Less risk of accidentally exposing credentials

2. **User Control:** Sheet owner maintains control
   - User explicitly grants permission via Google consent screen
   - Can revoke access anytime from Google account settings
   - Doesn't require sharing sheet with service account email

3. **Transparent Permissions:** Clear audit trail
   - Edits appear as the authenticated user in Google Sheets history
   - Easier to identify which application made changes
   - Better compliance with data access policies

**Trade-off:** Tokens expire (refresh token needs re-authorization periodically)
- Mitigation: Refresh tokens typically valid for 6+ months
- Admin receives clear error messages when re-authorization needed
- Simple re-connection flow via admin panel

### 3.3 Why Each Product = Separate Row?

**Decision:** Export each product in an order as a separate row, even if from same order.

**Rationale:**
1. **Data Analysis:** Easier to analyze product-level data
   - Each row represents one product purchase
   - Simple filtering, sorting, pivot tables in Google Sheets
   - Count total products sold (not orders)

2. **Reporting:** Cleaner reports
   - "How many BW-WEB-001 were sold?" → Count rows with that product number
   - No need to parse comma-separated product lists

3. **CRM Integration:** Each row is a complete record
   - Can be imported into CRM systems one-to-one
   - Each customer-product combination is independently tracked

4. **Data Integrity:** Avoids complex string concatenation
   - No issues with delimiter characters in product names
   - No arbitrary limits on products per order

**Trade-off:** More rows in sheet, some data duplication (customer info)
- Mitigation: Google Sheets can handle millions of rows
- Duplication is minimal (6 fields × duplicate rate)
- Benefits outweigh storage concerns

### 3.4 Why Local Database Table Instead of Google Sheets as Source?

**Decision:** Maintain local `gotowebinar_order_export` table to track exports.

**Rationale:**
1. **Performance:** Fast queries without external API calls
   - Admin dashboard loads instantly (no API latency)
   - CSV export doesn't require Google Sheets API read
   - "Recent exports" table queries local database

2. **Reliability:** Works even if Google Sheets is unavailable
   - Can still view export history
   - Can identify pending exports
   - Doesn't rely on external service for status

3. **Auditing:** Complete export history with timestamps
   - Track when each export was attempted
   - Store error messages for failed exports
   - Useful for debugging and compliance

4. **Duplicate Detection:** Prevents accidental re-exports
   - Can check if order/product already exported
   - Configurable duplicate policy (allow/prevent)

**Trade-off:** Additional database storage
- Mitigation: Table is relatively small (10-100k rows typical)
- Can be archived/cleaned periodically
- Negligible impact on database size

### 3.5 Why Allow Duplicates?

**Decision:** Default setting allows re-exporting same order/product combination.

**Rationale:**
1. **Error Recovery:** User can retry failed exports
   - If export failed, user can manually re-trigger
   - Doesn't require manual database cleanup

2. **Data Correction:** Can re-export if data was modified
   - If customer updates order information
   - If admin needs to refresh data in Google Sheets

3. **Flexibility:** User decides on data management
   - Can enable duplicate prevention if desired
   - Can manually deduplicate in Google Sheets if needed

**Trade-off:** Potential duplicate rows in Google Sheets
- Mitigation: Configurable via admin panel
- Google Sheets can remove duplicates (Data → Remove duplicates)
- Most use cases benefit from idempotent exports

### 3.6 Why Log Errors Instead of Retry Queue?

**Decision:** Log errors in database and system logs, retry on next scheduled run. No dedicated retry queue.

**Rationale:**
1. **Simplicity:** Fewer moving parts
   - No additional queue infrastructure (Redis, RabbitMQ, etc.)
   - Easier to understand and maintain
   - Reduces deployment complexity

2. **Automatic Retry:** Scheduled task naturally retries pending exports
   - Failed exports remain in "pending" state
   - Next scheduled run (e.g., hourly) automatically retries
   - No need for separate retry worker process

3. **Resource Efficiency:** Doesn't consume resources on repeated failures
   - Exponential backoff built-in via scheduled intervals
   - Doesn't hammer failed endpoints (e.g., if Google is down)
   - Rate limiting is naturally respected

4. **Operational Simplicity:** Clear status in database
   - Admins can see failed exports in dashboard
   - Manual intervention possible for persistent failures
   - Easy to identify patterns (all failing = credentials issue)

**Trade-off:** No immediate retry for transient failures
- Mitigation: Configurable intervals (down to 15 minutes)
- Manual export button for immediate retry
- Acceptable delay for reporting/analytics use case

---

## 4. Database Schema

### 4.1 Table: `gotowebinar_order_export`

**Purpose:** Track all order exports (pending, successful, failed)

```sql
CREATE TABLE `gotowebinar_order_export` (
    -- Primary Key
    `id` BINARY(16) NOT NULL,
    
    -- Order References
    `order_id` BINARY(16) NOT NULL,
    `order_number` VARCHAR(255) NOT NULL,
    
    -- Product References
    `product_id` BINARY(16) NOT NULL,
    `product_number` VARCHAR(255) NOT NULL,
    
    -- Customer Data (denormalized for export)
    `customer_first_name` VARCHAR(255) NOT NULL,
    `customer_last_name` VARCHAR(255) NOT NULL,
    `customer_email` VARCHAR(255) NOT NULL,
    
    -- Sales Channel
    `sales_channel_name` VARCHAR(255) NOT NULL,
    
    -- Export Status
    `exported_at` DATETIME(3) NULL,
    `google_sheet_row_id` VARCHAR(255) NULL,
    `export_status` VARCHAR(50) NOT NULL DEFAULT 'pending',
    `error_message` TEXT NULL,
    
    -- Timestamps
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    
    PRIMARY KEY (`id`),
    
    -- Indexes for performance
    KEY `idx_order_id` (`order_id`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_exported_at` (`exported_at`),
    KEY `idx_export_status` (`export_status`),
    KEY `idx_created_at` (`created_at`)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Field Details:**

| Field | Type | Purpose | Nullable |
|-------|------|---------|----------|
| `id` | BINARY(16) | UUID primary key | No |
| `order_id` | BINARY(16) | Foreign key to `order` table | No |
| `order_number` | VARCHAR(255) | Human-readable order number | No |
| `product_id` | BINARY(16) | Foreign key to `product` table | No |
| `product_number` | VARCHAR(255) | Product SKU/article number | No |
| `customer_first_name` | VARCHAR(255) | Customer first name (denormalized) | No |
| `customer_last_name` | VARCHAR(255) | Customer last name (denormalized) | No |
| `customer_email` | VARCHAR(255) | Customer email (denormalized) | No |
| `sales_channel_name` | VARCHAR(255) | Sales channel name (denormalized) | No |
| `exported_at` | DATETIME(3) | Timestamp when successfully exported | Yes |
| `google_sheet_row_id` | VARCHAR(255) | Google Sheets row identifier (future use) | Yes |
| `export_status` | VARCHAR(50) | Status: 'pending', 'success', 'failed' | No |
| `error_message` | TEXT | Error details if export failed | Yes |
| `created_at` | DATETIME(3) | Record creation timestamp | No |
| `updated_at` | DATETIME(3) | Record last update timestamp | Yes |

**Index Rationale:**
- `idx_order_id`: Quick lookup of all exports for an order
- `idx_product_id`: Quick lookup of all exports for a product
- `idx_exported_at`: Efficient sorting by export time (recent exports)
- `idx_export_status`: Fast filtering for pending/failed exports
- `idx_created_at`: Efficient pagination by creation time

**Data Denormalization:**
Customer and sales channel data is denormalized (copied) into this table because:
1. Export needs to reflect data at time of order placement
2. Customer/channel data may change later
3. Faster queries (no joins needed for export)
4. Export log is append-only (no updates to customer data)

### 4.2 Configuration Storage

Plugin configuration is stored in Shopware's `system_config` table:

```sql
-- Example configuration entries
INSERT INTO system_config (id, configuration_key, configuration_value, created_at) VALUES
(UUID(), 'GotoWebinarGoogleSheetsExport.config.enabled', '{"_value": true}', NOW()),
(UUID(), 'GotoWebinarGoogleSheetsExport.config.categoryId', '{"_value": "01234567890"}', NOW()),
(UUID(), 'GotoWebinarGoogleSheetsExport.config.googleSheetId', '{"_value": "1Bx..."}', NOW()),
(UUID(), 'GotoWebinarGoogleSheetsExport.config.googleClientId', '{"_value": "123..."}', NOW()),
(UUID(), 'GotoWebinarGoogleSheetsExport.config.googleClientSecret', '{"_value": "GOCSPX..."}', NOW()),
(UUID(), 'GotoWebinarGoogleSheetsExport.config.googleRefreshToken', '{"_value": "1//..."}', NOW());
```

**Security Note:** Sensitive values (Client Secret, Refresh Token) should be encrypted at rest. Consider using Shopware's `EncryptedConfigValue` for sensitive fields.

---

## 5. API Integration

### 5.1 Google Sheets API Overview

**API Version:** v4  
**Base URL:** `https://sheets.googleapis.com/v4/spreadsheets`

**Required OAuth Scopes:**
- `https://www.googleapis.com/auth/spreadsheets` - Full spreadsheet access

### 5.2 OAuth 2.0 Flow Implementation

**Step 1: Generate Authorization URL**

```php
// In GoogleSheetsService::getAuthorizationUrl()
$client = new Google_Client();
$client->setClientId($this->getClientId());
$client->setClientSecret($this->getClientSecret());
$client->setRedirectUri($this->getRedirectUri());
$client->addScope(Google_Service_Sheets::SPREADSHEETS);
$client->setAccessType('offline'); // Get refresh token
$client->setPrompt('consent'); // Force consent screen

$authUrl = $client->createAuthUrl();
return $authUrl;
```

**Step 2: Exchange Authorization Code for Tokens**

```php
// In GoogleSheetsService::authenticate($authCode)
$client = new Google_Client();
// ... configure client ...

$response = $client->fetchAccessTokenWithAuthCode($authCode);

// Response contains:
// {
//   "access_token": "ya29.a0...",
//   "expires_in": 3600,
//   "refresh_token": "1//...",  // Only on first authorization
//   "scope": "https://www.googleapis.com/auth/spreadsheets",
//   "token_type": "Bearer"
// }

// Store refresh_token in system config
$this->systemConfigService->set(
    'GotoWebinarGoogleSheetsExport.config.googleRefreshToken',
    $response['refresh_token']
);

return $response;
```

**Step 3: Refresh Access Token**

```php
// In GoogleSheetsService::refreshAccessToken()
$client = new Google_Client();
// ... configure client ...

$refreshToken = $this->systemConfigService->get(
    'GotoWebinarGoogleSheetsExport.config.googleRefreshToken'
);

$client->fetchAccessTokenWithRefreshToken($refreshToken);
$accessToken = $client->getAccessToken();

// New access token is valid for ~1 hour
return $accessToken['access_token'];
```

### 5.3 Appending Rows to Google Sheets

**API Endpoint:** `spreadsheets.values.append`

```php
// In GoogleSheetsService::appendRows($sheetId, $worksheetName, $rows)
$client = new Google_Client();
$client->setAccessToken($this->getValidAccessToken()); // Refreshes if needed

$service = new Google_Service_Sheets($client);

$range = $worksheetName . '!A:F'; // Columns A through F

$body = new Google_Service_Sheets_ValueRange([
    'values' => $rows // 2D array: [['Max', 'Mustermann', ...], ...]
]);

$params = [
    'valueInputOption' => 'RAW', // Don't interpret formulas
    'insertDataOption' => 'INSERT_ROWS' // Insert new rows
];

$result = $service->spreadsheets_values->append(
    $sheetId,
    $range,
    $body,
    $params
);

// Result contains:
// {
//   "spreadsheetId": "...",
//   "tableRange": "Bestellungen!A1:F1",
//   "updates": {
//     "spreadsheetId": "...",
//     "updatedRange": "Bestellungen!A2:F3",
//     "updatedRows": 2,
//     "updatedColumns": 6,
//     "updatedCells": 12
//   }
// }

return $result;
```

**Error Handling:**

```php
try {
    $result = $service->spreadsheets_values->append(...);
} catch (Google_Service_Exception $e) {
    // API errors (404, 403, etc.)
    $error = json_decode($e->getMessage(), true);
    $code = $e->getCode();
    
    if ($code === 404) {
        throw new \Exception('Sheet not found. Check Sheet ID.');
    } elseif ($code === 403) {
        throw new \Exception('Permission denied. Re-authenticate.');
    } elseif ($code === 429) {
        throw new \Exception('Rate limit exceeded. Retry later.');
    }
    
    throw $e;
} catch (\Exception $e) {
    // Network errors, timeouts, etc.
    throw $e;
}
```

### 5.4 API Rate Limits & Quotas

**Google Sheets API Quotas (as of 2025):**
- **Read requests:** 300 per minute per user
- **Write requests:** 300 per minute per user
- **Per-user rate limit:** 100 requests per 100 seconds per user

**Batch Processing Strategy:**
```php
// Instead of 50 separate API calls:
foreach ($exports as $export) {
    $this->googleSheetsService->appendRow($sheetId, $worksheet, [$export]);
}

// Use ONE API call for all 50 rows:
$rows = array_map(function($export) {
    return $this->formatExportRow($export);
}, $exports);

$this->googleSheetsService->appendRows($sheetId, $worksheet, $rows);
```

**Benefits:**
- 50× fewer API calls
- 50× faster execution
- Much lower risk of hitting rate limits

---

## 6. Security Implementation

### 6.1 OAuth Token Storage

**Sensitive Configuration Fields:**
- `googleClientSecret` - OAuth client secret
- `googleRefreshToken` - Long-lived refresh token

**Security Measures:**

1. **Encrypted Storage** (Recommended Implementation):
```php
// In config.xml, mark fields as encrypted
<input-field type="password">
    <name>googleClientSecret</name>
    <!-- Shopware will encrypt when storing -->
</input-field>
```

2. **Access Control:**
- Only admin users can access plugin configuration
- Shopware's ACL (Access Control List) enforced
- No API exposure of sensitive config values

3. **Token Rotation:**
- Access tokens expire after 1 hour (automatic refresh)
- Refresh tokens should be rotated periodically (future enhancement)

### 6.2 Admin API Endpoint Security

**All admin API endpoints require authentication:**

```php
// In AdminApiController
use Shopware\Core\Framework\Routing\Annotation\RouteScope;

/**
 * @RouteScope(scopes={"api"})
 * @Route("/api/_action/gotowebinar-sheets/export/manual", ...)
 */
public function manualExport(Request $request, Context $context): JsonResponse
{
    // Context contains authenticated admin user
    // Shopware automatically validates JWT token
    
    // Additional permission check (optional):
    if (!$this->hasPermission($context, 'gotowebinar_sheets:export')) {
        throw new PermissionDeniedException();
    }
    
    // ... export logic ...
}
```

### 6.3 Data Sanitization

**Before sending to Google Sheets:**

```php
// In OrderExportService::extractExportData()
private function sanitizeValue(string $value): string
{
    // Remove potential injection attacks
    $value = strip_tags($value); // Remove HTML
    $value = str_replace(["\r", "\n"], ' ', $value); // Remove line breaks
    
    // Prevent formula injection
    if (in_array($value[0] ?? '', ['=', '+', '-', '@'])) {
        $value = "'" . $value; // Prefix with single quote
    }
    
    return $value;
}

$exportData = [
    'customer_first_name' => $this->sanitizeValue($order->orderCustomer->firstName),
    'customer_last_name' => $this->sanitizeValue($order->orderCustomer->lastName),
    // ...
];
```

**Why This Matters:**
- Prevents CSV/formula injection attacks
- Protects against XSS if data is displayed in web views
- Ensures data integrity in Google Sheets

### 6.4 HTTPS Enforcement

All communications with Google APIs use HTTPS:
- OAuth authorization: `https://accounts.google.com/o/oauth2/auth`
- Token exchange: `https://oauth2.googleapis.com/token`
- Sheets API: `https://sheets.googleapis.com/v4/...`

**Verification:**
```php
// Google_Client enforces HTTPS by default
// No additional configuration needed
```

### 6.5 Security Checklist

- [ ] OAuth credentials not hardcoded (stored in database)
- [ ] Refresh token encrypted at rest
- [ ] Admin API endpoints require authentication
- [ ] Data sanitized before export
- [ ] No sensitive data in logs (mask tokens)
- [ ] HTTPS enforced for all Google API calls
- [ ] Error messages don't expose system details
- [ ] Google Cloud project has restricted API access

---

## 7. Performance Considerations

### 7.1 Database Query Optimization

**Efficient Pending Export Query:**

```php
// Optimized query with pagination
$criteria = new Criteria();
$criteria->addFilter(new EqualsFilter('exportStatus', 'pending'));
$criteria->setLimit($batchSize); // e.g., 50
$criteria->addSorting(new FieldSorting('createdAt', 'ASC')); // FIFO
$criteria->addAssociation('order'); // Avoid N+1 queries if needed

$pendingExports = $this->orderExportRepository->search($criteria, $context);
```

**Index Usage:**
- `idx_export_status` - Fast filtering by status
- `idx_created_at` - Efficient sorting

**Expected Performance:**
- 50 records: < 10ms query time
- 1000 records: < 50ms query time

### 7.2 Batch Processing

**Configurable Batch Size:**
- Default: 50 rows per scheduled run
- Max recommended: 100 rows per batch
- Adjustable via admin configuration

**Why Batch Processing?**
1. **API Efficiency:** One API call for 50 rows vs 50 separate calls
2. **Rate Limiting:** Stays well under 100 requests per 100 seconds
3. **Timeout Protection:** Processing 50 rows typically takes < 30 seconds
4. **Memory Management:** Limited memory footprint

**Batch Size Guidelines:**
- **Low order volume** (<100/day): 50 rows, hourly interval
- **Medium volume** (100-500/day): 50 rows, every 15 minutes
- **High volume** (500+/day): 100 rows, every 15 minutes

### 7.3 Caching Strategy

**Access Token Caching:**
```php
// In GoogleSheetsService
private $accessToken;
private $tokenExpiry;

public function getValidAccessToken(): string
{
    if ($this->accessToken && time() < $this->tokenExpiry) {
        return $this->accessToken; // Use cached token
    }
    
    // Token expired, refresh
    $this->accessToken = $this->refreshAccessToken();
    $this->tokenExpiry = time() + 3500; // 58 minutes (buffer before 60min expiry)
    
    return $this->accessToken;
}
```

**Benefits:**
- Reduces token refresh API calls
- Faster export processing
- Lower latency

**Category Tree Caching:**
```php
// In CategoryFilterService
private $categoryTreeCache = [];

public function productMatchesCategory(ProductEntity $product, string $targetCategoryId): bool
{
    $cacheKey = $product->getId();
    
    if (isset($this->categoryTreeCache[$cacheKey])) {
        return in_array($targetCategoryId, $this->categoryTreeCache[$cacheKey]);
    }
    
    $categoryIds = $this->getCategoryTreeForProduct($product);
    $this->categoryTreeCache[$cacheKey] = $categoryIds;
    
    return in_array($targetCategoryId, $categoryIds);
}
```

### 7.4 Scheduled Task Optimization

**Task Execution Time:**
- Target: < 30 seconds per run
- Timeout: 60 seconds (Symfony default)
- Batch size: Adjust to stay under timeout

**Preventing Overlapping Runs:**
```php
// Shopware's scheduled task system prevents overlapping by default
// Task won't run again until previous execution completes

// Additional safety check (optional):
public function run(): void
{
    $lockKey = 'gotowebinar_export_lock';
    
    if (!$this->lock->acquire($lockKey)) {
        $this->logger->info('Export already running, skipping');
        return;
    }
    
    try {
        $this->processExports();
    } finally {
        $this->lock->release($lockKey);
    }
}
```

### 7.5 Performance Metrics

**Target Performance:**
- Order placement: +0ms overhead (asynchronous logging)
- Pending export creation: < 50ms
- Scheduled export (50 rows): < 10 seconds
- Manual export (50 rows): < 10 seconds
- Admin dashboard load: < 500ms
- CSV download (100 rows): < 1 second

**Monitoring:**
```php
// Add timing logs
$startTime = microtime(true);
$this->googleSheetsService->appendRows($sheetId, $worksheet, $rows);
$duration = microtime(true) - $startTime;

$this->logger->info('Google Sheets export completed', [
    'rows' => count($rows),
    'duration_ms' => round($duration * 1000),
    'rows_per_second' => round(count($rows) / $duration)
]);
```

---

## 8. Error Handling Strategy

### 8.1 Error Categories & Responses

**1. Configuration Errors** (user fixable)
- Missing OAuth credentials
- Invalid Sheet ID
- Category not selected

**Response:**
- Clear error message in admin UI
- Prevent export execution
- Link to configuration page

**2. Authentication Errors** (user action required)
- OAuth token expired
- Insufficient permissions
- Invalid refresh token

**Response:**
- Mark exports as failed with "Re-authenticate" message
- Show "Connect to Google" button in admin
- Stop scheduled exports until fixed

**3. API Errors** (transient or permanent)
- Network timeout
- Google Sheets API unavailable
- Rate limit exceeded

**Response:**
- Log error with details
- Keep export in "pending" state (will retry)
- Send admin notification (future enhancement)

**4. Data Errors** (bug or data corruption)
- Missing order data
- Invalid product reference
- Database constraint violation

**Response:**
- Log error with full context
- Mark specific export as failed
- Continue processing other exports

### 8.2 Error Logging

**Shopware Monolog Integration:**

```php
// In OrderExportService
$this->logger->error('Failed to export order', [
    'order_number' => $order->getOrderNumber(),
    'product_number' => $product->getProductNumber(),
    'error' => $exception->getMessage(),
    'trace' => $exception->getTraceAsString()
]);
```

**Log Levels:**
- `DEBUG`: Detailed execution flow (development only)
- `INFO`: Successful exports, normal operations
- `WARNING`: Non-critical issues (rate limiting, retries)
- `ERROR`: Export failures, API errors
- `CRITICAL`: System failures (database errors)

**Log File Location:**
- Production: `var/log/prod.log`
- Development: `var/log/dev.log`

### 8.3 User-Facing Error Messages

**Example Error Messages:**

**Good (Actionable):**
```
❌ Export failed: Unable to connect to Google Sheets.
   Please check your internet connection and try again.
   [Retry] [View Logs]
```

**Bad (Vague):**
```
❌ Error: 500 Internal Server Error
```

**Implementation:**
```php
// In AdminApiController
try {
    $result = $this->orderExportService->exportPending();
    return new JsonResponse(['success' => true, 'exported' => $result]);
} catch (GoogleAuthenticationException $e) {
    return new JsonResponse([
        'success' => false,
        'error' => 'Authentication failed. Please reconnect to Google.',
        'action' => 'reauthenticate'
    ], 401);
} catch (RateLimitException $e) {
    return new JsonResponse([
        'success' => false,
        'error' => 'Google API rate limit reached. Please try again in a few minutes.',
        'action' => 'retry_later'
    ], 429);
} catch (\Exception $e) {
    $this->logger->error('Unexpected export error', ['exception' => $e]);
    return new JsonResponse([
        'success' => false,
        'error' => 'An unexpected error occurred. Please contact support.',
        'action' => 'contact_support'
    ], 500);
}
```

### 8.4 Retry Strategy

**Automatic Retry (via Scheduled Tasks):**
```
Order fails to export at 10:00 AM
↓
Remains in "pending" status
↓
Scheduled task runs at 11:00 AM (hourly interval)
↓
Retries export automatically
↓
Success or fails again (logged)
```

**Manual Retry (via Admin Button):**
- User clicks "Export Now" button
- All pending exports are attempted immediately
- User sees results in real-time

**Exponential Backoff (Future Enhancement):**
```php
// Track retry attempts
if ($export->getRetryCount() > 5) {
    $export->setExportStatus('failed_permanent');
    $this->logger->error('Export failed permanently after 5 retries', [
        'export_id' => $export->getId()
    ]);
    return;
}

$export->incrementRetryCount();
```

---

## 9. Testing Approach

### 9.1 Unit Tests

**Test Coverage Areas:**
1. **CategoryFilterService**
   - Product category matching logic
   - Nested category tree traversal
   - Edge cases (product without categories)

2. **OrderExportService**
   - Data extraction from order entities
   - Export log creation
   - Status updates

3. **CsvExportService**
   - CSV formatting
   - Header generation
   - Special character handling

**Example Unit Test:**

```php
// tests/Unit/Service/CategoryFilterServiceTest.php
namespace GotoWebinar\GoogleSheetsExport\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use GotoWebinar\GoogleSheetsExport\Service\CategoryFilterService;

class CategoryFilterServiceTest extends TestCase
{
    public function testProductMatchesDirectCategory(): void
    {
        $service = new CategoryFilterService(/* mock repos */);
        
        $product = $this->createProductWithCategories(['cat-123']);
        
        $result = $service->productMatchesCategory($product, 'cat-123');
        
        $this->assertTrue($result);
    }
    
    public function testProductMatchesParentCategory(): void
    {
        // Test nested category matching
        // Product in "Webinars > Sailing" should match "Webinars"
        
        $service = new CategoryFilterService(/* mock repos */);
        
        // Mock category tree: parent-cat -> child-cat
        // Product is in child-cat
        $product = $this->createProductWithCategories(['child-cat']);
        
        $result = $service->productMatchesCategory($product, 'parent-cat');
        
        $this->assertTrue($result);
    }
}
```

### 9.2 Integration Tests

**Test Coverage Areas:**
1. **OrderPlacedSubscriber**
   - Full order placement flow
   - Export log creation
   - Multiple products in one order

2. **ExportOrdersTaskHandler**
   - Scheduled export execution
   - Batch processing
   - Status updates

3. **GoogleSheetsService** (with mocks)
   - OAuth flow
   - Token refresh logic
   - API call formatting

**Example Integration Test:**

```php
// tests/Integration/Subscriber/OrderPlacedSubscriberTest.php
namespace GotoWebinar\GoogleSheetsExport\Tests\Integration\Subscriber;

use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use PHPUnit\Framework\TestCase;

class OrderPlacedSubscriberTest extends TestCase
{
    use IntegrationTestBehaviour;
    
    public function testOrderPlacedCreatesExportLog(): void
    {
        // Create test category "GotoWebinar Webinar"
        $categoryId = $this->createTestCategory('GotoWebinar Webinar');
        
        // Create test product in that category
        $productId = $this->createTestProduct($categoryId);
        
        // Configure plugin
        $this->setPluginConfig([
            'enabled' => true,
            'categoryId' => $categoryId
        ]);
        
        // Place order
        $orderId = $this->placeTestOrder([$productId]);
        
        // Verify export log created
        $exports = $this->getExportLogs($orderId);
        
        $this->assertCount(1, $exports);
        $this->assertEquals('pending', $exports[0]->getExportStatus());
        $this->assertEquals($productId, $exports[0]->getProductId());
    }
}
```

### 9.3 Manual Testing Checklist

**Pre-Deployment Testing:**

- [ ] **Installation**
  - [ ] Plugin installs without errors
  - [ ] Database tables created correctly
  - [ ] Configuration page accessible

- [ ] **Configuration**
  - [ ] Can set all config fields
  - [ ] Config saves correctly
  - [ ] OAuth connection flow works
  - [ ] Validation errors display properly

- [ ] **Order Export**
  - [ ] Place order with matching category product
  - [ ] Verify export log created with status "pending"
  - [ ] Trigger manual export
  - [ ] Verify data appears in Google Sheets
  - [ ] Verify export status changes to "success"

- [ ] **Multiple Products**
  - [ ] Place order with 2+ products in monitored category
  - [ ] Verify separate rows created in Google Sheets
  - [ ] Verify all products exported

- [ ] **Scheduled Export**
  - [ ] Set export interval to "Every 15 minutes"
  - [ ] Wait for scheduled task to run
  - [ ] Verify pending exports are processed
  - [ ] Check Shopware logs for task execution

- [ ] **CSV Export**
  - [ ] Click "Download CSV" button
  - [ ] Verify CSV file downloads
  - [ ] Open CSV and verify data format
  - [ ] Check last 100 entries included

- [ ] **Error Handling**
  - [ ] Test with invalid Sheet ID
  - [ ] Test with revoked OAuth token
  - [ ] Test with network disconnected
  - [ ] Verify error messages are clear

- [ ] **Duplicate Handling**
  - [ ] Export same order twice (manual export)
  - [ ] Verify duplicate rows created (if allowed)
  - [ ] Toggle duplicate prevention setting
  - [ ] Verify duplicates prevented when disabled

---

## 10. Deployment & Operations

### 10.1 Deployment Checklist

**Pre-Deployment:**
- [ ] Run unit tests: `bin/phpunit --testsuite Unit`
- [ ] Run integration tests: `bin/phpunit --testsuite Integration`
- [ ] Test on staging environment
- [ ] Backup production database
- [ ] Review Google Cloud project quotas

**Deployment Steps:**
```bash
# 1. Upload plugin files to server
scp -r GotoWebinarGoogleSheetsExport/ user@server:/path/to/shopware/custom/plugins/

# 2. SSH into server
ssh user@server
cd /path/to/shopware

# 3. Install dependencies
composer require google/apiclient
composer dump-autoload

# 4. Refresh plugins
bin/console plugin:refresh

# 5. Install plugin
bin/console plugin:install GotoWebinarGoogleSheetsExport

# 6. Activate plugin
bin/console plugin:activate GotoWebinarGoogleSheetsExport

# 7. Clear cache
bin/console cache:clear

# 8. Build administration (if needed)
bin/build-administration.sh

# 9. Verify scheduled task registered
bin/console scheduled-task:list | grep gotowebinar
```

**Post-Deployment:**
- [ ] Verify plugin appears in admin panel
- [ ] Configure plugin settings
- [ ] Test OAuth connection
- [ ] Place test order
- [ ] Trigger manual export
- [ ] Verify data in Google Sheets
- [ ] Monitor logs for errors

### 10.2 Monitoring & Maintenance

**Key Metrics to Monitor:**
1. **Export Success Rate**
   ```sql
   SELECT 
       export_status,
       COUNT(*) as count,
       COUNT(*) * 100.0 / SUM(COUNT(*)) OVER () as percentage
   FROM gotowebinar_order_export
   WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
   GROUP BY export_status;
   ```

2. **Average Export Latency**
   ```sql
   SELECT 
       AVG(TIMESTAMPDIFF(SECOND, created_at, exported_at)) as avg_latency_seconds
   FROM gotowebinar_order_export
   WHERE export_status = 'success'
       AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY);
   ```

3. **Failed Exports**
   ```sql
   SELECT 
       order_number,
       product_number,
       error_message,
       created_at
   FROM gotowebinar_order_export
   WHERE export_status = 'failed'
   ORDER BY created_at DESC
   LIMIT 10;
   ```

**Log Monitoring:**
```bash
# Watch for errors
tail -f var/log/prod.log | grep -E '(ERROR|CRITICAL)' | grep -i gotowebinar

# Monitor export activity
tail -f var/log/prod.log | grep -i 'export' | grep -i gotowebinar
```

**Health Check Command:**
```bash
# Create custom command: bin/console gotowebinar:health-check
# Checks:
# - Plugin is active
# - OAuth token is valid
# - Sheet is accessible
# - No stuck pending exports (>24 hours old)
# - Scheduled task is running

bin/console gotowebinar:health-check
```

### 10.3 Backup & Recovery

**Database Backup:**
```bash
# Backup export log table
mysqldump -u shopware -p shopware gotowebinar_order_export > gotowebinar_export_backup.sql

# Backup plugin configuration
mysqldump -u shopware -p shopware system_config --where="configuration_key LIKE 'GotoWebinarGoogleSheetsExport%'" > gotowebinar_config_backup.sql
```

**Recovery:**
```bash
# Restore export log
mysql -u shopware -p shopware < gotowebinar_export_backup.sql

# Restore configuration
mysql -u shopware -p shopware < gotowebinar_config_backup.sql
```

### 10.4 Troubleshooting Commands

```bash
# Check plugin status
bin/console plugin:list | grep GotoWebinar

# View recent exports
bin/console dbal:run-sql "SELECT * FROM gotowebinar_order_export ORDER BY created_at DESC LIMIT 10"

# Count pending exports
bin/console dbal:run-sql "SELECT COUNT(*) FROM gotowebinar_order_export WHERE export_status='pending'"

# Manually trigger scheduled task
bin/console scheduled-task:run gotowebinar.google_sheets_export

# Clear cache
bin/console cache:clear
```

---

## 11. Extending the Plugin

### 11.1 Adding Custom Export Fields

**Example: Add "Order Total" field**

**Step 1: Update Database Schema**
```sql
ALTER TABLE gotowebinar_order_export
ADD COLUMN order_total DECIMAL(10,2) NULL AFTER sales_channel_name;
```

**Step 2: Update Entity Definition**
```php
// In OrderExportDefinition.php
protected function defineFields(): FieldCollection
{
    return new FieldCollection([
        // ... existing fields ...
        
        (new FloatField('order_total', 'orderTotal'))
            ->addFlags(new Required()),
    ]);
}
```

**Step 3: Update Export Service**
```php
// In OrderExportService::extractExportData()
$exportData = [
    // ... existing fields ...
    'order_total' => $order->getAmountTotal(),
];
```

**Step 4: Update Google Sheets Export**
```php
// In GoogleSheetsService::formatRow()
$row = [
    $export->getCustomerFirstName(),
    $export->getCustomerLastName(),
    $export->getOrderNumber(),
    $export->getProductNumber(),
    $export->getSalesChannelName(),
    $export->getCustomerEmail(),
    $export->getOrderTotal(), // New field
];
```

### 11.2 Adding Custom Export Destinations

**Example: Export to CSV file on server**

**Step 1: Create New Service**
```php
// src/Service/FileExportService.php
class FileExportService
{
    public function exportToFile(array $exports, string $filename): void
    {
        $filepath = $this->getExportDirectory() . '/' . $filename;
        
        $handle = fopen($filepath, 'w');
        fputcsv($handle, $this->getHeaders());
        
        foreach ($exports as $export) {
            fputcsv($handle, $this->formatRow($export));
        }
        
        fclose($handle);
    }
}
```

**Step 2: Update Task Handler**
```php
// In ExportOrdersTaskHandler::run()
public function run(): void
{
    $exports = $this->orderExportService->getPendingExports();
    
    // Export to Google Sheets
    $this->googleSheetsService->appendRows(...);
    
    // Also export to file
    $this->fileExportService->exportToFile($exports, 'exports_' . date('Y-m-d') . '.csv');
}
```

### 11.3 Webhook Integration

**Example: Send webhook notification after export**

```php
// src/Service/WebhookService.php
class WebhookService
{
    public function sendExportNotification(array $exportData): void
    {
        $webhookUrl = $this->systemConfigService->get('GotoWebinarGoogleSheetsExport.config.webhookUrl');
        
        if (!$webhookUrl) {
            return; // Webhook not configured
        }
        
        $client = new \GuzzleHttp\Client();
        $client->post($webhookUrl, [
            'json' => [
                'event' => 'order_exported',
                'timestamp' => date('c'),
                'data' => $exportData
            ]
        ]);
    }
}
```

---

**End of Technical Documentation**

This documentation provides a comprehensive technical overview for developers working with or extending the GotoWebinarGoogleSheetsExport plugin.
