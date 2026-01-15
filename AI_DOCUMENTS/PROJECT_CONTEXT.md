# Project Context: shopware-tutorial-olli

**Last Updated:** 2026-01-15

## Database Configuration

**Database runs in Docker container with potentially shifting ports**

- Database credentials are stored in `.env.local` (not in version control)
- Connection settings may change when containers restart
- **Important:** When database connection errors occur, check:
  1. Is the Docker container running? (`docker ps`)
  2. Check `.env.local` for current connection details
  3. Port may have changed from default 3306

**Common Database Connection Error:**
```
SQLSTATE[HY000] [2002] Connection refused
```
This usually means:
- Docker database container isn't running, OR
- Port has shifted and `.env.local` needs updating

**Solution:**
```bash
# Check if database container is running
docker ps | grep mysql

# Start containers if needed
docker-compose up -d

# Verify connection settings in .env.local
cat .env.local | grep DATABASE
```

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
