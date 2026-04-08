# 📦 LOGISTICS SYSTEM - COMPLETE FLOW EXPLANATION

## 🎯 Overview

The Logistics System is a **three-part delivery & fleet management ecosystem** that connects your **Web Marketplace**, **Mobile App**, and **Farm Owner Portal** to manage orders → deliveries → drivers → payments in real-time.

---

## 🏗️ System Architecture

```
┌─ CONSUMERS (2 Platforms) ─────────────────┐
│                                            │
│  📱 Mobile App (Flutter)  💻 Web Platform │
│  • Browse products        • Browse products│
│  • Place orders           • Cart & checkout│
│  • Track deliveries       • Profile        │
│  • Rate drivers           • Order history  │
└─────────────────┬──────────────────────────┘
                  │
    ┌─────────────┴──────────────┐
    ▼                            ▼
┌─────────────────┐     ┌─────────────────────┐
│  MARKETPLACE    │     │  FARM OWNER PORTAL  │
│  (Web Products) │     │  (Orange Portal)    │
│  /products      │     │ /farm-owner/*       │
│  /orders        │     │ • Dashboard         │
│  /cart          │     │ • Manage orders     │
└────────┬────────┘     └────────┬────────────┘
         │                       │
         └───────────┬───────────┘
                     ▼
         ┌───────────────────────┐
         │   LARAVEL BACKEND     │
         │  (API + Web Routes)   │
         │   MySQL Database      │
         └───────────┬───────────┘
                     ▼
      ┌──────────────────────────────┐
      │   LOGISTICS PORTAL (NEW!)     │
      │   /department/logistics/*      │
      │   • Drivers management         │
      │   • Deliveries tracking        │
      │   • Schedule coordination      │
      │   • COD collection             │
      └──────────┬───────────────────┘
                 ▼
         ┌────────────────────┐
         │  DRIVERS (Mobile)   │
         │  • Receive orders   │
         │  • Update status    │
         │  • Collect COD      │
         │  • Proof of delivery│
         └────────────────────┘
```

---

## 📊 ORDER FLOW (Complete Journey)

### **STEP 1️⃣: Consumer Places Order**

#### **From Mobile App:**
```
Consumer picks product
    ↓
Adds to cart with quantity
    ↓
Checkout (POST /api/mobile/orders)
    ├─ Validates: bulk order rules, minimum quantity, stock availability
    ├─ Creates Order in database with status: "pending"
    ├─ Payment Method: COD | GCash | PayMaya
    └─ Order sent to farm owner
```

**Mobile API Endpoint:**
```php
POST /api/mobile/orders
{
  "farm_owner_id": 5,
  "items": [
    {
      "product_id": 1,
      "quantity": 10,
      "unit_price": 50.00
    }
  ],
  "delivery_address": "123 Main St, Manila",
  "delivery_city": "Manila",
  "delivery_province": "Metro Manila",
  "payment_method": "cod" // or "gcash", "paymaya"
}

Response:
{
  "order_id": 123,
  "order_number": "ORD-2026-001",
  "total_amount": 500.00,
  "payment_status": "pending",
  "checkout_url": "https://checkout.paymongo.com/..." // if online payment
}
```

#### **From Web Platform:**
```
Consumer browses farm products
    ↓
Adds items to cart
    ↓
Checkout form (delivery address, payment method)
    ↓
POST /orders → OrderController::store()
    ↓
Same validation & order creation
```

### **Order Status At This Point:**
- `status` = "pending" (awaiting farm owner confirmation)
- `payment_status` = "pending"
- `delivery_type` = "scheduled"

---

### **STEP 2️⃣: Farm Owner Confirms Order**

**Farm Owner Portal `/farm-owner/orders`:**

1. **Views pending orders**
2. **Confirms order** (manual action)
   - Order status changes: `pending` → `confirmed`
   - Notification sent to consumer (mobile/web)
   - Farm owner decides: Can fulfill? When?

```php
// OrderController::confirm()
Order::update([
    'status' => 'confirmed',
    'confirmation_date' => now()
]);

// Send notification to consumer
Notification::create([
    'user_id' => $order->consumer_id,
    'type' => 'order_confirmed',
    'title' => 'Your order has been confirmed!'
]);
```

