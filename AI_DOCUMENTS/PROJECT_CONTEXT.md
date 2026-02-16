# Project Context: shopware-tutorial-olli

**Last Updated:** 2026-02-16

## Database Configuration

### CRITICAL: `.env.local` is the authoritative config file

Symfony loads env files in this order: `.env` → `.env.local` (overrides).  
**`.env.local` always wins.** Never edit `.env` for database settings — changes there have NO effect.

**Database runs in Docker container with random host ports:**
- The MariaDB container exposes port 3306 internally, but Docker maps it to a **random host port** that changes on every `docker compose up`
- Credentials: `root` / `root`, database name: `shopware`

### When "Connection refused" errors occur:

```bash
# 1. Check the ACTUAL port Docker assigned:
docker compose ps
# Look for the database line, e.g.: 0.0.0.0:51255->3306/tcp

# 2. Update .env.local with the correct port:
# DATABASE_URL=mysql://root:root@127.0.0.1:<PORT>/shopware?serverVersion=8.0&logging=1

# 3. Clear Symfony cache (it caches env values!):
rm -rf var/cache/

# 4. Verify:
bin/console bundle:dump
# Should show [OK] WITHOUT any "Connection refused" warnings
```

### Build commands also need the DB connection:
- `bin/build-administration.sh` runs `bundle:dump` which needs DB
- `bin/build-storefront.sh` runs `bundle:dump` which needs DB
- Workaround without DB: `SHOPWARE_SKIP_BUNDLE_DUMP=1 bin/build-administration.sh`
  (but `plugins.json` won't be updated — only use if it's already correct)

## Testing Setup

**Unit Tests DO NOT require database connection**
- Unit tests use mocks, not real services
- Should run without database being available
- Use `--no-configuration` flag to skip TestBootstrap.php

**Integration Tests DO require database**
- Uses real database connection
- Requires Docker containers to be running
- Uses TestBootstrap.php to set up Shopware environment

## PHP Version

- Currently using: PHP 8.3.29
- Shopware 6.7.3.1 supports: PHP 8.2, 8.3, 8.4
- PHPUnit version: 9.6.31

## Security Notes

- `composer.json` has `"block-insecure": false` for local development only
- **Must remove before production deployment**
- Current version has known security advisories (acceptable for learning)
