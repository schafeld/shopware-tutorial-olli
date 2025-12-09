# SQL Logging Setup for Product Detail Pages

## Working Methods for SQL Debugging

### 1. Symfony Profiler (Recommended)
- Visit any product page: `http://127.0.0.1:8000/Detail/2a88d9b59d474c7e869d8071649be43c`
- Look at the bottom debug toolbar
- Click the database icon to see all SQL queries with timing

### 2. Custom Debug Route
- Use: `http://127.0.0.1:8000/product-debug/{productId}`
- Example: `http://127.0.0.1:8000/product-debug/2a88d9b59d474c7e869d8071649be43c`
- Check response headers for debug information
- Watch console for debug output

### 3. Real-time Log Monitoring
```bash
# In one terminal:
tail -f var/log/dev.log

# Or filter for database operations:
tail -f var/log/dev.log | grep -i -E "(query|sql|select|insert|update|delete)"
```

### 4. Console Command for Testing
```bash
# Debug specific product:
bin/console academy:product:debug 2a88d9b59d474c7e869d8071649be43c

# Debug first 5 products:
bin/console academy:product:debug
```

### 5. Browser Developer Tools
- Open Network tab in browser dev tools
- Filter by XHR/Fetch requests
- Look at response headers for `X-Debug-*` headers

## What You Get

### SQL Queries Visible:
- Product loading queries
- Category associations
- Media/image loading
- Manufacturer data
- Price calculations
- Stock information
- SEO URL generation

### Timing Information:
- Query execution time
- Total page load time
- Individual operation duration

## Example URLs to Test:
- `http://127.0.0.1:8000/product-debug/2a88d9b59d474c7e869d8071649be43c`
- `http://127.0.0.1:8000/product-debug/11dc680240b04f469ccba354cbf0b967`

## Console Commands:
```bash
# List all products with debug info:
bin/console academy:product:debug

# Debug specific product:
bin/console academy:product:debug 2a88d9b59d474c7e869d8071649be43c

# Watch logs in real-time:
tail -f var/log/dev.log | grep -E "(ProductDebug|SQL)"
```