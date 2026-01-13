# Deployment Guide - GotoWebinarGoogleSheetsExport

## Production Deployment Checklist

### Pre-Deployment

- [ ] All tests passing (`vendor/bin/phpunit`)
- [ ] OAuth credentials configured for production environment
- [ ] Google Sheet created and shared with service account
- [ ] Shop has valid SSL certificate (HTTPS)
- [ ] Scheduled task cron job configured
- [ ] Error logging configured (Sentry/Datadog optional)
- [ ] Backup current database before deployment
- [ ] Plugin configuration documented

### Environment-Specific Configuration

- [ ] Production Google OAuth Client ID/Secret
- [ ] Production Google Sheet ID
- [ ] Production redirect URI added to Google Cloud Console
- [ ] Export schedule appropriate for production load
- [ ] Batch size optimized for your server

---

## Deployment Methods

### Method 1: Composer Deployment (Recommended)

For shops using Composer-based deployment:

```bash
# On your local machine, commit and tag the plugin
cd custom/plugins/GotoWebinarGoogleSheetsExport
git tag v1.0.0
git push origin v1.0.0

# On production server
cd /var/www/shopware
composer require goto/webinar-google-sheets-export:^1.0

# Install and activate
bin/console plugin:refresh
bin/console plugin:install GotoWebinarGoogleSheetsExport --activate
bin/console cache:clear
```

### Method 2: Manual Upload

For traditional FTP/SFTP deployment:

```bash
# 1. Build plugin package locally
cd custom/plugins/GotoWebinarGoogleSheetsExport
composer install --no-dev --optimize-autoloader
zip -r GotoWebinarGoogleSheetsExport-v1.0.0.zip . \
  -x "*.git*" -x "tests/*" -x "node_modules/*"

# 2. Upload to server
scp GotoWebinarGoogleSheetsExport-v1.0.0.zip user@your-server:/var/www/shopware/custom/plugins/

# 3. On server, extract and install
cd /var/www/shopware/custom/plugins
unzip GotoWebinarGoogleSheetsExport-v1.0.0.zip -d GotoWebinarGoogleSheetsExport
cd ../../
bin/console plugin:refresh
bin/console plugin:install GotoWebinarGoogleSheetsExport --activate
bin/console cache:clear
```

### Method 3: Git Pull

For servers with direct Git access:

```bash
# On production server
cd /var/www/shopware/custom/plugins

# Clone or pull latest
git clone <repository-url> GotoWebinarGoogleSheetsExport
# OR if already exists:
cd GotoWebinarGoogleSheetsExport && git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Install plugin
cd ../../
bin/console plugin:refresh
bin/console plugin:install GotoWebinarGoogleSheetsExport --activate
bin/console cache:clear
```

---

## Post-Deployment Steps

### 1. Verify Installation

```bash
# Check plugin status
bin/console plugin:list | grep GotoWebinar

# Expected output:
# GotoWebinarGoogleSheetsExport    1.0.0    Yes    Yes

# Check database migration
bin/console dbal:run-sql "SHOW TABLES LIKE 'gotowebinar_order_export'"
```

### 2. Configure Plugin

```bash
# Option A: Via Admin UI
# Go to Settings → System → Plugins → GotoWebinarGoogleSheetsExport → Configure

# Option B: Via CLI (for automation)
bin/console system:config:set GotoWebinarGoogleSheetsExport.config.enabled true
bin/console system:config:set GotoWebinarGoogleSheetsExport.config.categoryId "YOUR_CATEGORY_ID"
bin/console system:config:set GotoWebinarGoogleSheetsExport.config.googleSheetId "YOUR_SHEET_ID"
bin/console system:config:set GotoWebinarGoogleSheetsExport.config.worksheetName "Bestellungen"
```

### 3. Complete OAuth Authentication

**Important:** This must be done manually after deployment.

```bash
# Generate OAuth URL (see Installation Guide Step 4)
# Complete authorization flow
# Exchange code for refresh token
```

### 4. Test Export

```bash
# Dry run (won't actually export)
bin/console gotowebinar:export-orders --help

# Test with force flag (even if plugin disabled)
bin/console gotowebinar:export-orders --force --limit 1

# Expected output:
# ✅ Successfully exported 1 row(s) to Google Sheets
```

### 5. Verify Scheduled Tasks

```bash
# Register scheduled task
bin/console scheduled-task:register

# List all scheduled tasks
bin/console scheduled-task:list | grep gotowebinar

# Run once manually
bin/console scheduled-task:run --filter="gotowebinar.google_sheets_export"
```

