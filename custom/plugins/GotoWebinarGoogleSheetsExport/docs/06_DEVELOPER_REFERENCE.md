# Developer Quick Reference - GotoWebinarGoogleSheetsExport

## Plugin Structure

```
GotoWebinarGoogleSheetsExport/
├── composer.json                    # Plugin metadata & dependencies
├── phpunit.xml                      # PHPUnit configuration
├── src/
│   ├── GotoWebinarGoogleSheetsExport.php  # Main plugin class
│   ├── Migration/
│   │   └── Migration1703246400CreateOrderExportTable.php
│   ├── Core/Content/OrderExport/   # Entity definitions
│   │   ├── OrderExportEntity.php
│   │   ├── OrderExportDefinition.php
│   │   └── OrderExportCollection.php
│   ├── Service/                     # Business logic
│   │   ├── GoogleSheetsService.php
│   │   ├── OrderExportService.php
│   │   ├── CategoryFilterService.php
│   │   └── CsvExportService.php
│   ├── Subscriber/
│   │   └── OrderPlacedSubscriber.php
│   ├── ScheduledTask/
│   │   ├── ExportOrdersTask.php
│   │   └── ExportOrdersTaskHandler.php
│   ├── Command/
│   │   └── ExportOrdersCommand.php
│   ├── Controller/
│   │   └── AdminApiController.php
│   └── Resources/config/
│       ├── services.xml             # DI container
│       └── config.xml               # Admin UI config
├── tests/
│   ├── bootstrap.php
│   └── Unit/Service/
│       ├── CsvExportServiceTest.php
│       └── CategoryFilterServiceTest.php
└── docs/
    ├── 01_ARCHITECTURE_PLANNING.md
    ├── 02_USER_MANUAL.md
    ├── 03_TECHNICAL_DOCUMENTATION.md
    ├── 04_INSTALLATION_GUIDE.md
    └── 05_DEPLOYMENT_GUIDE.md
```

---

## Key Components

### 1. Services (Business Logic)

#### GoogleSheetsService
```php
// OAuth authentication
$authUrl = $service->getAuthorizationUrl($redirectUri);
$token = $service->authenticate($authCode, $redirectUri);

// Export data
$service->appendRows($sheetId, $worksheetName, $rows);

// Check configuration
$isConfigured = $service->isConfigured();
```

#### OrderExportService
```php
// Get pending exports
$exports = $service->getPendingExports($context, $limit);

// Create export log
$service->createExportLog($orderId, $orderNumber, $productId, ...);

// Update status
$service->updateExportStatus($exportId, 'success', $context);

// Get stats
$stats = $service->getExportStats($context);
```

#### CategoryFilterService
```php
// Check if product matches category
$matches = $service->productMatchesCategory($product, $categoryId, $context);

// Find category by name
$category = $service->getCategoryByName('GotoWebinar', $context);
```

### 2. Events

#### OrderPlacedSubscriber
- **Listens to:** `state_enter.order_transaction.state.paid`
- **Action:** Creates export log entries for matching products
- **Status:** `pending` (actual export happens via scheduled task)

### 3. Scheduled Tasks

#### ExportOrdersTask
- **Task Name:** `gotowebinar.google_sheets_export`
- **Default Interval:** 3600 seconds (1 hour)
- **Configurable:** Via plugin settings

#### ExportOrdersTaskHandler
- Processes pending exports in batches
- Updates export status (success/failed)
- Logs errors for failed exports

### 4. Commands

#### gotowebinar:export-orders
```bash
# Basic usage
bin/console gotowebinar:export-orders

# With options
bin/console gotowebinar:export-orders --limit 100
bin/console gotowebinar:export-orders --force  # Ignore enabled check
```

### 5. Admin API Endpoints

```php
// Generate OAuth URL
POST /api/_action/gotowebinar-sheets/oauth/authorize
Body: { "redirectUri": "https://..." }

// Handle OAuth callback
POST /api/_action/gotowebinar-sheets/oauth/callback
Body: { "code": "...", "redirectUri": "..." }

// Manual export
POST /api/_action/gotowebinar-sheets/export/manual
Body: { "limit": 50 }

// Get stats
GET /api/_action/gotowebinar-sheets/export/stats

// Download CSV
GET /api/_action/gotowebinar-sheets/export/csv?limit=100
```

