# Changelog

All notable changes to the GotoWebinarGoogleSheetsExport plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.1] - 2026-01-16

### Fixed
- **Category filtering bug:** Fixed issue where all products in an order were exported when only some products belonged to the configured category. Now only products from the configured category are exported.
- **CategoryFilterService path handling:** Fixed `in_array()` TypeError - `category->getPath()` returns a pipe-delimited string, not an array.

### Added
- **Reset exports command:** New CLI command `gotowebinar:reset-exports` to clear the export log table for development/testing purposes.
  - `--status` option to only delete entries with a specific status (pending, success, failed)
  - `--force` option to skip confirmation prompt
  - Batched deletion for large datasets

## [1.0.0] - 2025-01-07

### Added
- Initial release of GotoWebinarGoogleSheetsExport plugin
- Automatic export of webinar order data to Google Sheets
- OAuth2 authentication with Google Sheets API
- Category-based product filtering for exports
- Scheduled task for automatic exports at configurable intervals
- Manual export via CLI command (`gotowebinar:export-orders`)
- Export log tracking in database (`gotowebinar_order_export` table)
- Admin API endpoints for manual exports and statistics
- CSV export functionality for export logs
- Configuration UI in Shopware Administration
- Support for multiple products per order (each as separate row)
- Error handling and logging
- Event subscriber for order payment events
- Unit tests for core services
- Comprehensive documentation:
  - Architecture planning document
  - User manual
  - Technical documentation
  - Installation guide
  - Deployment guide
  - Developer reference

### Features
- ✅ Export order data: First name, Last name, Order number, Product number, Sales channel, Email
- ✅ Configurable export schedule (15 minutes to weekly, or manual only)
- ✅ Batch processing with configurable batch size
- ✅ Duplicate export handling
- ✅ Multi-product support (one row per product)
- ✅ Export status tracking (pending, success, failed)
- ✅ Google Sheets API integration with token refresh
- ✅ CSV download of export history
- ✅ Scheduled task system integration
- ✅ Comprehensive error logging

### Technical Details
- Compatible with Shopware 6.5.0+
- PHP 8.1+ required
- Uses Google API PHP Client v2.15+
- Database migration for export log table
- Shopware DAL for data access
- Dependency injection via Symfony
- PSR-12 code style
- PHPUnit tests included

### Configuration Options
- Enable/disable plugin
- Select webinar category
- Google Sheet ID and worksheet name
- OAuth2 credentials (Client ID, Client Secret)
- Export interval selection
- Batch size (1-100)
- Allow duplicate exports
- Error logging toggle

### Security
- OAuth2 secure authentication
- Encrypted token storage
- HTTPS enforcement
- Admin-only API access
- No sensitive data in logs
- SQL injection prevention via DAL

### Performance
- Asynchronous export (non-blocking)
- Batch processing (50 exports per run by default)
- Database indexing for fast queries
- Token caching
- Configurable batch size

### Documentation
- Complete architecture documentation
- Step-by-step installation guide
- Production deployment guide
- User manual with screenshots
- Technical deep dive
- Developer quick reference
- Troubleshooting guide
- FAQ section

## [Unreleased]

### Planned for v1.1.0
- Admin UI dashboard widget for export statistics
- Admin UI button for manual export trigger
- OAuth flow directly in admin panel (no CLI needed)
- Export retry mechanism for failed exports
- Email notifications for export failures
- Export scheduling by time of day
- Multiple Google Sheet destinations
- Custom field mapping configuration
- Export filtering by sales channel
- API rate limit handling with backoff
- Webhook for real-time export notifications

### Planned for v2.0.0
- Support for custom export templates
- Multiple category support
- Advanced filtering rules
- Export to multiple formats (CSV, JSON, XML)
- Integration with other webinar platforms (Zoom, GoToWebinar)
- Custom event triggers for export
- Multi-language support in Google Sheets
- Export analytics and reporting dashboard

---

## Version History

- **1.0.0** (2025-01-07) - Initial release

---

## Upgrade Guide

### From Planning to v1.0.0

This is the initial release. Follow the [Installation Guide](04_INSTALLATION_GUIDE.md) for setup.

---

## Breaking Changes

None yet (initial release)

---

## Deprecations

None yet (initial release)

---

## Bug Fixes

None yet (initial release)

---

## Contributors

- Oliver Schafeld - Initial development and documentation

---

For detailed changes in each file, see the git commit history.
