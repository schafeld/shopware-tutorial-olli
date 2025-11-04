# Shopware 6 Command Cheat Sheet

## Essential Daily Commands 📝

### Plugin Management
```bash
# Refresh plugin list (discovers new plugins)
bin/console plugin:refresh

# List all plugins
bin/console plugin:list

# Install a plugin
bin/console plugin:install PluginName

# Install and activate in one command
bin/console plugin:install --activate PluginName

# Activate an installed plugin
bin/console plugin:activate PluginName

# Deactivate a plugin
bin/console plugin:deactivate PluginName

# Update a plugin
bin/console plugin:update PluginName

# Uninstall a plugin (keeps data)
bin/console plugin:uninstall PluginName

# Uninstall and remove data
bin/console plugin:uninstall --keep-user-data=false PluginName

# Create a new plugin
bin/console plugin:create MyNewPlugin

# Validate plugin structure
bin/console plugin:validate MyPlugin
```

### Cache Management
```bash
# Clear all caches (most common)
bin/console cache:clear

# Clear specific cache pools
bin/console cache:pool:clear cache.object
bin/console cache:pool:clear cache.http

# Warm up cache
bin/console cache:warmup

# Clear only HTTP cache
bin/console http:cache:warm:up

# Clear template cache
bin/console theme:compile
```

### Database & Migrations
```bash
# Run migrations
bin/console database:migrate --all

# Run migrations for specific plugin
bin/console database:migrate MyPlugin --all

# Create migration
bin/console database:create-migration -p MyPlugin

# Check migration status
bin/console database:migrate-status

# Reset database (CAREFUL!)
bin/console database:drop --force
bin/console database:create
bin/console database:migrate --all
```

### Theme & Frontend
```bash
# Compile themes
bin/console theme:compile

# Refresh theme
bin/console theme:refresh

# Change active theme
bin/console theme:change ThemeName

# List available themes
bin/console theme:list

# Create theme
bin/console theme:create MyTheme

# Dump theme configuration
bin/console theme:dump

# Build storefront (production)
./bin/build-storefront.sh

# Watch storefront changes (development)
./bin/watch-storefront.sh
```

### Administration Panel
```bash
# Build administration (production)
./bin/build-administration.sh

# Watch administration changes (development)
./bin/watch-administration.sh

# Create admin user
bin/console user:create admin

# Change user password
bin/console user:change-password admin
```

### Sales Channel & System
```bash
# List sales channels
bin/console sales-channel:list

# Create sales channel
bin/console sales-channel:create:storefront

# Create API sales channel
bin/console sales-channel:create:headless

# Update sales channel domain
bin/console sales-channel:update:domain

# System configuration
bin/console system:config:get core.basicInformation.email
bin/console system:config:set core.basicInformation.email "admin@example.com"

# Generate JWT keys
bin/console system:generate-jwt-secret
```

### Import/Export & Data
```bash
# Import demo data
bin/console framework:demodata

# Generate URL rewrites
bin/console dal:refresh:index

# Refresh search index
bin/console es:index

# Clear search index
bin/console es:reset

# Reindex products
bin/console product:reindex
```

### Development Tools
```bash
# Generate data fixtures
bin/console framework:demodata --products 100

# List all routes
bin/console debug:router

# Show specific route info
bin/console debug:router frontend.home

# List all services
bin/console debug:container

# Show service info
bin/console debug:container product.repository

# List all events
bin/console debug:event-dispatcher

# Validate service container
bin/console lint:container

# Check requirements
bin/console system:check-requirements
```

### Scheduled Tasks & Jobs
```bash
# Run scheduled tasks
bin/console scheduled-task:run

# List scheduled tasks
bin/console scheduled-task:list

# Run message queue consumer
bin/console messenger:consume async

# Stop message queue workers
bin/console messenger:stop-workers
```

## Development Workflow Commands

### Starting Development
```bash
# 1. Fresh start
composer install
bin/console system:install --create-database --basic-setup

# 2. Or update existing
composer update
bin/console database:migrate --all
bin/console cache:clear
```

### Plugin Development Cycle
```bash
# 1. Create plugin
bin/console plugin:create MyPlugin

# 2. Develop features
# ... edit files ...

# 3. Install/refresh plugin
bin/console plugin:refresh
bin/console plugin:install --activate MyPlugin

# 4. Test and iterate
bin/console cache:clear
# ... test functionality ...

# 5. Update after changes
bin/console plugin:update MyPlugin
bin/console cache:clear
```

### Frontend Development Cycle
```bash
# 1. Start development mode
./bin/watch-storefront.sh     # In one terminal
./bin/watch-administration.sh # In another terminal (if needed)

# 2. Make changes to SCSS/JS files
# ... edit files ...

# 3. Changes are automatically compiled

# 4. For production build
./bin/build-storefront.sh
./bin/build-administration.sh
```

## Debugging Commands

### Error Investigation
```bash
# Check logs
tail -f var/log/prod.log
tail -f var/log/dev.log

# Validate configuration
bin/console lint:yaml config/
bin/console lint:twig custom/

# Check system status
bin/console system:check-requirements
bin/console debug:container --parameters
```

### Performance Analysis
```bash
# Profile cache
bin/console debug:cache

# Check database queries
bin/console debug:doctrine

# Memory usage
bin/console about

# Clear opcache
bin/console opcache:clear
```

## Environment-Specific Commands

### Production Environment
```bash
# Optimize for production
composer install --no-dev --optimize-autoloader
bin/console cache:clear --env=prod
bin/console cache:warmup --env=prod
./bin/build-storefront.sh
./bin/build-administration.sh
```

### Development Environment
```bash
# Switch to dev mode
# Edit .env: APP_ENV=dev
bin/console cache:clear
bin/console assets:install
```

## Quick Reference Parameters

### Common Flags
- `--help` or `-h`: Show help for command
- `--verbose` or `-v`: Verbose output
- `--quiet` or `-q`: Quiet mode
- `--env=dev`: Use development environment
- `--no-interaction` or `-n`: Non-interactive mode
- `--force`: Force operation

### Plugin Command Options
- `--activate`: Activate after installation
- `--keep-user-data=false`: Remove user data on uninstall
- `--clearCache`: Clear cache after operation

## Emergency Commands 🚨

### System Recovery
```bash
# Complete cache reset
rm -rf var/cache/*
bin/console cache:clear

# Reset theme compilation
rm -rf public/theme/*
bin/console theme:compile

# Database recovery (CAREFUL!)
bin/console database:drop --force
bin/console database:create
bin/console database:migrate --all
bin/console framework:demodata
```

### Plugin Issues
```bash
# Force plugin reinstall
bin/console plugin:uninstall --keep-user-data=false PluginName
bin/console plugin:refresh
bin/console plugin:install --activate PluginName
```

**💡 Pro Tip**: Create aliases for frequently used commands:
```bash
# Add to your ~/.zshrc or ~/.bashrc
alias sw-clear='bin/console cache:clear'
alias sw-plugin-refresh='bin/console plugin:refresh'
alias sw-build='./bin/build-storefront.sh && ./bin/build-administration.sh'
```