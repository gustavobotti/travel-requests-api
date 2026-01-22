#!/bin/bash

# Script to test Travel Request Notification System
API_BASE_URL="http://localhost/api/v1"

echo "=========================================="
echo "Testing Travel Request Notification"
echo "=========================================="
echo ""

# Step 1: Register/Login Requester
echo "1. Registering requester (joao.silva@company.com)..."
REQUESTER_RESPONSE=$(curl -s -X POST "${API_BASE_URL}/register" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"name":"João Silva","email":"joao.silva@company.com","password":"password123","password_confirmation":"password123"}')

REQUESTER_TOKEN=$(echo "$REQUESTER_RESPONSE" | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -z "$REQUESTER_TOKEN" ]; then
  echo "   User exists, trying login..."
  REQUESTER_RESPONSE=$(curl -s -X POST "${API_BASE_URL}/login" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{"email":"joao.silva@company.com","password":"password123"}')
  REQUESTER_TOKEN=$(echo "$REQUESTER_RESPONSE" | grep -o '"token":"[^"]*' | cut -d'"' -f4)
fi

echo "   ✓ Requester token: ${REQUESTER_TOKEN:0:20}..."
echo ""

# Step 2: Register/Login Approver
echo "2. Registering approver (maria.santos@company.com)..."
APPROVER_RESPONSE=$(curl -s -X POST "${API_BASE_URL}/register" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"name":"Maria Santos","email":"maria.santos@company.com","password":"password123","password_confirmation":"password123"}')

APPROVER_TOKEN=$(echo "$APPROVER_RESPONSE" | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -z "$APPROVER_TOKEN" ]; then
  echo "   User exists, trying login..."
  APPROVER_RESPONSE=$(curl -s -X POST "${API_BASE_URL}/login" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{"email":"maria.santos@company.com","password":"password123"}')
  APPROVER_TOKEN=$(echo "$APPROVER_RESPONSE" | grep -o '"token":"[^"]*' | cut -d'"' -f4)
fi

echo "   ✓ Approver token: ${APPROVER_TOKEN:0:20}..."
echo ""

# Step 3: Create Travel Request
echo "3. Creating travel request..."
TRAVEL_RESPONSE=$(curl -s -X POST "${API_BASE_URL}/travel-requests" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ${REQUESTER_TOKEN}" \
  -d '{"requester_name":"João Silva","destination":"São Paulo - SP","departure_date":"2026-02-15","return_date":"2026-02-20"}')

# Extract ID - Laravel Resource wraps in "data" object
TRAVEL_ID=$(echo "$TRAVEL_RESPONSE" | grep -oP '"id":\K[0-9]+' | head -1)

# Fallback if grep -P not available
if [ -z "$TRAVEL_ID" ]; then
  TRAVEL_ID=$(echo "$TRAVEL_RESPONSE" | sed -n 's/.*"id":\([0-9]\+\).*/\1/p' | head -1)
fi

if [ -z "$TRAVEL_ID" ]; then
  echo "   ✗ Failed to extract travel request ID. Response:"
  echo "$TRAVEL_RESPONSE"
  exit 1
fi

echo "   ✓ Travel request created with ID: $TRAVEL_ID"
echo ""

# Step 4: Approve Travel Request (TRIGGERS NOTIFICATION)
echo "4. Approving travel request ID $TRAVEL_ID (TRIGGERS EMAIL)..."
APPROVAL_RESPONSE=$(curl -s -X PATCH "${API_BASE_URL}/travel-requests/${TRAVEL_ID}/status" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ${APPROVER_TOKEN}" \
  -d '{"status":"APPROVED"}')

echo "   ✓ Travel request approved!"
echo ""

# Step 5: Create another Travel Request to be Cancelled
echo "5. Creating second travel request (to be cancelled)..."
TRAVEL_RESPONSE2=$(curl -s -X POST "${API_BASE_URL}/travel-requests" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ${REQUESTER_TOKEN}" \
  -d '{"requester_name":"João Silva","destination":"Rio de Janeiro - RJ","departure_date":"2026-03-10","return_date":"2026-03-15"}')

TRAVEL_ID2=$(echo "$TRAVEL_RESPONSE2" | grep -oP '"id":\K[0-9]+' | head -1)

if [ -z "$TRAVEL_ID2" ]; then
  TRAVEL_ID2=$(echo "$TRAVEL_RESPONSE2" | sed -n 's/.*"id":\([0-9]\+\).*/\1/p' | head -1)
fi

echo "   ✓ Second travel request created with ID: $TRAVEL_ID2"
echo ""

# Step 6: Cancel the second Travel Request (TRIGGERS NOTIFICATION)
echo "6. Cancelling travel request ID $TRAVEL_ID2 (TRIGGERS EMAIL)..."
CANCEL_RESPONSE=$(curl -s -X PATCH "${API_BASE_URL}/travel-requests/${TRAVEL_ID2}/status" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ${APPROVER_TOKEN}" \
  -d '{"status":"CANCELLED"}')

echo "   ✓ Travel request cancelled!"
echo ""

# Step 7: Cancel the previously approved Travel Request (TRIGGERS NOTIFICATION)
echo "7. Cancelling previously approved travel ID $TRAVEL_ID (TRIGGERS EMAIL)..."
CANCEL_APPROVED_RESPONSE=$(curl -s -X PATCH "${API_BASE_URL}/travel-requests/${TRAVEL_ID}/status" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ${APPROVER_TOKEN}" \
  -d '{"status":"CANCELLED"}')

echo "   ✓ Previously approved travel request cancelled!"
echo ""

echo "=========================================="
echo "✓ Done! 3 email notifications queued:"
echo "=========================================="
echo ""
echo "1. ✅ Approved: São Paulo - SP"
echo "2. ❌ Cancelled: Rio de Janeiro - RJ"
echo "3. ❌ Cancelled (was approved): São Paulo - SP"
echo ""
echo "IMPORTANT: Make sure queue worker is running!"
echo "   php artisan queue:work"
echo ""
echo "Check emails at: http://localhost:8026"
echo "Email recipient: joao.silva@company.com"
echo ""

