# 🧪 MANUAL TESTING GUIDE - Step by Step

**Purpose**: Walk through the system MANUALLY to test every feature  
**Date**: April 4, 2026  
**Status**: Ready to test

---

## 📋 WHAT TO DO FIRST

### ✅ Prerequisites (Do This First!)

```
1. System Running?
   □ Terminal shows "Laravel development server running at..."
   □ Access: http://localhost:8000

2. Database Connected?
   □ Tables exist (check in code or dashboard)
   □ Can create users

3. Sample Data Created?
   □ At least 1 Farm Owner
   □ At least 1 Consumer user
   □ At least 1 Product
   □ At least 1 Driver
```

---

## 🚀 STEP 0: START THE SERVER

**Open Terminal** and run:

```bash
cd c:\Users\lawrence tabutol\Downloads\Final_system\poultry-system
php artisan serve --host=127.0.0.1 --port=8000
```

**Expected Output**:
```
INFO  Server running on [http://127.0.0.1:8000]

Press Ctrl+C to quit
```

**Verify**: Open browser → `http://localhost:8000` → Should see homepage

---

## ✨ STEP 1: CREATE TEST ACCOUNTS

### Scenario Setup:

You'll need these 4 test accounts:

1. **Consumer (Buyer)**: Juan Dela Cruz
2. **Farm Owner (Seller)**: Green Valley Poultry  
3. **Logistics Staff**: Lily Martinez
4. **Driver**: Fernando Cruz

---

### 1A. Register Consumer

**URL**: `http://localhost:8000/consumer/register`

```
Form:
┌─────────────────────────────────────┐
│ Consumer Registration Form          │
├─────────────────────────────────────┤
│ Full Name:        Juan Dela Cruz    │
│ Email:            juan@email.com   │
│ Phone:            09123456789      │
│ Password:         Secure123!       │
│ Confirm Password: Secure123!       │
└─────────────────────────────────────┘
```

**CLICK**: "Register" button

**Expected Result**:
- ✓ Popup or form shows: "OTP sent to your email!"
- ✓ Check email (or logs) for 6-digit code
- Screenshot: Page with OTP input box

---

### 1B. Enter OTP for Consumer

**On Same Page** or **New Page**: `http://localhost:8000/consumer/verify`

```
Enter:
Verification Code: [6-digit code from email]
```

**CLICK**: "Verify & Complete Registration"

**Expected Result**:
- ✓ Success message: "Registration complete!"
- ✓ Redirects to consumer dashboard
- ✓ Shows welcome: "Hi, Juan!"

---

### 1C. Create Farm Owner Account

**URL**: `http://localhost:8000/farm-owner/register`

```
Form:
┌─────────────────────────────────────┐
│ Farm Registration Form              │
├─────────────────────────────────────┤
│ Farm Name:        Green Valley      │
│ Permit/License#:  PERMIT-123456     │
│ Owner Name:       Miguel Santos     │
│ Email:            farm@email.com    │
│ Phone:            09876543210       │
│ Password:         Secure123!        │
└─────────────────────────────────────┘
```

**CLICK**: "Register Farm"

**Expected**: OTP verification (same as consumer)

**Enter OTP** (from email)

**Expected Result**:
- ✓ Farm dashboard loads
- ✓ Shows "Green Valley" farm name

---

### 1D. Create Logistics Staff Account

**URL**: `http://localhost:8000/department/login` (or admin)

**Note**: Logistics staff is created by super admin  
**Temporary**: Use existing admin credentials or create via database

```
Email: logistics@poultry.local
Password: password123
Role: logistics
```

**REMEMBER**: You'll log in with these later

---

### 1E. Create Driver

**Logistics Portal**: `http://localhost:8000/department/logistics/drivers/create`

**First**, log in as logistics staff:

```
Email: logistics@poultry.local
Password: password123
```

**Then** go to: `http://localhost:8000/department/logistics/drivers/create`

```
Form:
┌─────────────────────────────────────┐
│ Create Driver                       │
├─────────────────────────────────────┤
│ Full Name:        Fernando Cruz     │
│ Phone:            09555555555       │
│ Vehicle Type:     Van               │
│ License Number:   DLN-7654321       │
│ License Expiry:   2027-12-31        │
│ Address:          123 Driver St     │
└─────────────────────────────────────┘
```

**CLICK**: "Create Driver"

**Expected**:
- ✓ Driver created
- ✓ Shows in drivers list
- ✓ Status: "Available"

---

## 🏪 STEP 2: SET UP PRODUCTS

**URL**: `http://localhost:8000/farm-owner/dashboard`

**Log in as Farm Owner** (Miguel):
```
Email: farm@email.com
Password: Secure123!
```