### **CONSUMER TRACKING (Mobile/Web):**
```
Order Status: ✓ Confirmed
Farm is preparing your order
Estimated delivery: Next 2-3 days
[Show "Track Delivery" button]
```

---

### **STEP 3️⃣: Create Delivery Record**

**Farm Owner / Logistics Staff creates delivery:**

**Portal Route:** `/farm-owner/deliveries/create` or `/department/logistics/deliveries/create`

**Form Inputs:**
```
□ Select Order (only confirmed orders without delivery yet)
□ Recipient Name
□ Recipient Phone
□ Delivery Address (auto-filled from order)
□ Scheduled Date
□ Scheduled Time Range
□ Driver (optional - can assign later)
□ COD Amount (if payment method was COD)
□ Special Instructions
```

**Backend Logic:**
```php
// DeliveryController::store()
$delivery = Delivery::create([
    'farm_owner_id' => $farmOwner->id,
    'order_id' => $order->id,
    'driver_id' => null, // Not yet assigned
    'tracking_number' => 'TRK-2026-' . str_random(8), // Auto-generated
    'recipient_name' => $validated['recipient_name'],
    'delivery_address' => $validated['delivery_address'],
    'scheduled_date' => $validated['scheduled_date'],
    'status' => 'preparing', // Initial status
    'cod_amount' => $order->total_amount, // If COD order
    'cod_collected' => false
]);

// Link order to delivery
$order->update(['status' => 'processing']);

// Notify consumer of tracking number
Notification::create([
    'user_id' => $order->consumer_id,
    'type' => 'delivery_created',
    'data' => [
        'tracking_number' => $delivery->tracking_number,
        'scheduled_date' => $delivery->scheduled_date
    ]
]);
```

### **Delivery Status Timeline:**
```
1. preparing    ← Initial state (farm preparing package)
2. packed       ← Ready for pickup
3. assigned     ← Driver assigned
4. out_for_delivery ← Driver left with package
5. delivered    ← Delivered to customer
6. completed    ← Payment collected (if COD)
7. failed       ← Delivery failed/returned
```

---

### **STEP 4️⃣: Assign Driver**

**Logistics Portal `/department/logistics/deliveries`:**

1. **View all deliveries** (Logistics staff/employee with `logistics` role)
2. **Assign Driver** to specific delivery
3. **Coordinate schedule**

**Backend Logic:**
```php
// DeliveryController::assignDriver()
$delivery->driver_id = $driver_id;
$delivery->status = 'assigned';
$delivery->assigned_by = Auth::id();
$delivery->save();

// Update driver status
Driver::update(['status' => 'on_delivery']);

// Notify driver (Mobile push or in-app notification)
// Driver receives order details via mobile app
```

**Logistics Portal Features:**
- **Drivers Index:** See all drivers (total, available, on-delivery)
- **Drivers Edit:** Update license, vehicle, status
- **Delivery Schedule:** View today's → tomorrow's → unscheduled deliveries
- **COD Tracking:** See pending cash-on-delivery amounts
- **Real-time Stats:** Pending deliveries, dispatched, completed today

---

### **STEP 5️⃣: Driver Execution (Mobile App)**

**Driver receives order on mobile app** (if driver has app access)

```
🚗 DRIVER MOBILE APP
├─ New delivery assigned
├─ View order details:
│  ├─ Recipient: John Doe
│  ├─ Address: 123 Main St, Manila
│  ├─ COD Amount: ₱500
│  ├─ Special Notes: "Ring bell 2x"
│  └─ Navigation link
├─ Status buttons:
│  ├─ "Start Delivery" (out_for_delivery)
│  ├─ "Deliver" (delivered)
│  ├─ "Failed Delivery" (failed)
│  └─ "Collect COD Payment"
└─ Photo upload for proof
```

