# ✅ QUICK REFERENCE CHECKLIST

## 🚀 PRE-TESTING VERIFICATION

Before you start testing, make sure your system is ready:

```
□ Server Running at http://127.0.0.1:8000
□ Database Connected (PostgreSQL)
□ All tables verified to exist:
  □ drivers
  □ employees
  □ roles
  □ employee_roles
□ No PHP syntax errors
□ All caches rebuilt (config, route, view)
□ Driver role seeded in database
```

---

## 📋 TESTING WORKFLOW

### Phase 1: Create a Driver Employee ✅

**Location:** http://127.0.0.1:8000/farm-owner/employees/create

```
□ Fill in employee basic info:
  □ First Name
  □ Last Name
  □ Email
  □ Phone

□ Select Department: "Driver"
  OR Check Role: "Driver"
  
□ Watch for: "Driver Details" section appears automatically

□ Fill Driver Section:
  □ Vehicle Type: "Tricycle" / "Motorcycle" / "Van"
  □ Vehicle Plate: e.g., "ABC 1234"
  □ Vehicle Model: e.g., "Honda TMX"
  □ License Number: e.g., "LIC123456"
  □ License Expiry: Select date
  □ Delivery Fee: ₱50 (or your amount)
  □ Driver Notes: Optional

□ Click: [Create Employee]

✓ VERIFY:
  □ Employee created successfully
  □ Driver profile appears in list
  □ Go to Logistics → Driver sidebar
  □ See driver name in list ✅
```

---

### Phase 2: Create a Consumer & Place Order ✅

**Location:** Consumer Portal / Mobile App

```
□ Register as Consumer:
  □ Email
  □ Password
  □ Confirm Password
  □ Click: [Register]

□ Browse Products:
  □ Go to Home
  □ See product list (from Farm Owner)
  □ Click product
  □ See details and price

□ Place Order:
  □ Click [Add to Cart]
  □ Proceed to Checkout
  □ Confirm delivery address
  □ Choose payment method
  □ Click [Place Order]

✓ VERIFY:
  □ Order confirmation page appears
  □ Consumer receives notification: "Order received!"
  □ Status shows: "Pending"
```

---

### Phase 3: Farm Owner Confirms Order ✅

**Location:** http://127.0.0.1:8000/farm-owner/dashboard

```
□ Go to Dashboard:
  □ See pending orders

□ Click Order:
  □ Check items
  □ Check total
  □ Review delivery address

□ Click [Confirm Order]:
  □ Order status changes to "Confirmed"
  □ Farm owner marks as "Packing..."

□ Pack Items:
  □ Staff packs the items

□ Mark [Packed]:
  □ Order status: "Packed"

✓ VERIFY:
  □ Consumer notification: "Order packed!"
  □ Order visible to Logistics staff
```

---

### Phase 4: Logistics Creates Delivery ✅

**Location:** http://127.0.0.1:8000/logistics/deliveries

```
□ Go to Logistics Portal:
  □ Dashboard → New Deliveries
  □ See list of confirmed orders

□ Click Order:
  □ View order details
  □ View items and customer address

□ Create Delivery:
  □ Click [Create Delivery]
  □ Select Driver (see your new driver ✓)
  □ Confirm details

□ Delivery Status: "Preparing"
  □ Consumer sees: "Being prepared..."

□ Mark [Packed]:
  □ Status: "Packed"
  □ Consumer notification sent

□ Mark [Dispatched]:
  □ Select driver
  □ Status: "Out for delivery"
  □ Driver status: "on_delivery"

✓ VERIFY:
  □ Consumer gets notification with driver info:
    □ Driver name: (Your driver)
    □ Vehicle: (Your vehicle plate)
    □ Estimated time: 11:05 AM
```

---

### Phase 5: Driver Completes Delivery ✅

**Location:** Logistics Portal (Logistics Staff updates)

```
□ Driver performs delivery

□ Staff marks [Delivered]:
  □ Upload proof image (optional)
  □ Click [Mark as Delivered]
  □ Status: "Delivered"

✓ VERIFY:
  □ Consumer notification: "✅ Order delivered!"
  □ Rating prompt appears
  □ Driver earns commission:
    □ ₱50 credited to earnings
```

---

### Phase 6: Verify Driver Commission ✅

**Location:** http://127.0.0.1:8000/farm-owner/payroll

```
□ Go to Payroll section:
  □ Select driver employee
  □ Check month/period

□ See calculation:
  □ Base Salary: ₱15,000
  □ Delivery Count: 1
  □ Delivery Commission: ₱50 (1 × ₱50)
  □ Gross Pay: ₱15,050

✓ VERIFY:
  □ Commission automatically calculated
  □ Correct delivery count tracked
  □ Correct delivery_fee applied
  □ Shows in gross pay
```

---

## 🔧 TROUBLESHOOTING QUICK FIXES