### Add Products to Catalog

**Go to**: Farm Owner Portal → Products → Add New

```
Product 1:
┌─────────────────────────────────┐
│ Product Name:    Grade A Eggs   │
│ Category:        Poultry/Eggs   │
│ Price:           ₱8.99/dozen    │
│ Stock:           100             │
│ Min Order:       1               │
│ Description:     Free-range      │
└─────────────────────────────────┘
```

**CLICK**: "Add Product"

**Repeat** for 2-3 more products:
```
Product 2: Whole Chicken - ₱250 - Stock: 50
Product 3: Duck Eggs - ₱12.99/dozen - Stock: 30
```

**Expected**:
- ✓ Products show in farm's catalog
- ✓ Visible when browsing marketplace

---

## 🛒 STEP 3: CONSUMER PLACES ORDER

**Log in as Consumer** (Juan):
```
Email: juan@email.com
Password: Secure123!
```

### 3A. Browse Marketplace

**URL**: `http://localhost:8000/marketplace`

**See**:
- ✓ "Green Valley" farm displayed
- ✓ Farm rating shown
- ✓ Products: "Grade A Eggs", etc.

**CLICK**: "Green Valley" farm name/card

---

### 3B. View Farm Products

**Page**: Farm detail → Products list

**See**:
- Grade A Eggs - ₱8.99/dozen - Rating: N/A (first order)
- Other products
- Add to cart buttons

---

### 3C. Add Items to Cart

**SELECT**: "Grade A Eggs"

**Form**:
```
Quantity: 10

[Add to Cart]
```

**CLICK**: "Add to Cart"

**Expected**:
- ✓ Toast/notification: "Added to cart!"
- ✓ Cart badge shows "1" item

**Optional**: Add 1-2 more products to cart

---

### 3D. Proceed to Checkout

**URL**: `http://localhost:8000/marketplace/cart`

**Review Cart**:
```
Items:
- 10 × Grade A Eggs @ ₱8.99 = ₱89.90
- 2 × Whole Chicken @ ₱250 = ₱500.00

Subtotal: ₱589.90
Delivery: ₱100.00
Tax (VAT 12%): ₱83.09
──────────────
TOTAL: ₱772.99
```

**CLICK**: "Proceed to Checkout"

---

### 3E. Enter Delivery Address

**Form**:
```
Full Name:        Juan Dela Cruz
Phone:            09123456789
Address:          3 Mabini Street
City:             Makati
Province:         Metro Manila
Postal Code:      1231

[Save Address]
```

**CLICK**: "Save & Continue"

---

### 3F. Select Payment Method

**Choose ONE**:

#### Option A: Cash on Delivery (COD)
```
☑ Cash on Delivery
  "Pay ₱772.99 when order arrives"
  
[Continue]
```

#### Option B: Online Payment
```
☐ Online Payment (PayMongo)
  - GCash  [Tap]
  - PayMaya [Tap]
  
[Continue]
```

**For This Test**: Choose **COD** (easier to test)

---

### 3G. Review & Confirm Order

**Review Page**:
```
Order Summary:
───────────────────────────────
Items:        10 × Eggs, 2 × Chicken
Delivery To:  3 Mabini St, Makati
Payment:      COD (₱772.99 on delivery)
Estimated:    Tomorrow 2-4 PM
───────────────────────────────
Total:        ₱772.99

☑ I agree to terms
[Place Order]
```

**CLICK**: "Place Order"

**Expected**:
- ✓ Success page appears
- ✓ Order #: ORD-XXXXXXXX
- ✓ Message: "Order placed! Waiting for farm confirmation"
- ✓ Email sent to juan@email.com

**NOTE**: Take screenshot of order confirmation page

---

## 👨‍🌾 STEP 4: FARM OWNER CONFIRMS ORDER

**Log in as Farm Owner** (Miguel):
```
Email: farm@email.com
Password: Secure123!
```

**Go to**: `http://localhost:8000/farm-owner/orders`

**Expected**:
- ✓ 1 pending order from Juan
- ✓ Shows: Items, quantity, delivery address, total ₱772.99

**Click** on the order to view details

**See**:
- 10 × Grade A Eggs
- 2 × Whole Chicken
- Delivery to: 3 Mabini St, Makati
- Payment: COD
- Status: "Pending Confirmation"

**CLICK**: "Confirm Order" button

**Expected**:
- ✓ Status changes to "Confirmed ✓"
- ✓ Order moves to "Confirmed" tab
- ✓ Logistics notified automatically

---

## 📦 STEP 5: FARM OWNER CREATES DELIVERY

