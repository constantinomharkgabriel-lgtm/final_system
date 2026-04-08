# 🚗 DRIVER PORTAL ARCHITECTURE - SENIOR DESIGN

## 📋 EXECUTIVE SUMMARY

Current Problem:
- ❌ Driver is just linked to employee (no independent portal)
- ❌ Driver doesn't receive direct task assignments
- ❌ No driver-to-consumer communication
- ❌ Consumer doesn't see driver updates in real-time
- ❌ Email field unused for driver authentication

**Solution:**
- ✅ Independent Driver Portal + Auth system
- ✅ Real-time bidirectional communication
- ✅ Driver → Logistics → Consumer flow
- ✅ Payment confirmation from driver
- ✅ Live tracking integration

---

## 🏗️ SYSTEM ARCHITECTURE (V2 - REDESIGNED)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                     POULTRY MARKETPLACE V2                                  │
│                   (WITH DRIVER PORTAL)                                      │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐   ┌────────────┐ │
│  │  CONSUMER    │    │ FARM OWNER   │    │ LOGISTICS    │   │  DRIVER    │ │
│  │              │    │              │    │              │   │            │ │
│  │ WEB + MOBILE │    │ Dashboard    │    │ Manage       │   │ Portal     │ │
│  │              │    │              │    │ Deliveries   │   │            │ │
│  └──────┬───────┘    └───────┬──────┘    └──────┬───────┘   └─────┬──────┘ │
│         │                    │                  │                 │         │
│         │ (OrderUpdates)     │ (Confirm Order)  │ (Assign)       │         │
│         │                    │                  │                 │         │
│         └────────────┬───────┴──────────────────┼─────────────────┘         │
│                      │                         │                           │
│                      ▼                         ▼                           │
│         ┌────────────────────────┐  ┌─────────────────────┐               │
│         │   QUEUE SYSTEM         │  │  DRIVER ASSIGNMENT  │               │
│         │ (Redis/Database)       │  │  (Task Distribution)│               │
│         │                        │  │                     │               │
│         │ • pending_deliveries   │  │ • Send to Driver    │               │
│         │ • assigned_tasks       │  │ • Driver Accepts    │               │
│         │ • completed_tasks      │  │ • Status: "accepted"│               │
│         └────────────┬───────────┘  └────────┬────────────┘               │
│                      │                      │                            │
│                      │ (Real-time Updates)  │                            │
│                      │                      ▼                            │
│                      │   ┌──────────────────────────────┐                │
│                      │   │   DRIVER PORTAL              │                │
│                      │   │                              │                │
│                      │   │ • Assigned deliveries        │                │
│                      │   │ • Navigation/Maps            │                │
│                      │   │ • Delivery proof (photo)     │                │
│                      │   │ • Payment confirmation       │                │
│                      │   │ • Customer signature (opt)   │                │
│                      │   │ • Real-time task updates     │                │
│                      │   └────────────┬─────────────────┘                │
│                      │                │                                  │
│                      │                │ (Task Completion Photos/Proof)   │
│                      │                │ (Payment status update)          │
│                      │                │ (Delivery completion feedback)   │
│                      │                ▼                                  │
│         ┌────────────────────────────────────────────────┐               │
│         │    DELIVERY STATUS HISTORY                     │               │
│         │   (Real-time event log)                        │               │
│         │                                                │               │
│         │  Order Placed                                  │               │
│         │    ↓ (triggers notification)                   │               │
│         │  Preparing                                     │               │
│         │    ↓ (Logistics updates)                       │               │
│         │  Ready for pickup                              │               │
│         │    ↓                                           │               │
│         │  Assigned to Driver                            │               │
│         │    ↓ (Driver assigned via Driver Portal)       │               │
│         │  Driver Accepted                               │               │
│         │    ↓ (Driver Portal)                           │               │
│         │  Out for delivery                              │               │
│         │    ↓ (Logistics updates / Driver confirms)     │               │
│         │  Arrived at location                           │               │
│         │    ↓ (Driver Portal - GPS proof)               │               │
│         │  Handed to customer                            │               │
│         │    ↓ (Driver uploads photo)                    │               │
│         │  Awaiting payment                              │               │
│         │    ↓ (Driver Portal shows payment status)      │               │
│         │  Payment received (driver confirms)            │               │
│         │    ↓                                           │               │
│         │  Delivery completed                            │               │
│         │    ↓ (Both driver & consumer confirm)          │               │
│         │  Customer can rate                             │               │
│         │                                                │               │
│         └────────────────────────────────────────────────┘               │
│                      │                                                    │
│                      ▼                                                    │
│    ┌──────────────────────────────────────┐                             │
│    │  NOTIFICATION ENGINE                 │                             │
│    │                                      │                             │
│    │  • Push notifications (mobile)       │                             │
│    │  • Email notifications               │                             │
│    │  • SMS notifications                 │                             │
│    │  • In-app notifications              │                             │
│    │  • Real-time WebSocket updates       │                             │
│    └──────────────────────────────────────┘                             │
│                      │                                                    │
│  Send to:  Consumer  │  Logistics  │  Driver                            │
│                      ▼             ▼      ▼                             │
│                                                                          │
│    ┌──────────────────────────────────────────────────────────────┐    │
│    │  UNIFIED DATABASE (PostgreSQL)                              │    │
│    │                                                              │    │
│    │  Tables:                                                     │    │
│    │  • users (consumers, farm_owners, logistics, drivers)       │    │
│    │  • employees (farm staff connected to drivers)             │    │
│    │  • drivers (independent driver profiles)                   │    │
│    │  • orders (consumer orders)                                │    │
│    │  • deliveries (driver tasks)                               │    │
│    │  • delivery_history (status updates with timestamps)       │    │
│    │  • delivery_proofs (photos, signatures, receipts)          │    │
│    │  • task_assignments (driver ← logistics)                   │    │
│    │  • notifications (for all users)                           │    │
│    │  • payments (payment records & confirmations)              │    │
│    │  • driver_earnings (commission tracking)                   │    │
│    │                                                              │    │
│    └──────────────────────────────────────────────────────────────┘    │
│                                                                          │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## 👤 USER ROLES (CLEAR SEPARATION)