**Driver Updates Status:**
```php
// DeliveryController::dispatch()
$delivery->status = 'out_for_delivery';
$delivery->dispatched_at = now();
$delivery->save();

// Notify consumer
Notification::notify($order->consumer_id, [
    'type' => 'delivery_dispatched',
    'message' => 'Driver is on the way!',
    'tracking_number' => $delivery->tracking_number
]);
```

```php
// DeliveryController::markDelivered()
$delivery->status = 'delivered';
$delivery->delivered_at = now();
$delivery->proof_of_delivery_url = $request->file('proof')->store(...);
$delivery->save();

// If COD:
if ($delivery->cod_amount > 0) {
    $delivery->status = 'completed';
    $delivery->cod_collected = true;
    
    // Record income for farm owner
    IncomeRecord::create([
        'farm_owner_id' => $delivery->farm_owner_id,
        'order_id' => $delivery->order_id,
        'amount' => $delivery->cod_amount,
        'type' => 'cod_collection',
        'reference' => $delivery->tracking_number
    ]);
}

// Order status updated
$order->update(['status' => 'delivered']);

// Consumer notified
Notification::notify($order->consumer_id, [
    'type' => 'delivery_completed',
    'message' => 'Your order arrived! Please rate your experience.'
]);
```

---

### **STEP 6️⃣: Consumer Rating & Feedback**

**Mobile App / Web Platform:**

```
📱 Delivery Complete! 
[Rating Stars: 1-5]
[Time: Estimated 2 days, Actual 2 days ✓]
[Driver: John Doe]
[Comment: Great service!]
[Submit Rating]
```

**Backend:**
```php
// MobileMarketplaceController::submitRating()
$delivery->rating = $request->input('rating');
$delivery->feedback = $request->input('feedback');
$delivery->save();

// Update driver average rating
$driver->average_rating = $driver->deliveries()
    ->whereNotNull('rating')
    ->average('rating');
$driver->save();

// Notify farm owner
Notification::notify($farmOwner->user_id, [
    'type' => 'delivery_rated',
    'message' => "Order {$order->order_number} rated {$delivery->rating}⭐"
]);
```

---

## 🎯 WHO DOES WHAT?

### **🧑‍🌾 Farm Owner (Orange Portal)**
- ✓ Confirm/reject orders
- ✓ Create delivery records
- ✓ View all deliveries
- ✓ Manage drivers (add, edit, delete)
- ✓ View delivery schedule
- ✓ Track COD collections
- ✓ View income from deliveries

### **👥 Logistics Staff (Purple Portal - NEW!)**
- ✓ View all drivers roster
- ✓ Edit driver assignments
- ✓ Assign drivers to deliveries
- ✓ View delivery schedule (today/tomorrow/unscheduled)
- ✓ Filter & search deliveries by status
- ✓ Track COD amounts pending
- ✓ Monitor delivery performance

### **🚗 Driver (Mobile App)**
- ✓ Receive delivery assignments
- ✓ View order details & recipient info
- ✓ Navigate to delivery address
- ✓ Mark delivery status
- ✓ Collect COD payment
- ✓ Upload proof of delivery
- ✓ Submit delivery complete

### **👤 Consumer (Web/Mobile)**
- ✓ Place orders from farm catalog
- ✓ Choose payment method (COD/Online)
- ✓ Track delivery status in real-time
- ✓ Receive notifications at each stage
- ✓ Rate delivery experience
- ✓ Contact farm via support ticket

---

## 💰 PAYMENT FLOW (COD vs Online)

### **Cash on Delivery (COD):**
```
Order placed → Delivery assigned → Driver collects cash → 
Income recorded for farm owner → Status: completed
```

**In Logistics Portal:**
- COD Pending stat shows: ₱X,XXX total pending collection
- Logistics can see which orders need cash collection

### **Online Payment (GCash/PayMaya):**
```
Order placed → PayMongo checkout URL generated → 
Consumer pays online → Payment webhook received → 
Order auto-confirmed → Delivery process begins
```

**Mobile API:**
```php
POST /api/mobile/orders
Response includes: "checkout_url" → Consumer redirected to PayMongo
```

---

## 📡 DATA MODELS & RELATIONSHIPS

