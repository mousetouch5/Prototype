#!/bin/bash

# Test API endpoints locally
echo "Testing API endpoints..."

# Test register endpoint
echo "Testing POST /api/register..."
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"password123","password_confirmation":"password123"}' \
  -v

echo ""
echo "Testing GET /api/user (should fail without auth)..."
curl -X GET http://localhost:8000/api/user \
  -H "Accept: application/json" \
  -v