# 🎨 SYSTEM CONNECTIONS & ARCHITECTURE VISUAL GUIDE

---

## 🏗️ SYSTEM ARCHITECTURE

```
┌──────────────────────────────────────────────────────────────────────────┐
│                     POULTRY MARKETPLACE SYSTEM                           │
│                                                                          │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐              │
│  │  CONSUMERS   │    │  FARM OWNERS │    │  LOGISTICS   │              │
│  │              │    │              │    │  STAFF       │              │
│  │ • Register   │    │ • Register   │    │ • Manage     │              │
│  │ • Browse     │    │ • Sell       │    │   drivers    │              │
│  │ • Buy        │    │ • Confirm    │    │ • Create     │              │
│  │ • Track      │    │ • Manage     │    │   deliveries │              │
│  │ • Rate       │    │ • Dashboard  │    │ • Update     │              │
│  └─────┬────────┘    └───────┬──────┘    │   status     │              │
│        │                     │            └──────┬───────┘              │
│        │                     │                   │                      │
│        └─────────────────────┼───────────────────┘                      │
│                              │                                          │
│                              ▼                                          │
│            ┌──────────────────────────────────┐                        │
│            │   SHARED API ENDPOINTS           │                        │
│            │ /api/orders                      │                        │
│            │ /api/deliveries                  │                        │
│            │ /api/drivers                     │                        │
│            │ /api/products                    │                        │
│            │ /api/payments                    │                        │
│            │ /api/tracking                    │                        │
│            └──────────────────┬───────────────┘                        │
│                               │                                        │
│                               ▼                                        │
│              ┌────────────────────────────┐                           │
│              │   PostgreSQL DATABASE      │                           │
│              │                            │                           │
│              │ Tables:                    │                           │
│              │ • users                    │                           │
│              │ • employees                │                           │
│              │ • drivers          ←──┐    │                           │
│              │ • orders                │    │                           │
│              │ • deliveries       ←────┼──┘                           │
│              │ • products              │                           │
│              │ • payments              │                           │
│              │ • notifications         │                           │
│              │ • ratings               │                           │
│              └────────────────────────────┘                           │
│                                                                        │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## 👥 USERS & ROLES

```
┌─────────────────────────────────────────────────────────────┐
│              ROLE-BASED ACCESS CONTROL                      │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  CONSUMER                    FARM OWNER       LOGISTICS STAFF │
│  ├─ Browse products          ├─ Dashboard     ├─ Dashboard   │
│  ├─ Place orders            ├─ Confirm       ├─ View drivers│
│  ├─ Pay online              │  orders        ├─ Create      │
│  ├─ Track delivery          ├─ Manage        │  deliveries  │
│  ├─ Rate orders             │  products      ├─ Assign      │
│  ├─ View invoices           ├─ View sales    │  drivers     │
│  └─ Access mobile app       ├─ Reports       ├─ Update      │
│                             ├─ Employee      │  status      │
│                             │  management    ├─ View map    │
│                             ├─ Payroll       │  tracking    │
│                             └─ Manage        └─ Reports     │
│                                deliveries                    │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 🚗 DRIVER CREATION FLOW

```
BEFORE (March April):           AFTER (April 5 with new feature):
┌──────────────────┐            ┌────────────────────┐
│ Create Employee  │            │ Create Employee    │
│                  │            │                    │
│ Fill basic info  │            │ Fill basic info    │
│ ⬇️              │            │ ⬇️                │
│ Manually add     │            │ SELECT DEPARTMENT  │
│ driver fields    │            │ ⬇️                │
│ ⬇️              │            │ "Driver" selected? │
│ Driver profile   │            │ ⬇️ YES ⬇️        │
│ created          │            │ Driver form        │
└──────────────────┘            │ appears!          │
                                │ (or check role)   │
                                │ ⬇️                │
                                │ Fill driver info  │
                                │ • Vehicle type    │
                                │ • Plate number    │
                                │ • License info    │
                                │ • Delivery fee    │
                                │ ⬇️                │
                                │ Driver profile    │
                                │ created + role    │
                                │ auto-assigned     │
                                └────────────────────┘
```

