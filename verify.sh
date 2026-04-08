#!/bin/bash
# Comprehensive verification script

cd "$(dirname "$0")"

echo "========================================="
echo "POULTRY SYSTEM - COMPREHENSIVE VERIFICATION"
echo "========================================="
echo ""

echo "1. Checking Database Connection..."
php artisan db:show --database=default 2>&1 | head -10
echo ""

echo "2. Checking User & FarmOwner..."
php debug-subscription.php
echo ""

echo "3. Checking Routes..."
php artisan route:list | grep subscription
echo ""

echo "4. Checking View File..."
if [ -f "resources/views/auth/subscription-select.blade.php" ]; then
    echo "✓ Subscription view exists"
    echo "  Lines: $(wc -l < resources/views/auth/subscription-select.blade.php)"
else
    echo "✗ Subscription view NOT FOUND"
fi
echo ""

echo "5. Checking Controller..."
if grep -q "hasFreeSubscription" app/Http/Controllers/SubscriptionController.php; then
    echo "✓ Controller passes hasFreeSubscription to view"
else
    echo "✗ Controller does NOT pass hasFreeSubscription to view"
fi
echo ""

echo "========================================="
echo "VERIFICATION COMPLETE"
echo "========================================="
echo ""
echo "NEXT STEPS:"
echo "1. Navigate to: http://127.0.0.1:8000/subscribe"
echo "2. Scroll down to see the DEBUG panel"
echo "3. Check if 'hasFreeSubscription' shows TRUE or FALSE"
echo "4. If FALSE: User doesn't have free subscription in DB"
echo "5. If TRUE: User HAS free subscription, so card should be greyed out"
echo ""
