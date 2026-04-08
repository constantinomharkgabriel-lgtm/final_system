# 🧪 Practical Testing Guide - Step-by-Step

## Before You Start

**Assumptions:**
- You're on localhost or staging environment
- Laravel app is running: `php artisan serve`
- Database migrations have been applied
- All caches cleared

**Expected Duration:** 15-20 minutes

---

## TEST 1: Driver Creation (No Phone Error) ⭐

### Step 1: Login as Farm Owner
```
URL: http://localhost:8000/farm-owner/login
Email: [Your farm owner email]
Password: [Your password]
Expected: Login successful, dashboard loads
```

### Step 2: Navigate to Create Employee
```
Left Menu → HR → Employees
Button: "+ Add Employee"
```

### Step 3: Fill Employee Form (CRITICAL: No Phone)
```
Form Fields:
- First Name: "Test"
- Last Name: "Driver"
- Email: "testdriver@company.com"
- Department: "Logistics"
- Role: "Driver"
- Phone: [LEAVE EMPTY - don't fill]
- Vehicle Type: "Motorcycle"
- License Plate: "ABC-123"
- License Expiry: "2027-12-31"
- Delivery Fee: "50"

Submit: Click "Add Employee" button
```

### Step 4: Verify Success
```
✅ EXPECTED: 
   - Form submits successfully (no error)
   - Redirected to employees list
   - New "Test Driver" appears in list
   - No "null value in column 'phone'" error
   
❌ FAILURE:
   - Error appears: "Not null violation"
   - Page doesn't redirect
   - Database error in logs
```

### Step 5: Check Database (Optional - For Verification)
```bash
# In terminal/MySQL client
SELECT id, email, phone, is_verified FROM drivers 
WHERE email = 'testdriver@company.com';

Expected: 
- phone column = NULL (not 'pending')
- is_verified = 0 (false)
```

---

## TEST 2: Driver Visibility Fix ⭐⭐

### Step 1: Go to Logistics Portal
```
From Farm Owner Dashboard:
Left Menu → Logistics → Drivers

Expected: 
- New "Test Driver" from Test 1 appears
- Status shows: "Unverified"
- Not hidden/missing
```

### Step 2: Check Driver List Search
```
If there's a filter bar:
- Try: ?verified=0 (unverified drivers only)
- You should see Test Driver

Expected: Test Driver visible in both filtered and unfiltered views
```

### Step 3: Scroll Down
```
Check if driver statistics show:
- Total Drivers: Increased by 1
- Unverified Drivers: Increased by 1
- Or similar count

Expected: Numbers updated to include new driver
```

---

## TEST 3: Driver Verification Workflow ⭐⭐⭐

### Step 1: Access Pending Drivers List
```
Direct URL: http://localhost:8000/farm-owner/drivers-pending-verification

Expected: Page loads showing "Test Driver" in a table
```

### Step 2: Review Driver Details
```
If there's a "Review" button:
- Click "Review" button for Test Driver
- Expected: See driver details page

If no Review button:
- Skip to next step
```

### Step 3: Approve Driver
```
Action: Click "Approve" or "Verify" button

Expected:
- Button click succeeds
- Page shows success message
- Driver moves from unverified to verified
```

### Step 4: Verify Status Changed
```
Go back to: Logistics → Drivers

Expected:
- Test Driver still in list
- Status changed from "Unverified" to "Verified" 
- Maybe "Verified at: [timestamp]"
```

### Step 5: Check Database (Optional)
```bash
SELECT id, email, is_verified, verified_at FROM drivers 
WHERE email = 'testdriver@company.com';

Expected:
- is_verified = 1 (true)
- verified_at = [current timestamp]
```

---

## TEST 4: Order Creation & Farm Owner Notification ⭐⭐⭐

### Step 1: Create Order as Consumer
```
URL: http://localhost:8000/marketplace (or consumer portal)

OR use mobile app:
- Login as consumer
- Browse products
- Add to cart
- Proceed to checkout
```

### Step 2: Place Order
```
Fill checkout form:
- Delivery Address: Any address
- Payment Method: Any method
- Delivery Instructions: "Test order"

Button: "Place Order"

Expected: Order placed successfully, confirmation shown
```

### Step 3: Check Farm Owner Notifications
```
Login as Farm Owner (or stay logged in if same window)
Look for notification bell/badge

Expected: See notification like:
- "📦 New Order Received"
- "Order #ORD-[number]"
- Amount and customer name shown
```