---

## 📦 ORDER TO DELIVERY FLOW

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      COMPLETE ORDER LIFECYCLE                           │
│                                                                         │
│  CONSUMER SIDE                    BACKEND               FARM OWNER     │
│  ┌───────────────┐               ┌──────────┐         ┌────────────┐  │
│  │ 1. Browse     │               │ Products │         │ Dashboard  │  │
│  │    Products   │────[read]────▶│ Database │◀────────│ Inventory  │  │
│  └───────────────┘               └──────────┘         └────────────┘  │
│         │                                                    │           │
│         ▼                                                    │           │
│  ┌───────────────┐               ┌──────────┐              │           │
│  │ 2. Add to     │               │  Cart    │              │           │
│  │    Cart       │──[session]───▶│ Session  │              │           │
│  └───────────────┘               └──────────┘              │           │
│         │                                                   │           │
│         ▼                                                   │           │
│  ┌───────────────┐               ┌──────────┐              │           │
│  │ 3. Checkout   │               │ Payment  │              │           │
│  │               │──[payment]───▶│ Gateway  │              │           │
│  └───────────────┘               └──────────┘              │           │
│         │                                                   │           │
│         ▼                                                   ▼           │
│  ┌───────────────┐        ┌──────────────────┐    ┌────────────────┐ │
│  │ 4. Order      │        │ Order Created    │    │ 5. View        │ │
│  │    Confirmed  │       │ • Status:        │    │    Pending     │ │
│  │    (Pending)  │────▶  │   "pending"      │◀───│    Orders     │ │
│  └───────────────┘        │ • Items          │    └────────────────┘ │
│         │                 │ • Total          │            │          │
│         │                 └──────────────────┘            ▼          │
│         │                                          ┌────────────────┐ │
│         │                                          │ 6. CONFIRM    │ │
│         │                                          │    Order      │ │
│         │                                          │ (Mark ready)  │ │
│         │                                          └────────┬──────┘ │
│         │                                                   │        │
│         │                 NOTIFICATION SENT ◀──────────────┘        │
│         │                                                            │
│         └─── "Your order was confirmed" ─────────────────────────┘  │
│                                                                      │
│  LOGISTICS SIDE                                                      │
│  ┌──────────────────┐         ┌──────────────┐                     │
│  │ 7. Check for     │────────▶│ List of      │                     │
│  │    Confirmed     │         │ Confirmed   │  FILTER TYPE A       │
│  │    Orders        │         │ Orders      │                     │
│  └──────────────────┘         └──────────────┘                     │
│         │                                                            │
│         ▼                                                            │
│  ┌──────────────────┐         ┌──────────────┐                     │
│  │ 8. Create        │────────▶│ Delivery     │                     │
│  │    Delivery      │         │ Record      │                     │
│  │ • Select order   │         │             │                     │
│  │ • Assign driver  │         │ Status:     │                     │
│  │                 │         │ "preparing" │                     │
│  └──────────────────┘         └──────────────┘                     │
│         │                                                            │
│         └──── NOTIFY CONSUMER: "Your order is being prepared" ────┘ │
│                                                                      │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │                    STATUS UPDATES                            │   │
│  ├──────────────────────────────────────────────────────────────┤   │
│  │                                                              │   │
│  │  Staff Marks PACKED  ──────▶  Consumer sees: "Packed!"      │   │
│  │                                                              │   │
│  │  Staff DISPATCHES  ──────────▶  Consumer sees:              │   │
│  │                               🚗 "Out for delivery!"        │   │
│  │                               + Driver name, vehicle info   │   │
│  │                               + Estimated time              │   │
│  │                               + (Mobile: Live map)          │   │
│  │                                                              │   │
│  │  Staff MARKS DELIVERED ────▶  Consumer sees:                │   │
│  │                               ✅ "Arrived!"                │   │
│  │                               [⭐ RATE NOW]                │   │
│  │                                                              │   │
│  └──────────────────────────────────────────────────────────────┘   │
│                                                                      │
│  DRIVER SIDE                  COMMISSION EARNED                      │
│  ┌─────────────────┐          ┌──────────────────┐                 │
│  │ Driver assigned │          │ Delivery paid    │                 │
│  │ Status:        │          │ Delivery fee: ₱50│                 │
│  │ "Available"────│──✓okay───▶│ Driver earnings: │                 │
│  │        │       │          │ += ₱50           │                 │
│  │        ▼       │          │                  │                 │
│  │ Performs       │          │ During payroll:  │                 │
│  │ delivery       │          │ Salary + all     │                 │
│  │        │       │          │ delivery fees    │                 │
│  │        ▼       │          └──────────────────┘                 │
│  │ "Available"    │                                               │
│  │ (ready for     │                                               │
│  │  next delivery)│                                               │
│  └─────────────────┘                                               │
│                                                                      │
└──────────────────────────────────────────────────────────────────────┘
```

---

## 📱 MOBILE APP CONNECTION

```
┌────────────────────────────────────────────────────────────────┐
│              MOBILE APP MARKETPLACE (Flutter)                  │
├────────────────────────────────────────────────────────────────┤
│                                                                 │
│  [Home Screen]                                                 │
│  ├─ Browse Products  ──(API)──▶ GET /api/products            │
│  ├─ Search          ──(API)──▶ GET /api/search               │
│  └─ My Orders       ──(API)──▶ GET /api/orders               │
│                                                                 │
│  [Order Detail]                                                │
│  ├─ Order info      ──(API)──▶ GET /api/orders/{id}          │
│  ├─ Delivery Status ──(API)──▶ GET /api/deliveries/{id}      │
│  └─ Live Tracking   ──(API)──▶ GET /api/tracking/{id}        │
│                      Polls every 5 seconds                    │
│                                                                 │
│  ┌─────────────────────────────────────────┐                 │
│  │ Live Order Tracking (when out for del.) │                 │
│  │                                          │                 │
│  │ 🗺️ Map View:                            │                 │
│  │ • Current driver location (if GPS)     │                 │
│  │ • Estimated arrival: 11:05 AM          │                 │
│  │ • Live route                           │                 │
│  │ • Distance remaining                   │                 │
│  │                                         │                 │
│  │ 📱 Driver Info:                         │                 │
│  │ • Name: John Dela Cruz                 │                 │
│  │ • Rating: ⭐ 4.8                        │                 │
│  │ • Vehicle: Tricycle ABC 1234           │                 │
│  │ • [Call Driver]  [Message Driver]     │                 │
│  │                                         │                 │
│  └─────────────────────────────────────────┘                 │
│                                                                 │
│  [Order History]                                               │
│  ├─ Delivered Orders ──(API)──▶ GET /api/orders?status=delivered
│  ├─ Rating Section  ──(POST)──▶ POST /api/ratings             │
│  └─ View Invoices   ──(API)──▶ GET /api/invoices              │
│                                                                 │
│                    ▼                                           │
│  ┌────────────────────────────────┐                          │
│  │ Web Server (Laravel)           │                          │
│  │ http://127.0.0.1:8000          │                          │
│  │                                │                          │
│  │ API Routes:                    │                          │
│  │ /api/orders                    │                          │
│  │ /api/deliveries                │                          │
│  │ /api/drivers                   │                          │
│  │ /api/products                  │                          │
│  │ /api/tracking                  │                          │
│  │ /api/notifications             │                          │
│  │ /api/ratings                   │                          │
│  └────────┬───────────────────────┘                          │
│           │                                                    │
│           ▼                                                    │
│  ┌────────────────────────────────┐                          │
│  │ PostgreSQL Database            │                          │
│  │ (Returns JSON data)            │                          │
│  └────────────────────────────────┘                          │
│                                                                 │
└────────────────────────────────────────────────────────────────┘
```

---

## 🔌 DATA CONNECTIONS

```
┌─────────────────────────────────────────────────────────────┐
│           DATA FLOW BETWEEN COMPONENTS                      │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  CONSUMERS (Web/Mobile)                                     │
│  └─ Places order ──▶ Order table ──▶ Farm Owner sees     │
│                                                              │
│  FARM OWNER PORTAL                                          │
│  └─ Confirms order ──▶ Updates order status ──▶ Logistics │
│     └─ Packs items                                         │
│                                                              │
│  LOGISTICS PORTAL                                           │
│  └─ Creates delivery ──▶ Links order to driver             │
│     └─ Updates status ──▶ Notifications sent to consumer   │
│                                                              │
│  DRIVERS (embedded in Employee/User)                        │
│  └─ Assigned delivery ──▶ Status: "on_delivery"           │
│     └─ Marked complete ──▶ Earns commission               │
│                                                              │
│  DATABASE RELATIONSHIPS:                                    │
│                                                              │
│  users                                                      │
│    ├─ consumer user                                        │
│    ├─ farm_owner user                                      │
│    ├─ logistics_staff user                                │
│    └─ driver user ──┐                                      │
│                     ▼                                        │
│  employees ──┐   drivers                                   │
│      │       ▼──▶  ├─ driver_code                         │
│      │    (link)   ├─ name                                │
│      │              ├─ phone                               │
│      │              ├─ vehicle_type                        │
│      │              ├─ vehicle_plate                       │
│      │              ├─ license_number                      │
│      │              ├─ license_expiry                      │
│      │              ├─ delivery_fee     ◀─ COMMISSION     │
│      │              └─ ...                                 │
│      │                                                      │
│      └─▶ orders ──┐                                        │
│          │        ▼                                         │
│          │    deliveries                                   │
│          │    ├─ order_id (link to order)                 │
│          │    ├─ driver_id (link to driver)               │
│          │    ├─ status                                    │
│          │    └─ ...                                       │
│          │                                                 │
│          └─▶ payroll                                       │
│             ├─ employee_id                                 │
│             ├─ base_salary                                 │
│             ├─ delivery_count                              │
│             ├─ delivery_commission = delivery_count × delivery_fee
│             └─ gross_pay = salary + commission             │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔔 NOTIFICATION FLOW

