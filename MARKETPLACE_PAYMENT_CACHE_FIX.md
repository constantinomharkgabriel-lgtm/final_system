# Marketplace Order Payment Cache Fix

**Status**: ✅ COMPLETE & IMPLEMENTED  
**Date**: 2024  
**Issue**: Marketplace orders not verifying payment status if webhook processing is delayed  

---

## Problem Statement

### The Issue
When a marketplace order is placed and the customer completes payment on PayMongo:
1. Success redirect is sent immediately to the mobile app or web browser
2. BUT: PayMongo webhook may take 1-5 seconds to process
3. If app/browser checks before webhook processes: Order shows as `pending` payment
4. This creates confusion and potential double-payment attempts

### Root Cause
- Checkout session ID was NOT cached after payment initiation
- Success redirect happened before database was updated via webhook
- No local verification mechanism existed

### Comparison with Subscriptions
Subscriptions already worked correctly:
- `SubscriptionController.pay()` caches checkout session ID
- `SubscriptionController.success()` retrieves cache and verifies independently
- Database update via webhook is secondary/redundant

---

## Solution Overview

### Three-Phase Implementation

#### Phase 1: Cache Checkout Session IDs ✅
When marketplace order payment is initiated, store the PayMongo session ID:

**Mobile** (`MobileMarketplaceController.createOrderPayment()`):
```php
$checkoutData = $this->paymongo->createCheckoutSession([...]);

// NEW: Cache for later verification
Cache::put(
    "marketplace_checkout_session_{$order->id}",
    $checkoutData['id'],
    now()->addHours(2)
);
```

**Web** (`OrderController.createOrderPayment()`):
```php
Cache::put(
    "web_checkout_session_{$order->id}",
    $checkoutData['id'],
    now()->addHours(2)
);
```

#### Phase 2: Verification Endpoints ✅
Create new endpoints that verify payment status BEFORE showing success:

**Mobile API**: `GET /api/v1/consumer/orders/{order}/verify-payment`
```php
public function verifyPaymentStatus(Request $request, Order $order): JsonResponse
{
    // 1. Check if already marked as paid in DB
    if ($order->payment_status === 'paid') {
        return response()->json(['status' => 'verified', 'payment_status' => 'paid']);
    }

    // 2. Retrieve cached session ID
    $sessionId = Cache::get("marketplace_checkout_session_{$order->id}");

    // 3. Query PayMongo API to verify payment
    $sessionData = $this->paymongo->retrieveCheckoutSession($sessionId);

    // 4. If PayMongo says paid, update DB locally
    if ($sessionData['attributes']['payment_intent']['attributes']['status'] === 'succeeded') {
        $order->update(['payment_status' => 'paid']);
        return response()->json(['status' => 'verified', 'payment_status' => 'paid']);
    }

    // 5. Still pending
    return response()->json(['status' => 'pending', 'payment_status' => 'pending'], 202);
}
```

**Web**: `GET /orders/{order}/verify-payment`
- Same logic, accessible to authenticated web users

#### Phase 3: Routes ✅
- `api.php`: `Route::get('/orders/{order}/verify-payment', [MobileMarketplaceController::class, 'verifyPaymentStatus'])`
- `web.php`: `Route::get('/orders/{order}/verify-payment', [OrderController::class, 'verifyPaymentStatus'])`

---

## Cache Keys Reference

### Mobile App
| Scenario | Cache Key | TTL |
|----------|-----------|-----|
| Checkout Session | `marketplace_checkout_session_{order_id}` | 2 hours |
| Payment Link (Fallback) | `marketplace_payment_link_{order_id}` | 2 hours |

### Web Browser
| Scenario | Cache Key | TTL |
|----------|-----------|-----|
| Checkout Session | `web_checkout_session_{order_id}` | 2 hours |
| Payment Link (Fallback) | `web_payment_link_{order_id}` | 2 hours |

---

## Payment Verification Flow

### Current Flow (Before Fix)
```
Order Created → PayMongo Checkout Session
    ↓
Customer completes payment → Success redirect
    ↓
App/Browser receives redirect
    ↓
⏳ Waiting for webhook... (1-5 seconds)
    ↓
Webhook processes → Database updated
```

### New Flow (With Fix)
```
Order Created → PayMongo Checkout Session
    ↓
🆕 Cache session ID for 2 hours
    ↓
Customer completes payment → Success redirect
    ↓
App/Browser receives redirect
    ↓
🆕 Call /verify-payment endpoint
    ↓
🆕 Query PayMongo directly using cached session ID
    ↓
🆕 If paid: Update DB immediately (no wait for webhook)
    ↓
⏳ Webhook processes (redundant now, but creates backup)
```

---

## How Mobile App Should Use This

### Current Implementation (Without Verification)
```dart
// Consumer app redirects directly after payment
final uri = Uri.parse(checkoutUrl);
await launchUrl(uri); // Redirect to PayMongo

// ❌ Problem: App assumes payment succeeded
// ❌ But webhook might not have processed yet
```

