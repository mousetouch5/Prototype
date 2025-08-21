#!/bin/bash

# Test API endpoints locally and on production
BASE_URL=${1:-"https://prototype-chzt.onrender.com"}

echo "Testing API endpoints on: $BASE_URL"
echo "========================================"

# Test database connectivity
echo "1. Testing database connection..."
curl -X GET "$BASE_URL/api/test-db" \
  -H "Accept: application/json" \
  -s | jq '.' || echo "Failed to test database"

echo ""
echo "2. Testing health endpoint..."
curl -X GET "$BASE_URL/health" \
  -H "Accept: application/json" \
  -s || echo "Health check failed"

echo ""
echo "3. Testing POST /api/register..."
REGISTER_RESPONSE=$(curl -X POST "$BASE_URL/api/register" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name":"Test User",
    "email":"test'$(date +%s)'@example.com",
    "password":"password123",
    "password_confirmation":"password123"
  }' \
  -s -w "\nHTTP_STATUS:%{http_code}")

echo "$REGISTER_RESPONSE"

# Extract token if registration succeeded
TOKEN=$(echo "$REGISTER_RESPONSE" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

if [ ! -z "$TOKEN" ]; then
  echo ""
  echo "4. Testing GET /api/user with token..."
  curl -X GET "$BASE_URL/api/user" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer $TOKEN" \
    -s | jq '.' || echo "Failed to get user"
else
  echo ""
  echo "4. Testing GET /api/user (should fail without auth)..."
  curl -X GET "$BASE_URL/api/user" \
    -H "Accept: application/json" \
    -s -w "\nHTTP_STATUS:%{http_code}"
fi

echo ""
echo "========================================"
echo "Test completed."