### Step 4: Verify in Database
```bash
# Check notifications table
SELECT * FROM notifications 
WHERE title LIKE '%order%' OR title LIKE '%received%'
ORDER BY created_at DESC 
LIMIT 5;

Expected:
- New notification record exists
- user_id = farm_owner.id
- type = 'order'
- title contains "order"
- data contains order_id, amount, consumer_name
```

---

## TEST 5: Delivery Assignment & Dispatch Notification ⭐⭐⭐⭐

### Step 1: Login as Logistics Staff
```
URL: http://localhost:8000/logistics/login
OR use logistics portal access if multi-role
```

### Step 2: Find Orders to Deliver
```
Menu: Logistics → Orders
OR: Logistics → Pending Deliveries

Expected: See order created in Test 4
```

### Step 3: Assign Driver
```
Click on order from Test 4
Button: "Assign Driver" or "Edit"
Select: Test Driver (verified from Test 3)
Button: "Save" or "Assign"

Expected: Driver assigned successfully
```

### Step 4: Mark as Dispatched
```
Status: Change from "pending" → "out_for_delivery"
OR: Click button "Dispatch" / "Send to Driver"

Expected: Status updates, alert/toast appears
```

### Step 5: Check Notifications (3 Should Exist!)
```
Check 1: Consumer Notification
- Login as consumer
- Should see: "🚚 Your order is out for delivery!"
- Shows driver name

Check 2: Farm Owner Notification  
- Login as farm owner
- Should see: "🚗 Delivery dispatched..."
- Shows driver name and delivery city

Check 3: Driver Notification
- Login as driver (Test Driver)
- Should see: "📍 New delivery assigned"
- Shows delivery fee (₱50)
```

### Step 6: Verify in Database
```bash
# Check notifications for this order
SELECT * FROM notifications 
WHERE data->>'order_id' = '[order_id]'
ORDER BY created_at DESC;

Expected: 3 notification records
- One for consumer (title like "out for delivery")
- One for farm_owner (title like "dispatched")  
- One for driver (title like "assigned")
```

---

## TEST 6: Delivery Completion & Final Notifications ⭐⭐⭐⭐⭐

### Step 1: Login as Driver
```
Account: Test Driver (from Test 1)
Portal: Driver app/portal
```

### Step 2: Find Assigned Delivery
```
Menu: Active Deliveries or Deliveries
Expected: See delivery assigned in Test 5
```

### Step 3: Mark as Delivered
```
Action: Click "Mark Delivered" or "Complete"
Upload proof: Take photo or select file
Button: "Confirm Delivery"

Expected: Marked as delivered successfully
```

### Step 4: Check All 3 Completion Notifications

**Notification 1: Consumer**
```
Login as consumer
Expected notification:
- "✅ Order delivered!"
- "Please rate your experience"
- Option to give rating/review
```

**Notification 2: Farm Owner**
```
Login as farm owner
Expected notification:
- "Order confirmed"
- COD status if applicable: "₱2500 cash collected"
- Payment ready to mark
```

**Notification 3: Driver**
```
Login as driver
Expected notification:
- "💰 Delivery completed!"
- "You earned ₱50.00"
- Earnings added to dashboard
```

### Step 5: Verify in Database
```bash
# Check all notifications for this order
SELECT * FROM notifications 
WHERE data->>'order_id' = '[order_id]'
ORDER BY created_at DESC;

Expected: 6 total notifications
- 3 from dispatch (consumer, farm_owner, driver)
- 3 from completion (consumer, farm_owner, driver)
```

---

## TEST 7: Mobile App Compatibility ⭐⭐⭐

### Step 1: Get Notification via API
```
Method: GET
URL: http://localhost:8000/api/mobile/notifications
Header: Authorization: Bearer [mobile_token]
        Accept: application/json

Expected: Returns JSON array of notifications
```

### Response Structure
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 2,
      "title": "🚚 Your order is out for delivery",
      "message": "Driver: John Driver is heading to your location",
      "type": "order",
      "channel": "in_app",
      "data": {
        "order_id": 123,
        "delivery_id": 456,
        "driver_name": "John Driver"
      },
      "is_read": false,
      "created_at": "2026-04-08T12:00:00Z"
    },
    ...
  ],
  "meta": {
    "count": 3,
    "unread": 2
  }
}
```

### Step 2: Verify Mobile Can Display
```
In Flutter/mobile app:
- Parse JSON response
- Display notifications in list
- Show title and message
- Mark as read when tapped