### **Order Model:**
```php
Order
├─ consumer_id → User
├─ farm_owner_id → FarmOwner
├─ payment_method: cod|gcash|paymaya
├─ payment_status: pending|paid|refunded
├─ delivery_type: scheduled|express
├─ status: pending|confirmed|processing|delivered|cancelled
└─ delivery() → HasOne Delivery
```

### **Delivery Model:**
```php
Delivery (NEW)
├─ farm_owner_id → FarmOwner
├─ order_id → Order
├─ driver_id → Driver
├─ assigned_by → User
├─ tracking_number: TRK-2026-XXXXXX (unique)
├─ status: preparing|packed|assigned|out_for_delivery|delivered|completed|failed
├─ scheduled_date: date
├─ dispatched_at: timestamp
├─ delivered_at: timestamp
├─ cod_amount: decimal (if COD)
├─ cod_collected: boolean
├─ proof_of_delivery_url: storage path
└─ rating: 1-5 stars
```

### **Driver Model:**
```php
Driver
├─ farm_owner_id → FarmOwner
├─ name, phone, email
├─ vehicle_type: motorcycle|tricycle|van|truck
├─ vehicle_plate: string
├─ license_number, license_expiry
├─ status: available|on_delivery|off_duty|inactive
├─ average_rating: calculated from deliveries
└─ deliveries() → HasMany Delivery
```

---

## 🔄 REAL-TIME TRACKING

### **Consumer Views (Web/Mobile):**
```
Order Status Timeline:
┌─────────┬─────────┬──────────┬─────────┬───────────┬──────────────────┐
│ Pending │Confirmed│Processing│Dispatched│In Transit│ Delivered & Rated│
│   ⏳    │    ✓    │    📦    │   🚚    │    🗺️    │       ⭐⭐⭐⭐⭐  │
└─────────┴─────────┴──────────┴─────────┴───────────┴──────────────────┘

[Tracking Number: TRK-2026-ABC123]
Driver: John Doe (⭐ 4.8)
Arriving: Today 2-4 PM
```

### **API Endpoint (Mobile):**
```php
GET /api/mobile/orders
Response includes delivery status & tracking number for each order
```

---

## 🔐 ROLE-BASED ACCESS CONTROL

| Feature | Farm Owner | Logistics | Driver | Consumer |
|---------|-----------|-----------|--------|----------|
| View Deliveries | ✓ | ✓ | ✗ | ✓ (own) |
| Assign Driver | ✓ | ✓ | ✗ | ✗ |
| Manage Drivers | ✓ | ✓ | ✗ | ✗ |
| Update Status | ✗ | Limited | ✓ | ✗ |
| View COD | ✓ | ✓ | ✗ | ✗ |
| Rate Delivery | ✗ | ✗ | ✗ | ✓ |

---

## 📊 CACHING & PERFORMANCE

**Logistics portal uses caching for:**
- Delivery stats (120 second cache)
- Driver availability checks
- COD collection totals
- Daily delivery summaries

```php
// Cache Key: farm_{farmOwnerId}_delivery_stats
Cache::remember(key, 120 seconds, function() {
    return [
        'pending' => count of pending,
        'dispatched' => count of dispatched,
        'delivered_today' => count delivered today,
        'cod_pending' => sum of uncollected COD
    ];
});

// Cache cleared when:
$this->clearStatsCache($farmOwner->id);
// - New delivery created
// - Status updated
// - Driver assigned
```

---

## 🚀 ROUTES SUMMARY