---

## Database Schema

### Table: gotowebinar_order_export

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
    `exported_at` DATETIME(3) NULL,
    `google_sheet_row_id` VARCHAR(255) NULL,
    `export_status` VARCHAR(50) NOT NULL DEFAULT 'pending',
    `error_message` TEXT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_exported_at` (`exported_at`),
    KEY `idx_export_status` (`export_status`)
);
```

**Export Status Values:**
- `pending` - Waiting to be exported
- `success` - Successfully exported
- `failed` - Export failed (see error_message)

---

## Configuration Keys

All configuration stored under: `GotoWebinarGoogleSheetsExport.config.*`

```php
use Shopware\Core\System\SystemConfig\SystemConfigService;

// Get configuration
$enabled = $configService->get('GotoWebinarGoogleSheetsExport.config.enabled');
$categoryId = $configService->get('GotoWebinarGoogleSheetsExport.config.categoryId');
$sheetId = $configService->get('GotoWebinarGoogleSheetsExport.config.googleSheetId');
$worksheetName = $configService->get('GotoWebinarGoogleSheetsExport.config.worksheetName');
$clientId = $configService->get('GotoWebinarGoogleSheetsExport.config.googleClientId');
$clientSecret = $configService->get('GotoWebinarGoogleSheetsExport.config.googleClientSecret');
$refreshToken = $configService->get('GotoWebinarGoogleSheetsExport.config.googleRefreshToken');
$exportInterval = $configService->get('GotoWebinarGoogleSheetsExport.config.exportInterval');
$batchSize = $configService->get('GotoWebinarGoogleSheetsExport.config.batchSize');

// Set configuration
$configService->set('GotoWebinarGoogleSheetsExport.config.enabled', true);
```

**Available Configuration:**
- `enabled` (bool) - Enable/disable plugin
- `categoryId` (string) - Category UUID to monitor
- `googleSheetId` (string) - Google Sheet ID
- `worksheetName` (string) - Worksheet/tab name
- `googleClientId` (string) - OAuth Client ID
- `googleClientSecret` (string) - OAuth Client Secret
- `googleRefreshToken` (string) - OAuth Refresh Token
- `exportInterval` (string) - Export frequency
- `lastExportTimestamp` (datetime) - Last export time
- `allowDuplicates` (bool) - Allow duplicate exports
- `logErrors` (bool) - Enable error logging
- `batchSize` (int) - Exports per batch

---

## Common Tasks

### Add New Export Field

1. **Update database migration:**
```php
ALTER TABLE `gotowebinar_order_export` 
ADD COLUMN `new_field` VARCHAR(255) NULL;
```

2. **Update OrderExportEntity:**
```php
protected ?string $newField;

public function getNewField(): ?string
{
    return $this->newField;
}
```

3. **Update OrderExportDefinition:**
```php
new StringField('new_field', 'newField'),
```

4. **Update OrderExportService::createExportLog():**
```php
'newField' => $data['new_field'] ?? null,
```

5. **Update GoogleSheetsService export logic:**
```php
$row = [
    // ... existing fields
    $export->getNewField(),
];
```

### Change Export Trigger

Currently exports on payment. To export on order placement instead:

**In OrderPlacedSubscriber:**
```php
public static function getSubscribedEvents(): array
{
    return [
        CheckoutOrderPlacedEvent::class => 'onOrderPlaced',
        // Instead of: 'state_enter.order_transaction.state.paid'
    ];
}
```

### Add Custom Export Validation

**In OrderExportService:**
```php
public function shouldExportOrder(OrderEntity $order, Context $context): bool
{
    // Existing category check
    if (!$this->categoryFilterService->productMatchesCategory(...)) {
        return false;
    }
    
    // Add custom validation
    if ($order->getTotalAmount() < 1000) {
        return false; // Don't export orders under 10 EUR
    }
    
    return true;
}
```