```
┌────────────────────────────────────────────────────────────────────┐
│                         WHO DOES WHAT                              │
├────────────────────────────────────────────────────────────────────┤
│                                                                    │
│  CONSUMER                                                          │
│  ├─ Registers with email/password                                │
│  ├─ Browses products (farm owner listings)                       │
│  ├─ Places orders                                                │
│  ├─ Pays online                                                  │
│  ├─ Tracks order in real-time (web + mobile)                   │
│  ├─ [NEW] Sees driver name, vehicle, location                  │
│  ├─ [NEW] Receives updates from driver during delivery          │
│  ├─ [NEW] Confirms payment received status                      │
│  └─ Rates order & driver                                        │
│                                                                    │
│  FARM OWNER                                                       │
│  ├─ Manages products                                             │
│  ├─ Confirms new orders                                          │
│  ├─ Marks order as "ready_for_pickup"                           │
│  ├─ Views order status                                          │
│  ├─ Payroll & employee management                               │
│  └─ Commission tracking                                          │
│                                                                    │
│  LOGISTICS STAFF                                                  │
│  ├─ Views confirmed orders ready for delivery                   │
│  ├─ [CHANGED] Creates delivery task (NOT assigned yet)         │
│  ├─ Updates: "preparing" → "ready_for_pickup" → "packed"      │
│  ├─ [NEW] Assigns driver from available drivers                │
│  │        (via Driver Portal automatic notification)            │
│  ├─ Marks as "out_for_delivery" when driver accepts           │
│  ├─ Receives completion proof from driver                      │
│  └─ Confirms final delivery status                             │
│                                                                    │
│  DRIVER (NEW ROLE)                     ◄─── INDEPENDENT LOGIN     │
│  ├─ Registers in system with email                              │
│  ├─ [NEW] Has independent Driver Portal                         │
│  ├─ [NEW] Views assigned deliveries (notifications)            │
│  ├─ [NEW] Accepts/Rejects job assignments                      │
│  ├─ [NEW] Navigates to delivery locations (maps)              │
│  ├─ [NEW] Uploads delivery proof photos                        │
│  ├─ [NEW] Confirms delivery handoff                            │
│  ├─ [NEW] Marks payment status (received/pending)              │
│  ├─ [NEW] Receives real-time feedback from consumer           │
│  ├─ [NEW] Views earnings/commission                            │
│  └─ [NEW] Communicates delivery issues to logistics           │
│                                                                    │
└────────────────────────────────────────────────────────────────────┘
```

---

## 🎯 DRIVER PORTAL - FEATURES & STRUCTURE

### **Main Dashboard**

