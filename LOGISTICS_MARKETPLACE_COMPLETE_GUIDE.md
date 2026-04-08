# 🚚 LOGISTICS, DRIVERS & CONSUMER MARKETPLACE - COMPLETE FLOW GUIDE

**Last Updated**: April 4, 2026  
**System**: Poultry Farm Management B2B2C Platform

---

## 📋 TABLE OF CONTENTS

1. [System Overview](#system-overview)
2. [Key Players & Roles](#key-players--roles)
3. [Database Relationships](#database-relationships)
4. [Order Lifecycle](#order-lifecycle)
5. [Logistics Portal & Driver Management](#logistics-portal--driver-management)
6. [Web Marketplace Flow](#web-marketplace-flow)
7. [Mobile App Flow](#mobile-app-flow)
8. [API Endpoints](#api-endpoints)
9. [Real-World Example Scenarios](#real-world-example-scenarios)
10. [Payment Flows](#payment-flows)
11. [Decision Trees](#decision-trees)

---

## 🎯 SYSTEM OVERVIEW

```
┌─────────────────────────────────────────────────────────────────┐
│                    POULTRY MARKETPLACE SYSTEM                    │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  [CONSUMERS]              [FARM OWNERS]         [LOGISTICS TEAM]  │
│  .register                .register              .manage drivers   │
│  .browse/buy              .sell products         .schedule delivery│
│  .track delivery          .confirm orders        .assign drivers   │
│  .rate & review           .fulfill orders        .track shipments  │
│                           .manage deliveries      .update status    │
│                                                                    │
│           ↓                    ↓                      ↓            │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │          SHARED API & DATABASE LAYER                     │   │
│  │  (Orders, Deliveries, Drivers, Products, Payments)      │   │
│  └──────────────────────────────────────────────────────────┘   │
│           ↑                    ↑                      ↑            │
│                                                                    │
│  [WEB MARKETPLACE]      [FARM OWNER PORTAL]  [LOGISTICS PORTAL]  │
│   (Consumer view)        (Business view)      (Delivery view)     │
│   - Browse products      - Dashboard          - Drivers          │
│   - Add to cart          - Show orders        - Deliveries       │
│   - Checkout             - Confirm orders     - Schedule         │
│   - Track orders         - Manage delivery    - Assign           │
│   - Rate farms                                                    │
│                                                                    │
│  [MOBILE APP]                                                     │
│   (Consumer view)                                                 │
│   - Browse products                                               │
│   - Place order                                                   │
│   - Track delivery                                                │
│   - Pay online/COD                                                │
│   - Rate delivery                                                 │
│                                                                    │
└─────────────────────────────────────────────────────────────────┘
```

---

## 👥 KEY PLAYERS & ROLES

### 1. **CONSUMER** (Shopper)
**Who**: End users buying poultry products  
**Platforms**: Web Marketplace + Mobile App  
**Responsibilities**:
- Register and create account
- Browse products from multiple farms
- Add items to cart
- Place orders (COD or online payment)
- Pay for orders (PayMongo integration)
- Track delivery status in real-time
- Receive delivery from driver
- Rate farm and driver
- Track order history

**Endpoints Access**:
- POST `/consumer/register` - Create account
- POST `/consumer/verify` - Verify OTP
- GET `/api/mobile/marketplace/orders` - View orders
- POST `/api/mobile/marketplace/place-order` - Create order
- GET `/api/mobile/marketplace/deliveries` - Track deliveries
- POST `/api/mobile/marketplace/rate-delivery` - Rate order

---

### 2. **FARM OWNER** (Seller)
**Who**: Agricultural businesses selling products  
**Portal**: Orange-themed Farm Owner Portal  
**Responsibilities**:
- Register farm business
- Add products to catalog
- Set pricing and inventory
- Confirm/reject incoming orders
- Create deliveries from confirmed orders
- Assign drivers to deliveries
- Manage delivery schedule
- Track COD collections
- Monitor farm ratings

**Portal Access**:
- `/farm-owner/dashboard` - Business dashboard
- `/farm-owner/orders` - View and confirm orders
- `/farm-owner/deliveries/create` - Schedule new delivery
- `/farm-owner/deliveries` - View all deliveries
- `/farm-owner/drivers` - Manage drivers
- `/farm-owner/products` - Manage catalog

**Key Fields Visible**:
- Order status (pending → confirmed → fulfilled → delivered)
- Payment status (unpaid/COD → paid)
- Delivery tracking
- Driver assignment
- COD collection tracking

---

### 3. **LOGISTICS STAFF** (Fleet Manager)
**Who**: Team managing deliveries and drivers  
**Portal**: Purple-themed Logistics Portal  
**Responsibilities**:
- Manage driver roster
- Click "available" drivers into delivery queue
- Track real-time delivery status
- Monitor driver performance
- Schedule today's/tomorrow's trips
- Reassign drivers if needed
- Manage unscheduled pending deliveries
- Track delivery success rates

**Portal Access**:
- `/department/logistics` - Dashboard
- `/department/logistics/drivers` - Driver database
- `/department/logistics/drivers/create` - Onboard driver
- `/department/logistics/deliveries` - All deliveries
- `/department/logistics/deliveries/create` - Manual delivery entry
- `/department/logistics/delivery-schedule` - Today/Tomorrow/Unscheduled

**Visible Data**:
- Driver name, phone, vehicle type
- Delivery status (pending → assigned → dispatched → delivered)
- Recipient address and phone
- Scheduled dates/times
- COD amounts

---

### 4. **DRIVER** (Delivery Personnel)
**Who**: Delivery personnel executing orders  
**Interface**: Mobile app (or SMS updates)  
**Responsibilities**:
- Receive delivery assignments
- View delivery address & recipient info
- Navigate to delivery location
- Collect payment (if COD)
- Get recipient signature/photo
- Update delivery status
- Return to hub

**Driver Data Tracked**:
- Total deliveries completed
- Successful delivery rate
- Customer ratings average
- Performance metrics
- Current status (available/on-delivery/unavailable)

---

## 🗄️ DATABASE RELATIONSHIPS

```sql
-- Core Flow:
Consumer → Order ← Farm Owner
             ↓
          Delivery ← Driver
             ↓
          Rating

-- Database Tables:
users (id, email, phone, role: consumer|farm_owner|logistics)
│
├─── farm_owners (user_id, farm_name, permit_status)
│
├─── products (farm_owner_id, name, price, category)
│
├─── orders
│    ├─── consumer_id (FK → users.id)
│    ├─── farm_owner_id (FK → farm_owners.id)
│    ├─── status: pending|confirmed|preparing|packed|assigned|dispatched|delivered|completed
│    ├─── payment_status: unpaid|partial|paid|refunded
│    ├─── payment_method: cod|gcash|paymaya
│    └─── total_amount, delivery_address
│
├─── order_items (order_id, product_id, quantity, unit_price)
│
├─── deliveries
│    ├─── order_id (FK → orders.id)
│    ├─── driver_id (FK → drivers.id, nullable until assigned)
│    ├─── farm_owner_id (FK → farm_owners.id)
│    ├─── status: pending|assigned|dispatched|delivered|completed|failed
│    ├─── tracking_number (unique)
│    ├─── recipient_name, recipient_phone
│    ├─── delivery_address, city, province, postal_code
│    ├─── scheduled_date, scheduled_time_from, scheduled_time_to
│    ├─── cod_amount, cod_collected
│    ├─── rating (1-5, consumer rates delivery)
│    └─── feedback (consumer review)
│
└─── drivers (name, phone, vehicle_type, status: available|on_delivery|unavailable)
```

**Key Relationships**:

1. **Consumer → Order**: One-to-Many (1 consumer places many orders)
2. **Farm Owner → Order**: One-to-Many (1 farm sells to many orders)
3. **Order → Delivery**: One-to-One (1 order has 1 delivery)
4. **Delivery → Driver**: Many-to-One (many deliveries by 1 driver)
5. **Order Items**: Many-to-Many through OrderItem table

---

## 📦 ORDER LIFECYCLE

### Step-by-Step Timeline:

```
STEP 1: CONSUMER PLACES ORDER
────────────────────────────────────────────────────────────────
Timeline: T+0 minutes
Participant: Consumer (Web/Mobile)

Action:
- Consumer browses products from Farm A
- Adds 10 bags to cart
- Enters delivery address
- Selects payment method (COD or online)
- Clicks "Place Order"

Database Changes:
✓ Order created with status = 'pending'
✓ Order status updated to 'pending'
✓ Payment status = 'unpaid' (if COD) or 'paid' (if online)
✓ OrderItems created linking products to order

Consumer Sees:
- "Order placed! Order #ORD-123xyz"
- Redirects to tracking page


STEP 2: FARM OWNER RECEIVES & CONFIRMS ORDER
────────────────────────────────────────────────────────────────
Timeline: T+5 minutes
Participant: Farm Owner (Portal)

Location: Farm Owner Portal → Orders → Pending Orders

Action:
- Farm owner opens portal at `/farm-owner/orders`
- Sees pending order from consumer
- Reviews items, quantities, delivery address
- Clicks "Confirm Order" (or "Reject")

Database Changes:
✓ Order status = 'confirmed'
✓ Notification sent to logistics team
✓ Cache cleared for real-time updates

Farm Owner Sees:
- Order moves from "Pending" to "Confirmed" section
- Can now create delivery


STEP 3: FARM OWNER CREATES DELIVERY
────────────────────────────────────────────────────────────────
Timeline: T+10 minutes
Participant: Farm Owner (Portal)

Location: Farm Owner Portal → Deliveries → Create New

Action:
- Farm owner clicks "Schedule Delivery"
- Selects the order to fulfill
- Enters/confirms delivery details:
  - Recipient name & phone (auto-filled from order)
  - Delivery address (auto-filled from order)
  - Scheduled date & time window
  - Special instructions (e.g., "Leave at gate")
- Can optionally select available driver
- Clicks "Create Delivery"

Database Changes:
✓ Delivery record created with status = 'pending'
✓ Tracking number generated (e.g., "TRK-5F9E3C2A")
✓ Driver null (not yet assigned)
✓ Order status = 'confirmed' (waiting for dispatch)

Farm Owner Sees:
- Delivery card showing tracking number
- "Driver: Not yet assigned"
- Delivery appears in list


STEP 4: LOGISTICS STAFF ASSIGNS DRIVER
────────────────────────────────────────────────────────────────
Timeline: T+15 minutes
Participant: Logistics Staff (Logistics Portal)

Location: Logistics Portal → Deliveries → Show Delivery

Action:
- Logistics staff views dashboard
- Sees "1 unassigned delivery pending"
- Clicks into delivery details
- Views available drivers
- Selects driver (e.g., "Fernando - Available")
- Clicks "Assign Driver"

Database Changes:
✓ Delivery driver_id = 3 (Fernando's ID)
✓ Delivery status = 'assigned'
✓ Driver status = 'on_delivery'
✓ Order status = 'assigned'
✓ Notification sent to driver: "New delivery assigned!"

Logistics Sees:
- Delivery now shows "Driver: Fernando"
- Status is "Assigned"
- Driver removed from "Available" list

Consumer Sees:
- Order status updates: "Driver Assigned ✓"
- Driver name appears (optional)
- Estimated delivery time window


STEP 5: DRIVER DEPARTS & DISPATCHES
────────────────────────────────────────────────────────────────
Timeline: T+45 minutes
Participant: Driver (Mobile/Manual)

Action:
- Driver receives notification or SMS
- Picks up order from farm hub
- Inputs order details into mobile app
- Clicks "Out for Delivery"

Database Changes:
✓ Delivery status = 'dispatched'
✓ Delivery dispatched_at = now()
✓ Order status = 'out_for_delivery'
✓ Driver's current delivery marked as active

Logistics Sees:
- Delivery status: "Dispatched 14:32 ✓"
- Timeline updated

Consumer Sees:
- Order status: "Out for Delivery 🚗"
- SMS notification: "Your order is on the way!"
- Estimated arrival in 1-2 hours


STEP 6: DRIVER ARRIVES & DELIVERS
────────────────────────────────────────────────────────────────
Timeline: T+90 minutes
Participant: Driver (Mobile)

Action:
- Driver navigates to delivery address
- Finds recipient
- If COD: Collects ₱2,100 payment
- Asks for signature or photo confirmation
- Inputs into app:
  - COD amount collected: ₱2,100
  - Photos of delivery
  - Recipient signature
- Clicks "Mark Delivered"

Database Changes:
✓ Delivery status = 'delivered'
✓ Delivery delivered_at = now()
✓ COD collected = true
✓ COD_amount = 2100
✓ Order status = 'delivered'
✓ Driver status = 'available' (ready for next delivery)
✓ Notification: "Your order delivered!"

Driver Sees:
- Delivery marked complete ✓

Logistics Sees:
- Delivery status: "Delivered 15:37 ✓"
- COD collected: ₱2,100 ✓
- Driver back to available pool

Consumer Sees:
- Order status: "Delivered ✓"
- Timestamp: Delivered at 15:37
- SMS: "Your order delivered! Rate your experience."


STEP 7: CONSUMER RATES & COMPLETES
────────────────────────────────────────────────────────────────
Timeline: T+120 minutes
Participant: Consumer (Web/Mobile)

Location: Mobile App → Orders → Delivered Orders

Action:
- Consumer sees delivered order
- Clicks "Rate This Delivery"
- Selects rating (1-5 stars)
- Adds optional feedback
- Clicks "Submit Rating"

Database Changes:
✓ Delivery rating = 5 (example)
✓ Delivery feedback = "Fast delivery, good driver"
✓ Order status = 'completed'
✓ Driver average_rating updated
✓ Farm owner average_rating updated

Consumer Sees:
- Rating submitted: "Thank you! ✓"
- Order marked "Completed"

Driver Sees:
- Rating added to profile: 5★

Farm Owner Sees:
- Rating appears on delivery
- Average rating updated (now 4.8★ from many orders)


┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃  COMPLETE LIFECYCLE SUMMARY                              ┃
┣━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┫
┃ Step 1: Consumer orders         → Order: pending         ┃
┃ Step 2: Farm owner confirms     → Order: confirmed       ┃
┃ Step 3: Farm owner delivers     → Delivery: pending      ┃
┃ Step 4: Logistics assigns       → Delivery: assigned     ┃
┃ Step 5: Driver dispatches       → Delivery: dispatched   ┃
┃ Step 6: Driver delivers         → Delivery: delivered    ┃
┃ Step 7: Consumer rates          → Order: completed       ┃
┃                                                            ┃
┃ Total Time: ~2-3 hours          COD: ₱2,100 collected    ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛
```

---

## 🚛 LOGISTICS PORTAL & DRIVER MANAGEMENT

### Logistics Portal Structure

**URL**: `http://localhost:8000/department/logistics`  
**Theme**: Purple & Teal  
**Access**: `role:logistics` middleware

#### Pages Available:

1. **Dashboard** (`/department/logistics`)
   - Quick stats: Available drivers, pending deliveries, today's schedule
   - Recent activity feed
   - Alerts (overdue deliveries, unavailable drivers)

2. **Drivers** (`/department/logistics/drivers`)
   - List of all drivers
   - Columns: Name, Phone, Vehicle Type, Status, Total Deliveries, Rating
   - Search & filter by status
   - Quick actions: View details, call, message

3. **Create Driver** (`/department/logistics/drivers/create`)
   ```
   Form Fields:
   - Full Name (required)
   - Phone Number (required, unique)
   - Vehicle Type (Motorcycle, Van, Truck)
   - License Number (required, unique)
   - License Expiry Date
   - Address
   - Emergency Contact
   ```

4. **Deliveries** (`/department/logistics/deliveries`)
   - List of all deliveries
   - Columns: Tracking #, Recipient, Address, Status, Driver, Schedule Date
   - Filter by status: pending, assigned, dispatched, delivered, failed
   - Search by tracking number or recipient

5. **Create Manual Delivery** (`/department/logistics/deliveries/create`)
   ```
   For urgent/special orders:
   - Select Order (or manual entry)
   - Recipient Name & Phone
   - Delivery Address
   - Scheduled Date & Time Window
   - Special Instructions
   - COD Amount
   - Select Available Driver
   ```

6. **Delivery Schedule** (`/department/logistics/delivery-schedule`)
   ```
   Three Views:
   
   Today's Deliveries (tab)
   ├─ New deliveries scheduled for today
   ├─ Shows time window
   ├─ Driver assigned
   └─ Current status
   
   Tomorrow's Deliveries (tab)
   ├─ Scheduled for tomorrow
   ├─ Plan ahead view
   └─ Tentative assignments
   
   Unscheduled / Pending (tab)
   ├─ No date assigned yet
   ├─ Need to be scheduled
   ├─ High priority
   └─ Needs driver assignment
   ```

---

## 🌐 WEB MARKETPLACE FLOW

### Consumer Web Journey

```
STAGE 1: DISCOVERY & BROWSING
────────────────────────────────
URL: http://localhost:8000/marketplace

1. Consumer lands on marketplace
   - View featured farms
   - Browse product categories
   - Search for specific products

2. Select Farm (e.g., "Green Valley Poultry")
   - View farm profile
   - See farm rating (e.g., 4.8★)
   - Browse their products

3. View Product Details
   - Product name: "Free-Range Eggs A Grade"
   - Price: ₱8.99/dozen
   - Stock available: 50 units
   - Minimum order: 1 dozen
   - Description & farm info

4. Add to Cart
   - Select quantity: 10 dozens
   - Add notes: (optional)
   - Item added: ₱89.90

────────────────────────────────
STAGE 2: CHECKOUT
────────────────────────────────

5. View Cart
   - Subtotal: ₱89.90
   - Shipping: ₱100 (delivery)
   - Tax (VAT): ₱22.78
   - Total: ₱212.68

6. Enter Delivery Details
   - Delivery Address (required)
   - City, Province, Postal Code
   - Contact Phone
   - Special Instructions

7. Select Payment Method
   ┌─ Cash on Delivery (COD)
   │  - Pay when order arrives
   │  - No online fees
   │  - Driver collects payment
   │
   └─ Online Payment (PayMongo)
      - GCash: Scan QR, enter PIN
      - PayMaya: Enter card details
      - Instant confirmation

8. Review Order
   - Products: 10 × Eggs @ ₱8.99
   - Delivery address verified
   - Payment method confirmed
   - Terms & conditions accepted

9. Place Order
   - Click "Complete Purchase"
   - Order created with status: 'pending'
   - If online: Redirect to PayMongo checkout
   - If COD: Confirmation page

────────────────────────────────
STAGE 3: ORDER CONFIRMATION
────────────────────────────────

10. Order Confirmation Page
    - Order #ORD-5F9E3C2A
    - Expected delivery: Tomorrow 2-4 PM
    - Tracking link provided
    - Email confirmation sent

11. Track Order Status (Web)
    - Navigate to Orders → Order Details
    - Status progress bar:
      ☐ Pending → ☑ Confirmed → ☐ Packing → ☐ Out for Delivery → ☐ Delivered
    - Real-time updates
    - SMS notifications

12. Receive Delivery
    - Driver arrives with order
    - If COD: Pay ₱212.68
    - If prepaid: Just sign & receive
    - Optionally take photos

13. Rate & Review
    - Order shows "Delivered ✓"
    - Click "Rate This Order"
    - Rate farm (1-5 stars)
    - Leave feedback (optional)
    - Submit

────────────────────────────────
```

---

## 📱 MOBILE APP FLOW

### Consumer Mobile App Journey

```
APP STRUCTURE
═════════════════════════════════════════════════════════════

MAIN SCREENS:

1. Home Tab
   - Featured farms carousel
   - Browse categories
   - Promotional banners
   - Search bar

2. Browse Tab
   - All farms list
   - Filter by rating, distance
   - Search products
   - Sort by price, rating

3. Cart Tab
   - Items in cart count badge
   - Quick add/remove
   - View full cart
   - Proceed to checkout

4. Orders Tab
   - List of all orders
   - Tabs: Active | Delivered | Cancelled
   - Status badge for each order
   - Quick track button

5. Profile Tab
   - User info
   - Delivery addresses
   - Payment methods
   - Ratings history
   - Settings

═════════════════════════════════════════════════════════════

FLOW: BROWSE→ CART → CHECKOUT → TRACK → RATE


STEP 1: BROWSE PRODUCTS & ADD TO CART
──────────────────────────────────────

Screen 1: Home
- See "Green Valley Poultry" farm
- Rating: 4.8★ (342 reviews)
- Tap farm card

Screen 2: Farm Products
- See list of products
- Eggs, Chicken, Duck
- Each shows: price, stock, rating
- Search bar to filter

Screen 3: Product Detail
- Product: "Grade A Eggs"
- Price: ₱8.99/dozen
- In stock: 50 dozen
- Min order: 1 dozen
- View farm rating again
- Select quantity: 10 → Tap "Add to Cart"
- Success: "Added to cart ✓"

Screen 4: Cart After Adding
- Item: 10 × Eggs @ ₱8.99
- Subtotal: ₱89.90
- Option: Continue shopping or checkout


STEP 2: CHECKOUT & PAYMENT
──────────────────────────

Screen 5: Cart Review
- Subtotal: ₱89.90
- Delivery: ₱100
- Tax: ₱22.78
- Total: ₱212.68
- Edit cart / Continue

Screen 6: Delivery Address
- Saved addresses dropdown
  ┌─ Home (3 Mabini St, Makati) [Use This]
  └─ Office (5 Herrera St, Quezon City)
- Or enter new address
- Recipient phone number

Screen 7: Payment Method Selection
- ☑ Cash on Delivery (COD)
  "Pay ₱212.68 when order arrives"
  
- ☐ Online Payment
  - GCash: Tap → QR code display
  - PayMaya: Tap → Enter card

Screen 8: Order Review
- Items: 10 × Eggs @ ₱89.90
- Delivery: Home (3 Mabini St)
- Payment: COD
- Total: ₱212.68
- Terms agreed ☑
- Buttons: [Edit] [Confirm Order]

Screen 9: Order Confirmation
- SUCCESS! ✓
- Order #: ORD-ABC123XYZ
- Expected delivery: Tomorrow 2-4 PM
- Status: Pending confirmation from farm
- Button: [Track Order]


STEP 3: TRACK DELIVERY
──────────────────────

Screen 10: Order Details
Tabs: [Details] [Status] [Track]

Details Tab:
- Order #: ORD-ABC123XYZ
- Farm: Green Valley Poultry
- Items: 10 × Eggs @ ₱89.90
- Delivery: 3 Mabini St, Makati
- Phone: 09123456789
- Payment: COD ₱212.68

Status Tab:
- Progress bar:
  ■■■■■ 100% TIMELINE:
  
  ✓ Pending Confirmation (14:30)
  ✓ Confirmed by Farm (14:45)
  ✓ Packing Order (15:00)
  ⧖ Out for Delivery (15:30) [Current]
  ☐ Delivered (ETA: 16:30)

Track Tab:
- Map view (if GPS available)
- Driver: "Fernando Cruz"
- Rating: 4.9★
- Phone: 09987654321 [Call Driver]
- Vehicle: Silver Toyota Van
- Last update: 5 mins ago
- "Driver is 2km away"


STEP 4: RECEIVE & RATE
──────────────────────

Screen 11: Delivery Complete
- Order status: DELIVERED ✓
- Timestamp: 16:28
- Driver: Fernando Cruz (5★)
- Button: [Rate This Delivery]

Screen 12: Rate Delivery
- Questions:
  □ Overall rating: [★★★★★] (tap to rate)
  □ Delivery quality: Good/Neutral/Poor
  □ Driver helpfulness: Yes/No
  □ Feedback (optional text):
    "It came fresh and on time! ✓"
  
- Button: [Submit Rating]

Screen 13: Rating Submitted
- "Thank you! Your feedback helps farmers improve ✓"
- Option: [Rate the Farm] or [My Ratings]

Screen 14: Order History
- Order #ORD-ABC123XYZ
- Status: COMPLETED ✓
- Total: ₱212.68
- Delivery: 3 Mabini St
- Date: Apr 4, 2026 16:28
- Rating: ★★★★★ "Fresh & on time!"

═════════════════════════════════════════════════════════════

KEY FEATURES IN MOBILE APP:

1. Real-time Order Tracking
   - Status updates every 5 minutes
   - SMS notifications at key milestones

2. Driver Communication
   - Call driver directly
   - In-app messaging (future)
   - Share location pin

3. Multiple Payment Options
   - COD (no fees)
   - GCash (online)
   - PayMaya (online)
   - Full receipt emailed

4. Rating & Reviews
   - Rate delivery experience
   - Rate farm/seller
   - View farm ratings when browsing
   - Track your ratings history

5. Order History
   - All past orders with status
   - Reorder from favorites
   - View delivery addresses
```

---

## 🔌 API ENDPOINTS

### Mobile Marketplace APIs

**Base URL**: `http://localhost:8000/api/mobile`

#### Authentication
```
POST /auth/register
  Body: { name, email, phone, password }
  Response: { token, user }

POST /auth/login  
  Body: { email, password }
  Response: { token, user }

POST /auth/logout
  Headers: Authorization: Bearer {token}
```

#### Products & Marketplace
```
GET /marketplace/farms
  Response: [ { id, name, rating, product_count } ]

GET /marketplace/farms/{id}/products
  Response: [ { id, name, price, category, farm_rating } ]

GET /marketplace/products/search?q=eggs
  Response: [ { id, name, farm_name, price } ]
```

#### Orders
```
GET /marketplace/orders
  Headers: Authorization: Bearer {token}
  Response: [ { id, order_number, status, farm_name, total_amount } ]

GET /marketplace/orders/{id}
  Response: { order details, items, delivery status }

POST /marketplace/place-order
  Headers: Authorization: Bearer {token}
  Body: {
    items: [ { product_id, quantity } ],
    delivery_address, delivery_city, payment_method
  }
  Response: { order created, payment_url (if online) }

POST /marketplace/cancel-order/{id}
  Response: { order cancelled }

POST /marketplace/retry-payment/{id}
  Response: { payment_url }
```

#### Deliveries & Tracking
```
GET /marketplace/deliveries
  Headers: Authorization: Bearer {token}
  Response: [ { id, tracking_number, status, driver_name, scheduled_date } ]

GET /marketplace/deliveries/{id}
  Response: { 
    tracking_number,
    status, 
    recipient_name,
    delivery_address,
    driver: { name, phone, rating },
    map_location (if available)
  }

GET /marketplace/delivery-stages/{id}
  Response: {
    current_stage: "dispatched",
    stages_completed: [
      { name: "pending_confirmation", completed_at },
      { name: "confirmed", completed_at },
      { name: "packing", completed_at }
    ]
  }
```

#### Ratings & Reviews
```
POST /marketplace/rate-delivery/{id}
  Body: { rating, feedback }
  Response: { message, farm_updated_rating }

GET /marketplace/ratings
  Headers: Authorization: Bearer {token}
  Response: [ { order_id, farm_name, rating, feedback } ]
```

#### Payments (PayMongo)
```
POST /marketplace/create-payment
  Body: { order_id, payment_method (gcash|paymaya) }
  Response: { checkout_url, payment_id }

POST /webhooks/paymongo
  (Webhook from PayMongo for payment confirmation)
  Response: { order status updated }
```

---

## 🎬 REAL-WORLD EXAMPLE SCENARIOS

### Scenario 1: Happy Path - Online Payment COD Order

```
CHARACTERS:
- Consumer: Juan Dela Cruz (Juan)
- Farm Owner: Green Valley Poultry (Miguel)
- Logistics: Lily (Logistics Manager)
- Driver: Fernando Cruz (Delivery Driver)

TIMELINE:
═════════════════════════════════════════════════════════════

14:30 - Juan's Order (Mobile App)
─────────────────────────────────
ACTION: Juan browses app, finds "Grade A Eggs ₱8.99"
SYSTEM: Displays product with 4.8★ farm rating
JUAN: "Add 10 dozens to cart"
SYSTEM: Cart total: ₱212.68 (incl. ₱100 delivery)
JUAN: Proceeds to checkout
JUAN: Selects COD payment (pay when arrives)
JUAN: Enters delivery address: "3 Mabini St, Makati"
JUAN: Clicks "Place Order"
SYSTEM DATABASE:
  ✓ Order created: ORD-5F9E3C2A
  ✓ Order.status = 'pending'
  ✓ Order.payment_status = 'unpaid'
  ✓ Consumer notified: "Order placed! Waiting for farm confirmation"

──────────────────────────────────

14:45 - Miguel Confirms Order (Farm Portal)
────────────────────────────────────────────
ACTION: Miguel opens farm owner portal `/farm-owner/orders`
SYSTEM: Shows 1 pending order from Juan
MIGUEL: Reviews order detail:
  - 10 dozens eggs @ ₱8.99 = ₱89.90
  - Delivery: 3 Mabini St, Makati
  - Payment: COD ₱212.68
MIGUEL: "Check! We have stock, let's confirm"
MIGUEL: Clicks "Confirm Order"
SYSTEM DATABASE:
  ✓ Order.status = 'confirmed'
  ✓ Notification: Farm owner → Logistics: "1 new confirmed order"
  ✓ Consumer notified: "Order confirmed by Green Valley!"

──────────────────────────────────

15:00 - Miguel Creates Delivery (Farm Portal)
──────────────────────────────────────────────
ACTION: Miguel goes to `/farm-owner/deliveries/create`
MIGUEL: Selects order ORD-5F9E3C2A
SYSTEM: Auto-fills recipient name (Juan), address, phone
MIGUEL: Selects scheduled date: "Today 16:00-17:30"
MIGUEL: Adds special note: "Leave at front gate"
MIGUEL: Clicks "Create Delivery"
SYSTEM DATABASE:
  ✓ Delivery created (TRK-5F9E3C2A)
  ✓ Delivery.status = 'pending'
  ✓ Delivery.driver_id = NULL (not assigned yet)
  ✓ Order.status = 'confirmed'

──────────────────────────────────

15:15 - Lily Assigns Driver (Logistics Portal)
────────────────────────────────────────────────
ACTION: Lily opens Logistics Portal `/department/logistics`
SYSTEM: Shows dashboard with 1 pending unassigned delivery
LILY: Sees TRK-5F9E3C2A needs driver
LILY: Clicks on delivery to view details
SYSTEM: Shows:
  - Recipient: Juan Dela Cruz
  - Address: 3 Mabini St, Makati
  - Time window: 16:00-17:30
  - Available drivers: Fernando (Van), Ramon (Motorcycle)
LILY: "Fernando has Van, perfect for eggs. Let's assign."
LILY: Selects Fernando → Clicks "Assign Driver"
SYSTEM DATABASE:
  ✓ Delivery.driver_id = 3 (Fernando's ID)
  ✓ Delivery.status = 'assigned'
  ✓ Delivery.assigned_at = NOW
  ✓ Driver(3).status = 'on_delivery'
  ✓ SMS sent to Fernando: "New delivery TRK-5F9E3C2A to 3 Mabini St"
  ✓ Order.status = 'assigned'
  ✓ Consumer notified: "Driver Fernando assigned! ETA 16:30"

──────────────────────────────────

15:45 - Fernando Picks Up & Departs (Mobile/Driver)
────────────────────────────────────────────────────
ACTION: Fernando arrives at farm hub
FERNANDO: Picks up order ORD-5F9E3C2A (10 dozen eggs)
FERNANDO: Scans barcode / enters tracking # TRK-5F9E3C2A
SYSTEM: Shows delivery details:
  - Recipient: Juan Dela Cruz, 09123456789
  - Address: 3 Mabini St, Makati
  - COD: ₱212.68
  - Instructions: "Leave at front gate"
FERNANDO: Clicks "Out for Delivery"
SYSTEM DATABASE:
  ✓ Delivery.status = 'dispatched'
  ✓ Delivery.dispatched_at = NOW (15:45)
  ✓ Order.status = 'out_for_delivery'
  ✓ SMS to Juan: "Your order is out for delivery! Driver: Fernando. ETA: 16:30"
  ✓ Mobile app shows: "Out for Delivery 🚗"

──────────────────────────────────

16:28 - Fernando Delivers & Collects COD (Mobile/Driver)
─────────────────────────────────────────────────────────
ACTION: Fernando navigates using GPS
FERNANDO: Arrives at 3 Mabini St, Makati at 16:28
FERNANDO: Calls Juan: "I'm here with your order!"
JUAN: Comes down, receives order
FERNANDO: "That's ₱212.68 for COD"
JUAN: Pays ₱212.68 in cash
FERNANDO: Updates in app:
  - "Delivery arrived at 16:28"
  - Taps "Collect Payment"
  - Enters: ₱212.68 (cash)
  - Takes photo of delivery (proof)
  - Clicks "Mark Delivered"
SYSTEM DATABASE:
  ✓ Delivery.status = 'delivered'
  ✓ Delivery.delivered_at = NOW (16:28)
  ✓ Delivery.cod_collected = true
  ✓ Delivery.cod_amount_collected = 212.68
  ✓ Order.status = 'delivered'
  ✓ Order.payment_status = 'paid'
  ✓ Driver.status = 'available' (ready for next)
  ✓ SMS to Juan: "Delivered at 16:28! Rate your experience."
  ✓ Mobile app shows: "Delivered ✓"

──────────────────────────────────

16:30 - Juan Rates Delivery (Mobile App)
─────────────────────────────────────────
ACTION: Juan opens app, sees "Delivered ✓"
JUAN: Taps "Rate This Delivery"
SYSTEM: Shows form:
  - Rating: [★★★★★] - Juan taps 5 stars
  - Feedback: "Perfect! Eggs came fresh, driver was polite"
  - Farm: Green Valley (also can rate)
JUAN: Clicks "Submit"
SYSTEM DATABASE:
  ✓ Delivery.rating = 5
  ✓ Delivery.feedback = "Perfect! Eggs came fresh, driver was polite"
  ✓ Order.status = 'completed'
  ✓ Driver average rating updated: 4.8 → 4.85 (from many orders)
  ✓ Farm rating updated: 4.8 (maintained or changed based on all ratings)
  ✓ SMS: "Thank you Juan! Your rating helps farmers."

──────────────────────────────────

FINAL STATE:
════════════════════════════════════════════════════════════
Juan's Side:
  - Order: Completed ✓
  - Payment: Paid ₱212.68 COD
  - Rating: 5★ "Perfect! Fresh eggs, polite driver"

Miguel's (Farm) Side:
  - Order: Completed & paid
  - Revenue: +₱212.68 (minus platform fee)
  - Rating: +5★ from Juan

Fernando's (Driver) Side:
  - Delivery: Completed
  - Payment: ₱212.68 COD → Remitted to hub
  - Rating: +5★ from Juan
  - Status: Back to 'available'

Lily's (Logistics) Side:
  - 1 successful delivery today ✓
  - Driver performance: On time
  - No issues reported

════════════════════════════════════════════════════════════
```

---

## 💳 PAYMENT FLOWS

### Payment Method: COD (Cash on Delivery)

```
Consumer → Order placed with COD
         ↓
Farm Owner → Confirms order
         ↓
Logistics   → Assigns driver
         ↓
Driver → Picks up order
      ↓
      Collects ₱{amount} from consumer
      ↓
      Remits to hub/farm owner
      ↓
Consumer sees "Paid ✓"
```

**Database State at Each Step**:
- Order created: `payment_status = 'unpaid'`
- Driver delivers & collects: `payment_status = 'partial'` (being collected)
- Driver remits: `payment_status = 'paid'`

---

### Payment Method: Online (PayMongo - GCash/PayMaya)

```
Consumer → Selects GCash/PayMaya
         ↓
         Clicks "Confirm & Pay"
         ↓
         → PayMongo checkout opens
         ↓
         Scans QR / Enters card
         ↓
         Payment processed
         ↓
PayMongo → Sends webhook to system
         ↓
         POST /webhooks/paymongo
         ↓
System   → Verifies payment (idempotent check)
         → Updates Order.payment_status = 'paid'
         → Creates Delivery
         → Notifies farm owner
         ↓
Farm Owner sees order confirmed + paid
```

---

## 🔀 DECISION TREES

### Order Confirmation Decision

```
Order Received from Consumer
│
├─ Stock Check?
│  ├─ YES: Can we fulfill?
│  │   ├─ YES → Farm Owner confirms order ✓
│  │   │
│  │   └─ ISSUES (e.g., quality) → Farm Owner rejects
│  │                               → Consumer gets refund (if prepaid)
│  │
│  └─ NO: Out of stock
│       → Farm Owner rejects
│       → Consumer notified
│       → Can reorder different quantity
│
└─ Result:
   Confirmed → Create Delivery
   Rejected  → Order marked failed
```

### Delivery Status Decision

```
Delivery Created
│
├─ Assign Driver?
│  ├─ YES → Delivery.status = 'assigned'
│  │       → Driver notified
│  │       → Can be unassigned if needed
│  │
│  └─ NO DRIVERS AVAILABLE
│       → Delivery.status = 'pending'
│       → Retry assignment later
│
├─ Dispatch
│  └─ Driver marks "Out for Delivery"
│        → Delivery.status = 'dispatched'
│        → Consumer notified of ETA
│
├─ Attempt Delivery
│  ├─ SUCCESS
│  │  ├─ Collect payment (if COD)
│  │  ├─ Get signature
│  │  └─ Mark delivered ✓
│  │     → Delivery.status = 'delivered'
│  │     → Order.status = 'delivered'
│  │     → Consumer can rate
│  │
│  └─ FAILED
│     ├─ Reason? (Address wrong, no one home, etc.)
│     ├─ Mark failed
│     └─ Retry delivery next day? 
│        → Delivery_attempts++
│        → Notify consumer & farm
```

---

## 📊 KEY METRICS & REPORTING

### For Logistics Manager

```
Dashboard Shows:

TODAY'S PERFORMANCE:
- Deliveries completed: 23/25 (92%)
- On-time delivery: 21/25 (84%)
- Failed deliveries: 2 (reasons: address wrong, no one home)
- Average delivery time: 47 minutes
- COD collected: ₱15,342

DRIVER PERFORMANCE:
- Fernando: 8 deliveries, 8 completed, 5.0★ rating
- Ramon: 6 deliveries, 5 completed, 1 failed, 4.8★
- Maria: 9 deliveries, 9 completed, 4.9★

ALERTS:
⚠ 2 drivers absent today
⚠ 1 delivery without driver assigned yet
```

---

## 🎓 SUMMARY

This system creates a complete marketplace where:

1. **Consumers** browse, purchase, and track orders
2. **Farms** receive, confirm, and fulfill orders via delivery
3. **Logistics team** manages drivers and schedules deliveries
4. **Drivers** execute deliveries and collect payments
5. **Everyone** rates and reviews for accountability

All connected through a unified database, web portals, APIs, and mobile app with **real-time status updates** at every stage.

---

**Questions?** Check specific sections above or ask for clarification!