### Customize Google Sheets Format

**In ExportOrdersTaskHandler::processExports():**
```php
$rows = [];
foreach ($pendingExports as $export) {
    $row = [
        // Customize order and format
        $export->getOrderNumber(),
        $export->getCustomerFirstName() . ' ' . $export->getCustomerLastName(),
        $export->getCustomerEmail(),
        $export->getProductNumber(),
        $export->getSalesChannelName(),
        date('Y-m-d H:i:s'), // Add timestamp
    ];
    $rows[] = $row;
}
```

### Add Email Notifications

**Create new EmailService:**
```php
namespace GotoWebinarGoogleSheetsExport\Service;

class EmailService
{
    public function sendExportSummary(array $stats): void
    {
        // Send email to admin with export summary
    }
}
```

**Call from ExportOrdersTaskHandler:**
```php
public function run(): void
{
    $this->processExports();
    
    // Send summary email
    $stats = $this->orderExportService->getExportStats($context);
    $this->emailService->sendExportSummary($stats);
}
```

---

## Testing

### Run All Tests
```bash
cd custom/plugins/GotoWebinarGoogleSheetsExport
vendor/bin/phpunit
```

### Run Specific Test
```bash
vendor/bin/phpunit tests/Unit/Service/CsvExportServiceTest.php
```

### Test Coverage
```bash
vendor/bin/phpunit --coverage-html coverage/
```

### Manual Testing

```bash
# 1. Create test order
# Use Shopware storefront or admin to create test order

# 2. Check export log was created
bin/console dbal:run-sql "SELECT * FROM gotowebinar_order_export ORDER BY created_at DESC LIMIT 5"

# 3. Run export manually
bin/console gotowebinar:export-orders --force

# 4. Verify in Google Sheet
# Open your Google Sheet and check for new rows

# 5. Check logs
tail -f var/log/prod.log | grep GotoWebinar
```

---

## Debugging

### Enable Debug Logging

```php
// In any service, inject logger
use Psr\Log\LoggerInterface;

public function __construct(
    // ... other dependencies
    private readonly LoggerInterface $logger
) {}

// Add debug logs
$this->logger->debug('Custom debug message', [
    'orderId' => $orderId,
    'data' => $data,
]);
```

### Common Issues

**Issue: "Google API credentials not configured"**
- Check `googleClientId` and `googleClientSecret` in config
- Verify no extra spaces or line breaks

**Issue: "No refresh token available"**
- Complete OAuth flow again
- Check `googleRefreshToken` is stored in database

**Issue: "Failed to append rows to Google Sheet"**
- Verify Sheet ID is correct
- Check worksheet name matches exactly
- Ensure authenticated user has edit access

**Issue: Exports stuck in "pending"**
- Check scheduled task is running: `bin/console scheduled-task:list`
- Run manually: `bin/console scheduled-task:run`
- Check cron is configured

---

## Performance Tips

1. **Optimize batch size** based on order volume
2. **Archive old exports** periodically
3. **Use database indexes** (already configured)
4. **Monitor Google API quota**
5. **Adjust export frequency** based on needs

---

## Security

- ✅ OAuth2 credentials encrypted in database
- ✅ HTTPS required for OAuth
- ✅ Admin API endpoints protected by Shopware ACL
- ✅ No sensitive data in logs
- ✅ SQL injection prevention via DAL
- ✅ XSS prevention via Twig auto-escaping

---

## Admin Dashboard Implementation (v1.1.0 - Completed)

### Admin Dashboard Widget

**Status:** ✅ **FULLY IMPLEMENTED** (January 7, 2026)

**Location:** `src/Resources/app/administration/src/module/gotowebinar-sheets/`

