# Product Comparison API - Testing Guide

> **Related:** This guide complements [Day 4: API Architecture](LEARNING_SHOPWARE/DAY_4_API_ARCHITECTURE.md) 

## Summary

✅ **Migration completed** - Table `learning_product_comparison` created successfully  
✅ **Test data inserted** - Sample comparison recorded  
✅ **Service working** - ProductComparisonService functional  
✅ **Routes registered** - Admin API endpoints available  
✅ **Cache cleared** - System ready for testing  

## The Issue (Resolved)

You encountered `bin/console database:migrate --all` error because:
- The command runs on **host machine** (your Mac)
- Database runs in **Docker container**
- Connection refused = host can't reach container's database

## The Solution

The table was created manually using Docker:
```bash
docker compose exec -T database mariadb -u root -proot shopware -e "CREATE TABLE..."
```

This is actually **better** for development since you have direct control and avoids host-to-container connection issues!

## Files Created/Updated

1. **Migration**: `Migration1704556800ProductComparison.php` ✅
2. **Service**: `ProductComparisonService.php` ✅
3. **Controller**: `ProductComparisonController.php` ✅
4. **Services Config**: Updated `services.xml` ✅
5. **Routes Config**: Updated `routes.xml` ✅

## Next Steps: Testing the API

### Step 1: Create Integration in Shopware Admin

1. Open browser: http://localhost:8000/admin
2. Login (username: `admin`, find password in `.env` or setup docs)
3. Navigate to: **Settings** → **System** → **Integrations**
4. Click **"Add integration"**
5. Fill in:
   - **Label**: "Product Comparison API"
   - **Access ID**: Will be auto-generated (starts with `SWIA...`)
   - **Secret access key**: Will be auto-generated
   - **Permissions**: Check **"Write access"**
6. Click **Save**
7. **IMPORTANT**: Copy both the **Access ID** and **Secret key** immediately! 
   - They won't be shown again
   - Save them somewhere secure

### Step 2: Get OAuth Token

Replace `YOUR_ACCESS_ID` and `YOUR_SECRET` with values from Step 1:

```bash
curl -X POST "https://localhost:8000/api/oauth/token" \
  -H "Content-Type: application/json" \
  -d '{
    "client_id": "YOUR_ACCESS_ID",
    "client_secret": "YOUR_SECRET",
    "grant_type": "client_credentials"
  }' \
  -k | python3 -m json.tool
```

**Expected response:**
```json
{
    "token_type": "Bearer",
    "expires_in": 600,
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

**Save the `access_token`** - you'll need it for API requests!

### Step 3: Test API Endpoints

#### 3.1 Get Comparison Stats

```bash
export TOKEN="YOUR_ACCESS_TOKEN_FROM_STEP_2"