**Still as Farm Owner**, go to:  
**URL**: `http://localhost:8000/farm-owner/deliveries/create`

**Form**:
```
Order:            [Dropdown] - Select Juan's order
Recipient Name:   Juan Dela Cruz (auto-filled)
Recipient Phone:  09123456789 (auto-filled)
Delivery Address: 3 Mabini St (auto-filled)
City/Province:    Makati, Metro Manila (auto-filled)

Scheduled Date:   [Pick today or tomorrow]
Time Window:      14:00-16:00 (2 PM - 4 PM)

Special Notes:    "Leave at gate if not home"

[Create Delivery]
```

**CLICK**: "Create Delivery"

**Expected**:
- ✓ Delivery created
- ✓ Tracking #: TRK-XXXXXXXX shown
- ✓ Status: "Pending" (driver not assigned yet)
- ✓ Appears in farm's delivery list

**NOTE**: Copy the tracking number (TRK-XXXXXXXX)

---

## 🚨 STEP 6: LOGISTICS ASSIGNS DRIVER

**Log in as Logistics Staff**:
```
Email: logistics@poultry.local
Password: password123
```

**Go to**: `http://localhost:8000/department/logistics`

**Dashboard Shows**:
- ✓ 1 unassigned delivery pending
- ✓ Stats: 0 drivers busy, 1 available

**Click** on the pending delivery (or "View Delivery")

**Delivery Details**:
```
Tracking:     TRK-XXXXXXXX
Recipient:    Juan Dela Cruz
Address:      3 Mabini St, Makati
Schedule:     Today 14:00-16:00
Status:       Pending (no driver)
Available Drivers: [Fernando ✓ available]
```

**CLICK**: "Assign Driver" or dropdown → Select "Fernando Cruz"

**CLICK**: "Assign"

**Expected**:
- ✓ Status changes: "Pending" → "Assigned ✓"
- ✓ Shows: "Driver: Fernando Cruz"
- ✓ Fernando's status: "Available" → "On Delivery"
- ✓ SMS sent to Juan: "Order assigned to driver Fernando!"

**NOTE**: Check Juan's email/phone for notification

---

## 🚗 STEP 7: DRIVER MARKS "OUT FOR DELIVERY"