```
┌─────────────────────────────────────────────────────────────┐
│              CONSUMER NOTIFICATIONS                         │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  EVENT                          NOTIFICATION SENT           │
│  ────────────────────────────────────────────────           │
│                                                              │
│  Order Confirmed               → "Your order was confirmed" │
│  (Farm owner confirms)            Email + In-app             │
│                                                              │
│  Delivery Created              → "Order ready for delivery" │
│  (Logistics creates delivery)    Email + In-app + SMS       │
│                                                              │
│  Order Packed                  → "Your order is packed!"    │
│  (Logistics marks packed)        In-app notification        │
│                                                              │
│  Delivery Dispatched           → "Out for delivery! 🚗"    │
│  (Logistics dispatches)          + Driver info              │
│                                  + Estimated time           │
│                                  Email + In-app + SMS       │
│                                  + Push notification        │
│                                                              │
│  Delivery Completed            → "✅ Order delivered!"     │
│  (Logistics marks delivered)     + Rating prompt            │
│                                  Email + In-app + SMS       │
│                                                              │
│  All notifications saved in:   → Notification Table       │
│                                  (for delivery history)     │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 💰 COMMISSION CALCULATION

```
┌──────────────────────────────────────────────────────┐
│         DRIVER EARNINGS SYSTEM                       │
├──────────────────────────────────────────────────────┤
│                                                       │
│  DRIVER PROFILE (Created when Employee has "driver│
│  role)                                               │
│  ├─ delivery_fee: ₱50 (per delivery)               │
│  └─ earnings history tracked                        │
│                                                       │
│  COMMISSION CALCULATION:                            │
│                                                       │
│  Step 1: Delivery completed                         │
│    └─ Status changes to "delivered"                 │
│                                                       │
│  Step 2: Payroll period (monthly)                   │
│    ├─ Count completed deliveries: 42 deliveries    │
│    ├─ Delivery fee: ₱50/delivery                   │
│    └─ Total commission = 42 × ₱50 = ₱2,100        │
│                                                       │
│  Step 3: Gross pay calculation                      │
│    ├─ Base salary: ₱15,000                          │
│    ├─ Commission: ₱2,100                           │
│    ├─ Overtime: ₱500                               │
│    ├─ Holiday pay: ₱300                            │
│    ├─ Bonuses: ₱0                                  │
│    └─ Allowances: ₱100                             │
│    ─────────────────────                            │
│    = TOTAL GROSS: ₱18,000                          │
│                                                       │
│  TRACKING COMMISSION:                               │
│                                                       │
│  In Payroll Record:                                 │
│  ├─ Column: delivery_count = 42                    │
│  ├─ Column: delivery_commission = ₱2,100           │
│  └─ Used in: gross_pay calculation                 │
│                                                       │
│  In Driver Profile:                                 │
│  ├─ total_earnings += ₱2,100                       │
│  ├─ completed_deliveries = 42                      │
│  └─ Displayed on driver details page               │
│                                                       │
└──────────────────────────────────────────────────────┘
```

---

## 🗺️ DATABASE SCHEMA SNIPPET

```
┌─────────────────────────────────────────────────────────┐
│              KEY TABLES & RELATIONSHIPS                 │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  users TABLE                                           │
│  ├─ id, name, email                                    │
│  ├─ password, role                                     │
│  └─ status                                             │
│       │                                                 │
│       ▼ (one_to_many)                                  │
│  employees TABLE                                       │
│  ├─ id, user_id, employee_id                          │
│  ├─ first_name, last_name                             │
│  ├─ department (can be "driver")                       │
│  ├─ daily_rate, monthly_salary                        │
│  ├─ farm_owner_id                                     │
│  └─ status                                             │
│       │                                                 │
│       ├─ one_to_many ──────────────┐                  │
│       │                            ▼                   │
│       │                  employee_roles TABLE         │
│       │                  ├─ employee_id               │
│       │                  └─ role_id                   │
│       │                                                 │
│       └─ has_one ──────────────┐                      │
│                                 ▼                      │
│  drivers TABLE                                         │
│  ├─ id, employee_id, user_id                         │
│  ├─ driver_code                                       │
│  ├─ name, phone                                       │
│  ├─ vehicle_type, vehicle_plate, vehicle_model       │
│  ├─ license_number, license_expiry                   │
│  ├─ delivery_fee ◀──────────────────────────┐         │
│  ├─ status ("available"/"on_delivery")     │         │
│  └─ total_earnings                         │         │
│                                             │         │
│  orders TABLE                               │         │
│  ├─ id                                      │         │
│  ├─ consumer_id                             │         │
│  ├─ farm_owner_id                           │         │
│  ├─ status                                  │         │
│  └─ total                                   │         │
│       │                                     │         │
│       ▼ (one_to_one)                        │         │
│  deliveries TABLE                           │         │
│  ├─ id, order_id                            │         │
│  ├─ driver_id ──────────────────────────────┘         │
│  ├─ status ("preparing"/"packed"/...                  │
│  │  "out_for_delivery"/"delivered")                   │
│  ├─ proof_image                                       │
│  └─ completed_at                                      │
│                                                        │
│  payroll TABLE                                         │
│  ├─ id, employee_id                                   │
│  ├─ base_salary                                       │
│  ├─ delivery_count (only for driver roles)            │
│  ├─ delivery_commission = delivery_count × delivery_fee
│  ├─ gross_pay = base_salary + delivery_commission    │
│  └─ period_start, period_end                         │
│                                                        │
│  notifications TABLE                                  │
│  ├─ id, user_id                                       │
│  ├─ type ("order.confirmed", "delivery.dispatched"...) 
│  ├─ message                                           │
│  ├─ data (JSON with details)                         │
│  └─ read_at                                          │
│                                                        │
└─────────────────────────────────────────────────────────┘
```

---

## 🎯 DECISION TREE: "What happens when I...?"

```
┌─────────────────────────────────────────────────────────────────┐
│         CONSUMER ACTION                                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  "I placed an order"                                            │
│  └─▶ Order created in "pending" status                         │
│      └─▶ Farm owner sees it                                    │
│          └─▶ Farm owner clicks "Confirm"                      │
│              └─▶ Order moved to "confirmed"                   │
│                  └─▶ You get notification: "Confirmed!"       │
│                      └─▶ Farm owner packs it                  │
│                          └─▶ Logistics staff sees it         │
│                              └─▶ Creates delivery            │
│                                  └─▶ Assigns driver          │
│                                      └─▶ You see driver info │
│                                          └─▶ Watches status  │
│                                                               │
│  "Why is my order still 'preparing'?"                         │
│  └─▶ Farm owner is packing it                                │
│      └─▶ Once packed, Logistics marks as "packed"           │
│          └─▶ You'll see "Packed!" update                     │
│                                                               │
│  "It says 'out for delivery' but no driver assigned?"         │
│  └─▶ This shouldn't happen! Report to support               │
│      └─▶ Check if driver info visible in app               │
│          └─▶ If not, refresh and try again                 │
│                                                               │
│  "How long until delivery?"                                  │
│  └─▶ ⏱️ Check estimated time in order detail                │
│      └─▶ Mobile app shows live map                         │
│          └─▶ Updated every 5 seconds                       │
│              └─▶ Tap driver card to call/message           │
│                                                               │
└─────────────────────────────────────────────────────────────────┘


