# GotoWebinarGoogleSheetsExport Plugin

**Version:** 1.0.0  
**Shopware Version:** 6.5.0+  
**Status:** Planning Phase - Documentation Complete

---

## Overview

This Shopware 6 plugin automatically exports order data to Google Sheets when customers purchase products from a configurable category (default: "GotoWebinar"). The plugin supports both scheduled automatic exports and manual on-demand exports via an administration dashboard.

## Features

- ✅ **Automatic Export** - Scheduled exports at configurable intervals (15 minutes to weekly)
- ✅ **Manual Export** - On-demand export via admin dashboard button
- ✅ **Category-Based Filtering** - Export only orders containing products from specific category
- ✅ **OAuth2 Authentication** - Secure Google account integration
- ✅ **Multi-Product Support** - Each product exported as separate row
- ✅ **Export Tracking** - Local database tracks all exports with status
- ✅ **CSV Export** - Download last 100 exports as CSV file
- ✅ **Error Handling** - Comprehensive logging and retry mechanism
- ✅ **Admin Dashboard** - View statistics, recent exports, and manage settings

## Exported Data Fields

For each product in an order that matches the configured category:
- Customer First Name
- Customer Last Name
- Order Number
- Product Number
- Sales Channel Name
- Customer Email

## Documentation

### 📋 [01_ARCHITECTURE_PLANNING.md](docs/01_ARCHITECTURE_PLANNING.md)
Complete technical blueprint for AI-driven development including:
- System architecture and components
- Database schema design
- Service layer structure
- API integration details
- Implementation phases
- Development guidelines

### 📖 [02_USER_MANUAL.md](docs/02_USER_MANUAL.md)
End-user guide covering:
- Prerequisites and requirements
- Google Sheets setup instructions
- OAuth2 configuration walkthrough
- Plugin installation (CLI and admin methods)
- Configuration settings
- Daily usage and operations
- Troubleshooting common issues
- FAQ section

### 🔧 [03_TECHNICAL_DOCUMENTATION.md](docs/03_TECHNICAL_DOCUMENTATION.md)
Developer and technical administrator guide including:
- Detailed system architecture
- Data flow diagrams
- Technical decision rationale
- Security implementation
- Performance optimization
- Error handling strategies
- Testing approach
- Deployment procedures
- Extension guidelines

## Project Status

**Current Phase:** Planning & Documentation  
**Next Phase:** Implementation

### Completed
- ✅ Requirements gathering
- ✅ Architecture design
- ✅ Database schema design
- ✅ User documentation
- ✅ Technical documentation
- ✅ Development guidelines

### Pending
- ⏳ Plugin scaffolding
- ⏳ Core service implementation
- ⏳ Google Sheets API integration
- ⏳ Admin interface development
- ⏳ Testing
- ⏳ Deployment

## Quick Start (for AI Development)

When ready to implement, follow this order:

1. **Read Planning Document First**
   - Review [01_ARCHITECTURE_PLANNING.md](docs/01_ARCHITECTURE_PLANNING.md)
   - Understand all technical decisions
   - Follow implementation phases

2. **Start with Foundation**
   ```bash
   # Generate plugin structure
   bin/console plugin:create BlauwasserGoogleSheetsExport
   
   # Create database migration
   # Implement entity definitions
   # Set up service container
   ```

3. **Build Services Layer**
   - CategoryFilterService
   - OrderExportService
   - GoogleSheetsService
   - CsvExportService

4. **Implement Event Handling**
   - OrderPlacedSubscriber
   - Scheduled task handler

5. **Create Admin Interface**
   - Configuration UI
   - Dashboard
   - Manual export functionality

6. **Test & Deploy**
   - Unit tests
   - Integration tests
   - Manual testing
   - Production deployment

## System Requirements

### Shopware
- Shopware 6.5.0 or higher
- PHP 8.1 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Composer

### External Services
- Google Cloud Platform account
- Google Sheets API enabled
- OAuth2 credentials configured

### Server
- HTTPS enabled (required for OAuth)
- Scheduled tasks (cron) configured
- Internet connectivity for Google API

## Installation Overview

Detailed installation instructions are in [02_USER_MANUAL.md](docs/02_USER_MANUAL.md).

**Quick Install (CLI):**
```bash
# Install dependencies
composer require google/apiclient

# Refresh and install plugin
bin/console plugin:refresh
bin/console plugin:install --activate GotoWebinarGoogleSheetsExport

# Clear cache
bin/console cache:clear
```

## Configuration

All configuration is managed via Shopware Administration:
- **Settings → System → Plugins → GotoWebinarGoogleSheetsExport**

Key settings:
- Enable/disable plugin
- Select category to monitor
- Google Sheets credentials (OAuth2)
- Export schedule interval
- Advanced options (batch size, duplicate handling)

## Support & Maintenance

### Logging
All export activities and errors are logged to:
- `var/log/prod.log` (production)
- `var/log/dev.log` (development)

### Monitoring
Monitor export health via:
- Admin dashboard statistics
- Database queries (see Technical Documentation)
- System logs

### Backup
Regular backups recommended for:
- `gotowebinar_order_export` table
- Plugin configuration in `system_config` table
- Google Sheets (use Google's version history)

## Security

- ✅ OAuth2 secure authentication (no passwords stored)
- ✅ Encrypted storage of refresh tokens
- ✅ HTTPS enforced for all Google API calls
- ✅ Data sanitization to prevent injection attacks
- ✅ Admin-only access to configuration
- ✅ No sensitive data in logs

## Performance

- ⚡ Asynchronous export (doesn't block order placement)
- ⚡ Batch processing (50 rows per API call)
- ⚡ Database indexing for fast queries
- ⚡ Token caching to reduce API calls
- ⚡ Configurable batch size and intervals

## License

MIT License (or specify your license)

## Author

Oliver Schafeld / GotoWebinar Integration

## Contributing

This plugin is designed for AI-driven development. When implementing:
1. Follow all guidelines in [01_ARCHITECTURE_PLANNING.md](docs/01_ARCHITECTURE_PLANNING.md)
2. Maintain code style consistency (PSR-12)
3. Add tests for all new functionality
4. Update documentation as needed

## Changelog

### Version 1.0.0 (Planned)
- Initial release
- Order export to Google Sheets
- Scheduled and manual export modes
- OAuth2 authentication
- Admin dashboard
- CSV export functionality
- Comprehensive error handling

---

**Ready for Implementation** 🚀

All planning documents are complete. The plugin can now be implemented following the architecture outlined in the documentation.
