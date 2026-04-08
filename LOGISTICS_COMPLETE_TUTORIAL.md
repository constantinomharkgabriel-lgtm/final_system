# 🚚 COMPLETE LOGISTICS TUTORIAL - Step by Step
**Updated**: April 5, 2026  
**System Version**: With Driver Selection Feature

---

## 📚 TABLE OF CONTENTS
1. [Quick Start](#quick-start)
2. [Part 1: Create Your First Driver](#part-1-create-your-first-driver)
3. [Part 2: Logistics Staff Creates Delivery](#part-2-logistics-staff-creates-delivery)
4. [Part 3: Delivery Status Updates](#part-3-delivery-status-updates)
5. [Part 4: Consumer Sees Order](#part-4-consumer-sees-order)
6. [Part 5: Mobile App Marketplace](#part-5-mobile-app-marketplace)
7. [Real-World Example](#real-world-example)
8. [Troubleshooting](#troubleshooting)

---

## ⚡ QUICK START

**1. Start the Server:**
```bash
cd c:\Users\lawrence tabutol\Downloads\Final_system\poultry-system
php artisan serve --host=127.0.0.1 --port=8000
```

**2. Open Browser:**
```
http://127.0.0.1:8000
```

**3. You should see the homepage** with links to different portals.

---

# 🎓 PART 1: CREATE YOUR FIRST DRIVER

## Step 1.1: Access the Employee Form

**For Farm Owner:**
1. Go to `http://127.0.0.1:8000/farm-owner/employees/create`
2. Login as Farm Owner if needed

**For HR Staff:**
1. Go to `http://127.0.0.1:8000/hr/employees/create`
2. Login as HR user if needed

## Step 1.2: Fill Employee Basic Information

Fill in these fields:
- **First Name**: John
- **Last Name**: Dela Cruz
- **Email**: john.driver@example.com
- **Phone**: 09123456789 (optional, for testing)
- **Gender**: Male
- **Password**: password123
- **Confirm Password**: password123

## Step 1.3: Fill Employment Details

- **Department**: Select **"Driver"** ← This triggers the driver form!
- **Position**: Delivery Driver
- **Employment Type**: Full Time
- **Hire Date**: Today's date
- **Daily Rate**: 500 (₱500/day)
- **Monthly Salary**: Auto-calculates to 11,000 (500 × 22 working days)

## Step 1.4: IMPORTANT - Watch for Driver Details Section!

When you select "Driver" from the Department dropdown:

✨ **MAGIC HAPPENS** ✨
```
⬇️ Driver Details section appears automatically! ⬇️
```

A blue section titled "🚗 Driver Details" will appear below with these fields:

- **Vehicle Type**: Select "Tricycle" (or motorcycle, van, truck)
- **Plate Number**: ABC 1234
- **Vehicle Model**: Isuzu NPR
- **License Number**: DL1234567890
- **License Expiry**: 2028-12-31
- **Delivery Fee**: 50 ← **IMPORTANT: This is the commission per delivery!**
- **Notes**: Good condition, recently serviced

## Step 1.5: Optional - Add Driver Role

You can ALSO check the "Driver" role checkbox under "Roles & Permissions" if you want - it will also show the driver form. Either method works!

```
✓ Select "Driver" department, OR
✓ Check "Driver" role checkbox, OR  
✓ Both work!
```

## Step 1.6: Submit the Form

Click "Add Employee" button.

### ✅ What Happens Behind the Scenes:

1. ✓ Employee record created in database
2. ✓ User account created with email
3. ✓ **Driver role automatically assigned**
4. ✓ Driver profile created with:
   - Driver code: DRV-{farm_id}-{timestamp}-{random}
   - Vehicle info stored
   - Delivery fee set to ₱50 per delivery
   - Status: "available"
5. ✓ Cache cleared
6. ✓ Driver automatically shows in Logistics sidebar

## Step 1.7: Verify Driver Was Created

1. Go to **Logistics Portal** → **Drivers**
2. You should see your new driver in the list:

```
Name: John Dela Cruz
Vehicle: Tricycle
Plate: ABC 1234
Status: Available
```

**CONGRATULATIONS!** Your first driver is ready! 🎉

---

# 📦 PART 2: LOGISTICS STAFF CREATES DELIVERY

Now that you have a driver, let's create an actual delivery order!

## Step 2.1: Access the Deliveries Page

1. Go to **Logistics Portal** → **Deliveries**
2. Click **"+ Create Delivery"** button

## Step 2.2: Select the Order

You'll see a form with this section:

```
Select Order
[ Dropdown with available orders ]
```

**What's an order?** 
- A consumer placed an order on the marketplace
- Farm owner confirmed it (marked as "confirmed" or "processing")
- Now logistics staff needs to deliver it

**Select** an order from the dropdown. If no orders exist, you need to:
1. Go to Web Marketplace as a Consumer
2. Place an order
3. Go to Farm Owner Portal
4. Confirm the order
5. Then come back here

## Step 2.3: Assign the Driver

Under "Driver Assignment" section:

```
Driver: [ Select Driver Dropdown ]
```

Select "John Dela Cruz" (the driver we just created).

## Step 2.4: Set Initial Status

- **Status**: Usually "preparing" or "ready_for_pickup"
- **Notes**: "Order ready, package on shelf"

## Step 2.5: Click "Create Delivery"

### ✅ What Happens:

1. ✓ Delivery record created in database
2. ✓ Status set to "preparing"
3. ✓ Driver assigned to delivery
4. ✓ Consumer gets notified (if notifications enabled)
5. ✓ Delivery appears in list

---

# 🔄 PART 3: DELIVERY STATUS UPDATES

This is where logistics staff controls EVERYTHING the consumer sees!

## Step 3.1: View the Delivery

In **Logistics Portal** → **Deliveries**:

```
You see your delivery:
Order: #ORD-2026-001
Driver: John Dela Cruz
Status: Preparing
↓ [View] [Edit] [Delete]
```

Click **[View]** to see details.

## Step 3.2: Update to "Packed"

On the delivery detail page:

```
Current Status: Preparing
⬇️
[Packed] button
```

Click "Packed" button.

### What the consumer sees:
```
Their order status changes:
"Preparing your order" → "Packed and ready"
```

## Step 3.3: Dispatch the Delivery

```
Current Status: Packed
⬇️
[Dispatch] or [Out for Delivery] button
```

Click to dispatch.

### Behind the Scenes:
1. ✓ Status changes to "out_for_delivery"
2. ✓ Driver status changes to "on_delivery"
3. ✓ **Consumer gets notification: "Your order is out for delivery!"**
4. ✓ Delivery tracking info sent to consumer
5. ✓ Notification includes driver name if available

### What the consumer sees:
```
🚗 Your order is on the way!
Driver: John Dela Cruz
Vehicle: Tricycle (ABC 1234)
Estimated Time: Next 2 hours
Track Order → [Button to see tracking page]
```

## Step 3.4: Mark as Delivered

When driver confirms delivery:

```
Current Status: Out for Delivery
⬇️
[Mark as Delivered] button
```

Click and you can optionally upload delivery proof.

### Behind the Scenes:
1. ✓ Status changes to "delivered"
2. ✓ Driver marked "available" again (ready for next delivery)
3. ✓ **Driver earns commission!** (₱50 added to his earnings)
4. ✓ Consumer notified: "Your order has been delivered!"
5. ✓ Order is complete and can be rated

### What the consumer sees:
```
✅ Your order was delivered successfully!

Delivered on: April 5, 2026 - 2:30 PM
Driver: John Dela Cruz
Rating: ⭐⭐⭐⭐⭐ [Click to rate]
```

---

# 👥 PART 4: CONSUMER SEES ORDER

Now let's see how the consumer experience works!

## Step 4.1: Consumer Places Order

**As a Consumer** (go to Web Marketplace):

1. Browse available products
2. Click "Add to Cart"
3. Proceed to checkout
4. Place order

**Order Status**: "pending" or "processing"

## Step 4.2: Farm Owner Confirms

**As Farm Owner**:

1. Go to Farm Owner Portal → Orders
2. See the pending order
3. Click "Confirm" button
4. Order status changes to "confirmed"

## Step 4.3: Logistics Creates Delivery

**As Logistics Staff**: (Follow Part 2 above)

1. Create delivery from this order
2. Assign driver
3. Mark as packed
4. Dispatch

## Step 4.4: Consumer Tracks Order

**Back as Consumer**:

1. Go to **Web Marketplace** → **My Orders**
2. Click on the order

**They see:**
```
┌─────────────────────────────────────────┐
│  Order #ORD-2026-001                    │
│  Status: 🚗 Out for Delivery            │
│                                          │
│  Items:                                  │
│  • Chicken (1KG) - ₱250                │
│  • Eggs (1 tray) - ₱150                │
│                                          │
│  Total: ₱400                            │
│                                          │
│  Delivery Info:                         │
│  Driver: John Dela Cruz               │
│  Vehicle: Tricycle ABC 1234           │
│  Estimated: Today 2-3 PM              │
│                                          │
│  [View Driver Location/Details]        │
│  [Chat with Driver]                     │
│  [Track Order]                          │
└─────────────────────────────────────────┘
```

---

# 📱 PART 5: MOBILE APP MARKETPLACE

The mobile app gives consumers on-the-go access!

## Step 5.1: Download Consumer Mobile App

Location: `poultry_consumer_app/`

**Build for Android/iOS** using Flutter.

## Step 5.2: Features Available in Mobile App

### Browse Products:
```
Home Screen:
├─ Search Products
├─ Browse by Category
├─ Favorites
├─ Recommended
└─ Special Offers
```

### Place Order:
```
Add to Cart → Checkout → Payment → Order Placed
```

### Track Order:
```
My Orders Tab:
├─ Pending Orders
├─ Active Deliveries  ← 🚗 Real-time tracking!
├─ Completed Orders
└─ Order History
```

### Real-Time Delivery Tracking:
```
Live Map:
├─ Driver location (if GPS available)
├─ Estimated arrival
├─ Driver contact
└─ Message driver
```

## Step 5.3: Mobile App Data Flow

```
┌─────────────────┐
│ Mobile App      │
│ (Consumer)      │
└────────┬────────┘
         │
         │ Makes API calls
         ↓
    ┌───────────────────────┐
    │  Laravel API (HTTP)   │
    │  /api/orders          │
    │  /api/deliveries      │
    │  /api/drivers         │
    │  /api/tracking        │
    └───────────┬───────────┘
                │
                ↓
         ┌─────────────────┐
         │  PostgreSQL DB  │
         │  (shared data)  │
         └─────────────────┘
```

---

# 🎬 REAL-WORLD EXAMPLE

Let me walk through a complete scenario from start to finish!

## Scenario: Luntian Farm delivers to Mr. Santos

### 🕐 9:00 AM - Mr. Santos Orders

**On Web Marketplace:**
```
Mr. Santos browses → Adds 1KG Chicken to cart → Checks out → Pays
Order Created:
  #ORD-2026-00534
  Status: pending
  Total: ₱500
```

### 🕑 9:30 AM - Farm Owner Confirms

**At Farm Owner Portal:**
```
Owner sees order #ORD-2026-00534
Clicks "Confirm Order"
Status changes: pending → confirmed
Packs the order
```

### 🕒 9:45 AM - Logistics Staff Creates Delivery

**At Logistics Portal:**
```
1. Staff goes to Deliveries → Create
2. Selects order #ORD-2026-00534
3. Assigns driver: John Dela Cruz
4. Sets status: "ready_for_pickup"
5. Clicks "Create Delivery"
```

**Mr. Santos Status Changes:**
```
"Processing" → "Packed and ready to ship"
✉️ Notification: "Your order is ready for delivery!"
```

### 🕓 10:15 AM - Staff Marks as Packed

**At Logistics Portal:**
```
Delivery detail page → Click "Packed"
Status: ready_for_pickup → packed
```

### 🕔 10:30 AM - Driver Departs (Dispatch)

**At Logistics Portal:**
```
Staff clicks "Dispatch" / "Out for Delivery"
Status: packed → out_for_delivery
Driver John status: available → on_delivery
```

**Mr. Santos Sees on Web & Mobile:**
```
🚗 IN TRANSIT!

Order Status: Out for Delivery
Driver: John Dela Cruz
Vehicle: Tricycle (ABC 1234)
Last Update: Just now
Estimated: 11:00 AM - 11:30 AM

[View Driver] [Track] [Message Driver]
```

**He can see on Mobile App:**
```
Live map showing driver location
Driver contact number
Message button to communicate
```

### 🕕 11:00 AM - Delivery Arrives

**Driver John arrives at Mr. Santos's location**

**At Logistics Portal:**
```
Staff clicks "Mark as Delivered"
Can upload photo/proof
Status: out_for_delivery → delivered
```

**Mr. Santos Sees:**
```
✅ DELIVERED!

Successfully delivered by: John Dela Cruz
Time: 11:05 AM
Location: Confirmed
Details: Received in good condition

⭐⭐⭐⭐⭐ Rate this delivery
[View Order] [Contact Support]
```

### 💰 Driver Earns Commission

**Behind the Scenes:**
```
Delivery.completed = true
Driver.delivery_fee = ₱50
Driver.earnings += ₱50

During payroll:
  Driver's commission = ₱50 × number_of_deliveries
  Added to base salary
  Example: Base ₱15,000 + Commission ₱300 = ₱15,300
```

### 📊 The Complete Flow Diagram

```
┌──────────────────────────────────────────────────────────────┐
│                    9:00 AM - ORDER PLACED                    │
│   Consumer places order on Web/Mobile Marketplace            │
│   Status: PENDING                                            │
└────────────┬─────────────────────────────────────────────────┘
             │
             ▼
┌──────────────────────────────────────────────────────────────┐
│               9:30 AM - FARM OWNER CONFIRMS                  │
│   Farm owner sees order, confirms, packs                    │
│   Status: CONFIRMED → PACKED                                │
└────────────┬─────────────────────────────────────────────────┘
             │
             ▼
┌──────────────────────────────────────────────────────────────┐
│          9:45 AM - LOGISTICS CREATES DELIVERY                │
│   Staff creates delivery, assigns driver John               │
│   Status: PREPARING → READY_FOR_PICKUP                      │
│   Consumer sees: "Order is packed and ready!"               │
└────────────┬─────────────────────────────────────────────────┘
             │
             ▼
┌──────────────────────────────────────────────────────────────┐
│           10:15 AM - MARKED AS PACKED                        │
│   Logistics staff marks as packed                           │
│   Status: PACKED                                            │
│   Consumer sees: "Packed and ready to ship"                 │
└────────────┬─────────────────────────────────────────────────┘
             │
             ▼
┌──────────────────────────────────────────────────────────────┐
│         10:30 AM - DISPATCHED (OUT FOR DELIVERY)             │
│   Staff dispatches delivery                                 │
│   Driver John status: available → on_delivery               │
│   Status: OUT_FOR_DELIVERY                                  │
│   Consumer sees: "🚗 Your order is on the way!"             │
│              Shows driver photo, vehicle, arrival time      │
│   Mobile App: Live tracking map available                   │
└────────────┬─────────────────────────────────────────────────┘
             │
             ▼
┌──────────────────────────────────────────────────────────────┐
│         11:00 AM - MARKED AS DELIVERED                       │
│   Driver delivers, staff marks complete                     │
│   Status: DELIVERED                                         │
│   Consumer sees: "✅ Your order arrived!"                    │
│              Can rate the delivery                          │
│   Driver John: Earns ₱50 commission                         │
│               Status: on_delivery → available (ready for next)
└──────────────────────────────────────────────────────────────┘
```

---

# 🔧 TROUBLESHOOTING

## Q: Driver section doesn't appear when I select "Driver" department?

**A:** 
1. Make sure you selected "Driver" from the dropdown (not just typed it)
2. Refresh the page
3. Clear browser cache: Ctrl+Shift+Delete
4. Check browser console (F12) for JavaScript errors

## Q: Driver was created but doesn't appear in Logistics drivers list?

**A:**
1. Refresh the page
2. Check if you're on the correct farm owner account
3. Check database tables:
   ```
   php artisan tinker
   >> Driver::all()
   ```

## Q: Consumer doesn't see delivery notifications?

**A:**
1. Check if maildriver is set to 'log' (for testing)
2. Notifications sent but not displayed in UI in log mode
3. In production, use SMTP or other mail driver

## Q: Driver Delivery Fee not working for commission?

**A:**
1. Make sure delivery_fee was entered when creating driver
2. Check payroll calculation in PayrollController
3. Verify delivery marked as "completed" (not just "delivered")

## Q: Mobile app not connecting to API?

**A:**
1. Ensure server is running: `php artisan serve`
2. Check API_URL in mobile app config
3. Verify CORS settings in Laravel
4. Check network: `http://127.0.0.1:8000` accessible?

---

# 📋 COMPLETE CHECKLIST

### ✅ You Should Now Understand:

- [ ] How to create a driver using the new form (department or role)
- [ ] How driver details are captured (vehicle, license, delivery fee)
- [ ] How logistics staff creates and manages deliveries
- [ ] The complete delivery status workflow
- [ ] What consumers see at each stage
- [ ] How mobile app connects to the system
- [ ] How drivers earn commission
- [ ] How notifications flow to consumers

### 🚀 Next Steps:

1. **Test in Browser**: Follow the real-world example above
2. **Try Mobile App**: Build and run the Flutter consumer app
3. **Test with Team**: Do this with multiple users simultaneously
4. **Monitor Performance**: Check response times, notification delivery
5. **Deploy**: When ready, deploy to production

---

## 📞 QUICK REFERENCE

| Action | Where | What Changes |
|--------|-------|--------------|
| Create Driver | Employees → New | Driver profile created, appears in Logistics sidebar |
| Create Delivery | Logistics → Deliveries | Consumer sees order status change |
| Pack Order | Delivery Detail | Consumer: "Packing your order" |
| Dispatch | Delivery Detail | Consumer: "Out for delivery!" + Driver details |
| Deliver | Delivery Detail | Consumer: "Arrived!" + Rating option |
| Rating | Web/Mobile Consumer | Appears on driver profile |
| Commission | Payroll | Driver earns delivery_fee × completed_deliveries |

---

**Happy Delivering!** 🚗📦✨