**Implemented Files:**
```
Resources/app/administration/src/
├── main.js                                    # ✅ Entry point
├── module/
│   └── gotowebinar-sheets/
│       ├── index.js                           # ✅ Module registration
│       ├── snippet/
│       │   ├── de-DE.json                     # ✅ German translations
│       │   └── en-GB.json                     # ✅ English translations
│       ├── page/
│       │   └── gotowebinar-sheets-dashboard/
│       │       ├── index.js                   # ✅ Dashboard component
│       │       ├── gotowebinar-sheets-dashboard.html.twig  # ✅ Template
│       │       └── gotowebinar-sheets-dashboard.scss       # ✅ Styles
│       └── component/
│           ├── gotowebinar-stats-card/
│           │   ├── index.js                   # ✅ Statistics card
│           │   ├── gotowebinar-stats-card.html.twig       # ✅ Template
│           │   └── gotowebinar-stats-card.scss             # ✅ Styles
│           ├── gotowebinar-export-button/
│           │   ├── index.js                   # ✅ Export button
│           │   └── gotowebinar-export-button.html.twig    # ✅ Template
│           ├── gotowebinar-oauth-button/
│           │   ├── index.js                   # ✅ OAuth button
│           │   └── gotowebinar-oauth-button.html.twig     # ✅ Template
│           └── gotowebinar-export-list/
│               ├── index.js                   # ✅ Export log viewer
│               ├── gotowebinar-export-list.html.twig      # ✅ Template
│               └── gotowebinar-export-list.scss           # ✅ Styles
```

**Total: 17 files implemented**

**Key Implementation Details:**

### 1. Admin Module Registration

The module is registered in Shopware's admin module system with:
- **Route:** `gotowebinar.sheets.dashboard`
- **Navigation:** Appears in Settings → Plugins submenu
- **Icon:** `default-action-share` (share icon)
- **Color:** `#ff3d58` (red theme)

### 2. Dashboard Component Features

**Statistics Card:**
- Displays total exports, pending count, last export timestamp
- Real-time refresh on export actions
- Visual indicators (icons, colored badges)
- Configuration status warning if OAuth not connected

**Action Buttons:**
- **OAuth Button:** Opens popup window for Google authorization
  - Changes state: "Connect to Google" → "Connected to Google"
  - Handles popup blocking with user notification
  - Monitors OAuth redirect and closes popup automatically

- **Export Button:** Triggers manual export with confirmation modal
  - Configurable batch limit (default: 50)
  - Disabled when no pending exports
  - Shows success notification with count

**Export Log Viewer:**
- Paginated table (25 entries per page)
- Columns: Exported At, Order Number, Product Number, Customer Name, Email, Status
- Status badges: Success (green), Pending (blue), Failed (red)
- Error tooltips on failed exports
- Refresh button and CSV download

### 3. API Integration

All components use direct HTTP calls to existing API endpoints:

```javascript
// Statistics
this.$http.get('/_action/gotowebinar-sheets/export/stats')

// Manual export
this.$http.post('/_action/gotowebinar-sheets/export/manual', { limit: 50 })

// OAuth authorization
this.$http.post('/_action/gotowebinar-sheets/oauth/authorize', { redirectUri })

// OAuth callback
this.$http.post('/_action/gotowebinar-sheets/oauth/callback', { code, redirectUri })

// CSV download
window.open('/_action/gotowebinar-sheets/export/csv?limit=100', '_blank')
```

### 4. Building Admin Assets

After modifications, rebuild the administration:

```bash
bin/build-administration.sh
# or
bin/console bundle:dump
./bin/build-administration.sh
```

**Estimated Effort:**
- Dashboard page: 1-2 days
- Stats cards: 1 day
- Export button: 0.5 day
- OAuth button: 1 day
- Export log viewer: 2-3 days
- Testing & polish: 1-2 days
- **Total: 1-2 weeks**

---

## Resources

- [Shopware Developer Documentation](https://developer.shopware.com/)
- [Shopware Admin Extension SDK](https://developer.shopware.com/docs/guides/plugins/plugins/administration/)
- [Google Sheets API Reference](https://developers.google.com/sheets/api)
- [Plugin Documentation](docs/)

---

## Support

For development questions:
1. Check this quick reference
2. Review full documentation in `docs/`
3. Check Shopware developer docs
4. Review plugin source code (well-commented)
5. For admin UI development, see Shopware Admin SDK docs

---

**Happy Coding! 🚀**