```
┌─────────────────────────────────────────────────────────┐
│  DRIVER PORTAL - MAIN DASHBOARD                         │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  👤 Driver: John Dela Cruz                             │
│  ⭐ Rating: 4.8 / 5.0                                  │
│  💰 Today's Earnings: ₱450                             │
│  📊 This Month: ₱12,450                                │
│                                                         │
│  ┌──────────────────┐  ┌──────────────────┐            │
│  │ 📍 MY TASKS      │  │ 📋 HISTORY       │            │
│  │ (Today)          │  │                  │            │
│  │                  │  │ View completed   │            │
│  │ • 3 Active       │  │ deliveries       │            │
│  │ • 2 Pending      │  │                  │            │
│  │ • 1 Completed    │  │ Filter by date   │            │
│  └──────────────────┘  └──────────────────┘            │
│                                                         │
│  ┌──────────────────┐  ┌──────────────────┐            │
│  │ 💵 PAYMENTS      │  │ ⚙️ SETTINGS      │            │
│  │                  │  │                  │            │
│  │ Transactions:    │  │ • Edit profile   │            │
│  │ ₱450 / ₱500      │  │ • Change vehicle │            │
│  │ pending / paid   │  │ • Bank details   │            │
│  │                  │  │ • Availability   │            │
│  └──────────────────┘  └──────────────────┘            │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

### **Active Tasks/Deliveries Section**

```
┌────────────────────────────────────────────────────────────────┐
│  MY ACTIVE TASKS                                               │
├────────────────────────────────────────────────────────────────┤
│                                                                 │
│  TASK #1: ORDER-DEL-00542  [ENROUTE]  ⏱️ 15 min ETA           │
│  ├─ Customer: Maria Santos, Makati                             │
│  ├─ Phone: 09XX-XXX-XXXX                                       │
│  ├─ Address: 123 Roosevelt Ave, Makati                         │
│  ├─ Items: 5kg Chicken Quarters × 2                            │
│  ├─ Total Amount: ₱550                                         │
│  ├─ Payment Status: [Pending payment]                          │
│  ├─ Customer Contact: [  CALL  ] [ MESSAGE ]                  │
│  ├─ [📍 NAVIGATE] [📸 PHOTO] [✓ COMPLETE]                    │
│  └─ Assigned: 11:30 AM | Accepted: 11:35 AM                   │
│                                                                 │
│  ─────────────────────────────────────────────────────────────  │
│                                                                 │
│  TASK #2: ORDER-DEL-00541  [GATHERING INFO]  ⏱️ Pending       │
│  ├─ Customer: Ramon Cruz, Antipolo                             │
│  ├─ Phone: 09XX-XXX-XXXX                                       │
│  ├─ Address: 456 Marcos Highway, Antipolo                      │
│  ├─ Items: 10kg Whole Chicken × 1                              │
│  ├─ Total Amount: ₱1,200                                       │
│  ├─ Payment Status: [Cash on Delivery]                         │
│  ├─ Updated by Logistics: "Customer not home yet"              │
│  ├─ [ RESCHEDULE ] [ CALL ] [ CANCEL ]                         │
│  └─ Assigned: 10:45 AM (Not yet accepted)                     │
│                                                                 │
│  ─────────────────────────────────────────────────────────────  │
│                                                                 │
│  TASK #3: ORDER-DEL-00540  [AWAITING PAYMENT]  ⏱️ 25 min      │
│  ├─ Customer: Lucia Fernandez, Quezon City                     │
│  ├─ Phone: 09XX-XXX-XXXX                                       │
│  ├─ Address: 789 Commonwealth Ave, Quezon City                 │
│  ├─ Items: 3kg Chicken Wings × 3                               │
│  ├─ Total Amount: ₱750                                         │
│  ├─ Payment Status: [PAYMENT PENDING - WAITING FOR CUSTOMER]   │
│  ├─ [💵 MARK PAID] [🕐 WAITING] [❌ ISSUE]                    │
│  └─ Arrived: 11:50 AM | Status: Handed over to customer       │
│                                                                 │
└────────────────────────────────────────────────────────────────┘
```

### **Task Detail Page**

```
┌────────────────────────────────────────────────────────────────┐
│  DELIVERY DETAIL - ORDER-DEL-00542                             │
├────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─ CURRENT STATUS ──────────────────────────────────────────┐ │
│  │                                                             │ │
│  │  ORDER PLACED    PREPARING    PACKED    OUT FOR DELIVERY   │ │
│  │       ✓             ✓          ✓            🔵 (current)   │ │
│  │     11:00         11:15      11:30          11:35          │ │
│  │                                                             │ │
│  └─────────────────────────────────────────────────────────────┘ │
│                                                                 │
│  📦 ORDER ITEMS                                                 │
│  ├─ Chicken Quarters (5kg) × 2 = ₱550                         │
│  ├─ Special Instructions: Handle with care, cold package      │
│  └─ Total: ₱550 + ₱50 Delivery Fee ($600 for driver)         │
│                                                                 │
│  👤 CUSTOMER DETAILS                                            │
│  ├─ Name: Maria Santos                                        │
│  ├─ Phone: 09XX-XXX-XXXX [ CALL ] [ SMS ]                     │
│  ├─ Email: maria@email.com                                    │
│  ├─ Address: 123 Roosevelt Ave, Makati                        │
│  └─ GPS: 14.5623°N, 121.0148°E                                │
│                                                                 │
│  🗺️ DELIVERY ROUTE                                            │
│  ├─ Current Location: Makati Avenue (5.2 km away)              │
│  ├─ Distance: 5.2 km | Est. Time: 15 minutes                 │
│  ├─ [ NAVIGATE WITH MAPS ]                                    │
│  └─ Map Preview: [Shows current route]                        │
│                                                                 │
│  💵 PAYMENT STATUS                                             │
│  ├─ Amount: ₱550                                              │
│  ├─ Type: Cash on Delivery                                    │
│  ├─ Status: [AWAITING PAYMENT FROM CUSTOMER]                 │
│  ├─ Driver Commission: ₱50 (automatically added)              │
│  ├─ Instructions:                                             │
│  │  "Collect cash from customer before handing item"         │
│  └─ [ MARK PAID ] [ ISSUE ]                                  │
│                                                                 │
│  📸 DELIVERY PROOF                                             │
│  ├─ [ TAKE PHOTO ] (at customer location)                    │
│  ├─ [ TAKE SIGNATURE ] (optional)                             │
│  ├─ [ UPLOAD RECEIPT ] (if applicable)                        │
│  └─ Uploaded: None yet                                        │
│                                                                 │
│  ⏱️ TIMELINE                                                   │
│  ├─ Assigned: 11:30 AM (3 min ago)                            │
│  ├─ Accepted: 11:35 AM (in progress)                          │
│  ├─ Expected Arrival: 11:50 AM                                │
│  ├─ Handed Over: -                                            │
│  ├─ Payment Confirmed: -                                      │
│  └─ Completed: -                                              │
│                                                                 │
│  📞 COMMUNICATION                                              │
│  ├─ Logistics messages:                                       │
│  │  "Customer prefers afternoon delivery, confirm"            │
│  ├─ [ SEND MESSAGE TO LOGISTICS ]                             │
│  ├─ [ SEND MESSAGE TO CONSUMER ]                              │
│  └─ Last update: 2 min ago                                    │
│                                                                 │
│  🎯 ACTIONS                                                     │
│  ├─ [📍 NAVIGATE]    Go to delivery location                  │
│  ├─ [☑️ ARRIVED]      Mark arrival at location               │
│  ├─ [✓ COMPLETE]     Mark delivery complete                  │
│  ├─ [💬 MESSAGE]     Communicate with customer/logistics     │
│  ├─ [⚠️ ISSUE]       Report problem                           │
│  └─ [🔙 CANCEL]      Cancel delivery (need approval)          │
│                                                                 │
└────────────────────────────────────────────────────────────────┘
```

---

## 📊 DATABASE SCHEMA (Updated)

### **New Tables**

```sql
-- DRIVERS TABLE (Independent)
CREATE TABLE drivers (
    id BIGINT PRIMARY KEY,
    user_id BIGINT UNIQUE NOT NULL,      -- Links to users table
    employee_id BIGINT NULLABLE,         -- Optional: if also employee
    driver_code VARCHAR(50) UNIQUE,
    name VARCHAR(255),
    phone VARCHAR(20),
    email VARCHAR(255) UNIQUE,           -- [NEW] For independent login
    vehicle_type VARCHAR(50),
    vehicle_plate VARCHAR(50) UNIQUE,
    vehicle_model VARCHAR(100),
    license_number VARCHAR(100) UNIQUE,
    license_expiry DATE,
    delivery_fee DECIMAL(10,2),          -- Commission per delivery
    status ENUM('available', 'on_delivery', 'unavailable', 'offline'),
    total_earnings DECIMAL(15,2),
    total_deliveries INT DEFAULT 0,
    total_completed INT DEFAULT 0,
    average_rating DECIMAL(3,2),
    is_verified BOOLEAN DEFAULT false,
    last_active_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- TASK ASSIGNMENTS TABLE (NEW)
CREATE TABLE task_assignments (
    id BIGINT PRIMARY KEY,
    delivery_id BIGINT NOT NULL UNIQUE,  -- Links to deliveries
    driver_id BIGINT NOT NULL,           -- Links to drivers
    
    -- Assignment workflow
    assigned_at TIMESTAMP,
    assigned_by_user_id BIGINT,          -- Logistics staff who assigned
    
    -- Driver acceptance/rejection
    status ENUM('pending', 'accepted', 'rejected', 'en_route', 'arrived', 'completed', 'cancelled'),
    accepted_at TIMESTAMP NULLABLE,
    rejected_at TIMESTAMP NULLABLE,
    rejection_reason TEXT NULLABLE,
    
    -- Tracking
    started_at TIMESTAMP NULLABLE,
    arrived_at TIMESTAMP NULLABLE,
    completed_at TIMESTAMP NULLABLE,
    
    -- Communication
    notes TEXT,
    
    FOREIGN KEY (delivery_id) REFERENCES deliveries(id),
    FOREIGN KEY (driver_id) REFERENCES drivers(id),
    FOREIGN KEY (assigned_by_user_id) REFERENCES users(id),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- DELIVERY HISTORY TABLE (Enhanced)
CREATE TABLE delivery_history (
    id BIGINT PRIMARY KEY,
    delivery_id BIGINT NOT NULL,
    order_id BIGINT,
    
    -- Status progression
    status VARCHAR(50),                  -- 'placed', 'preparing', 'packed', 'out_for_delivery', 'arrived', 'delivered'
    previous_status VARCHAR(50),
    
    -- Who updated
    updated_by_type ENUM('consumer', 'farm_owner', 'logistics', 'driver', 'system'),
    updated_by_id BIGINT,                -- User ID who made update
    
    -- Timestamp
    created_at TIMESTAMP,
    
    -- Additional info
    notes TEXT,
    metadata JSON,                       -- Extra data like GPS coordinates, etc
    
    FOREIGN KEY (delivery_id) REFERENCES deliveries(id),
    INDEX (delivery_id, created_at)
);

-- DELIVERY PROOFS TABLE (NEW)
CREATE TABLE delivery_proofs (
    id BIGINT PRIMARY KEY,
    delivery_id BIGINT NOT NULL,
    task_assignment_id BIGINT NOT NULL,
    
    -- Proof types
    proof_type ENUM('photo', 'signature', 'receipt', 'gps_location'),
    image_path VARCHAR(500) NULLABLE,    -- Photo of delivery
    signature_path VARCHAR(500) NULLABLE,-- Customer signature
    receipt_path VARCHAR(500) NULLABLE,  -- Receipt/invoice proof
    
    -- Location proof
    gps_latitude DECIMAL(10,8),
    gps_longitude DECIMAL(11,8),
    gps_accuracy DECIMAL(5,2),           -- Accuracy in meters
    
    -- Fingerprint for verification
    driver_comment TEXT,
    
    uploaded_at TIMESTAMP,
    uploaded_by_driver_id BIGINT,
    verified_by_logistics_id BIGINT NULLABLE,
    verified_at TIMESTAMP NULLABLE,
    
    FOREIGN KEY (delivery_id) REFERENCES deliveries(id),
    FOREIGN KEY (task_assignment_id) REFERENCES task_assignments(id),
    FOREIGN KEY (uploaded_by_driver_id) REFERENCES drivers(id),
    FOREIGN KEY (verified_by_logistics_id) REFERENCES users(id),
    created_at TIMESTAMP
);

-- PAYMENT CONFIRMATIONS TABLE (NEW)
CREATE TABLE payment_confirmations (
    id BIGINT PRIMARY KEY,
    delivery_id BIGINT NOT NULL UNIQUE,
    
    -- Payment info
    order_amount DECIMAL(15,2),
    delivery_fee DECIMAL(10,2),
    total_amount DECIMAL(15,2),
    
    -- Payment collection
    payment_method ENUM('cash', 'online', 'check', 'credit'),
    payment_collected_by ENUM('driver', 'logistics', 'consumer', 'farm_owner'),
    collection_proof_photo VARCHAR(500) NULLABLE,  -- Photo of payment
    
    -- Confirmation
    confirmed_by_driver BOOLEAN DEFAULT false,
    confirmed_by_consumer BOOLEAN DEFAULT false,
    confirmed_by_logistics BOOLEAN DEFAULT false,
    
    -- Timestamps
    expected_at TIMESTAMP,
    collected_at TIMESTAMP NULLABLE,
    confirmed_at TIMESTAMP NULLABLE,
    
    -- Notes
    notes TEXT,
    
    FOREIGN KEY (delivery_id) REFERENCES deliveries(id),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- DRIVER EARNINGS TABLE (For commission tracking)
CREATE TABLE driver_earnings (
    id BIGINT PRIMARY KEY,
    driver_id BIGINT NOT NULL,
    delivery_id BIGINT NULLABLE,
    
    -- Earning breakdown
    base_delivery_fee DECIMAL(10,2),
    bonus_multiplier DECIMAL(3,2) DEFAULT 1.0,  -- Rain/weather/rush hour bonus
    actual_earning DECIMAL(10,2),               -- base × multiplier
    
    -- Tracking
    status ENUM('pending', 'confirmed', 'paid'),
    transaction_date DATE,
    payment_date DATE NULLABLE,
    
    notes TEXT,
    
    FOREIGN KEY (driver_id) REFERENCES drivers(id),
    FOREIGN KEY (delivery_id) REFERENCES deliveries(id),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## 🔄 COMMUNICATION FLOW

### **Workflow 1: Order Placed → Delivery Completed**

```
┌─────────────────────────────────────────────────────────────────────────┐
│              COMPLETE ORDER LIFECYCLE WITH DRIVER PORTAL                │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  1️⃣ CONSUMER PLACES ORDER                                              │
│     Consumer: Browses → Adds to cart → Checks out                      │
│     └─ Order created with status: "placed"                             │
│        ├─ Notification: Consumer phone/email/app                       │
│        └─ Notification: Farm Owner (new order alert)                   │
│                                                                          │
│  2️⃣ FARM OWNER CONFIRMS & PACKS                                        │
│     Farm Owner: Dashboard → Confirms order                              │
│     └─ Status: "confirmed"                                             │
│        ├─ Notification: Consumer ("Order confirmed!")                  │
│        ├─ Staff starts packing                                         │
│        └─ Marks: "preparing" → "packed" → "ready_for_pickup"         │
│           └─ Notification: Logistics staff                            │
│                                                                          │
│  3️⃣ LOGISTICS CREATES DELIVERY TASK                                    │
│     Logistics: Dashboard → Creates delivery                             │
│     └─ Status: "ready_for_pickup"                                      │
│        ├─ System: Finds best driver (available, ratings, location)    │
│        ├─ [NEW] Sends notification to Driver Portal                   │
│        └─ Task in Driver Portal: "PENDING ACCEPTANCE"                 │
│           └─ Notification: Driver phone/email                         │
│                                                                          │
│  4️⃣ DRIVER ACCEPTS TASK (NEW)                                          │
│     Driver Portal: Views assigned deliveries                            │
│     ├─ Taps: [ACCEPT] or [REJECT]                                      │
│     ├─ Status: "accepted"                                              │
│     │  ├─ [NEW] Sends notification to Logistics Portal                │
│     │  ├─ [NEW] Sends notification to Consumer ("Driver assigned!")  │
│     │  └─ Consumer sees: Driver name, vehicle, phone                  │
│     └─ Logistics: Marks delivery as "out_for_delivery"                │
│        └─ Notification: Consumer ("Out for delivery!")                │
│                                                                          │
│  5️⃣ DRIVER EN ROUTE (NEW)                                              │
│     Driver Portal: Taps [NAVIGATE]                                      │
│     ├─ Maps opens with customer address                                │
│     ├─ GPS tracking enabled                                            │
│     ├─ [NEW] Real-time location updates sent to:                      │
│     │       Consumer (mobile app shows live tracking)                 │
│     ├─ [NEW] Estimated arrival automatically calculated               │
│     ├─ Driver can message customer (from portal)                      │
│     └─ Notification: Consumer ("Driver 2 min away")                   │
│                                                                          │
│  6️⃣ DRIVER ARRIVES (NEW)                                               │
│     Driver Portal: [ARRIVED] button tapped                              │
│     ├─ GPS location recorded as proof                                  │
│     ├─ [NEW] Takes photo of delivery location                         │
│     ├─ Status: "arrived"                                              │
│     ├─ Notification: Consumer ("Driver at your door!")                │
│     └─ Consumer sees: Photo + location on map                          │
│                                                                          │
│  7️⃣ DELIVERY HANDOFF (NEW)                                             │
│     Driver: Hands over package to consumer                              │
│     Driver Portal:                                                      │
│     ├─ [✓ COMPLETE] → Opens payment screen                           │
│     ├─ Taps: "Customer received package"                              │
│     ├─ [NEW] Uploads photo proof                                       │
│     ├─ [NEW] Customer provides optional signature (app prompts)       │
│     ├─ Status: "delivered"                                            │
│     └─ Notification: Logistics ("Delivery handed over")               │
│                                                                          │
│  8️⃣ PAYMENT COLLECTION (NEW)                                           │
│     Driver Portal: Shows payment section                                │
│     Driver Views:                                                       │
│     ├─ Amount to collect: ₱550 (+ ₱50 delivery fee for display)     │
│     ├─ Payment method: Cash on Delivery                               │
│     ├─ [TAKE PAYMENT PHOTO] (proof of cash)                           │
│     ├─ Taps: [PAYMENT RECEIVED] or [PAYMENT PENDING]                 │
│     │                                                                    │
│     │  CONSUMER APP (Simultaneously):                                 │
│     │  ├─ Gets: "Ready to pay?" prompt                               │
│     │  ├─ Shows: amount ₱550                                          │
│     │  ├─ [CONFIRM PAYMENT] or [WAITING]                             │
│     │                                                                    │
│     └─ Both driver & consumer confirm → Status: "payment_confirmed" │
│        ├─ Notification: Logistics ("Payment confirmed")              │
│        ├─ Notification: Farm Owner ("Payment received")              │
│        └─ Driver: EARNINGS ADDED ₱50                                 │
│                                                                          │
│  9️⃣ FINAL CONFIRMATION (NEW)                                           │
│     Logistics Portal: Views delivery detail                             │
│     ├─ Sees: Photos, GPS proof, payment confirmation                  │
│     ├─ [VERIFY & COMPLETE] button                                      │
│     └─ Final status: "completed"                                       │
│        ├─ Consumer: Can now [RATE ORDER] & [RATE DRIVER]              │
│        ├─ Driver: Earnings finalized in payroll                       │
│        └─ Farm Owner: Can access payment in reports                   │
│                                                                          │
│  🔟 POST-DELIVERY (NEW)                                                │
│     Consumer App:                                                       │
│     ├─ ⭐ Rate the order (1-5 stars)                                   │
│     ├─ ⭐ Rate the driver (separate 1-5 stars)                        │
│     ├─ 💬 Leave review comments                                        │
│     └─ Driver profile updates with new rating                         │
│        └─ Affects future job assignment priority                      │
│                                                                          │
└──────────────────────────────────────────────────────────────────────── ─┘
```

### **Workflow 2: What If Driver Rejects?**

```
┌────────────────────────────────────────────────────────────────┐
│              DRIVER REJECTION FLOW                             │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│  Driver Portal: Task shows "PENDING ACCEPTANCE"               │
│     ├─ [ACCEPT] ──┐                                           │
│     │             ├─ Goes to Workflow 1 (Step 4)             │
│     └─ [REJECT] ──┤                                           │
│                   │                                            │
│                   ▼                                            │
│  Driver taps [REJECT]:                                        │
│  ├─ Reason dropdown:                                          │
│  │  • Too far                                                 │
│  │  • Not in my route                                         │
│  │  • Offline/Unavailable                                    │
│  │  • Vehicle issue                                           │
│  │  • Other (text input)                                      │
│  └─ [CONFIRM REJECTION]                                       │
│     ├─ Status: "rejected"                                    │
│     ├─ Task removed from driver's portal                     │
│     ├─ Notification: Logistics ("Driver rejected task")       │
│     └─ System: Auto-assign to next available driver          │
│        ├─ Filter by: rating, location, availability         │
│        └─ Goes back to: Driver Portal notification           │
│                                                                │
│  Logistics can see:                                           │
│  ├─ Rejection history                                        │
│  ├─ Pattern analysis (frequent rejections = lower priority)  │
│  └─ Can manually reassign to different driver                │
│                                                                │
└────────────────────────────────────────────────────────────────┘
```

---

## 🔌 API ENDPOINTS (Driver Portal)

```
┌──────────────────────────────────────────────────────────────┐
│         DRIVER PORTAL API ROUTES                             │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│  AUTHENTICATION                                              │
│  POST   /api/drivers/auth/register    Register new driver   │
│  POST   /api/drivers/auth/login        Login with email     │
│  POST   /api/drivers/auth/logout       Logout              │
│  POST   /api/drivers/auth/refresh      Refresh token       │
│                                                              │
│  DRIVER PROFILE                                              │
│  GET    /api/drivers/me                Get own profile      │
│  PUT    /api/drivers/me                Update profile       │
│  PUT    /api/drivers/me/vehicle        Update vehicle info  │
│  PUT    /api/drivers/me/availability   Set online/offline   │
│  GET    /api/drivers/me/stats          Get earnings stats   │
│                                                              │
│  TASK MANAGEMENT                                             │
│  GET    /api/drivers/tasks             Get all tasks        │
│  GET    /api/drivers/tasks/pending     Pending assignments  │
│  GET    /api/drivers/tasks/active      Active deliveries    │
│  GET    /api/drivers/tasks/{id}        Get single task      │
│                                                              │
│  TASK ACTIONS                                                │
│  POST   /api/drivers/tasks/{id}/accept Accept task          │
│  POST   /api/drivers/tasks/{id}/reject Reject task          │
│  POST   /api/drivers/tasks/{id}/start  Mark started         │
│  POST   /api/drivers/tasks/{id}/arrived Mark arrived       │
│  POST   /api/drivers/tasks/{id}/complete Complete delivery │
│                                                              │
│  PROOF & DOCUMENTATION                                       │
│  POST   /api/drivers/tasks/{id}/proof  Upload photo/sig    │
│  GET    /api/drivers/tasks/{id}/proof  Get proof images    │
│  POST   /api/drivers/tasks/{id}/location Update GPS        │
│                                                              │
│  PAYMENT CONFIRMATION                                        │
│  GET    /api/drivers/tasks/{id}/payment Get payment status │
│  POST   /api/drivers/tasks/{id}/payment/confirm Confirm p. │
│  POST   /api/drivers/tasks/{id}/payment/proof Upload proof │
│                                                              │
│  COMMUNICATION                                               │
│  GET    /api/drivers/tasks/{id}/messages Get messages       │
│  POST   /api/drivers/tasks/{id}/messages Send message       │
│  GET    /api/drivers/notifications      Get all notif.      │
│  POST   /api/drivers/notifications/{id}/read Mark read      │
│                                                              │
│  EARNINGS & HISTORY                                          │
│  GET    /api/drivers/earnings/today     Today's earnings    │
│  GET    /api/drivers/earnings/month     Monthly earnings    │
│  GET    /api/drivers/earnings/history   Earning history     │
│  GET    /api/drivers/deliveries/history Completed jobs     │
│  GET    /api/drivers/ratings            Get ratings         │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

---

## 🔔 REAL-TIME NOTIFICATIONS

### **What Drivers Receive**

```
Task Assignment:
├─ 🔔 SMS: "New delivery: Maria Santos, ₱550, 2.5km away"
├─ 📱 Push: "New task assigned - Order #542"
├─ 💻 Portal: Task appears in active list (1st)

Auto-Acceptance Countdown:
├─ Portal reminder: "Accept within 5 minutes or auto-reject"

Task Status Updates:
├─ 🔔 SMS: "Customer arrived - open door"
├─ 💬 Message: "Customer says gate is locked, please call"

Payment Issues:
├─ ⚠️ Alert: "Payment unconfirmed - customer says different amount"

Earnings:
├─ 💰 Notification: "Delivery completed - ₱50 earned"
├─ 📊 Summary: "Today's earnings: ₱450"

Ratings:
├─ ⭐ Update: "Customer gave you 5 stars!"

System Messages:
├─ 🔧 Alert: "Vehicle inspection due - 3 days remaining"

```

### **What Consumers Receive**

```
Order Confirmation:
├─ ✓ Email: "Order confirmed"
├─ 📱 Push: "Your order #542 confirmed"

Delivery Assignment:
├─ ⏰ "Driver assigned! John (4.8★) with Tricycle ABC1234"
├─ 📞 Driver phone pre-populated for calling

En Route:
├─ 🚗 "Driver 5 min away"
├─ 🗺️ Live tracking enabled
├─ ⏱️ "Expected arrival: 11:50 AM"

Near Arrival:
├─ 🔔 "Driver is 2 minutes away"
├─ 📸 Photo: Driver's location

Arrived:
├─ ✓ "Driver at your door!"
├─ 📸 Proof photo shown

Ready to Pay:
├─ 💵 "Confirm payment received: ₱550"
├─ [CONFIRM PAYMENT] button

Post-Delivery:
├─ ⭐ "Rate your order and driver"
├─ 🎁 "Review bonus: Earn 10 loyalty points"
```

---

## 📱 MOBILE APP INTEGRATION

### **Consumer Mobile App**

```
┌─────────────────────────────────────────────────────┐
│  CONSUMER MOBILE APP (Updates)                      │
├─────────────────────────────────────────────────────┤
│                                                     │
│  ORDER DETAIL SCREEN (During Delivery)             │
│                                                     │
│  Status: 🟢 Out for delivery (Live)               │
│                                                     │
│  🚗 Driver Assignment:                             │
│  ├─ Photo: [Driver's profile pic]                  │
│  ├─ Name: John Dela Cruz                          │
│  ├─ Rating: ⭐⭐⭐⭐⭐ (4.8)                       │
│  ├─ Vehicle: Tricycle ABC 1234                    │
│  ├─ Phone: [Call button] [Message button]         │
│  └─ Status: Enroute (5 min away)                  │
│                                                     │
│  🗺️ LIVE TRACKING:                                │
│  ├─ Map showing:                                   │
│  │  • Your location (pin)                          │
│  │  • Driver's real-time location (moving pin)    │
│  │  • Route to your address                        │
│  │  • Time remaining                               │
│  │  • Distance remaining                           │
│  └─ [Tap for full screen map]                     │
│                                                     │
│  ⏰ DELIVERY TIMELINE:                             │
│  ├─ ✓ Order placed: 11:00 AM                      │
│  ├─ ✓ Confirmed: 11:15 AM                         │
│  ├─ ✓ Packed: 11:30 AM                            │
│  ├─ ✓ Out for delivery: 11:35 AM                  │
│  ├─ 🟡 Expected arrival: 11:50 AM                │
│  ├─ ⚪ Handed over: -                              │
│  └─ ⚪ Payment confirmed: -                        │
│                                                     │
│  💬 MESSAGES:                                       │
│  ├─ [Driver]: "Arriving soon, main gate ok?"       │
│  ├─ [You]: "Yes, it's open"                       │
│  └─ [Driver]: "Thanks! 2 min away"                │
│                                                     │
│  📸 PROOF (When arriving nearby):                 │
│  ├─ Photo: Driver's location (for security)       │
│  └─ "Photo taken at your address"                │
│                                                     │
│  💵 PAYMENT SECTION (Ready when near):            │
│  ├─ Amount: ₱550                                  │
│  ├─ Status: [Ready to Pay]                        │
│  ├─ [ MARK AS PAID ]  [ WAITING ]                │
│  └─ After paid: [CONFIRM DELIVERY]               │
│                                                     │
│  [ ✓ CONFIRM DELIVERY ]  [ ⚠️ REPORT ISSUE ]      │
│                                                     │
└─────────────────────────────────────────────────────┘
```

---

## 🏪 Web Marketplace Updates

```
┌──────────────────────────────────────────────────────────┐
│  CONSUMER WEB MARKETPLACE (Order Tracking)               │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  MY ORDERS → Order #542                                 │
│                                                          │
│  Order Status: Out for Delivery 🟢                      │
│                                                          │
│  Timeline Progress:                                      │
│  ├─ ✓ Confirmed (11:15 AM)                             │
│  ├─ ✓ Packed (11:30 AM)                                │
│  ├─ ✓ Out for Delivery (11:35 AM)                     │
│  ├─ ⏳ Driver Arrived (Est. 11:50 AM)                 │
│  ├─ ⚪ Handed Over                                      │
│  └─ ⚪ Completed                                        │
│                                                          │
│  👤 DRIVER INFO:                                         │
│  ├─ Photo + Name: John Dela Cruz                       │
│  ├─ Rating: 4.8/5.0 ⭐                               │
│  ├─ Vehicle: Tricycle ABC 1234                        │
│  ├─ Phone: [CALL] [MESSAGE]                           │
│  └─ Time to Arrival: 5 minutes                         │
│                                                          │
│  🗺️ LIVE MAP:                                           │
│  ├─ Shows driver approaching                           │
│  ├─ Your address marked                                │
│  ├─ Refresh every 5 seconds                            │
│  └─ [Full Screen Map]                                  │
│                                                          │
│  📦 ORDER ITEMS:                                        │
│  ├─ Chicken Quarters (5kg) × 2                         │
│  ├─ Total: ₱550                                        │
│  └─ Delivery Fee: ₱50 (shown for transparency)         │
│                                                          │
│  💬 CHAT:                                               │
│  ├─ [Driver]: "Almost there! Main gate open?"          │
│  ├─ [You]: "Yes, gate is open"                         │
│  └─ [Driver]: "Thanks! I'm here"                       │
│                                                          │
│  ⏰ REAL-TIME UPDATES:                                  │
│  ├─ "Driver is 2 minutes away" (auto-updates)         │
│  ├─ "Driver has arrived" (auto-update)                │
│  ├─ "Awaiting payment confirmation" (auto-update)     │
│  └─ Red dot = notification badge                       │
│                                                          │
│  💵 PAYMENT:                                            │
│  ├─ Amount: ₱550                                       │
│  ├─ Status: Awaiting payment                           │
│  ├─ [ CONFIRM PAID ]  [ WAITING ]                     │
│  └─ After confirmed: [ RATE ORDER & DRIVER ]          │
│                                                          │
└──────────────────────────────────────────────────────────┘
```

---

## 🔐 SECURITY & VERIFICATION

```
┌──────────────────────────────────────────────────────┐
│         DRIVER VERIFICATION SYSTEM                  │
├──────────────────────────────────────────────────────┤
│                                                      │
│  DRIVER ONBOARDING:                                 │
│  ├─ Upload Valid ID (Driver's License)             │
│  ├─ Upload Vehicle Registration                     │
│  ├─ Photo verification (selfie)                     │
│  ├─ Background check (automated/manual)            │
│  ├─ Vehicle inspection (photos + checklist)        │
│  └─ Status: PENDING / VERIFIED / REJECTED           │
│                                                      │
│  PROOF OF DELIVERY VERIFICATION:                    │
│  ├─ Photo timestamp validation (can't be old)       │
│  ├─ GPS geofencing (delivery at correct location)   │
│  ├─ Payment photo attached (for COD)                │
│  ├─ Signature (optional but preferred)              │
│  └─ Consumer confirmation (can dispute if wrong)    │
│                                                      │
│  DRIVER FRAUD DETECTION:                            │
│  ├─ GPS spoofing detection                          │
│  ├─ Pattern analysis (unusual routes/times)         │
│  ├─ Photo metadata verification                     │
│  ├─ Payment discrepancy alerts                      │
│  └─ Consumer dispute tracking                       │
│                                                      │
│  RATING & REPUTATION SYSTEM:                        │
│  ├─ Track driver ratings (1-5 stars)               │
│  ├─ Min 4.0 rating to get premium deliveries       │
│  ├─ Below 3.5 = warnings + review                   │
│  ├─ Frequent fraud = driver deactivation            │
│  └─ Public profile shows stats & reviews            │
│                                                      │
│  COMPLAINT RESOLUTION:                              │
│  ├─ Consumer files complaint                        │
│  ├─ Logistics reviews proof & gives driver chance   │
│  ├─ Driver can provide additional explanation       │
│  ├─ Decision: Resolved / Chargeback / Penalties    │
│  └─ Repeat issues = escalation                      │
│                                                      │
└──────────────────────────────────────────────────────┘
```

---

## 📊 DRIVER DASHBOARD

```
┌────────────────────────────────────────────────────────────┐
│  DRIVER PORTAL - ANALYTICS DASHBOARD                       │
├────────────────────────────────────────────────────────────┤
│                                                            │
│  📈 TODAY'S PERFORMANCE                                    │
│  ├─ Tasks completed: 8/10                                 │
│  ├─ Total earnings: ₱450                                  │
│  ├─ Acceptance rate: 95%                                  │
│  ├─ Average rating: 4.9★                                  │
│  └─ On-time deliveries: 100%                              │
│                                                            │
│  💰 WEEKLY EARNINGS                                        │
│  ├─ Mon: ₱520                                             │
│  ├─ Tue: ₱480                                             │
│  ├─ Wed: ₱620                                             │
│  ├─ Thu: ₱450                                             │
│  ├─ Fri: ₱550                                             │
│  ├─ Sat: ₱750                                             │
│  ├─ Sun: ₱400                                             │
│  └─ Total: ₱3,770                                         │
│                                                            │
│  📊 MONTHLY STATS                                          │
│  ├─ Total deliveries: 156                                 │
│  ├─ Total earnings: ₱12,450                               │
│  ├─ Bonus earnings: ₱2,100 (rush hour)                   │
│  ├─ Rejection rate: 2% (low)                              │
│  └─ Customer satisfaction: 98%                            │
│                                                            │
│  ⭐ RATING BREAKDOWN                                       │
│  ├─ 5 stars: ✓✓✓✓✓ (145 customers)                      │
│  ├─ 4 stars: ✓✓✓✓ (8 customers)                          │
│  ├─ 3 stars: ✓✓✓ (2 customers)                            │
│  ├─ 2 stars: ✓✓ (0 customers)                             │
│  ├─ 1 star: ✓ (1 customer - disputed & resolved)         │
│  └─ Average: 4.92★                                        │
│                                                            │
│  🚗 VEHICLE STATUS                                         │
│  ├─ Registration valid until: Oct 2027                    │
│  ├─ Insurance valid until: Aug 2026                       │
│  ├─ License valid until: Dec 2028                         │
│  ├─ Last inspection: 2 weeks ago ✓                        │
│  └─ Next inspection due: 5 weeks                          │
│                                                            │
│  🎯 OPTIMIZATION TIPS                                      │
│  ├─ Accept more rush-hour deliveries (extra ₱10/order)  │
│  ├─ Maintain 100% on-time rate (gets priority jobs)     │
│  ├─ Aim for 5.0 rating (access premium orders)           │
│  └─ Peak hours: 11-12 AM, 5-7 PM (max earnings)         │
│                                                            │
│  [ VIEW DETAILED REPORT ]  [ EXPORT CSV ]                │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

---

## 🔧 IMPLEMENTATION ROADMAP

```
PHASE 1: Driver Authentication & Profile (Week 1-2)
├─ Create Driver model with email field
├─ Build Driver registration form
├─ Implement independent login system
├─ Driver profile management
└─ Vehicle management interface

PHASE 2: Task Assignment System (Week 3-4)
├─ Create TaskAssignment table & model
├─ Build task notification system
├─ Implement accept/reject workflow
├─ Auto-assignment algorithm
└─ Task history tracking

PHASE 3: Real-time Communication (Week 5-6)
├─ WebSocket/Pusher setup for live updates
├─ GPS tracking integration (Google Maps API)
├─ Real-time notification delivery
├─ Message system (driver ↔ consumer/logistics)
└─ Live map display on consumer app

PHASE 4: Proof & Verification (Week 7-8)
├─ Photo upload module
├─ GPS geofencing verification
├─ Signature capture (digital)
├─ Payment confirmation photos
└─ Fraud detection system

PHASE 5: Payment Integration (Week 9-10)
├─ Payment confirmation workflow
├─ Multiple payment method support
├─ Cash collection verification
├─ Commission calculation automation
└─ Driver earnings dashboard

PHASE 6: Analytics & Dashboards (Week 11-12)
├─ Driver performance dashboard
├─ Consumer tracking dashboard
├─ Logistics management console
├─ Real-time analytics
└─ Reporting system

PHASE 7: Mobile App Integration (Week 13-14)
├─ Update Flutter app for live tracking
├─ Implement real-time notifications
├─ Payment confirmation UI updates
├─ Driver rating system in app
└─ Full end-to-end testing

PHASE 8: Testing & Deployment (Week 15+)
├─ End-to-end testing
├─ Performance optimization
├─ Security audit
├─ User training
└─ Production deployment
```

---

## ✅ SUCCESS CRITERIA

```
✓ Driver has independent login with email
✓ Driver receives task notifications in real-time
✓ Driver can accept/reject assignments from portal
✓ Consumer sees live driver tracking on mobile & web
✓ Driver can upload proof photos at delivery
✓ Payment confirmed by both driver & consumer
✓ Commission automatically calculated post-delivery
✓ All data flows integrated without manual intervention
✓ No PHP/database errors in logs
✓ Real-time notifications delivered < 2 seconds
✓ All stakeholders (consumer, logistics, driver) see consistent status
```

---

This is a professional, enterprise-grade design. Implement phase by phase! 🚀
