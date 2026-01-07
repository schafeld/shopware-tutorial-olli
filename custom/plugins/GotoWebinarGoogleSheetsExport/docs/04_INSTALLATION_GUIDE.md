# Installation Guide - GotoWebinarGoogleSheetsExport

## Prerequisites

Before installing the plugin, ensure you have:

- ✅ Shopware 6.5.0 or higher
- ✅ PHP 8.1 or higher
- ✅ Composer installed
- ✅ Access to Shopware CLI commands
- ✅ Google Cloud Platform account
- ✅ Google Sheets API enabled

> **📝 Note on Admin UI:**  
> Version 1.0 provides full functionality via CLI commands and API endpoints. Admin UI components (dashboard, manual export button, OAuth UI flow) are planned for v1.1. The plugin is fully functional without these UI enhancements.

---

## Step 1: Google Cloud Setup

### 1.1 Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Note your project ID

### 1.2 Enable Google Sheets API

1. In the Google Cloud Console, go to **APIs & Services → Library**
2. Search for "Google Sheets API"
3. Click **Enable**

### 1.3 Create OAuth 2.0 Credentials

1. Go to **APIs & Services → Credentials**
2. Click **Create Credentials → OAuth client ID**
3. Configure OAuth consent screen if prompted:
   - User Type: **External** (or Internal for Google Workspace)
   - App name: "Shopware Webinar Export"
   - Add your email as support and developer contact
   - Add scope: `https://www.googleapis.com/auth/spreadsheets`
4. Application type: **Web application**
5. Name: "Shopware Plugin"
6. Authorized redirect URIs:
   - Add: `https://your-shop-domain.com/admin`
   - Add: `http://localhost/admin` (for local development)
7. Click **Create**
8. **Save the Client ID and Client Secret** - you'll need these later

### 1.4 Create Google Sheet