### 6. Configure Cron Job

Add to server crontab:

```bash
# Edit crontab
sudo crontab -e -u www-data

# Add scheduled task runner (runs every minute)
* * * * * cd /var/www/shopware && php bin/console scheduled-task:run >> /var/log/shopware/scheduled_tasks.log 2>&1
```

---

## Configuration Management

### Environment Variables

For sensitive configuration, use environment variables:

```bash
# In .env.local
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret
GOOGLE_SHEET_ID=your_sheet_id
```

Then update plugin to read from env vars (optional enhancement).

### Configuration Backup

```bash
# Export current configuration
bin/console system:config:dump GotoWebinarGoogleSheetsExport > config_backup.json

# Import configuration (e.g., on another environment)
bin/console system:config:import config_backup.json
```

---

## Monitoring & Logging

### 1. Enable Detailed Logging

```bash
# Check log configuration in config/packages/prod/monolog.yaml
# Ensure logs are written to var/log/prod.log

# Tail logs in real-time
tail -f var/log/prod.log | grep GotoWebinar
```

### 2. Set Up Error Alerting (Optional)

**Using Sentry:**

```bash
composer require sentry/sentry-symfony

# Configure in config/packages/sentry.yaml
```

**Using Email Notifications:**

```php
// Add to ExportOrdersTaskHandler.php
if ($status === 'failed') {
    $this->sendAdminAlert($error);
}
```

### 3. Monitor Export Stats

Create a monitoring script:

```bash
#!/bin/bash
# monitor_exports.sh

cd /var/www/shopware

# Get pending export count
PENDING=$(bin/console dbal:run-sql "SELECT COUNT(*) FROM gotowebinar_order_export WHERE export_status='pending'" --raw | tail -n 1)

# Alert if too many pending
if [ "$PENDING" -gt 100 ]; then
    echo "⚠️ WARNING: $PENDING pending exports!" | mail -s "Webinar Export Alert" admin@example.com
fi

# Get failed count in last hour
FAILED=$(bin/console dbal:run-sql "SELECT COUNT(*) FROM gotowebinar_order_export WHERE export_status='failed' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)" --raw | tail -n 1)

if [ "$FAILED" -gt 10 ]; then
    echo "❌ ERROR: $FAILED failed exports in last hour!" | mail -s "Webinar Export Error" admin@example.com
fi
```

Run via cron:

```bash
# Check export health every 15 minutes
*/15 * * * * /usr/local/bin/monitor_exports.sh
```

---

## Performance Optimization

### 1. Database Indexing

Already included in migration, but verify:

```sql
SHOW INDEX FROM gotowebinar_order_export;
```

### 2. Optimize Batch Size

Test different batch sizes:

```bash
# Test with 10 exports
time bin/console gotowebinar:export-orders --limit 10

# Test with 50 exports
time bin/console gotowebinar:export-orders --limit 50

# Test with 100 exports
time bin/console gotowebinar:export-orders --limit 100
```

Adjust `batchSize` config based on results.

### 3. Caching

Enable PHP OPcache in production:

```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
```

### 4. Schedule Optimization

Avoid peak hours:

```bash
# Instead of running every hour at :00
# Stagger the run time
0 */6 * * * cd /var/www/shopware && php bin/console scheduled-task:run
```

---

## Backup Strategy

### 1. Database Backup

```bash
# Backup export data table
mysqldump -u shopware -p shopware_db gotowebinar_order_export > backup_exports_$(date +%Y%m%d).sql

# Automate daily backups
0 2 * * * mysqldump -u shopware -p'PASSWORD' shopware_db gotowebinar_order_export > /backups/exports_$(date +\%Y\%m\%d).sql
```

### 2. Configuration Backup

```bash
# Backup plugin configuration
bin/console system:config:dump GotoWebinarGoogleSheetsExport > config_backup_$(date +%Y%m%d).json
```

### 3. Google Sheets Backup

Enable Google Sheets version history:
- In Google Sheet: File → Version History → See Version History
- Or use Google Takeout for full export

---

## Rollback Procedure

If something goes wrong:

```bash
# 1. Deactivate plugin immediately
bin/console plugin:deactivate GotoWebinarGoogleSheetsExport

# 2. Clear cache
bin/console cache:clear

# 3. Restore previous version (if using Git)
cd custom/plugins/GotoWebinarGoogleSheetsExport
git checkout v0.9.0  # or previous stable version

# 4. Reinstall
cd /var/www/shopware
bin/console plugin:install GotoWebinarGoogleSheetsExport --activate

# 5. Restore database backup if needed
mysql -u shopware -p shopware_db < backup_exports_20250107.sql

# 6. Restore configuration
bin/console system:config:import config_backup_20250107.json
```

