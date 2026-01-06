#!/bin/bash

# Test Rate Limiter for Product View API
# Tests that rate limiting works correctly

# Setup variables
PRODUCT_ID="${PRODUCT_ID:-019b4610a6697180b4fd97770223e1da}"
SW_ACCESS_KEY="${SW_ACCESS_KEY:-SWSCQJDIU3D3SUDTDEHDNVH2UW}"

echo "Testing Rate Limiter (Max 100 requests per 60 seconds)"
echo "======================================================="
echo ""
echo "Product ID: $PRODUCT_ID"
echo "Access Key: $SW_ACCESS_KEY"
echo ""

SUCCESS_COUNT=0
RATE_LIMITED_COUNT=0
ERROR_COUNT=0

# Test by making 105 requests quickly
for i in {1..105}; do
  RESPONSE=$(curl -s -X POST "https://localhost:8000/store-api/learning/product-view/${PRODUCT_ID}" \
    -H "sw-access-key: ${SW_ACCESS_KEY}" -k)
  
  # Check if response contains success
  if echo "$RESPONSE" | grep -q '"success":true'; then
    SUCCESS_COUNT=$((SUCCESS_COUNT + 1))
    echo "✓ Request $i: Success"
  # Check if response contains rate limit error
  elif echo "$RESPONSE" | grep -q 'LEARNING__RATE_LIMIT_EXCEEDED'; then
    RATE_LIMITED_COUNT=$((RATE_LIMITED_COUNT + 1))
    echo "⊗ Request $i: Rate Limited (Expected after 100 requests)"
  # Check for other errors
  elif echo "$RESPONSE" | grep -q '"errors"'; then
    ERROR_COUNT=$((ERROR_COUNT + 1))
    echo "✗ Request $i: Error - $(echo $RESPONSE | head -c 100)"
  else
    echo "? Request $i: Unknown response"
  fi
done

echo ""
echo "Results:"
echo "========================================"
echo "Successful requests:    $SUCCESS_COUNT"
echo "Rate limited requests:  $RATE_LIMITED_COUNT"
echo "Error requests:         $ERROR_COUNT"
echo ""

if [ $SUCCESS_COUNT -eq 100 ] && [ $RATE_LIMITED_COUNT -eq 5 ]; then
  echo "✓ TEST PASSED: Rate limiter working correctly!"
  exit 0
elif [ $SUCCESS_COUNT -eq 105 ]; then
  echo "✗ TEST FAILED: Rate limiter NOT working - all requests succeeded"
  exit 1
elif [ $ERROR_COUNT -gt 0 ]; then
  echo "✗ TEST FAILED: Server errors detected"
  exit 1
else
  echo "⚠ TEST INCONCLUSIVE: Unexpected results"
  echo "Expected: 100 success, 5 rate limited"
  echo "Got: $SUCCESS_COUNT success, $RATE_LIMITED_COUNT rate limited, $ERROR_COUNT errors"
  exit 1
fi