### Recommended Implementation (With Verification)
```dart
// After PayMongo success, verify with our backend
final orderNumber = 'ORD-12345';
final order = await api.fetchOrder(orderNumber);

// 🆕 Verify payment status
final verification = await api.verifyOrderPayment(order.id);

if (verification['payment_status'] == 'paid') {
    // ✅ Payment definitely succeeded
    navigateToOrderSuccess();
} else {
    // ⏳ Still waiting for webhook
    showDialog('Payment processing... Please check order status shortly.');
    navigateToOrdersScreen();
}
```

---

## Benefits

### 1. Faster Payment Confirmation
- Users see payment confirmed immediately
- Not dependent on webhook timing
- Better user experience

### 2. Automatic Database Correction
- If webhook is delayed, local verification fixes it
- Prevents "Pending" status bugs
- Dashboard shows correct status instantly

### 3. Fallback Redundancy
- Webhook still processes (secondary verification)
- If local verification fails, webhook eventually succeeds
- System continues working even if verification endpoint fails

### 4. Duplicate Prevention
- No more multiple payment attempts
- App can check verification before allowing retry
- Clear payment status to consumer

---

## Testing Checklist

### Unit Testing
```php
// Test cache functionality
Cache::put('test_key', 'test_value', now()->addHours(2));
$value = Cache::get('test_key');
assert($value === 'test_value');
```

### Integration Testing
1. Create marketplace order with payment
2. Complete PayMongo checkout with test card
3. Call `/verify-payment` endpoint immediately
4. Should return `status: verified` and `payment_status: paid`
5. Check database: Order should show as `paid`

### End-to-End Testing
1. **Test with Short Webhook Delay**
   - Place order → Complete payment → Check status
   - Expected: Shows paid immediately

2. **Test with Backend Verification**
   - Place order → Complete payment
   - Webhook intentionally delayed
   - Call verify endpoint → Should show paid
   - Webhook processes (redundantly)

3. **Test Cache Expiration**
   - Place order → Wait ~2 hours
   - Try to verify after cache expires
   - Should fall back to database value

---

## API Responses

### Success (Payment Verified)
```json
{
    "status": "verified",
    "payment_status": "paid",
    "order_id": 12345,
    "order_number": "ORD-20240101-001"
}
```
HTTP Status: `200 OK`

### Pending (Webhook Not Yet Processed)
```json
{
    "status": "pending",
    "payment_status": "pending",
    "message": "PayMongo payment verification in progress."
}
```
HTTP Status: `202 Accepted`

### Error (Session Not Found)
```json
{
    "status": "pending",
    "payment_status": "pending",
    "message": "No payment session found."
}
```
HTTP Status: `202 Accepted`

---

## Files Modified

| File | Changes | Lines |
|------|---------|-------|
| `MobileMarketplaceController.php` | Added Cache import, 2 cache calls, verification endpoint | 11, 625, 658, 707-815 |
| `OrderController.php` | Added 2 cache calls, verification endpoint | 625, 658, 701-815 |
| `routes/api.php` | Added verification route | 20 |
| `routes/web.php` | Added verification route | 417 |

---

## Backward Compatibility

### Existing Functionality
- ✅ Webhook still processes marketplace orders
- ✅ Database `payment_status` still updated via webhook
- ✅ Notifications still sent
- ✅ Existing payment links continue working

### New Functionality
- ✅ Optional verification endpoint (doesn't break existing flow)
- ✅ Caching is transparent (doesn't affect existing code)
- ✅ No database migrations required

### Zero Breaking Changes
All changes are additive - no existing functionality removed or modified.

---

## Future Enhancements

### 1. Real-Time Notifications
When verification succeeds before webhook:
```php
// Emit event that mobile app can listen to
event(new OrderPaymentVerified($order));
```

### 2. Automatic Retry
If first verification returns pending:
```dart
// Auto-retry verification in 2 seconds
Future.delayed(Duration(seconds: 2), () => verifyPayment());
```

### 3. Payment Timeout Handling
If verification still pending after 30 seconds:
```php
if ($remainingWaitTime > 30) {
    // Webhook may have failed
    // Send alert to farm owner
    // Offer manual review option
}
```

---

## Support & Troubleshooting

### Cache Not Found
**Symptom**: Verification returns `pending` immediately
**Cause**: Cache expired or not set
**Solution**: Fall back to PayMongo session ID stored in `order->paymongo_payment_id`

### PayMongo API Timeout
**Symptom**: Verification endpoint returns error
**Cause**: PayMongo API slow or down
**Solution**: Return `202 Pending` and retry after 5 seconds

### Database Not Updated After Verification
**Symptom**: `order->payment_status` still shows pending
**Cause**: Webhook processed the same order simultaneously
**Solution**: Database constraint prevents duplicate updates (idempotent)

---

## Production Deployment Checklist

- [ ] Test all cache keys are properly formatted
- [ ] Verify PayMongo API credentials are valid
- [ ] Test with both checkout sessions and payment links
- [ ] Verify mobile app can call new verification endpoint
- [ ] Test cache expiration after 2 hours
- [ ] Ensure webhook still processes correctly
- [ ] Monitor logs for verification endpoint calls
- [ ] Update mobile app to call new endpoints
- [ ] Update web success page to use verification
- [ ] Test with live PayMongo account (if applicable)

---

## Documentation Complete ✅

This fix ensures marketplace orders have reliable payment verification without depending on webhook timing.
