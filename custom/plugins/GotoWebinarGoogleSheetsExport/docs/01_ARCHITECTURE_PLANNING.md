# GotoWebinarGoogleSheetsExport - Architecture & Planning Document

**Version:** 1.0.0  
**Date:** December 22, 2025  
**Purpose:** AI-driven development guide for Shopware 6 plugin

---

## 1. Project Overview

### 1.1 Plugin Name
**GotoWebinarGoogleSheetsExport**

### 1.2 Purpose
Export specific order data to Google Sheets when orders contain products from a configurable category (default: "GotoWebinar").

### 1.3 Key Requirements
- **Trigger:** Orders containing items from specified category
- **Export Data:** First name, last name, order number, product number, sales channel, customer email
- **Export Methods:** Scheduled intervals + manual export
- **Authentication:** OAuth2 (user authenticates with Google account)
- **Multi-product Handling:** Each product = separate row in Google Sheets
- **Local Storage:** Database table to track exports (for CSV export feature)
- **Duplicate Handling:** Allow re-sending
- **Error Handling:** Log errors without retry queue

---

## 2. System Architecture

### 2.1 Component Overview

```
GotoWebinarGoogleSheetsExport/
├── src/
│   ├── GotoWebinarGoogleSheetsExport.php       # Main plugin class
│   ├── Resources/
│   │   ├── config/
│   │   │   ├── services.xml                    # DI container configuration
│   │   │   └── config.xml                      # Admin configuration UI
│   │   └── app/
│   │       └── administration/
│   │           └── src/
│   │               └── module/
│   │                   └── gotowebinar-sheets/  # Admin module
│   ├── Service/
│   │   ├── GoogleSheetsService.php            # Google Sheets API integration
│   │   ├── OrderExportService.php             # Order data export logic
│   │   ├── CategoryFilterService.php          # Category filtering logic
│   │   └── CsvExportService.php               # CSV export functionality
│   ├── Subscriber/
│   │   └── OrderPlacedSubscriber.php          # Listen to order events
│   ├── Command/
│   │   └── ExportOrdersCommand.php            # Manual CLI export
│   ├── ScheduledTask/
│   │   ├── ExportOrdersTask.php               # Scheduled task definition
│   │   └── ExportOrdersTaskHandler.php        # Scheduled task handler
│   ├── Controller/
│   │   └── AdminApiController.php             # Admin API endpoints
│   ├── Core/
│   │   └── Content/
│   │       └── OrderExport/
│   │           ├── OrderExportEntity.php      # Export log entity
│   │           ├── OrderExportDefinition.php  # Entity definition
│   │           └── OrderExportCollection.php  # Entity collection
│   └── Migration/
│       └── Migration1703246400CreateOrderExportTable.php
├── composer.json                               # Plugin metadata
└── docs/
    ├── 01_ARCHITECTURE_PLANNING.md            # This file
    ├── 02_USER_MANUAL.md                      # User setup guide
    └── 03_TECHNICAL_DOCUMENTATION.md          # Technical details
```

### 2.2 Database Schema

**Table: `gotowebinar_order_export`**