1. Go to [Google Sheets](https://sheets.google.com/)
2. Create a new spreadsheet
3. Name it (e.g., "Webinar Orders")
4. Add headers in the first row:
   ```
   First Name | Last Name | Order Number | Product Number | Sales Channel | Email
   ```
5. From the URL, copy the **Sheet ID**:
   ```
   https://docs.google.com/spreadsheets/d/[THIS_IS_THE_SHEET_ID]/edit
   ```
6. Ensure the account that will authenticate has edit access to this sheet

---

## Step 2: Plugin Installation

### 2.1 Install via Composer (Recommended)

If published as a Composer package:

```bash
cd /path/to/shopware
composer require goto/webinar-google-sheets-export
```

### 2.2 Install Manually

If installing from source:

```bash
# Navigate to your Shopware plugins directory
cd custom/plugins

# Clone or copy the plugin
git clone <repository-url> GotoWebinarGoogleSheetsExport
# OR
# Copy the plugin folder to custom/plugins/GotoWebinarGoogleSheetsExport

# Install Google API client dependency
cd GotoWebinarGoogleSheetsExport
composer install --no-dev
```

### 2.3 Install the Plugin in Shopware

```bash
# Return to Shopware root
cd /path/to/shopware

# Refresh plugin list
bin/console plugin:refresh

# Install the plugin
bin/console plugin:install GotoWebinarGoogleSheetsExport --activate

# Clear cache
bin/console cache:clear
```

### 2.4 Verify Installation

Check that the plugin is active:

```bash
bin/console plugin:list | grep GotoWebinar
```

You should see:
```
GotoWebinarGoogleSheetsExport    1.0.0    Yes    Yes
```

---

## Step 3: Plugin Configuration

### 3.1 Access Configuration

1. Log in to Shopware Administration
2. Go to **Settings → System → Plugins**
3. Find **GotoWebinarGoogleSheetsExport**
4. Click the **three dots** → **Configure**

### 3.2 Basic Configuration

**Enable Plugin:**
- ☑ Check "Enable Plugin"

**Webinar Category:**
- Select the category containing your webinar products
- Example: "GotoWebinar" or "Webinars"
- All products in this category (and subcategories) will be exported

### 3.3 Google Sheets Configuration

**Google Sheet ID:**
- Paste the Sheet ID you copied earlier
- Example: `1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms`

**Worksheet Name:**
- Enter the name of the sheet tab (default: "Bestellungen")
- Must match exactly the tab name in your Google Sheet

**Google OAuth Client ID:**
- Paste the Client ID from Google Cloud Console

**Google OAuth Client Secret:**
- Paste the Client Secret from Google Cloud Console

**Save Configuration** (don't worry about Refresh Token yet)

---

## Step 4: OAuth Authentication

### 4.1 Generate Authorization URL

Currently, you need to manually generate the OAuth URL. Here's how:

```bash
# Create a temporary script to generate the OAuth URL
cd /path/to/shopware

# Create test script
cat > generate_oauth_url.php << 'EOF'
<?php

require 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Load environment
(new Dotenv())->bootEnv(dirname(__FILE__).'/.env');

// Boot Shopware kernel
$kernel = new \Shopware\Core\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$googleService = $container->get('GotoWebinarGoogleSheetsExport\Service\GoogleSheetsService');

// Generate OAuth URL
$redirectUri = 'https://your-shop-domain.com/admin';
$authUrl = $googleService->getAuthorizationUrl($redirectUri);

echo "OAuth Authorization URL:\n";
echo $authUrl . "\n\n";
echo "1. Open this URL in your browser\n";
echo "2. Authorize the application\n";
echo "3. Copy the authorization code from the URL\n";
EOF

# Run the script
php generate_oauth_url.php
```

### 4.2 Authorize Application

1. Copy the generated OAuth URL
2. Open it in your browser
3. Sign in with your Google account
4. Click **Allow** to grant permissions
5. You'll be redirected to your admin panel
6. Copy the `code` parameter from the URL:
   ```
   https://your-shop.com/admin?code=4/0AY0e-g7...LONG_CODE_HERE...&scope=https://...
   ```

### 4.3 Exchange Code for Token

```bash
# Create token exchange script
cat > exchange_oauth_token.php << 'EOF'
<?php

require 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(dirname(__FILE__).'/.env');

$kernel = new \Shopware\Core\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$googleService = $container->get('GotoWebinarGoogleSheetsExport\Service\GoogleSheetsService');

// Replace with your authorization code
$authCode = 'YOUR_AUTH_CODE_HERE';
$redirectUri = 'https://your-shop-domain.com/admin';

try {
    $token = $googleService->authenticate($authCode, $redirectUri);
    echo "✅ Successfully authenticated!\n";
    echo "Refresh token has been saved to configuration.\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
EOF

# Edit the script with your auth code
nano exchange_oauth_token.php

# Run it
php exchange_oauth_token.php
```

### 4.4 Verify Connection

```bash
# Test the connection
bin/console gotowebinar:export-orders --force
```

If successful, you should see:
```
✅ Successfully exported X row(s) to Google Sheets
```

---

## Step 5: Configure Export Schedule

### 5.1 Set Export Interval

In the plugin configuration:

**Export Interval:**
- Choose frequency: Hourly, Every 4 hours, Daily, etc.
- Start with "Hourly" for testing
- Can be set to "Disabled" for manual-only exports

### 5.2 Configure Scheduled Tasks

Ensure Shopware's scheduled task runner is active:

```bash
# Check if scheduled tasks are running
bin/console scheduled-task:list

# Run scheduled tasks manually (for testing)
bin/console scheduled-task:run
```

For production, set up a cron job:

```bash
# Add to crontab
crontab -e

# Add this line (runs every minute)
* * * * * cd /path/to/shopware && php bin/console scheduled-task:run >> var/log/scheduled_tasks.log 2>&1
```

### 5.3 Advanced Options

**Allow Duplicate Exports:**
- ☑ Enabled: Same order can be exported multiple times
- ☐ Disabled: Each order exported only once

**Log Errors:**
- ☑ Enabled: Errors logged to `var/log/prod.log`

**Batch Size:**
- Default: 50
- Increase for better performance (max: 100)
- Decrease if encountering API rate limits

---

## Step 6: Testing

### 6.1 Create Test Category

1. In Shopware Admin, go to **Catalogues → Categories**
2. Create a category called "Test Webinars"
3. Note the category ID

### 6.2 Create Test Product

1. Create a product in the "Test Webinars" category
2. Name: "Test Webinar Product"
3. Price: 10.00 EUR
4. Set as active

### 6.3 Place Test Order

1. Open your storefront
2. Add the test product to cart
3. Complete checkout
4. Complete payment (use fake payment for testing)

### 6.4 Verify Export

**Option 1: Manual Export**
```bash
bin/console gotowebinar:export-orders
```

**Option 2: Check Database**
```bash
bin/console dbal:run-sql "SELECT * FROM gotowebinar_order_export ORDER BY created_at DESC LIMIT 5"
```

**Option 3: Check Google Sheet**
- Open your Google Sheet
- Verify the new row appears with order data

---

## Step 7: Monitoring & Maintenance

### 7.1 View Export Logs

```bash
# View recent exports
tail -f var/log/prod.log | grep GotoWebinar
```

### 7.2 Export Statistics

Check stats via API:
```bash
curl -X GET "https://your-shop.com/api/_action/gotowebinar-sheets/export/stats" \
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

### 7.3 Download CSV Report

In Shopware Admin:
- The API endpoint for CSV download will be added in a future admin UI
- For now, use the CLI or API directly

---

## Troubleshooting

### Problem: "Plugin not found"

**Solution:**
```bash
bin/console plugin:refresh
bin/console cache:clear
```

### Problem: "Google API credentials not configured"

**Solution:**
- Double-check Client ID and Secret in plugin config
- Ensure no extra spaces or line breaks

### Problem: "No refresh token received"

**Solution:**
- Revoke app access in Google Account settings
- Generate new OAuth URL and re-authorize
- Ensure redirect URI matches exactly

### Problem: "Failed to append rows to Google Sheet"

**Solution:**
- Verify Sheet ID is correct
- Check that authenticated Google account has edit access
- Ensure worksheet name matches exactly (case-sensitive)

### Problem: "Orders not being exported automatically"

**Solution:**
```bash
# Check if scheduled task is registered
bin/console scheduled-task:list | grep gotowebinar

# Run manually to test
bin/console scheduled-task:run

# Check cron is running
crontab -l
```

### Problem: "Product not being exported"

**Solution:**
- Verify product is in the configured category
- Check if plugin is enabled
- Look for errors in `var/log/prod.log`

---

## Uninstallation

### Keep Data

```bash
bin/console plugin:deactivate GotoWebinarGoogleSheetsExport
bin/console plugin:uninstall GotoWebinarGoogleSheetsExport --keep-user-data
```

### Remove All Data

```bash
bin/console plugin:deactivate GotoWebinarGoogleSheetsExport
bin/console plugin:uninstall GotoWebinarGoogleSheetsExport
```

This will:
- Deactivate the plugin
- Remove the `gotowebinar_order_export` table
- Remove all configuration

---

## Support

For issues or questions:
- Check logs: `var/log/prod.log`
- Review documentation in `docs/` folder
- Check GitHub issues (if open source)
- Contact: [your-support-email]

---

## Next Steps

After successful installation:

1. ✅ Read [User Manual](02_USER_MANUAL.md) for daily operations
2. ✅ Review [Technical Documentation](03_TECHNICAL_DOCUMENTATION.md) for advanced configuration
3. ✅ Set up monitoring and alerting for export failures
4. ✅ Configure backup strategy for export data