Expected: Notifications render correctly in app
```

---

## TEST 8: Error Scenarios (Edge Cases)

### Scenario 1: Create Driver WITH Phone
```
Fill form WITH phone number: "09171234567"

Expected: Should still work (phone not required)
Result: Driver created, appears in list
```

### Scenario 2: Create Multiple Drivers (Same Farm)
```
Create 3 drivers:
- Driver A: No phone
- Driver B: No phone  
- Driver C: Phone: "09171234567"

Expected: All succeed (no duplicate unique key error)
```

### Scenario 3: Reject a Driver
```
Go to: /farm-owner/drivers-pending-verification
Click: "Reject" on a driver

Expected: 
- Driver marked inactive
- Can't be assigned deliveries
- Rejection notification sent to driver
```

### Scenario 4: Re-verify a Driver
```
Same driver approved then rejected
Click: "Approve" again on same driver

Expected: Driver reactivated, marked verified
```

---

## Troubleshooting Guide

### Problem: Driver Still Not in List After Creation

**Check 1: Database**
```bash
SELECT * FROM drivers WHERE email = 'testdriver@company.com';
# Should have a record
```

**Check 2: Logs**
```bash
tail -f storage/logs/laravel.log
# Look for "Driver created" or errors
```

**Check 3: DriverController**
```
Open: app/Http/Controllers/DriverController.php
Search for: $query->verified()
Result: Should NOT be there (should be removed)
```

**Solution: Clear cache and refresh**
```bash
php artisan cache:clear
php artisan view:clear
```

---

### Problem: Notifications Not Appearing

**Check 1: Events Firing**
```bash
grep -i "event" storage/logs/laravel.log
# Should see event firing entries
```

**Check 2: Listener Running**
```bash
grep -i "SendOrderCreated\|SendDeliveryDispatched" storage/logs/laravel.log
# Should see listener execution
```

**Check 3: Database Insert**
```bash
SELECT COUNT(*) FROM notifications;
# Should increase with each action
```

**Check 4: Event Service Provider**
```php
# Verify file exists and is registered
ls -la app/Providers/EventServiceProvider.php
php artisan event:list
```

**Solution: Restart app**
```bash
# Kill running process and restart
php artisan cache:clear
php artisan serve
```

---

### Problem: "Event Class Not Found"

**Check: Import Statement**
```php
# In app/Http/Controllers/OrderController.php
# Should have: use App\Events\OrderCreated;

# If missing, add it manually
```

**Solution:**
```bash
# Files might be in wrong namespace
# Check: app/Events/OrderCreated.php top line
# Should have: namespace App\Events;
```

---

## ✅ Success Checklist

When all tests pass:

- [ ] Test 1: Driver created without phone - ✅ NO ERROR
- [ ] Test 2: Driver visible in logistics list - ✅ APPEARS
- [ ] Test 3: Driver verification works - ✅ STATUS CHANGES
- [ ] Test 4: Farm owner gets order notification - ✅ NOTIFICATION CREATED
- [ ] Test 5: Dispatch sends 3 notifications - ✅ ALL 3 CREATED
- [ ] Test 6: Completion sends 3 notifications - ✅ ALL 3 CREATED  
- [ ] Test 7: Mobile API returns notifications - ✅ JSON RECEIVED
- [ ] Test 8: Edge cases don't break system - ✅ NO ERRORS

---

## Quick Test (5 minute version)

If you're in a hurry, run this abbreviated test:

```
1. Login HR → Add driver (no phone) → Check drivers list
   Expected: ✅ Appears immediately

2. Login Logistics → Approve driver
   Expected: ✅ Status changes

3. Create order as consumer
   Expected: ✅ Farm owner gets notification

4. Test 5 + 6 combined:
   - Assign driver
   - Mark dispatched
   - Check 3 notifications exist
   - Mark delivered
   - Check 3 more notifications exist
   
5. Test API:
   GET /api/mobile/notifications
   Expected: ✅ Returns JSON with notifications

RESULT: If all passes → System is working! 🎉
```

---

## Final Validation

Run these commands to verify system health:

```bash
# Check migrations applied
php artisan migrate:status

# Check all events listed
php artisan event:list

# Check routes registered
php artisan route:list | grep driver

# Check no errors in latest logs
tail -20 storage/logs/laravel.log
```

All green? You're ready for production! 🚀
