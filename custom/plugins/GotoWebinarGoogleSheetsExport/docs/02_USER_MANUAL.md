# GotoWebinarGoogleSheetsExport - User Manual & Setup Guide

**Version:** 1.0.0  
**Last Updated:** December 22, 2025

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Prerequisites](#2-prerequisites)
3. [Google Sheets Setup](#3-google-sheets-setup)
4. [Plugin Installation](#4-plugin-installation)
5. [Configuration](#5-configuration)
6. [Usage](#6-usage)
7. [Troubleshooting](#7-troubleshooting)
8. [FAQ](#8-faq)

---

## 1. Introduction

### What is GotoWebinarGoogleSheetsExport?

This Shopware 6 plugin automatically exports order data to a Google Sheets spreadsheet when customers order products from a specific category (default: "GotoWebinar").

### What data is exported?

For each product in an order that matches the configured category, the following information is exported as a separate row:

- **First Name** - Customer's first name
- **Last Name** - Customer's last name
- **Order Number** - Shopware order number (e.g., 10001)
- **Product Number** - Product SKU/article number
- **Sales Channel** - Name of the sales channel where order was placed
- **Customer Email** - Customer's email address

### Export Methods

The plugin supports two export methods:

1. **Scheduled Automatic Export** - Runs at configurable intervals (every 15 minutes, hourly, every 4 hours, daily, or weekly)
2. **Manual Export** - Via CLI command (admin UI button planned for v1.1)

**Current Version (v1.0) - CLI/API Based:**
- ✅ Full configuration via Admin Settings panel
- ✅ Manual export via CLI: `bin/console gotowebinar:export-orders`
- ✅ OAuth setup via CLI scripts (see Installation Guide)
- ✅ CSV download via API endpoint
- ✅ Statistics via API endpoint

**Planned Version (v1.1) - Enhanced Admin UI:**
- ⏳ Dashboard widget with visual statistics
- ⏳ One-click manual export button
- ⏳ Browser-based OAuth flow
- ⏳ Export log viewer with filtering

For most users, scheduled automatic exports are sufficient. Manual CLI commands are available for testing or on-demand exports.

---

## 2. Prerequisites

Before installing this plugin, ensure you have:

### Shopware Requirements
- ✅ Shopware 6.5.0 or higher
- ✅ Admin access to Shopware administration panel
- ✅ SSH/Terminal access to server (for installation via CLI)
- ✅ Composer installed on server

### Google Account Requirements
- ✅ A Google account (Gmail, Google Workspace, etc.)
- ✅ Access to Google Cloud Console
- ✅ Ability to create/edit Google Sheets

### Technical Skills Required
- 📊 Basic understanding of Google Sheets
- 🔧 Basic Shopware administration knowledge
- 💻 Basic command line usage (for CLI installation)

---

## 3. Google Sheets Setup

### Step 1: Create Your Google Sheet

1. Go to [Google Sheets](https://sheets.google.com)
2. Click **"+ Blank"** to create a new spreadsheet
3. Name your spreadsheet (e.g., "GotoWebinar Orders")
4. Create/rename the first worksheet/tab (e.g., "Bestellungen" or "Orders")

### Step 2: Set Up Column Headers (Recommended)

Add these headers in the first row (A1-F1):

| A | B | C | D | E | F |
|---|---|---|---|---|---|
| First Name | Last Name | Order Number | Product Number | Sales Channel | Email |

> **Note:** The plugin will automatically append new rows below existing content. Headers are optional but recommended for clarity.

### Step 3: Note Your Sheet Information

You'll need these two pieces of information for configuration:

1. **Sheet ID** - Found in the URL:
   ```
   https://docs.google.com/spreadsheets/d/[SHEET_ID]/edit#gid=0
                                          ^^^^^^^^^^^
   ```
   Example: `1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms`

2. **Worksheet Name** - The name of the tab (bottom of the sheet)
   Example: `Bestellungen` or `Sheet1`

### Step 4: Create Google Cloud Project & OAuth Credentials

#### 4.1 Create a Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click **"Select a project"** → **"New Project"**
3. Enter project name: `Shopware Sheets Export` (or your preference)
4. Click **"Create"**
5. Wait for project creation, then select your new project

#### 4.2 Enable Google Sheets API

1. In the left sidebar, click **"APIs & Services"** → **"Library"**
2. Search for: `Google Sheets API`
3. Click on **"Google Sheets API"**
4. Click **"Enable"** button
5. Wait for API to be enabled

#### 4.3 Configure OAuth Consent Screen

1. Go to **"APIs & Services"** → **"OAuth consent screen"**
2. Select **"External"** (unless you have Google Workspace)
3. Click **"Create"**

**Fill in App Information:**
- **App name:** `Shopware Order Export`
- **User support email:** Your email
- **Developer contact email:** Your email
- Click **"Save and Continue"**

**Scopes:**
- Click **"Add or Remove Scopes"**
- Search for: `Google Sheets API`
- Select: `https://www.googleapis.com/auth/spreadsheets`
- Click **"Update"**
- Click **"Save and Continue"**

**Test Users (for External app):**
- Click **"+ Add Users"**
- Enter the Google account email that owns the spreadsheet
- Click **"Add"**
- Click **"Save and Continue"**

**Summary:**
- Review settings
- Click **"Back to Dashboard"**

#### 4.4 Create OAuth 2.0 Credentials

1. Go to **"APIs & Services"** → **"Credentials"**
2. Click **"+ Create Credentials"** → **"OAuth client ID"**
3. **Application type:** Select `Web application`
4. **Name:** `Shopware GotoWebinar Plugin`

**Authorized redirect URIs:**
- Click **"+ Add URI"**
- Enter your Shopware admin URL with this path:
  ```
  https://your-shop-domain.com/admin#/gotowebinar/sheets/oauth/callback
  ```
  Example: `https://shop.example.com/admin#/gotowebinar/sheets/oauth/callback`
  
  > ⚠️ **Important:** Replace `your-shop-domain.com` with your actual Shopware URL

5. Click **"Create"**

**Save Your Credentials:**
A popup will appear with your credentials:
- ✅ **Client ID** - Copy this (looks like: `123456789-abcdef.apps.googleusercontent.com`)
- ✅ **Client Secret** - Copy this (looks like: `GOCSPX-aBcDeFgHiJkLmNoPqRsTuVwXyZ`)

> 🔐 **Security Note:** Keep these credentials secure! Never share them publicly or commit to version control.

---

## 4. Plugin Installation

### Method 1: Installation via Shopware Administration (Recommended for Non-Technical Users)

> ⚠️ **Note:** This method requires the plugin to be packaged as a ZIP file first.

1. Package the plugin directory as ZIP:
   ```bash
   cd custom/plugins
   zip -r GotoWebinarGoogleSheetsExport.zip GotoWebinarGoogleSheetsExport/
   ```

2. In Shopware Admin, go to: **Extensions → My extensions**
3. Click **"Upload extension"**
4. Select the ZIP file
5. Click **"Install"**
6. Click **"Activate"**

### Method 2: Installation via CLI (Recommended)

#### Step 1: Install Plugin Files

If you received the plugin as a ZIP file:
```bash
# Navigate to Shopware plugins directory
cd /path/to/shopware/custom/plugins

# Extract plugin
unzip GotoWebinarGoogleSheetsExport.zip

# Or if you have direct access, clone/copy plugin directory
```

If plugin is already in `custom/plugins/GotoWebinarGoogleSheetsExport/`, proceed to Step 2.

#### Step 2: Install Composer Dependencies

```bash
# Navigate to your Shopware root directory
cd /path/to/shopware

# Install plugin dependencies
composer require google/apiclient

# Regenerate autoloader
composer dump-autoload
```

#### Step 3: Refresh Plugin List

```bash
# Refresh Shopware's plugin list
bin/console plugin:refresh
```

Expected output:
```
Plugin list refreshed
```

#### Step 4: Install Plugin

```bash
# Install the plugin (this creates database tables)
bin/console plugin:install GotoWebinarGoogleSheetsExport
```

Expected output:
```
Plugin "GotoWebinarGoogleSheetsExport" has been installed successfully.
```

#### Step 5: Activate Plugin

```bash
# Activate the plugin
bin/console plugin:activate GotoWebinarGoogleSheetsExport
```

Expected output:
```
Plugin "GotoWebinarGoogleSheetsExport" has been activated successfully.
```

#### Step 6: Clear Cache

```bash
# Clear Shopware cache
bin/console cache:clear
```

#### Step 7: Build Administration (if needed)

```bash
# Only needed if admin interface doesn't appear
bin/build-administration.sh
```

### Verify Installation

1. Log into Shopware Administration
2. Go to: **Settings → System → Plugins**
3. Search for: `GotoWebinarGoogleSheetsExport`
4. Status should show: ✅ **Active**

---

## 5. Configuration

### Step 1: Access Plugin Configuration

**Option A: Via Plugin List**
1. Go to: **Settings → System → Plugins**
2. Find **"GotoWebinarGoogleSheetsExport"**
3. Click the **three dots (⋮)** → **"Configure"**

**Option B: Via Plugin Dashboard**
1. Go to: **Extensions → My extensions**
2. Find **"GotoWebinarGoogleSheetsExport"**
3. Click **"Configure"**

### Step 2: Feature Activation

**Card 1: Feature Activation**

- **✅ Enable Plugin:** Toggle ON to activate export functionality
- **📁 Category:** Select the product category to monitor
  - Default: "GotoWebinar"
  - Click the field and search for your category
  - Only orders containing products from this category will be exported

> 💡 **Tip:** If you can't find your category, ensure it exists in **Catalogues → Categories**

### Step 3: Google Sheets Configuration

**Card 2: Google Sheets Configuration**

Fill in the information you gathered earlier:

1. **Google Sheet ID:** Paste the Sheet ID from your Google Sheets URL
   ```
   Example: 1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms
   ```

2. **Worksheet Name:** Enter the exact name of the worksheet/tab
   ```
   Example: Bestellungen (or Sheet1)
   ```

3. **Google Client ID:** Paste the OAuth Client ID from Google Cloud Console
   ```
   Example: 123456789-abcdef.apps.googleusercontent.com
   ```

4. **Google Client Secret:** Paste the OAuth Client Secret
   ```
   Example: GOCSPX-aBcDeFgHiJkLmNoPqRsTuVwXyZ
   ```

5. **Google Refresh Token:** Leave this empty (will be filled automatically after OAuth)

### Step 4: Connect to Google Account

1. **Save** the configuration above
2. Navigate to: **Custom Menu → GotoWebinar Sheets Export** (new menu item)
3. Click: **"Connect to Google"** button
4. You'll be redirected to Google's authorization page
5. **Sign in** with the Google account that owns the spreadsheet
6. Review permissions: The app needs to **"See, edit, create, and delete your spreadsheets"**
7. Click **"Allow"**
8. You'll be redirected back to Shopware
9. You should see: ✅ **"Successfully connected to Google Sheets"**

> ⚠️ **Important:** Use the same Google account that owns the target spreadsheet, or ensure the spreadsheet is shared with the account you use.

### Step 5: Export Schedule Configuration

**Card 3: Export Schedule**

- **Export Interval:** Choose how often automatic exports should run
  - **Disabled** - No automatic exports (manual only)
  - **Every 15 minutes** - High frequency (for time-sensitive data)
  - **Hourly** - Recommended for most use cases
  - **Every 4 hours** - Moderate frequency
  - **Daily** - Once per day (at midnight)
  - **Weekly** - Once per week (Sunday at midnight)

> 💡 **Tip:** Start with "Hourly" and adjust based on your order volume.

### Step 6: Advanced Options (Optional)

**Card 4: Advanced Options**

- **Allow Duplicates:** ON by default
  - When ON: Same order/product can be exported multiple times
  - When OFF: Each order/product is exported only once
  
- **Log Errors:** ON by default
  - Logs export errors to Shopware system logs
  
- **Batch Size:** 50 (default)
  - Number of rows to export per scheduled run
  - Increase if you have many pending exports
  - Decrease if experiencing timeout issues

### Step 7: Save Configuration

Click **"Save"** at the bottom of the configuration page.

---

## 6. Usage

### 6.1 Automatic Export (Scheduled)

Once configured, the plugin works automatically:

1. **Customer places order** with a product from the configured category
2. **Order is logged** in the plugin's internal database
3. **Scheduled task runs** at the configured interval
4. **Data is exported** to your Google Sheet
5. **Export is logged** with timestamp and status

**No action required from you!** 🎉

### 6.2 Manual Export

To export pending orders immediately:

1. Go to: **Custom Menu → GotoWebinar Sheets Export**
2. You'll see the dashboard with:
   - Total exports
   - Last export time
   - Pending exports count
3. Click: **"Export Now"** button
4. Wait for confirmation: **"Successfully exported X orders"**

### 6.3 View Recent Exports

On the dashboard you'll see a table with the last 100 exports:

| Export Date | Order Number | Product Number | Customer | Status |
|-------------|--------------|----------------|----------|--------|
| 2025-12-22 10:30 | 10001 | BW-WEB-001 | Max Mustermann | ✅ Success |
| 2025-12-22 10:31 | 10002 | BW-WEB-002 | Anna Schmidt | ❌ Failed |

**Status indicators:**
- ✅ **Success** - Exported successfully
- ⏳ **Pending** - Waiting to be exported
- ❌ **Failed** - Export error (click for details)

### 6.4 Download CSV Export

To download a CSV file of recent exports:

1. Go to: **Custom Menu → Blauwasser Sheets Export**
2. Click: **"Download CSV"** button
3. A file named `gotowebinar_exports_YYYY-MM-DD.csv` will be downloaded

**CSV Contents:**
- Last 100 export entries
- Same data as in Google Sheets
- Can be opened in Excel, Google Sheets, etc.

### 6.5 Handling Multiple Products in One Order

If a customer orders multiple products from the monitored category in a single order:

- Each product will be exported as a **separate row**
- Same customer information will be repeated
- Different product numbers will be shown

**Example:**
Customer orders 2 webinars in one order:

| First Name | Last Name | Order Number | Product Number | Sales Channel | Email |
|------------|-----------|--------------|----------------|---------------|-------|
| Max | Mustermann | 10001 | BW-WEB-001 | Storefront | max@example.com |
| Max | Mustermann | 10001 | BW-WEB-002 | Storefront | max@example.com |

---

## 7. Troubleshooting

### Problem 1: Plugin Not Appearing in Admin

**Symptoms:**
- Plugin not visible in plugin list
- No "Blauwasser Sheets Export" menu

**Solutions:**

```bash
# 1. Refresh plugin list
bin/console plugin:refresh

# 2. Clear cache
bin/console cache:clear

# 3. Rebuild administration
bin/build-administration.sh

# 4. Check plugin is activated
bin/console plugin:list | grep GotoWebinar
```

**Expected output:**
```
GotoWebinarGoogleSheetsExport  1.0.0  Yes        Installed  Active
```

### Problem 2: OAuth Connection Fails

**Symptoms:**
- "Connect to Google" button doesn't work
- Error: "Invalid redirect URI"
- Stuck on Google authorization page

**Solutions:**

1. **Verify Redirect URI in Google Cloud Console:**
   - Go to Google Cloud Console → Credentials
   - Edit your OAuth client
   - Ensure redirect URI exactly matches:
     ```
     https://your-domain.com/admin#/blauwasser/sheets/oauth/callback
     ```
   - Check for typos, missing `https://`, wrong domain

2. **Check OAuth Credentials:**
   - Ensure Client ID and Secret are copied correctly
   - No extra spaces or line breaks
   - Re-copy from Google Cloud Console if needed

3. **Clear Browser Cache:**
   - Press `Ctrl+Shift+Delete` (or `Cmd+Shift+Delete` on Mac)
   - Clear cookies and cached data
   - Try OAuth connection again

### Problem 3: No Data Appearing in Google Sheets

**Symptoms:**
- Orders are placed but Google Sheet remains empty
- Exports show as "Success" but no rows added

**Solutions:**

1. **Verify Sheet Configuration:**
   - Double-check Sheet ID is correct
   - Verify Worksheet Name matches exactly (case-sensitive!)
   - Ensure Google account has edit access to the sheet

2. **Check Category Configuration:**
   - Verify the correct category is selected
   - Ensure test products are actually in that category
   - Check product category in: **Catalogues → Products** → [Product] → **Categories** tab

3. **Test Manual Export:**
   - Go to dashboard
   - Check if there are "Pending" exports
   - Click "Export Now"
   - Check export log for errors

4. **Check Logs:**
   ```bash
   # View Shopware logs
   tail -f var/log/prod.log | grep -i blauwasser
   ```

### Problem 4: Export Scheduled Task Not Running

**Symptoms:**
- No automatic exports happening
- Only manual exports work

**Solutions:**

1. **Verify Scheduled Tasks are Running:**
   ```bash
   # Check if scheduled task exists
   bin/console scheduled-task:list | grep blauwasser
   ```

2. **Run Scheduled Tasks Manually:**
   ```bash
   # Execute scheduled tasks
   bin/console scheduled-task:run
   ```

3. **Check Cron Job:**
   - Ensure Shopware's cron job is configured:
     ```bash
     */5 * * * * cd /path/to/shopware && php bin/console scheduled-task:run >> /dev/null 2>&1
     ```

4. **Verify Export Interval:**
   - Check plugin configuration
   - Ensure "Export Interval" is not set to "Disabled"

### Problem 5: "Rate Limit Exceeded" Errors

**Symptoms:**
- Exports fail with rate limit error
- Only first few rows export successfully

**Solutions:**

1. **Reduce Batch Size:**
   - Go to plugin configuration
   - Reduce "Batch Size" from 50 to 25 or 10
   - Save configuration

2. **Increase Export Interval:**
   - Change from "Every 15 minutes" to "Hourly"
   - Reduces frequency of API calls

3. **Wait and Retry:**
   - Google Sheets API limits: 100 requests per 100 seconds per user
   - Failed exports will be retried on next scheduled run

### Problem 6: Database Errors

**Symptoms:**
- "Table 'blauwasser_order_export' doesn't exist"
- Plugin installation errors

**Solutions:**

```bash
# 1. Uninstall plugin
bin/console plugin:uninstall BlauwasserGoogleSheetsExport

# 2. Reinstall plugin (recreates tables)
bin/console plugin:install BlauwasserGoogleSheetsExport

# 3. Activate plugin
bin/console plugin:activate BlauwasserGoogleSheetsExport

# 4. Clear cache
bin/console cache:clear
```

### Problem 7: Token Expired Errors

**Symptoms:**
- Exports worked before, now failing
- Error: "Invalid credentials" or "Token expired"

**Solutions:**

1. **Reconnect to Google:**
   - Go to plugin dashboard
   - Click "Disconnect from Google" (if available)
   - Click "Connect to Google" again
   - Re-authorize the application

2. **Check OAuth Consent Screen Status:**
   - Go to Google Cloud Console → OAuth consent screen
   - If app is in "Testing" mode, tokens expire after 7 days
   - Consider publishing the app or adding all users as test users

---

## 8. FAQ

### Q1: Can I export to multiple Google Sheets?

**A:** Not in version 1.0. You can only configure one Google Sheet. However, you can:
- Export different categories to different worksheets in the same sheet
- Use the CSV export feature to manually import to other sheets

### Q2: Can I customize which data fields are exported?

**A:** Not in version 1.0. The exported fields are fixed:
- First Name, Last Name, Order Number, Product Number, Sales Channel, Email

Future versions may include custom field mapping.

### Q3: What happens if Google Sheets is down?

**A:** The plugin will:
1. Mark the export as "Failed" in the database
2. Log the error in Shopware logs
3. Keep the export as "Pending"
4. Retry on the next scheduled run or manual export

### Q4: Can I export historical orders (before plugin was installed)?

**A:** No, the plugin only tracks orders placed **after** the plugin is installed and activated. Historical orders are not automatically exported.

**Workaround:** You can manually export historical order data using Shopware's built-in export features or custom SQL queries, then import to Google Sheets manually.

### Q5: Does this work with guest orders?

**A:** Yes! The plugin exports data from all orders, including guest checkouts. The customer email, first name, and last name are captured regardless of whether the customer has an account.

### Q6: How do I change the monitored category?

1. Go to plugin configuration
2. In "Feature Activation" card, change the "Category" field
3. Save configuration
4. New orders will be checked against the new category
5. Old pending exports will still use the old category setting

### Q7: Can I stop exporting a specific order?

**A:** Not directly in the admin UI. However, you can:
1. Deactivate the plugin temporarily
2. Or manually delete the pending export from the database:
   ```sql
   DELETE FROM blauwasser_order_export 
   WHERE order_number = '10001' AND export_status = 'pending';
   ```

### Q8: Is my data secure?

**A:** Yes, the plugin uses:
- ✅ Google OAuth2 for secure authentication (no passwords stored)
- ✅ Encrypted storage of refresh tokens in Shopware database
- ✅ HTTPS for all Google API communications
- ✅ No third-party services involved (direct Shopware ↔ Google connection)

**Important:** Ensure your Google Sheets access permissions are properly configured. Don't share the sheet publicly if it contains sensitive customer data.

### Q9: Does this affect my store's performance?

**A:** Minimal impact:
- ✅ Order placement is not delayed (export happens asynchronously)
- ✅ Scheduled tasks run in the background
- ✅ Batch processing prevents overload
- ✅ No impact on storefront performance

### Q10: How do I uninstall the plugin?

See section below.

---

## 9. Uninstalling the Plugin

### Option 1: Uninstall via Administration

1. Go to: **Settings → System → Plugins**
2. Find **"GotoWebinarGoogleSheetsExport"**
3. Click the **three dots (⋮)** → **"Deactivate"**
4. Wait for confirmation
5. Click the **three dots (⋮)** again → **"Uninstall"****
6. Choose one of:
   - **"Remove all plugin data"** - Deletes export history from database
   - **"Keep plugin data"** - Preserves export history

### Option 2: Uninstall via CLI

```bash
# Navigate to Shopware directory
cd /path/to/shopware

# Deactivate plugin
bin/console plugin:deactivate GotoWebinarGoogleSheetsExport

# Uninstall plugin (keeps data)
bin/console plugin:uninstall GotoWebinarGoogleSheetsExport

# Or uninstall and remove all data
bin/console plugin:uninstall --remove-data GotoWebinarGoogleSheetsExport

# Clear cache
bin/console cache:clear
```

### Complete Removal (Including Files)

After uninstalling via one of the methods above:

```bash
# Remove plugin files
rm -rf custom/plugins/GotoWebinarGoogleSheetsExport

# Regenerate autoloader
composer dump-autoload
```

> ⚠️ **Warning:** This permanently deletes all export history and cannot be undone!

---

## 10. Support & Contact

### Getting Help

1. **Check Troubleshooting Section** - Most common issues are covered above
2. **Check Shopware Logs:**
   ```bash
   tail -f var/log/prod.log | grep -i gotowebinar
   ```
3. **Review Google Cloud Console** - Check OAuth credentials and API quota

### Reporting Issues

When reporting issues, please include:
- ✅ Shopware version (`bin/console --version`)
- ✅ Plugin version
- ✅ Error message (exact text)
- ✅ Steps to reproduce
- ✅ Relevant log entries
- ✅ Screenshot (if applicable)

---

## 11. Best Practices

### Daily Operations

✅ **Do:**
- Monitor the export dashboard occasionally
- Check Google Sheets to ensure data is flowing correctly
- Keep OAuth token connected (re-authorize if expired)
- Review failed exports and resolve issues

❌ **Don't:**
- Don't modify the Google Sheet structure while exports are running
- Don't share your OAuth credentials
- Don't delete the worksheet configured in the plugin
- Don't manually delete database entries unless you know what you're doing

### Data Management

- **Backup Google Sheets regularly** (File → Make a copy)
- **Archive old data** periodically to keep sheet performant
- **Use Google Sheets filters/pivot tables** for data analysis
- **Export to CSV** periodically for local backups

### Performance Optimization

- **Adjust batch size** based on your order volume
- **Use appropriate export interval** (hourly for most cases)
- **Monitor Google API quota** in Google Cloud Console
- **Clean up old export logs** occasionally (keep last 1000 entries)

---

**End of User Manual**

For technical documentation and architecture details, see:
- `01_ARCHITECTURE_PLANNING.md`
- `03_TECHNICAL_DOCUMENTATION.md`

For support or questions, contact your Shopware administrator or plugin developer.