```sql
CREATE TABLE `gotowebinar_order_export` (
    `id` BINARY(16) NOT NULL,
    `order_id` BINARY(16) NOT NULL,
    `order_number` VARCHAR(255) NOT NULL,
    `product_id` BINARY(16) NOT NULL,
    `product_number` VARCHAR(255) NOT NULL,
    `customer_first_name` VARCHAR(255) NOT NULL,
    `customer_last_name` VARCHAR(255) NOT NULL,
    `customer_email` VARCHAR(255) NOT NULL,
    `sales_channel_name` VARCHAR(255) NOT NULL,
    `exported_at` DATETIME(3) NOT NULL,
    `google_sheet_row_id` VARCHAR(255) NULL,
    `export_status` VARCHAR(50) NOT NULL DEFAULT 'success',
    `error_message` TEXT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_exported_at` (`exported_at`),
    KEY `idx_export_status` (`export_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Fields Explanation:**
- `id`: Primary key (UUID)
- `order_id`: Reference to order
- `order_number`: Human-readable order number
- `product_id`: Product that triggered export
- `product_number`: Human-readable product number
- `customer_first_name`, `customer_last_name`: Customer name
- `customer_email`: Customer email
- `sales_channel_name`: Sales channel name
- `exported_at`: Timestamp of export
- `google_sheet_row_id`: Google Sheets row identifier (for future updates)
- `export_status`: 'success', 'failed', 'pending'
- `error_message`: Error details if failed
- `created_at`, `updated_at`: Shopware standard timestamps

---

## 3. Configuration Schema

### 3.1 Plugin Configuration Fields (config.xml)

**Card 1: Feature Activation**
- `enabled` (bool): Enable/disable plugin functionality
- `categoryId` (entity-select): Category to monitor (default: "GotoWebinar")

**Card 2: Google Sheets Configuration**
- `googleSheetId` (text): Google Sheet ID from URL
- `worksheetName` (text): Worksheet/Tab name (default: "Bestellungen")
- `googleClientId` (text): OAuth2 Client ID
- `googleClientSecret` (password): OAuth2 Client Secret
- `googleRefreshToken` (password): OAuth2 Refresh Token (auto-filled after auth)

**Card 3: Export Schedule**
- `exportInterval` (single-select): 
  - Options: "disabled", "every_15_minutes", "hourly", "every_4_hours", "daily", "weekly"
  - Default: "hourly"
- `lastExportTimestamp` (datetime, readonly): Last successful export time

**Card 4: Advanced Options**
- `allowDuplicates` (bool): Allow re-exporting same order/product (default: true)
- `logErrors` (bool): Log errors to Shopware logs (default: true)
- `batchSize` (int): Number of orders to export per batch (default: 50)

### 3.2 Configuration Keys
All configuration accessible via: `GotoWebinarGoogleSheetsExport.config.<field_name>`

Example:
```php
$this->systemConfigService->get('GotoWebinarGoogleSheetsExport.config.enabled');
```

---

## 4. Core Services

### 4.1 GoogleSheetsService

**Responsibilities:**
- OAuth2 authentication flow
- Token refresh management
- Write data to Google Sheets
- Validate sheet access

**Key Methods:**
```php
class GoogleSheetsService
{
    public function authenticate(string $authCode): array; // Exchange code for tokens
    public function refreshAccessToken(): string; // Refresh expired token
    public function appendRows(string $sheetId, string $worksheetName, array $rows): void;
    public function validateSheetAccess(string $sheetId): bool;
    public function getAuthorizationUrl(): string; // Generate OAuth URL
}
```

**Dependencies:**
- Google API PHP Client library: `google/apiclient`
- SystemConfigService (to store/retrieve tokens)
- Logger

**OAuth2 Flow:**
1. Admin clicks "Connect to Google" in admin panel
2. Redirect to Google OAuth consent screen
3. User grants permissions
4. Google redirects back with authorization code
5. Exchange code for access token + refresh token
6. Store refresh token in config (encrypted)
7. Use access token for API calls (refresh when expired)

### 4.2 OrderExportService

**Responsibilities:**
- Fetch orders matching category criteria
- Extract required data fields
- Format data for Google Sheets
- Create export log entries

**Key Methods:**
```php
class OrderExportService
{
    public function getOrdersToExport(\DateTimeInterface $since = null): array;
    public function extractExportData(OrderEntity $order, OrderLineItemEntity $lineItem): array;
    public function createExportLog(array $data, string $status, ?string $error = null): void;
    public function getRecentExports(int $limit = 100): array;
}
```

**Dependencies:**
- OrderRepository
- CategoryFilterService
- OrderExportRepository (custom)
- SystemConfigService

**Data Extraction Logic:**
```php
[
    'customer_first_name' => $order->orderCustomer->firstName,
    'customer_last_name' => $order->orderCustomer->lastName,
    'customer_email' => $order->orderCustomer->email,
    'order_number' => $order->orderNumber,
    'product_number' => $lineItem->product->productNumber,
    'sales_channel_name' => $order->salesChannel->name,
]
```

### 4.3 CategoryFilterService

**Responsibilities:**
- Check if product belongs to configured category
- Handle nested categories (check entire category tree)

**Key Methods:**
```php
class CategoryFilterService
{
    public function productMatchesCategory(ProductEntity $product, string $categoryId): bool;
    public function getCategoryByName(string $categoryName): ?CategoryEntity;
    private function isInCategoryTree(array $productCategoryIds, string $targetCategoryId): bool;
}
```

**Dependencies:**
- CategoryRepository
- ProductRepository

### 4.4 CsvExportService

**Responsibilities:**
- Generate CSV from export log entries
- Format data for CSV download

**Key Methods:**
```php
class CsvExportService
{
    public function generateCsv(array $exportLogs): string; // Returns CSV content
    public function getCsvHeaders(): array;
    private function formatRow(OrderExportEntity $log): array;
}
```

**CSV Format:**
```
Export Date,Order Number,Product Number,First Name,Last Name,Email,Sales Channel,Status
2025-12-22 10:30:00,10001,BW-WEBINAR-001,Max,Mustermann,max@example.com,Storefront,success
```

---

## 5. Event-Driven Architecture

### 5.1 OrderPlacedSubscriber

**Event:** `CheckoutOrderPlacedEvent` (Shopware core event)

**Workflow:**
1. Listen to order placed event
2. Check if plugin is enabled
3. Iterate through order line items
4. For each line item:
   - Check if product belongs to configured category
   - If yes, queue for export or export immediately (depending on mode)
5. Create export log entry

**Implementation:**
```php
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
        // Check if enabled
        // Filter products by category
        // Create export log entries with status 'pending'
        // Actual export happens via scheduled task or manual trigger
    }
}
```

**Important:** Don't export immediately in event handler to avoid blocking order placement. Instead, mark as "pending" and let scheduled task handle export.

---

## 6. Scheduled Task System

### 6.1 ExportOrdersTask

**Purpose:** Define scheduled task for Shopware's task scheduler

**Configuration:**
```php
class ExportOrdersTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'gotowebinar.google_sheets_export';
    }

    public static function getDefaultInterval(): int
    {
        return 3600; // 1 hour default
    }
}
```

**Dynamic Interval:** Read from plugin config and update task interval accordingly.

### 6.2 ExportOrdersTaskHandler

**Workflow:**
1. Fetch pending export log entries
2. Batch entries (default: 50 per run)
3. For each batch:
   - Prepare Google Sheets rows
   - Call GoogleSheetsService::appendRows()
   - Update export log status (success/failed)
   - Log errors if any
4. Update last export timestamp in config

**Error Handling:**
- Catch all exceptions per row
- Mark individual rows as failed
- Continue processing remaining rows
- Log error message in database

---

## 7. Administration Module

### 7.1 Custom Admin Module

**Location:** `Resources/app/administration/src/module/gotowebinar-sheets/`

**Status:** ✅ **IMPLEMENTED** (v1.1.0 - January 7, 2026)

**Implemented Components:**
- ✅ Configuration form (XML-based, fully functional)
- ✅ API endpoints for all operations
- ✅ CLI commands for manual operations
- ✅ Overview page with export statistics dashboard
- ✅ Google OAuth connection button (browser-based flow)
- ✅ Manual export trigger button (one-click export)
- ✅ CSV download button in UI
- ✅ Recent exports table viewer (paginated, last 100 entries)
- ✅ Real-time export status updates

**Admin Routes:**
- `/gotowebinar/sheets/dashboard` - Main dashboard with statistics and controls
- Settings → Plugins → Webinar Export - Menu integration

**Vue.js Components Implemented:**
1. **gotowebinar-sheets-dashboard** - Main dashboard page
2. **gotowebinar-stats-card** - Statistics display with total/pending/last export
3. **gotowebinar-export-button** - Manual export trigger with modal confirmation
4. **gotowebinar-oauth-button** - Google OAuth flow with popup window handling
5. **gotowebinar-export-list** - Paginated export log table with CSV download

**Features:**
- Multi-language support (German/English translations)
- Modal confirmations for destructive actions
- Loading states and error handling
- Responsive design with Shopware admin components

### 7.2 AdminApiController

**Endpoints:**

**POST** `/api/_action/gotowebinar-sheets/oauth/authorize`
- Generate Google OAuth URL
- Return: `{ authUrl: string }`

**POST** `/api/_action/gotowebinar-sheets/oauth/callback`
- Body: `{ code: string }`
- Exchange authorization code for tokens
- Store refresh token in config
- Return: `{ success: boolean }`

**POST** `/api/_action/gotowebinar-sheets/export/manual`
- Trigger immediate export of pending entries
- Return: `{ exported: int, failed: int }`

**GET** `/api/_action/gotowebinar-sheets/export/csv`
- Query: `?limit=100`
- Return CSV file download

**GET** `/api/_action/gotowebinar-sheets/export/stats`
- Return: `{ totalExports: int, lastExport: datetime, pendingExports: int }`

---

## 8. Implementation Phases

### Phase 1: Foundation (Day 1)
- [ ] Create plugin structure with `bin/console plugin:create`
- [ ] Set up composer.json with dependencies
- [ ] Create database migration
- [ ] Create OrderExport entity + definition + repository
- [ ] Basic configuration schema (config.xml)

### Phase 2: Core Services (Day 2-3)
- [ ] Implement CategoryFilterService
- [ ] Implement OrderExportService
- [ ] Create OrderPlacedSubscriber (log pending exports only)
- [ ] Test order placement creates log entries

### Phase 3: Google Sheets Integration (Day 4-5)
- [ ] Install Google API PHP Client via Composer
- [ ] Implement GoogleSheetsService
- [ ] OAuth2 flow implementation
- [ ] Test sheet writing functionality

### Phase 4: Scheduled Export (Day 6)
- [ ] Create ExportOrdersTask
- [ ] Implement ExportOrdersTaskHandler
- [ ] Test scheduled export execution
- [ ] Implement batch processing

### Phase 5: Admin Interface (Day 7-8)
- [x] Create admin module structure
- [x] Implement OAuth connection UI
- [x] Manual export button
- [x] Recent exports table
- [x] Export statistics dashboard

### Phase 6: CSV Export & Polish (Day 9)
- [ ] Implement CsvExportService
- [ ] Add CSV download endpoint
- [ ] Error handling improvements
- [ ] Logging enhancements

### Phase 7: Testing & Documentation (Day 10)
- [ ] Unit tests for core services
- [ ] Integration tests
- [ ] Manual testing with real orders
- [ ] Update documentation

---

## 9. Dependencies

### 9.1 Composer Dependencies

```json
{
    "require": {
        "shopware/core": "~6.5.0",
        "google/apiclient": "^2.15"
    }
}
```

### 9.2 Shopware Services Used
- `Shopware\Core\System\SystemConfig\SystemConfigService`
- `Shopware\Core\Checkout\Order\OrderEntity`
- `Shopware\Core\Content\Product\ProductEntity`
- `Shopware\Core\Content\Category\CategoryEntity`
- `Shopware\Core\Framework\DataAbstractionLayer\EntityRepository`
- `Psr\Log\LoggerInterface`

---

## 10. Security Considerations

### 10.1 OAuth Token Storage
- Store refresh token encrypted in system_config table
- Never expose client secret in frontend
- Validate OAuth state parameter to prevent CSRF

### 10.2 API Rate Limiting
- Google Sheets API has quota limits (100 requests per 100 seconds per user)
- Implement batch processing to minimize API calls
- Handle rate limit errors gracefully

### 10.3 Access Control
- All admin endpoints require admin authentication
- Validate Google Sheet access before writing
- Sanitize all data before inserting to Google Sheets

---

## 11. Testing Strategy

### 11.1 Unit Tests
- CategoryFilterService: Category tree logic
- OrderExportService: Data extraction
- CsvExportService: CSV formatting
- GoogleSheetsService: Token refresh logic (mock API)

### 11.2 Integration Tests
- OrderPlacedSubscriber: Full order flow
- ExportOrdersTaskHandler: Batch export
- AdminApiController: API endpoints

### 11.3 Manual Testing Checklist
- [ ] Place order with matching category product
- [ ] Verify export log entry created
- [ ] Trigger manual export via admin
- [ ] Verify data appears in Google Sheets
- [ ] Test OAuth connection flow
- [ ] Download CSV export
- [ ] Test scheduled task execution
- [ ] Test error handling (invalid credentials, network errors)
- [ ] Test with multiple products in single order
- [ ] Test duplicate export handling

---

## 12. Performance Considerations

### 12.1 Database Queries
- Add indexes on frequently queried fields (order_id, exported_at, export_status)
- Use pagination for admin export list
- Limit CSV export to last 100 entries by default

### 12.2 Batch Processing
- Process exports in batches (default: 50)
- Use Google Sheets batch API (append multiple rows in one request)
- Implement timeout protection for long-running tasks

### 12.3 Caching
- Cache category tree lookups
- Cache Google OAuth access token (refresh only when expired)

---

## 13. Error Handling

### 13.1 Error Categories

**Network Errors:**
- Google API unreachable
- Timeout errors
- Action: Log error, mark export as failed, retry on next run

**Authentication Errors:**
- Invalid/expired refresh token
- Insufficient permissions
- Action: Log error, notify admin, stop exports until re-authenticated

**Data Errors:**
- Invalid sheet ID
- Missing required fields
- Action: Log error, mark specific export as failed, continue with others

**Rate Limit Errors:**
- Google API quota exceeded
- Action: Log warning, mark exports as pending, retry later

### 13.2 Logging Strategy
- Use Shopware's Monolog logger
- Log level: INFO for successful exports, ERROR for failures
- Log context: order number, product number, error message
- Store error message in database export log

---

## 14. Future Enhancements (Out of Scope for v1.0)

- Real-time export (export immediately on order placement)
- Update existing Google Sheets rows instead of append-only
- Support for multiple Google Sheets (different sheets per sales channel)
- Email notifications for export failures
- Retry queue for failed exports
- Export filtering by date range in admin
- Export to multiple destinations (Google Sheets + local CSV)
- Webhook support for other integrations

---

## 15. AI Coding Guidelines

### 15.1 Code Style
- Follow Shopware coding standards (PSR-12)
- Use strict typing: `declare(strict_types=1);`
- Use type hints for all method parameters and return types
- Document all public methods with PHPDoc

### 15.2 Naming Conventions
- Services: `*Service.php`
- Subscribers: `*Subscriber.php`
- Commands: `*Command.php`
- Controllers: `*Controller.php`
- Entities: `*Entity.php`, `*Definition.php`, `*Collection.php`

### 15.3 Dependency Injection
- Always use constructor injection
- Never use static methods for services
- Use Symfony's service container
- Tag services appropriately (event_subscriber, console.command, etc.)

### 15.4 Error Messages
- User-facing: Friendly, actionable messages (German + English)
- Technical: Detailed error context for debugging
- Never expose sensitive data (tokens, credentials)

### 15.5 Testing
- Write tests for all business logic
- Mock external dependencies (Google API)
- Use Shopware's testing framework
- Test both success and failure scenarios

---

## 16. File Checklist for Development

### Core Plugin Files
- [ ] `composer.json`
- [ ] `src/GotoWebinarGoogleSheetsExport.php`

### Configuration
- [ ] `src/Resources/config/services.xml`
- [ ] `src/Resources/config/config.xml`

### Database
- [ ] `src/Migration/Migration*CreateOrderExportTable.php`
- [ ] `src/Core/Content/OrderExport/OrderExportEntity.php`
- [ ] `src/Core/Content/OrderExport/OrderExportDefinition.php`
- [ ] `src/Core/Content/OrderExport/OrderExportCollection.php`

### Services
- [ ] `src/Service/GoogleSheetsService.php`
- [ ] `src/Service/OrderExportService.php`
- [ ] `src/Service/CategoryFilterService.php`
- [ ] `src/Service/CsvExportService.php`

### Event Handling
- [ ] `src/Subscriber/OrderPlacedSubscriber.php`

### Scheduled Tasks
- [ ] `src/ScheduledTask/ExportOrdersTask.php`
- [ ] `src/ScheduledTask/ExportOrdersTaskHandler.php`

### Admin API
- [ ] `src/Controller/AdminApiController.php`

### Administration (JavaScript/Vue)
- [x] `src/Resources/app/administration/src/main.js`
- [x] `src/Resources/app/administration/src/module/gotowebinar-sheets/index.js`
- [x] `src/Resources/app/administration/src/module/gotowebinar-sheets/snippet/de-DE.json`
- [x] `src/Resources/app/administration/src/module/gotowebinar-sheets/snippet/en-GB.json`
- [x] `src/Resources/app/administration/src/module/gotowebinar-sheets/page/gotowebinar-sheets-dashboard/index.js`
- [x] `src/Resources/app/administration/src/module/gotowebinar-sheets/page/gotowebinar-sheets-dashboard/gotowebinar-sheets-dashboard.html.twig`
- [x] `src/Resources/app/administration/src/module/gotowebinar-sheets/page/gotowebinar-sheets-dashboard/gotowebinar-sheets-dashboard.scss`
- [x] `src/Resources/app/administration/src/module/gotowebinar-sheets/component/gotowebinar-stats-card/index.js`
- [x] `src/Resources/app/administration/src/module/gotowebinar-sheets/component/gotowebinar-stats-card/gotowebinar-stats-card.html.twig`
- [x] `src/Resources/app/administration/src/module/gotowebinar-sheets/component/gotowebinar-stats-card/gotowebinar-stats-card.scss`
- [x] `src/Resources/app/administration/src/module/gotowebinar-sheets/component/gotowebinar-export-button/index.js`
- [x] `src/Resources/app/administration/src/module/gotowebinar-sheets/component/gotowebinar-export-button/gotowebinar-export-button.html.twig`
- [x] `src/Resources/app/administration/src/module/gotowebinar-sheets/component/gotowebinar-oauth-button/index.js`
- [x] `src/Resources/app/administration/src/module/gotowebinar-sheets/component/gotowebinar-oauth-button/gotowebinar-oauth-button.html.twig`
- [x] `src/Resources/app/administration/src/module/gotowebinar-sheets/component/gotowebinar-export-list/index.js`
- [x] `src/Resources/app/administration/src/module/gotowebinar-sheets/component/gotowebinar-export-list/gotowebinar-export-list.html.twig`
- [x] `src/Resources/app/administration/src/module/gotowebinar-sheets/component/gotowebinar-export-list/gotowebinar-export-list.scss`

### Commands (Optional, for CLI access)
- [ ] `src/Command/ExportOrdersCommand.php`
- [ ] `src/Command/TestGoogleConnectionCommand.php`

---

## 17. Development Order Recommendation

**Start with this order to minimize dependencies and enable incremental testing:**

1. **Setup & Database** (Foundation)
   - composer.json
   - Main plugin class
   - Database migration + entities
   
2. **Configuration** (Enable admin config early)
   - config.xml
   - Test in admin panel
   
3. **Basic Services** (Build from bottom up)
   - CategoryFilterService
   - OrderExportService (without Google Sheets)
   - Test with order placement
   
4. **Event Handling** (Log creation)
   - OrderPlacedSubscriber
   - Test order creates log entries
   
5. **Google Integration** (External dependency)
   - GoogleSheetsService
   - OAuth flow
   - Test sheet writing
   
6. **Scheduled Export** (Automation)
   - ExportOrdersTask + Handler
   - Test scheduled execution
   
7. **Admin Interface** (UI)
   - AdminApiController
   - Admin module (Vue.js)
   - Manual export button
   
8. **CSV Export** (Additional feature)
   - CsvExportService
   - Download endpoint
   
9. **Polish & Testing** (Quality)
   - Error handling
   - Unit tests
   - Documentation

---

**End of Architecture Planning Document**

This document should serve as the complete blueprint for AI-driven development. All technical decisions have been made based on user requirements and Shopware best practices.