┌─────────────────────────────────────────────────────────────────┐
│         LOGISTICS STAFF ACTION                                  │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  "I created a delivery"                                         │
│  └─▶ Delivery appears in list                                 │
│      └─▶ Status: "preparing"                                 │
│          └─▶ Consumer gets notification                     │
│              └─▶ Staff marks "Packed"                       │
│                  └─▶ Consumer sees "Packed!"               │
│                      └─▶ Staff marks "Dispatch"            │
│                          └─▶ Driver status: "on_delivery"  │
│                              └─▶ Consumer sees 🚗 + info   │
│                                                             │
│  "Driver delivered but I forgot to mark complete"            │
│  └─▶ Driver doesn't earn commission yet!                   │
│      └─▶ Go to Delivery detail                             │
│          └─▶ Click "Mark as Delivered"                    │
│              └─▶ Commission added to driver instantly     │
│                                                             │
│  "I want to see how much commission this driver earned"     │
│  └─▶ Go to Payroll for that month                         │
│      └─▶ Select the driver                                │
│          └─▶ See: delivery_count × delivery_fee           │
│              └─▶ Equals commission amount                 │
│                                                             │
└─────────────────────────────────────────────────────────────────┘


┌─────────────────────────────────────────────────────────────────┐
│         DRIVER ACTION                                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  "I'm assigned a delivery"                                     │
│  └─▶ My status changes to "on_delivery"                      │
│      └─▶ I know when to go to pickup location              │
│          └─▶ I perform the delivery                        │
│              └─▶ Staff marks as "delivered"               │
│                  └─▶ ✅ Commission earned: ₱50            │
│                      └─▶ Added to my earnings             │
│                          └─▶ Included in next payroll    │
│                                                             │
│  "How do I know my delivery fee?"                            │
│  └─▶ Check with Logistics manager                         │
│      └─▶ Or go to Payroll section                        │
│          └─▶ See driver profile with delivery_fee       │
│              └─▶ That's what you earn per delivery      │
│                                                             │
└─────────────────────────────────────────────────────────────────┘
```

---

This visual guide shows all the connections in your system! 🎨✨