### **Logistics Portal Routes** (NEW):
```
/department/logistics
├─ GET  /department/logistics                      (dashboard)
├─ GET  /department/logistics/drivers              (index all drivers)
├─ GET  /department/logistics/drivers/create       (new driver form)
├─ POST /department/logistics/drivers              (store new driver)
├─ GET  /department/logistics/drivers/{driver}     (view driver)
├─ GET  /department/logistics/drivers/{driver}/edit (edit form)
├─ PUT  /department/logistics/drivers/{driver}     (update driver)
├─ GET  /department/logistics/deliveries           (index all deliveries)
├─ GET  /department/logistics/deliveries/create    (new delivery form)
├─ POST /department/logistics/deliveries           (store new delivery)
├─ GET  /department/logistics/deliveries/{delivery}(view delivery)
├─ GET  /department/logistics/deliveries/{delivery}/edit (edit form)
├─ PUT  /department/logistics/deliveries/{delivery}(update delivery)
├─ POST /department/logistics/deliveries/{delivery}/assign-driver
├─ POST /department/logistics/deliveries/{delivery}/mark-packed
├─ POST /department/logistics/deliveries/{delivery}/dispatch
├─ POST /department/logistics/deliveries/{delivery}/mark-delivered
├─ POST /department/logistics/deliveries/{delivery}/mark-completed
├─ GET  /department/logistics/delivery-schedule    (today/tomorrow/unscheduled)
```

### **Mobile API Routes:**
```
/api/mobile
├─ POST /auth/login
├─ POST /auth/logout
├─ GET  /products
├─ GET  /profile
├─ PATCH /profile
├─ GET  /orders                     ← Consumer sees tracking here
├─ POST /orders                     ← Places order
├─ POST /orders/{order}/cancel
├─ POST /orders/{order}/retry-payment
├─ GET  /notifications
├─ POST /complaints
├─ GET  /ratings
├─ POST /ratings/{delivery}         ← Consumer rates driver
```

---

## 🎬 COMPLETE EXAMPLE FLOW

```
DAY 1 - MORNING:
└─ Consumer opens Mobile App
   └─ Browses Farm A's eggs
      └─ Adds 10 trays to cart
         └─ Checkout: COD payment
            └─ POST /api/mobile/orders
               └─ Order created: ORD-2026-001 (pending)
                  └─ Notification to Farm Owner

DAY 1 - AFTERNOON:
└─ Farm Owner opens Farm Portal /farm-owner/orders
   └─ Sees pending order ORD-2026-001
      └─ Clicks "Confirm Order"
         └─ Order status: pending → confirmed
            └─ Notification to Consumer: "Your order confirmed!"

DAY 1 - EVENING:
└─ Farm Owner goes to /farm-owner/deliveries/create
   └─ Creates delivery for ORD-2026-001
      └─ Selects recipient, address, date (tomorrow 2-4 PM)
         └─ Delivery created with status: "preparing"
            └─ Tracking number: TRK-2026-ABC123
               └─ Notification to Consumer

DAY 2 - MORNING:
└─ Logistics Staff opens /department/logistics/deliveries
   └─ Views "Today's Deliveries" (4 unassigned)
      └─ Clicks "Assign Driver" for TRK-2026-ABC123
         └─ Selects "John Doe" (Tricycle, available)
            └─ Delivery status: assigned
               └─ John receives notification on mobile

DAY 2 - 1:30 PM:
└─ John (Driver) opens Mobile App
   └─ Sees: "Delivery assigned for John (egg order)"
      └─ Views: Recipient address, contact, COD: ₱500
         └─ Clicks "Start Delivery"
            └─ Status: out_for_delivery
               └─ Consumer gets notification: "Driver is on the way!"

DAY 2 - 2:45 PM:
└─ John arrives at delivery address
   └─ Knocks, customer opens door
      └─ John collects ₱500 cash
         └─ Takes photo proof
            └─ Clicks "Mark Delivered"
               └─ Uploads proof of delivery
                  └─ Status: completed
                     └─ Income recorded: ₱500 for Farm Owner

DAY 2 - 3:00 PM:
└─ Consumer gets notification: "Your order delivered!"
   └─ Opens app, sees delivery completed
      └─ Clicks "Rate Your Delivery"
         └─ Gives 5⭐ stars, writes: "Fast and friendly!"
            └─ Rating saved, John's average updated to 4.9⭐
               └─ Farm Owner sees: "Delivery ORD-2026-001 rated 5⭐"
```

---

end of flow explanation! Your logistics system now connects:
✅ Consumer ordering (web/mobile)
✅ Farm owner confirmation
✅ Delivery creation & scheduling
✅ Driver assignment & execution
✅ Real-time status tracking
✅ COD collection & income recording
✅ Consumer ratings & feedback