---

## Security Checklist

- [ ] HTTPS enforced on production
- [ ] Google OAuth credentials stored securely (encrypted)
- [ ] Refresh token stored in database (not in version control)
- [ ] Admin API endpoints protected by Shopware ACL
- [ ] Logs don't contain sensitive customer data
- [ ] Google Sheet access limited to necessary accounts
- [ ] Regular security updates applied
- [ ] File permissions set correctly (755 for directories, 644 for files)

### File Permissions

```bash
# Set correct permissions
cd /var/www/shopware
chown -R www-data:www-data custom/plugins/GotoWebinarGoogleSheetsExport
find custom/plugins/GotoWebinarGoogleSheetsExport -type d -exec chmod 755 {} \;
find custom/plugins/GotoWebinarGoogleSheetsExport -type f -exec chmod 644 {} \;
```

---

## Scaling Considerations

### High-Volume Shops

For shops with >100 webinar orders per day:

**1. Increase Batch Size:**
```bash
bin/console system:config:set GotoWebinarGoogleSheetsExport.config.batchSize 100
```

**2. Run Exports More Frequently:**
```bash
# Every 15 minutes instead of hourly
*/15 * * * * cd /var/www/shopware && php bin/console scheduled-task:run
```

**3. Use Google Sheets API Batching:**
- Already implemented in GoogleSheetsService
- Sends up to 100 rows per API call

**4. Consider Multiple Sheets:**
- Archive old data to separate sheet monthly
- Keep main sheet under 10,000 rows for performance

### Load Balancing

If using multiple app servers:

- Only run scheduled tasks on ONE server
- Or use a dedicated cron server
- Prevent duplicate exports

```bash
# On primary server only
*/15 * * * * cd /var/www/shopware && php bin/console scheduled-task:run
```

---

## Maintenance

### Regular Tasks

**Weekly:**
- Check export success rate
- Review error logs
- Verify Google Sheets access

**Monthly:**
- Archive old export logs
- Review and optimize batch size
- Check Google API quota usage
- Update dependencies

**Quarterly:**
- Full backup of export data
- Review and update documentation
- Security audit
- Performance optimization review

### Cleanup Old Data

```bash
# Delete export logs older than 90 days
bin/console dbal:run-sql "DELETE FROM gotowebinar_order_export WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY) AND export_status = 'success'"
```

Or create a scheduled task for automatic cleanup.

---

## Troubleshooting Production Issues

### Issue: Exports suddenly stopped

**Check:**
```bash
# 1. Is plugin still active?
bin/console plugin:list | grep GotoWebinar

# 2. Is scheduled task running?
ps aux | grep scheduled-task

# 3. Check cron logs
tail -f /var/log/cron.log

# 4. Manual test
bin/console gotowebinar:export-orders --force
```

### Issue: High number of failed exports

**Investigate:**
```bash
# Check recent errors
bin/console dbal:run-sql "SELECT error_message, COUNT(*) as count FROM gotowebinar_order_export WHERE export_status='failed' GROUP BY error_message ORDER BY count DESC LIMIT 5"

# Common causes:
# - Google API rate limit exceeded
# - OAuth token expired
# - Sheet ID or worksheet name changed
# - Network connectivity issues
```

### Issue: Performance degradation

**Check:**
```bash
# 1. Export table size
bin/console dbal:run-sql "SELECT COUNT(*) FROM gotowebinar_order_export"

# 2. Check for missing indexes
bin/console dbal:run-sql "SHOW INDEX FROM gotowebinar_order_export"

# 3. Analyze slow queries
# Enable MySQL slow query log and review
```

---

## Support & Contact

For production issues:

1. **Check logs first:** `var/log/prod.log`
2. **Review documentation:** All docs in `/docs` folder
3. **Check database:** Query `gotowebinar_order_export` table
4. **Test manually:** Use CLI command to isolate issue
5. **Contact support:** [your-support-email]

---

## Version History

### v1.0.0 (2025-01-07)
- Initial production release
- Core export functionality
- OAuth2 authentication
- Scheduled and manual exports
- CSV export capability
- Admin API endpoints

---

**Deployment Complete! 🚀**

Remember to monitor exports for the first 24 hours after deployment.