**Simulate Driver Actions** (Can't test without driver mobile app, but can simulate):

**Go to**: `http://localhost:8000/department/logistics/deliveries`

**Find** the delivery (TRK-XXXXXXXX)

**CLICK** on it → "Actions" or "Update Status"

**Select**: "Out for Delivery"

**CLICK**: "Update"

**Expected**:
- ✓ Status: "Assigned" → "Dispatched ✓"
- ✓ Juan gets SMS: "Your order is out for delivery! ETA: 15:30"
- ✓ Consumer app shows: "Out for Delivery 🚗"

---

## 📍 STEP 8: DRIVER DELIVERS & COLLECTS PAYMENT

**Go to**: Same delivery page

**CLICK**: "Mark Delivered" or "Complete Delivery"

**Form**:
```
Delivery Time:    15:28 (auto-filled)
COD Collected:    ₱772.99 (from order)
Payment Method:   Cash
Recipient Notes:  "Received in good condition"

[Confirm Delivery]
```

**CLICK**: "Confirm Delivery"

**Expected**:
- ✓ Status: "Dispatched" → "Delivered ✓"
- ✓ COD marked as "Collected"
- ✓ Order status: "Delivered"
- ✓ Juan gets SMS: "Delivered! Rate your experience →"
- ✓ Juan's app shows: "Delivered ✓"

---

## ⭐ STEP 9: CONSUMER RATES DELIVERY

**Log in as Consumer** (Juan):
```
Email: juan@email.com
Password: Secure123!
```

**Go to**: Consumer Dashboard → Orders

**Find** the delivered order

**CLICK**: "Rate This Delivery"

**Form**:
```
Overall Rating:
★ ★ ★ ★ ★  (Click 5 stars)

Feedback:
"Perfect! Eggs came fresh, driver was polite ✓"

[Submit Rating]
```

**CLICK**: "Submit Rating"

**Expected**:
- ✓ Success: "Thank you! Your rating helps farmers ✓"
- ✓ Order status: "Delivered" → "Completed"
- ✓ Rating appears on delivery record
- ✓ Farm owner sees rating (+5★)
- ✓ Driver rating updated

---

## 🔍 STEP 10: VERIFY COMPLETE FLOW

After completing the manual flow, verify these:

### ✅ Consumer Checklist:
- [ ] Registered with OTP verification
- [ ] Browsed marketplace
- [ ] Added items to cart
- [ ] Completed checkout (COD)
- [ ] Order placed successfully
- [ ] Received order confirmation email
- [ ] Viewed order status updates
- [ ] Received "Out for Delivery" notification
- [ ] Received "Delivered" notification
- [ ] Rated delivery (5★)
- [ ] Order shows "Completed"

### ✅ Farm Owner Checklist:
- [ ] Registered farm
- [ ] Added 3+ products
- [ ] Received order from Juan
- [ ] Confirmed order
- [ ] Created delivery
- [ ] Received confirmation when driver assigned
- [ ] Saw delivery status updates
- [ ] Can see customer rating (5★) on delivery

### ✅ Logistics Checklist:
- [ ] Logged into logistics portal
- [ ] Created driver (Fernando)
- [ ] Saw pending unassigned delivery
- [ ] Assigned driver to delivery
- [ ] Updated delivery status to "dispatched"
- [ ] Marked delivery as "delivered"
- [ ] Dashboard shows 1 completed delivery

### ✅ Driver Checklist:
- [ ] Created in system
- [ ] Status: "Available"
- [ ] Got assigned to delivery
- [ ] Status changed to "On Delivery"
- [ ] Could mark delivery complete
- [ ] Received 5★ rating

---

## 📊 VERIFY DATABASE CHANGES

After completing the flow, the database should show:

```sql
-- Check Consumer Created
SELECT * FROM users WHERE email = 'juan@email.com';
-- Expected: 1 row, role = 'consumer', phone = '09123456789'

-- Check Order Created
SELECT * FROM orders WHERE consumer_id = [juan_id];
-- Expected: 1 row, status = 'completed', payment_status = 'paid', total_amount = 772.99

-- Check Delivery Created
SELECT * FROM deliveries WHERE order_id = [order_id];
-- Expected: 1 row, driver_id = [fernando_id], status = 'delivered', rating = 5

-- Check Rating Created
SELECT * FROM delivery_ratings WHERE delivery_id = [delivery_id];
-- Expected: 1 row, rating = 5, feedback = 'Perfect! Fresh eggs...'

-- Check Driver Status Back to Available
SELECT * FROM drivers WHERE name = 'Fernando Cruz';
-- Expected: status = 'available', total_deliveries = 1
```

---

## 🔧 TROUBLESHOOTING

### Issue: "Order not created"
**Check**:
- [ ] Payment processed (if online)?
- [ ] Consumer logged in?
- [ ] Product stock available?
- [ ] Cart not empty?

### Issue: "Driver not assigned"
**Check**:
- [ ] Driver created?
- [ ] Driver status = 'available'?
- [ ] Delivery pending in system?

### Issue: "Status not updating"
**Check**:
- [ ] Page refreshed?
- [ ] Correct user logged in?
- [ ] No browser cache issues? (Ctrl+F5)

### Issue: "Email/OTP not working"
**Check**:
- [ ] Mail configuration correct in .env?
- [ ] Gmail SMTP credentials valid?
- [ ] Check logs: `storage/logs/laravel.log`

---

## 📞 QUICK REFERENCE

### Login Credentials:

```
CONSUMER (Juan):
Email: juan@email.com
Password: Secure123!
Access: /marketplace

FARM OWNER (Miguel):
Email: farm@email.com
Password: Secure123!
Access: /farm-owner/dashboard

LOGISTICS (Lily):
Email: logistics@poultry.local
Password: password123
Access: /department/logistics
```

### URLs Quick Links:

```
Homepage:         http://localhost:8000
Marketplace:      http://localhost:8000/marketplace
Farm Owner:       http://localhost:8000/farm-owner/dashboard
Logistics:        http://localhost:8000/department/logistics
My Orders:        http://localhost:8000/consumer/orders
Add Product:      http://localhost:8000/farm-owner/products/create
Create Driver:    http://localhost:8000/department/logistics/drivers/create
Create Delivery:  http://localhost:8000/farm-owner/deliveries/create
Deliveries List:  http://localhost:8000/department/logistics/deliveries
```

---

## ✨ SUCCESS METRICS

You'll know the system works when:

✅ **Consumer Journey Works**:
- Register → OTP → Browse → Order → Get Notified → Rate → Complete

✅ **Farm Owner Works**:
- Register → Add Products → Confirm Orders → Create Deliveries

✅ **Logistics Works**:
- View Dashboard → See Pending → Assign Drivers → Track Status

✅ **Driver Works**:
- Gets Assigned → Collects Payment → Receives Rating

✅ **Data Flows End-to-End**:
- Order created → Delivery created → Driver assigned → Delivered → Rated

---

**READY?** Start with **STEP 0** and follow the guide! 🚀

Any issues? Check the troubleshooting section above.