### ❌ Problem: Driver section doesn't appear

**Solution:**
```
□ Refresh page (Ctrl+F5)
□ Check browser console for errors (F12)
□ Verify JavaScript is enabled
□ Try different browser
□ Check if "driver" role is checked OR department is "driver"
```

### ❌ Problem: Driver not assigned to delivery

**Solution:**
```
□ Verify driver is created (check sidebar)
□ Check driver status isn't "unavailable"
□ In deliveries, assign from driver dropdown
□ Ensure driver_id is saved before dispatch
□ Go to order detail → see assigned driver
```

### ❌ Problem: Commission not calculated

**Solution:**
```
□ Verify delivery marked as "delivered" (not just "packed")
□ Check delivery_fee is set in driver profile
□ Refresh payroll page (Ctrl+F5)
□ Verify payroll period matches delivery date
□ Check driver role is assigned to employee
```

### ❌ Problem: Mobile app can't see driver info

**Solution:**
```
□ Verify API endpoint: /api/deliveries/{id}
□ Check JSON response includes driver object
□ Verify driver assigned before dispatch
□ Check mobile app has latest code
□ Test in browser first: api/deliveries/1
```

---

## 📍 KEY ENDPOINTS (For API Testing)

```
PRODUCTS:
GET /api/products
GET /api/products/{id}

ORDERS:
GET /api/orders
GET /api/orders/{id}
POST /api/orders (create)

DELIVERIES:
GET /api/deliveries
GET /api/deliveries/{id}
POST /api/deliveries (create)
PUT /api/deliveries/{id}/status (update status)

DRIVERS:
GET /api/drivers
GET /api/drivers/{id}

NOTIFICATIONS:
GET /api/notifications
GET /api/notifications/{id}

TRACKING:
GET /api/tracking/{delivery_id}
```

**Test in Browser:**
```
http://127.0.0.1:8000/api/drivers
http://127.0.0.1:8000/api/orders
http://127.0.0.1:8000/api/deliveries
```

---

## 🎯 SUCCESS CRITERIA CHECKLIST

### ✅ System is working if:

```
□ Driver form appears when department or role selected
□ Driver profile saves with all fields
□ Driver appears in Logistics sidebar
□ Delivery can be assigned to driver
□ Consumer sees driver info when out for delivery
□ Driver commission calculated after delivery marked complete
□ Mobile app shows driver tracking info
□ All notifications sent at appropriate times
□ No PHP errors in any logs
□ Database relationships intact (all 4 tables)
```

---

## 📞 SUPPORT CONTACTS

| Issue | Contact | Location |
|-------|---------|----------|
| Database errors | Check `storage/logs/laravel.log` | Server-side |
| PHP syntax errors | Check application errors | VS Code problems panel |
| JavaScript errors | Open browser DevTools (F12) | Browser console |
| API not responding | Check routes file | `routes/api.php` |
| Migration issues | Run `php artisan migrate` | Terminal |
| Cache problems | Run `php artisan cache:clear` | Terminal |

---

## 🎓 EDUCATIONAL PATHS

**Learning Path 1: From Zero to Delivery**
1. 📖 Read LOGISTICS_COMPLETE_TUTORIAL.md (Part 1)
2. 🔧 Create a test driver
3. 🔧 Create test consumer
4. 🔧 Place test order
5. ✅ Complete workflow
6. 📊 Check commission in payroll

**Learning Path 2: Mobile App Integration**
1. 📖 Read LOGISTICS_COMPLETE_TUTORIAL.md (Part 5)
2. 📱 Build Flutter app
3. 🧪 Test API endpoints in browser first
4. 📲 Connect mobile app to test server
5. 🗺️ Monitor live tracking

**Learning Path 3: Troubleshooting**
1. 🐛 Use this troubleshooting section
2. 📝 Check logs first
3. 🔍 Verify database integrity
4. 🧪 Test API endpoints
5. 📞 Escalate if needed

---

## 🚢 DEPLOYMENT CHECKLIST

When moving to production:

```
□ All tests passed locally
□ No errors in logs
□ Database backed up
□ Environment variables set for production
□ SSL certificate installed
□ Email service configured
□ Payment gateway configured
□ Notification service (SMS/Email) configured
□ File storage (images) configured
□ Backups automated
□ Monitoring set up
□ Error tracking (Sentry/etc) set up
□ API rate limiting configured
□ CORS configured for production domain
```

---

## 📝 NOTES FOR FUTURE REFERENCE

```
✏️ Driver delivery_fee: ₱50 (can be adjusted per driver)
✏️ Commission calculation: Simple multiplication
✏️ Payroll period: Monthly (adjustable in config)
✏️ Driver status: auto-updates to "on_delivery" when assigned
✏️ Mobile app polls tracking: Every 5 seconds
```

---

**🎉 READY TO START TESTING? Follow Phase 1 above!**