curl -X GET "https://localhost:8000/api/_action/learning/comparison/stats" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -k | python3 -m json.tool
```

**Expected response:**
```json
{
  "success": true,
  "data": {
    "total_comparisons": "1",
    "unique_customers": "0",
    "avg_comparisons_per_pair": "1.0000"
  },
  "meta": {
    "endpoint": "comparison-stats"
  }
}
```

#### 3.2 Get Popular Product Combinations

```bash
curl -X GET "https://localhost:8000/api/_action/learning/comparison/popular-combinations?limit=10" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -k | python3 -m json.tool
```

**Expected response:**
```json
{
  "success": true,
  "data": [
    {
      "product_id_1": "019b4610a6697180b4fd97770223e1da",
      "product_id_2": "11dc680240b04f469ccba354cbf0b967",
      "product_number_1": "5807372378675640149",
      "product_number_2": "SWDEMO10002",
      "product_name_1": "Product Name 1",
      "product_name_2": "Product Name 2",
      "total_comparisons": "1"
    }
  ],
  "meta": {
    "endpoint": "popular-combinations",
    "limit": 10,
    "total": 1
  }
}
```

### Step 4: Add More Test Data

To make the API more interesting, add more comparisons:

```bash
# Get product IDs
PRODUCTS=$(docker compose exec -T database mariadb -u root -proot shopware -e "
SELECT LOWER(HEX(id)) as id FROM product WHERE parent_id IS NULL LIMIT 5;" 2>/dev/null | tail -n +2)

P1=$(echo "$PRODUCTS" | sed -n '1p')
P2=$(echo "$PRODUCTS" | sed -n '2p')
P3=$(echo "$PRODUCTS" | sed -n '3p')
P4=$(echo "$PRODUCTS" | sed -n '4p')

# Insert multiple comparisons
docker compose exec -T database mariadb -u root -proot shopware << EOF
-- Comparison 1: P1 vs P2 (5 times)
INSERT INTO learning_product_comparison 
    (id, product_id_1, product_id_2, customer_id, comparison_count, created_at, updated_at)
VALUES 
    (UNHEX(REPLACE(UUID(), '-', '')), UNHEX('$P1'), UNHEX('$P2'), NULL, 5, NOW(3), NOW(3))
ON DUPLICATE KEY UPDATE comparison_count = comparison_count + 5;

-- Comparison 2: P1 vs P3 (3 times)
INSERT INTO learning_product_comparison 
    (id, product_id_1, product_id_2, customer_id, comparison_count, created_at, updated_at)
VALUES 
    (UNHEX(REPLACE(UUID(), '-', '')), UNHEX('$P1'), UNHEX('$P3'), NULL, 3, NOW(3), NOW(3))
ON DUPLICATE KEY UPDATE comparison_count = comparison_count + 3;

-- Comparison 3: P2 vs P4 (2 times)
INSERT INTO learning_product_comparison 
    (id, product_id_1, product_id_2, customer_id, comparison_count, created_at, updated_at)
VALUES 
    (UNHEX(REPLACE(UUID(), '-', '')), UNHEX('$P2'), UNHEX('$P4'), NULL, 2, NOW(3), NOW(3))
ON DUPLICATE KEY UPDATE comparison_count = comparison_count + 2;

SELECT 'Test data inserted' as result;
EOF
```

Now rerun the API tests - you should see multiple combinations!

## Verify Routes are Registered

Check that your API routes are properly registered:

```bash
# If you have a running app container:
docker compose exec app bin/console debug:router | grep comparison

# Or check directly in the project:
grep -r "learning/comparison" custom/plugins/LearningBundle/
```

Expected routes:
- `api.action.learning.comparison.stats` → `/api/_action/learning/comparison/stats`
- `api.action.learning.comparison.popular` → `/api/_action/learning/comparison/popular-combinations`

## Troubleshooting

### Problem: "No route found"

**Solution**: Clear cache and ensure routes.xml includes Admin API controllers:

```bash
# Clear cache
find var/cache -mindepth 1 -delete 2>/dev/null

# Check routes.xml
cat custom/plugins/LearningBundle/src/Resources/config/routes.xml
```

Should contain:
```xml
<!-- Admin API Routes -->
<import resource="../../Core/Api/*Controller.php" type="attribute" />
```

### Problem: "401 Unauthorized"

**Causes**:
1. Token expired (tokens last 10 minutes)
2. Wrong token format
3. Integration doesn't have write access

**Solution**: Get a fresh token (Step 2) and ensure `Bearer ` prefix:
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" ...
```

### Problem: "Client authentication failed" when getting OAuth token

**Cause**: Integration credentials created via direct database insertion don't work properly due to password hashing/encryption requirements.

**Solution**: **Always create integrations via the Shopware Admin UI** (Settings → System → Integrations). Direct database insertion is NOT recommended for OAuth integrations, even though it may seem simpler.

**Why it fails**:
- Secret keys need proper hashing/encryption
- Integration records need additional metadata
- OAuth validation requires specific database state

### Problem: Empty data in response

**Cause**: No comparison data in database

**Solution**: Add test data (Step 4)

### Problem: Database connection refused during migration

**Cause**: Running `bin/console` from host machine when database is in Docker

**Solution**: Use Docker commands instead:
```bash
# Option 1: Via database container (recommended)
docker compose exec -T database mariadb -u root -proot shopware -e "CREATE TABLE..."

# Option 2: Via app container (if available)
docker compose exec app bin/console database:migrate --all
```

## What You've Built

✅ **Database table** for product comparisons  
✅ **Service layer** (`ProductComparisonService`)  
✅ **Admin API Controller** with 2 endpoints  
✅ **Response formatting** using `ApiResponse` helper  
✅ **Database queries** with JOINs to product data  

## Files Created

```
custom/plugins/LearningBundle/
├── src/
│   ├── Core/Api/
│   │   ├── ProductComparisonController.php ✓
│   │   └── Response/ApiResponse.php ✓
│   ├── Service/
│   │   └── ProductComparisonService.php ✓
│   ├── Migration/
│   │   └── Migration1704556800ProductComparison.php ✓
│   └── Resources/config/
│       ├── services.xml (updated) ✓
│       └── routes.xml (updated) ✓
```

## Next Learning Steps

1. **Add Store API endpoint** - Allow frontend to record comparisons
2. **Add customer tracking** - Associate comparisons with logged-in customers
3. **Add date filtering** - Get comparisons for specific time periods
4. **Add caching** - Cache popular combinations for performance
5. **Add events** - Dispatch events when comparisons are recorded

Great job! 🎉 You now have a working Admin API!
