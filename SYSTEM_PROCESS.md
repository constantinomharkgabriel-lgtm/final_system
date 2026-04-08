# Poultry System - Process Documentation (Updated)

## System Overview

The Poultry System is an integrated management platform consisting of:
- **Web Application** (Laravel 11 - public-facing dashboard)
- **Mobile Application** (Flutter - consumer-facing app)
- **Shared Database** (MySQL/MariaDB)
- **API Layer** (Laravel REST API)

---

## Architecture

### Tech Stack

**Backend:**
- Laravel 11 (PHP framework)
- MySQL/MariaDB database
- RESTful API
- Vite (asset bundling)
- Tailwind CSS

**Frontend - Web:**
- Laravel Blade templates
- Alpine.js for interactivity
- Tailwind CSS for styling
- Vite-compiled assets

**Frontend - Mobile:**
- Flutter (Dart)
- HTTP client for API requests
- SQLite for local caching
- Device storage for offline data

---

## Deployment Structure (Hostinger)

```
Domain: poultryandsupplies.com
├── public_html/                    (Web app public files)
│   ├── index.php                   (Entry point)
│   ├── build/
│   │   └── manifest.json           (Vite manifest)
│   ├── assets/                     (CSS/JS compiled)
│   │   ├── app-X_LkV_kk.css
│   │   └── app-4u5Jb2Nr.js
│   ├── .htaccess
│   ├── robots.txt
│   └── favicon.ico
│
└── Main Directory (Laravel app root)
    ├── app/                        (Controllers, Models, Middleware)
    ├── bootstrap/
    ├── config/
    ├── database/                   (Migrations, Seeders)
    ├── resources/
    │   ├── css/
    │   ├── js/
    │   └── views/                  (Blade templates)
    ├── routes/
    │   ├── web.php                 (Web routes)
    │   ├── api.php                 (API routes)
    │   └── auth.php                (Auth routes)
    ├── storage/
    │   ├── app/
    │   ├── framework/
    │   │   └── views/              (Compiled views cache)
    │   └── logs/                   (laravel.log)
    ├── vendor/                     (Composer packages)
    ├── .env                        (Environment config)
    ├── .env.example
    ├── composer.json
    ├── vite.config.js
    ├── tailwind.config.js
    └── package.json
```

---

## Subscription & Pricing System

### **Subscription Tiers for Farm Owners**

#### **Tier 1: FREE PLAN (3 Months Trial)**
- **Cost:** FREE
- **Product Limit:** 1 product
- **Order Limit:** 10 orders/month
- **Commission Rate:** 0% (no fees)
- **Duration:** 3 months (automatic)
- **Status:** Available to all new farm owners
- **Features:**
  - Basic farm monitoring
  - Limited inventory tracking
  - Manual order processing
  - No commission on sales
  - Basic support

**Auto-Assignment:** New farm owners automatically receive FREE plan on signup

---

#### **Tier 2: STARTER PLAN**
- **Cost:** ₱100/month
- **Product Limit:** 2 products
- **Order Limit:** 50 orders/month
- **Commission Rate:** 5%
- **Unlock After:** Free plan expires
- **Features:**
  - All Free Plan features
  - Up to 2 products
  - Better order management
  - Dashboard analytics
  - Email support

---

#### **Tier 3: PROFESSIONAL PLAN**
- **Cost:** ₱500/month
- **Product Limit:** 10 products
- **Order Limit:** 200 orders/month
- **Commission Rate:** 3% (2% discount vs Starter)
- **Features:**
  - All Starter features
  - Up to 10 products
  - Advanced analytics
  - Priority support
  - High-volume selling

---

#### **Tier 4: ENTERPRISE PLAN**
- **Cost:** ₱1,200/month
- **Product Limit:** Unlimited
- **Order Limit:** Unlimited
- **Commission Rate:** 1.5% (lowest rate)
- **Features:**
  - All Professional features
  - Unlimited products
  - Unlimited monthly orders
  - Dedicated account manager
  - Custom integrations
  - 24/7 priority support

---

### **Subscription Workflow**

```
NEW FARM OWNER REGISTRATION
├── Account created
├── Farm profile setup
└── AUTO-ASSIGN FREE PLAN (3 months)
    ├── No payment required
    ├── Limits: 1 product, 10 orders/month
    ├── Can start selling immediately
    └── Expires in 90 days

3 MONTHS LATER (Free plan expires)
├── Reminder notification (7 days before)
├── Subscription page displays all plans
├── Shows pricing for each tier
├── Farm owner chooses plan:
│   ├── Starter: ₱100/month
│   ├── Professional: ₱500/month
│   ├── Enterprise: ₱1,200/month
│   └── Or renew Free Plan (if available)
├── Redirect to PayMongo payment
├── Payment confirmation
├── Subscription activated
└── Increased limits take effect

MONTHLY RENEWAL
├── Automatic payment via PayMongo (if enabled)
├── Renewal reminder 7 days before expiry
├── Payment receipt sent
├── Subscription extended
└── Limits remain active

PLAN UPGRADE (Anytime)
├── Farm owner browses subscription page
├── Selects higher-tier plan
├── PayMongo charges difference (if applicable)
├── Plan upgraded immediately
├── Limits updated in real-time
└── Dashboard refreshes with new features
```

---

### **Subscription Management in Web Dashboard**

**Farm Owner View:**
```
Subscriptions Page
├── Current Subscription Card:
│   ├── Plan type (FREE, Starter, Professional, Enterprise)
│   ├── Monthly cost
│   ├── Days remaining
│   ├── Renewal date
│   ├── Product limit: X/limit
│   ├── Order limit: X/limit remaining this month
│   └── Status badge (Active, Expiring Soon, Expired)
│
├── Plan Limits Panel:
│   └── Shows current usage:
│       ├── Products: 1/1 (100% used)
│       ├── Orders this month: 8/10 (80% used)
│       ├── Commission rate: 0% (FREE plan)
│       └── Storage: X GB used
│
├── Action Buttons:
│   ├── "Renew Subscription" (if expiring)
│   ├── "Upgrade Plan" (if not on Enterprise)
│   ├── "Downgrade Plan" (available on active plan)
│   └── "Pause Subscription" (temporarily disable)
│
├── Available Plans Section:
│   └── Show all tiers with:
│       ├── Plan name & cost
│       ├── Features list
│       ├── Comparison vs current plan
│       └── "Upgrade to [Plan]" button
│
└── Billing History:
    ├── Past invoices list
    ├── Payment method on file
    ├── Download receipts (PDF)
    └── Manage auto-renewal settings
```

---

### **Pricing Display to Consumers**

**Before Free Plan Expires:**
- Mobile app shows: "FREE PLAN ACTIVE"
- No pricing display
- Farm owner can sell without payment

**After Subscription:**
- Dashboard shows: "Plan: Starter - ₱100/month"
- Pricing appears in subscription panel
- Product limits displayed: "2/2 products (upgrade to add more)"
- Order limits shown: "45/50 orders remaining this month"
- Upgrade prompts when limits are close

---

### **Payment Processing**

**Payment Gateway:** PayMongo

```
User selects plan
↓
Redirect to PayMongo Checkout
├── For FREE: Skip payment → Auto-activate
├── For PAID: Show payment form
│   ├── Card details entry
│   ├── OTP verification
│   └── Payment confirmation
↓
Webhook verification
↓
Subscription activated/renewed
↓
Confirmation email sent
↓
Dashboard updated with new limits
```

---

### **Upgrade/Downgrade Process**

**Upgrade (e.g., Free → Starter)**
1. Farm owner clicks "Upgrade to Starter"
2. Accepts new plan terms (₱100/month)
3. PayMongo processes payment
4. Subscription updates immediately
5. Limits increase from 1→2 products, 10→50 orders
6. Additional features unlock

**Downgrade (e.g., Professional → Starter)**
1. Farm owner clicks "Downgrade to Starter"
2. Confirms action (warning if over new limits)
3. Refund calculated for unused time (if applicable)
4. Payment processed
5. Plan downgraded at end of billing period
6. Limits reduced on next renewal

---

### **Database Schema**

**Subscriptions Table:**
```
id
farm_owner_id (foreign key)
plan_type (enum: free, starter, professional, enterprise)
monthly_cost (decimal)
product_limit (nullable - unlimited if null)
order_limit (nullable - unlimited if null)
commission_rate (decimal: 0, 3, 5, 1.5)
status (enum: active, paused, cancelled, expired)
started_at (timestamp)
ends_at (timestamp)
renewal_at (timestamp)
paymongo_subscription_id (nullable)
paymongo_payment_method_id (nullable)
created_at
updated_at
deleted_at (soft deletes)
```

---

## Application Modules

### 1. **Farm Operations**
- Farm owner dashboard
- Flock management
- Chicken/Egg monitoring
- Daily records tracking
- Performance analytics

**Models:**
- `FarmOwner`
- `Flock`
- `ChickenMonitoring`
- `EggMonitoring`
- `FlockRecord`

### 2. **Inventory Management**
- Stock tracking (feed, supplies, medicines)
- Inventory adjustments
- Usage monitoring

**Models:**
- `Inventory`

### 3. **Employee/HR Management**
- Employee records
- Attendance tracking
- Payroll management
- Role-based access control

**Models:**
- `Employee`
- `Attendance`
- `Payroll`

### 4. **Order & Delivery (Marketplace)**
- Consumer orders
- Order management
- Delivery tracking
- Driver management

**Models:**
- `Order`
- `OrderItem`
- `Delivery`
- `Driver`

### 5. **Financial Management**
- Income tracking
- Expense recording
- Financial reports

**Models:**
- `IncomeRecord`
- `Expense`

### 6. **Communication**
- Internal messaging
- Notifications
- Client requests

**Models:**
- `InternalMessage`
- `Notification`
- `ClientRequest`

### 7. **Consumer Marketplace (Mobile App)**
- Product browsing
- Orders & subscriptions
- Delivery tracking
- Account management
- Verification & authentication

---

## User Roles & Access

### Web Application
1. **Super Admin** - Full system access
2. **Farm Owner** - Farm management + dashboard
3. **HR Manager** - Employee & payroll management
4. **Inventory Manager** - Stock management
5. **Delivery Manager** - Orders & delivery tracking

### Mobile Application
1. **Consumer** - Browse products, place orders, track delivery
2. **Anonymous** - Browse public listings

---

## API Endpoints Structure

**Base URL:** `https://poultryandsupplies.com/api/`

### Authentication
- `POST /login` - User login
- `POST /register` - Consumer registration
- `POST /logout` - User logout
- `POST /refresh-token` - Token refresh

### Farm Operations (Web)
- `GET/POST /farms` - Farm data
- `GET/POST /flocks` - Flock management
- `GET/POST /monitoring/chicken` - Chicken monitoring
- `GET/POST /monitoring/eggs` - Egg monitoring

### Orders & Delivery (Mobile/Web)
- `GET /products` - List products
- `POST /orders` - Create order
- `GET /orders/{id}` - Order details
- `GET /deliveries` - Delivery tracking

### Inventory
- `GET/POST /inventory` - Stock management
- `POST /inventory/adjust` - Adjust inventory

### Employee/Payroll
- `GET/POST /employees` - Employee records
- `POST /attendance` - Record attendance
- `GET/POST /payroll` - Payroll management

---

## Database Schema (Key Tables)

| Table | Purpose |
|-------|---------|
| `users` | All system users |
| `farms` | Farm records |
| `flocks` | Poultry flocks |
| `chicken_monitoring` | Daily chicken data |
| `egg_monitoring` | Egg production tracking |
| `employees` | Employee records |
| `attendance` | Attendance logs |
| `payroll` | Salary/payment records |
| `inventory` | Stock inventory |
| `orders` | Customer orders |
| `order_items` | Order line items |
| `deliveries` | Delivery records |
| `drivers` | Delivery driver info |
| `income_records` | Income tracking |
| `expenses` | Expense records |

---

## Workflow Processes

### 1. **Consumer Order Process**
1. Consumer opens mobile app
2. Browses products
3. Adds items to cart
4. Submits order
5. Payment processing (PayMongo integration)
6. Notification sent to farm
7. Order picked & packed
8. Driver assigned
9. Consumer receives delivery notification
10. Delivery completed

### 2. **Farm Production Workflow**
1. Farm owner logs flock info
2. Daily monitoring entries (chicken count, egg production, health)
3. System calculates trends
4. Alerts for abnormal data
5. Records saved to database
6. Reports generated

### 3. **Employee Payroll**
1. HR tracks daily attendance
2. End of pay period: Generate payroll
3. Calculate deductions & overtime
4. Generate reports
5. Process payment
6. Archive records

### 4. **Inventory Management**
1. Stock received → Log entry
2. Items used → Record usage
3. Low stock alerts triggered
4. Reorder placed
5. New stock received → Update inventory

---

## Deployment Process

### Local Development to Hostinger

1. **Build assets locally**
   ```bash
   npm run build
   ```
   Creates: `public/build/manifest.json` + compiled assets

2. **Upload to Hostinger**
   - Main Laravel app → Main directory
   - `public_html/` contents → Domain's `public_html/`
   - Include: `index.php`, `build/`, `assets/`, `.htaccess`

3. **Configure Environment**
   - Set `.env` with database credentials
   - Set `APP_URL` to domain
   - Generate `APP_KEY`

4. **Set Permissions**
   - `storage/` → 775
   - `bootstrap/cache/` → 775

5. **Verify**
   - Check `storage/logs/laravel.log` for errors
   - Test web app & API endpoints

---

## Inter-Role Relationships & Data Flow

### 1. **Farm Owner ↔ HR Manager**

```
Farm Owner
├── Hires/deploys employees on farm
├── Assigns roles to workers
├── Monitors daily operations
└── Reviews employee performance
     ↓
HR Manager
├── Records employee details
├── Tracks attendance (input from farm operations)
├── Calculates payroll based on attendance
├── Manages leave & benefits
└── Generates salary reports
     ↓
Farm Owner
└── Approves & reviews payroll
```

**Data Flow:**
- Farm Owner inputs: Employee assignments, task allocations
- HR calculates: Wages based on attendance records
- Finance records: Payroll expenses

---

### 2. **Farm Owner ↔ Finance**

```
Farm Operations (Farm Owner Domain)
├── Daily production (eggs, chickens)
├── Inventory usage (feed, supplies)
├── Consumer orders (through mobile app)
└── Sales revenue
     ↓
Finance Module
├── Records income from sales
├── Tracks production costs
├── Expense management
└── Profitability analysis
     ↓
Reports to Farm Owner
├── Daily/Weekly/Monthly P&L
├── Revenue trends
├── Cost analysis
└── ROI calculations
```

**Data Flow:**
- Farm Owner executes: Production tasks, sales
- Finance tracks: All income/expense transactions
- Reports back: Financial health metrics

---

### 3. **HR Manager ↔ Finance**

```
HR Operations
├── Employee records
├── Attendance tracking
├── Overtime/benefits calculation
└── Salary structure management
     ↓
Finance Module
├── Records payroll expenses
├── Tracks benefits costs
├── Tax/deduction calculations
├── Payment processing
└── Financial records
     ↓
Reports to HR
├── Payroll status
├── Cost per employee
├── Budget utilization
└── Compliance records
```

**Data Flow:**
- HR provides: Attendance, shifts, overtime hours
- Finance calculates: Total payroll cost
- Verifies: Payments processed correctly

---

### 4. **Complete Workflow Integration**

```
┌─────────────────────────────────────────────────────────────┐
│                    FARM OWNER (Central)                      │
│  - Manages farm operations, employees, sales, inventory      │
└────────────────┬──────────────────────────┬──────────────────┘
                 │                          │
         ┌───────▼─────────┐        ┌─────▼──────────┐
         │   HR MANAGER    │        │  FINANCE MGR   │
         │                 │        │                │
         │ • Attendance    │        │ • Income       │
         │ • Payroll calc. │◄─────►│ • Expenses     │
         │ • Benefits      │        │ • Payroll      │
         │ • Reports       │        │ • Reporting    │
         └─────────────────┘        └────────────────┘
                 △                          △
                 │                          │
         Input: Daily Operations   Input: Sales & Production
```

---

### 5. **Data Dependencies**

| Process | Depends On | Provides To |
|---------|-----------|------------|
| **Daily Monitoring** | Flock data, Employee performance | Finance (cost tracking), HR (attendance) |
| **Payroll Processing** | Attendance records, Employee salary structure | Finance (expense data), Farm Owner (cost review) |
| **Financial Reporting** | Sales data, Payroll records, Expenses | Farm Owner (P&L), HR (budget), Management (decisions) |
| **Inventory Management** | Production needs, Past usage | Finance (cost), HR (supply availability) |
| **Order Fulfillment** | Available inventory, Assigned staff | Finance (revenue), HR (workload) |

---

### 6. **Monthly Business Cycle**

```
Week 1: Production & Operations
├── Farm Owner: Daily monitoring, employee assignments
├── Production recorded (eggs, chickens sold)
└── Inventory tracked

Week 2-3: Hiring & HR Activities
├── HR: Reviews attendance
├── Calculates overtime, bonuses, deductions
├── Farm Owner: Approves changes
└── Finance: Prepares payroll data

Week 4: Financial Closing
├── Finance: Compiles all expenses & income
├── HR: Finalizes payroll
├── Farm Owner: Reviews complete P&L
├── Reports generated
└── Payroll processed & distributed
```

---

### 7. **Key Business Metrics**

**Tracked by Farm Owner:**
- Production output (eggs/chickens)
- Sales revenue
- Employee utilization
- Operational efficiency

**Calculated by Finance:**
- Net profit/loss
- Cost per unit produced
- Revenue per employee
- Expense distribution

**Managed by HR:**
- Attendance rate
- Productivity per employee
- Payroll cost
- Staff turnover

---

## Product Management Process

### 1. **Product Addition & Setup**

**Who:** Farm Owner / Inventory Manager (Web)

```
Farm Owner/Admin
├── Accesses Product Management section
├── Clicks "Add New Product"
├── Enters product details:
│   ├── Product name (e.g., "Fresh Brown Eggs")
│   ├── SKU/Product code
│   ├── Description
│   ├── Category (Eggs, Chicken, Supplies, etc.)
│   ├── Unit type (dozen, kg, pack, etc.)
│   ├── Base price per unit
│   ├── Upload product images
│   └── Product specifications
├── Sets availability (Active/Inactive)
└── Saves product to database
```

**Database Entry:**
- Product created in `products` table
- Attributes stored: name, sku, category, unit_type, base_price, description, images

---

### 2. **Quantity Management**

```
INVENTORY SYSTEM
├── Initial Stock Entry
│   ├── Source: Production (farm output) or Purchase
│   ├── Quantity added to inventory
│   ├── Batch tracking (expiry dates for perishables)
│   ├── Storage location recorded
│   └── Entry logged with timestamp
│
├── Real-time Quantity Updates
│   ├── Consumer order placed → Stock decreases
│   ├── New production recorded → Stock increases
│   ├── Damage/wastage recorded → Stock decreases
│   ├── Reorder point triggers alert if qty < threshold
│   └── Dashboard shows live stock levels
│
└── Stock Visibility (Web Dashboard)
    ├── Total available qty per product
    ├── Reserved qty (pending orders)
    ├── Available for sale qty
    ├── Expiry date tracking
    └── Low stock alerts
```

**Models Involved:**
- `Inventory` - Stock tracking
- `Product` - Product master data
- `OrderItem` - Link between orders and products

---

### 3. **Bulk Operations**

```
BULK PRODUCT MANAGEMENT
├── Bulk Upload
│   ├── CSV/Excel template download
│   ├── Fill multiple products at once
│   ├── Upload file to system
│   ├── System validates all entries
│   ├── Preview before committing
│   └── Batch create products
│
├── Bulk Price Adjustment
│   ├── Select products by category
│   ├── Apply percentage increase/decrease
│   ├── Preview price changes
│   ├── Confirm & update all at once
│   └── History logged for audit
│
├── Bulk Quantity Update
│   ├── Select multiple products
│   ├── Add/subtract quantity from batch
│   ├── Mark bulk as processing
│   └── Update all inventory records
│
├── Bulk Status Change
│   ├── Activate/Deactivate multiple products
│   ├── Move products in/out of promotions
│   └── Batch operations logged
│
└── Bulk Reorder
    ├── Auto-reorder low stock items
    ├── Generate purchase orders
    └── Notify suppliers
```

---

## Mobile App Client Experience (Consumer)

### **User Journey: Browse → Order → Track → Receive**

```
┌────────────────────────────────────────────────────────────┐
│                CONSUMER MOBILE APP FLOW                     │
└────────────────────────────────────────────────────────────┘

STEP 1: LAUNCH & AUTHENTICATION
├── Open app
├── Check if logged in
│   ├── New user? Create account (phone, email, verification code)
│   └── Existing? Skip to dashboard
├── Verification (SMS/OTP)
└── Enter home screen

STEP 2: HOME DASHBOARD
├── Display:
│   ├── Available products (eggs, chicken, supplies)
│   ├── Special offers/promotions
│   ├── Featured products
│   ├── Search bar
│   ├── Categories (Eggs, Chicken, Supplies, etc.)
│   └── Quick cart badge (number of items)
├── User can:
│   ├── Search by name
│   ├── Filter by category
│   ├── Sort (price, rating, new)
│   └── View product details

STEP 3: PRODUCT BROWSING
├── Product card shows:
│   ├── Product image
│   ├── Name & description
│   ├── Price per unit
│   ├── Stock status (In stock/Low stock)
│   ├── Ratings & reviews
│   ├── Available quantity selector
│   └── "Add to Cart" button
├── User clicks product:
│   ├── Detailed view opens
│   ├── Large image gallery
│   ├── Full description & specs
│   ├── Bulk options (if available)
│   ├── Quantity increment/decrement
│   └── Add to Cart / Add to Wishlist

STEP 4: BULK PURCHASING
├── Product detail page shows:
│   ├── Single unit price: Php 5.00/egg
│   ├── Bulk discounts:
│   │   ├── 1 dozen (12): Php 55.00
│   │   ├── 5 dozens (60): Php 270.00 (10% off)
│   │   ├── 10 dozens (120): Php 500.00 (16% off)
│   │   └── 30 dozens (360): Php 1,450.00 (19% off)
│   ├── Weight/total quantity displayed
│   ├── Delivery cost estimate
│   └── Estimated total shown
├── User selects bulk quantity
├── System updates price breakdown
└── Confirms & adds to cart

STEP 5: SHOPPING CART
├── Cart shows:
│   ├── All items added
│   ├── Quantity per item
│   ├── Price per item
│   ├── Subtotal
│   ├── Delivery fee estimate
│   ├── Total amount
│   ├── Promo code input field
│   └── "Proceed to Checkout" button
├── User can:
│   ├── Increase/decrease quantity
│   ├── Remove items
│   ├── Apply promo/discount code
│   └── Save for later

STEP 6: CHECKOUT
├── Order summary displayed
├── Delivery address selection:
│   ├── Select saved address OR
│   ├── Enter new delivery address
│   ├── Map selection available
│   ├── Contact number verification
│   └── Special instructions for delivery (gate code, etc.)
├── Payment method selection:
│   ├── Online payment (PayMongo)
│   ├── Cash on delivery (if available)
│   ├── Wallet balance (if feature enabled)
│   └── Installment options
├── Final confirmation:
│   ├── All details reviewed
│   ├── Best before date shown
│   ├── Delivery time estimate
│   └── "Place Order" button

STEP 7: PAYMENT
├── Redirect to PayMongo (if online payment)
├── Enter card details
├── OTP verification
├── Payment confirmation
└── Return to app with success message

STEP 8: ORDER CONFIRMATION
├── Order number generated
├── Screen shows:
│   ├── Order ID
│   ├── Items ordered
│   ├── Total amount paid
│   ├── Delivery address
│   ├── Expected delivery date/time
│   ├── Seller contact info
│   └── Customer service chat option
├── Push notification sent
├── Email confirmation sent
└── User redirected to "My Orders" tab

STEP 9: ORDER TRACKING
├── Real-time status updates:
│   ├── Order received
│   ├── Preparing/Packing
│   ├── Ready for delivery
│   ├── With driver (location map)
│   ├── Arriving soon
│   └── Delivered
├── Timeline view showing each milestone
├── Driver info displayed (name, photo, phone)
├── Live tracking map (showing driver's location)
├── Estimated arrival time countdown
├── Chat with driver/seller option

STEP 10: DELIVERY & COMPLETION
├── Driver arrives (notification sent)
├── Customer confirms receipt in app
├── Rating & review prompt:
│   ├── Product rating (1-5 stars)
│   ├── Delivery rating (1-5 stars)
│   ├── Written review/feedback
│   ├── Photos (optional)
│   └── Submit
├── Order marked as complete
├── Feedback visible to seller
└── Recommendation engine suggests similar products

STEP 11: ACCOUNT MANAGEMENT
├── User can access:
│   ├── Order history
│   ├── Wishlist/Favorites
│   ├── Profile (edit, change password)
│   ├── Saved addresses
│   ├── Payment methods
│   ├── Loyalty points/wallet
│   ├── Notifications settings
│   ├── Help & Support
│   └── Logout
```

---

## Web App Client Experience (Farm Owner/Admin)

### **Farm Owner Dashboard Journey**

```
┌────────────────────────────────────────────────────────────┐
│              FARM OWNER WEB DASHBOARD FLOW                  │
└────────────────────────────────────────────────────────────┘

STEP 1: LOGIN
├── Navigate to web app
├── Enter credentials (email/phone + password)
├── OTP verification (if enabled)
├── Redirect to dashboard

STEP 2: MAIN DASHBOARD (KPI Overview)
├── Top metrics displayed:
│   ├── Today's Production (eggs collected, chickens count)
│   ├── Total Revenue (today, this month)
│   ├── Pending Orders (count + alert)
│   ├── Active Employees (on-site)
│   ├── Inventory Status (low stock alerts)
│   └── System Health
├── Charts & graphs:
│   ├── Production trends (last 30 days)
│   ├── Revenue vs Expenses (monthly)
│   ├── Employee attendance rate
│   ├── Top selling products
│   └── Customer acquisition funnel
├── Quick action buttons:
│   └── New order, Add product, Record attendance, etc.

STEP 3: FARM OPERATIONS
├── Flock Management:
│   ├── View all active flocks
│   ├── Add new flock (breed, count, date started)
│   ├── Edit flock details
│   ├── View flock health metrics
│   └── Historical records
├── Daily Monitoring:
│   ├── Enter chicken count
│   ├── Log production (eggs collected)
│   ├── Record health issues
│   ├── Feed consumption logged
│   └── Water usage tracked
├── Monitoring data displayed as:
│   ├── Daily entries list
│   ├── Trend graphs (production over time)
│   ├── Alerts for abnormal data
│   └── Export options (PDF, CSV)

STEP 4: INVENTORY & PRODUCT MANAGEMENT
├── Inventory Dashboard:
│   ├── Stock levels (all products)
│   ├── Low stock alerts
│   ├── Expiry date tracking
│   ├── Storage location mapping
│   └── Stock value (total inventory cost)
├── Product Management:
│   ├── View all products
│   ├── Add new product:
│   │   ├── Name, SKU, category
│   │   ├── Unit type, base price
│   │   ├── Upload images
│   │   └── Save
│   ├── Edit product details
│   ├── Bulk upload (CSV)
│   ├── Bulk operations:
│   │   ├── Price adjustment
│   │   ├── Quantity update
│   │   ├── Activate/Deactivate
│   │   └── Generate barcodes
│   └── Archive/Delete products
├── Quantity Management:
│   ├── Manual stock adjustment
│   ├── Add stock from production
│   ├── Use inventory (quantity consumed)
│   ├── Damage/Wastage log
│   ├── Batch tracking (for perishables)
│   └── Audit trail (all changes logged)

STEP 5: ORDER MANAGEMENT
├── Orders Dashboard:
│   ├── New orders (real-time notification)
│   ├── Order status pipeline:
│   │   ├── Pending (new orders)
│   │   ├── Processing (payment confirmed)
│   │   ├── Packing (items being packaged)
│   │   ├── Ready (ready to ship)
│   │   ├── Shipped (with driver)
│   │   └── Delivered (completed)
│   └── Filter by status, date, customer
├── Order Details:
│   ├── Customer name, address, phone
│   ├── Items ordered (quantity, price)
│   ├── Order total & payment status
│   ├── Delivery address map view
│   ├── Special instructions
│   └── Customer notes
├── Order Actions:
│   ├── Confirm payment
│   ├── Mark as processing
│   ├── Print packing slip
│   ├── Assign to driver
│   ├── Update status
│   ├── Contact customer
│   └── Cancel/Refund options

STEP 6: DRIVER & DELIVERY MANAGEMENT
├── Driver List:
│   ├── Active drivers (online/offline status)
│   ├── Driver profile (name, phone, vehicle)
│   ├── Performance metrics (deliveries completed, ratings)
│   └── Add new driver
├── Delivery Assignments:
│   ├── View ready orders
│   ├── Assign orders to drivers
│   ├── Route optimization map
│   ├── Delivery tracking real-time
│   ├── Customer notifications automatic
│   └── Delivery completion confirmation

STEP 7: EMPLOYEE & HR MANAGEMENT
├── Employee Directory:
│   ├── List all employees
│   ├── Filter by department, role, status
│   ├── Employee profile:
│   │   ├── Bio, contact info, benefits
│   │   ├── Current role & salary
│   │   ├── Start date
│   │   └── Performance rating
│   └── Add/Edit employee info
├── Attendance Tracking:
│   ├── Daily attendance records
│   ├── Mark present/absent/leave
│   ├── Overtime hours
│   ├── Attendance report (monthly)
│   └── Alert on high absence rate
├── Payroll Management:
│   ├── View all employees' salaries
│   ├── Calculate payroll (automatic from attendance)
│   ├── Deductions & bonuses
│   ├── Generate payslips
│   ├── Process payment
│   └── Payroll history

STEP 8: FINANCIAL TRACKING
├── Income Management:
│   ├── Daily sales revenue
│   ├── Revenue by product category
│   ├── Top selling products
│   ├── Revenue trend (graph)
│   └── Invoice generation
├── Expense Management:
│   ├── Log expenses (feed, supplies, utilities)
│   ├── Expense categories
│   ├── Recurring expenses
│   ├── Expense trend analysis
│   └── Budget vs actual
├── Financial Reports:
│   ├── Profit & Loss statement
│   ├── Revenue vs Expenses comparison
│   ├── Cost per unit produced
│   ├── Monthly/Quarterly/Annual reports
│   ├── Export (PDF, Excel)
│   └── Print for records

STEP 9: REPORTING & ANALYTICS
├── Dashboard Reports:
│   ├── Production efficiency
│   ├── Sales performance
│   ├── Employee productivity
│   ├── Customer satisfaction (ratings)
│   ├── Inventory turnover
│   └── Financial health
├── Custom Reports:
│   ├── Select date range
│   ├── Choose metrics
│   ├── Generate custom report
│   ├── Export options
│   └── Schedule reports (email)
├── Data Visualization:
│   ├── Charts (line, bar, pie)
│   ├── Heatmaps (by time/location)
│   ├── KPI cards
│   └── Comparison views

STEP 10: SETTINGS & ADMIN
├── Account Settings:
│   ├── Profile edit
│   ├── Password change
│   ├── Two-factor authentication
│   └── Session management
├── Farm Settings:
│   ├── Farm name & details
│   ├── Operating hours
│   ├── Delivery zones
│   ├── Contact info
│   └── Bank details (for payouts)
├── System Settings:
│   ├── User roles & permissions
│   ├── Enable/Disable features
│   ├── Integration settings
│   ├── Notification preferences
│   └── Data backup
├── User Management (Admin):
│   ├── Create/Edit user accounts
│   ├── Assign roles
│   ├── View activity logs
│   ├── Deactivate users
│   └── Set permissions
```

---

## Complete Product-to-Customer Flow

```
1. PRODUCTION
   Farm Owner records daily production
   └─→ Eggs: 500 units, Chickens: 8 units

2. INVENTORY ENTRY
   Add to inventory system
   └─→ Inventory increased, expiry date tracked

3. PRODUCT LISTING
   Product available on mobile app
   ├─→ Fresh Brown Eggs (12pcs per dozen)
   ├─→ Price: Php 60/dozen
   └─→ Stock: 42 dozens available

4. BULK PRICING APPLIED
   System shows options:
   ├─→ 1 dozen: Php 60
   ├─→ 5 dozens: Php 280 (7% discount)
   └─→ 10 dozens: Php 540 (10% discount)

5. CONSUMER ORDERS
   ├─→ Customer A buys 5 dozens (Php 280)
   └─→ Stock reduced to 37 dozens

6. ORDER PROCESSING
   ├─→ Web dashboard shows new order
   ├─→ Payment confirmed
   ├─→ Packing slip printed
   └─→ Order marked "Ready for delivery"

7. FINANCIAL RECORDING
   ├─→ Revenue: +Php 280 (income record)
   ├─→ COGS: -Php 150 (cost of goods)
   ├─→ Profit: +Php 130 (tracked in finance)
   └─→ Included in daily P&L report

8. DELIVERY
   ├─→ Driver assigned
   ├─→ Customer tracks real-time
   ├─→ Delivery completed
   └─→ Customer rates & reviews

9. INVENTORY CLOSING
   ├─→ Stock reconciled
   ├─→ Sold qty recorded
   ├─→ Running inventory updated
   └─→ Expiry dates monitored

10. MONTHLY REPORTS
    ├─→ Farm Owner reviews performance
    ├─→ HR finalizes payroll
    ├─→ Finance closes books
    └─→ Next cycle begins
```

---

### Farm Owner (Web)
- Dashboard with KPIs
- Flock management
- Daily monitoring entries
- Performance reports
- Inventory overview

### Consumer (Mobile App)
- Browse available products
- Place orders
- Track delivery in real-time
- View order history
- Manage account
- Verification/authentication

### HR Manager (Web)
- Employee directory
- Attendance tracking
- Payroll processing
- Reports & analytics

### Admin (Web)
- System configuration
- User management
- Financial reports
- System health monitoring

---

## Error Handling & Debugging

**Common Issues:**

1. **500 Error on Login**
   - Check `storage/logs/laravel.log`
   - Verify Vite manifest exists
   - Confirm database connection

2. **Vite Manifest Not Found**
   - Ensure `public_html/build/manifest.json` exists
   - Check asset paths are correct
   - Verify `path.public` binding in `index.php`

3. **Database Connection Issues**
   - Verify `.env` credentials
   - Check MySQL server status
   - Confirm database user permissions

---

## Security Measures

- CSRF token validation
- Password encryption (bcrypt)
- Session management
- API token authentication
- Role-based access control (RBAC)
- SQL injection prevention (Eloquent ORM)
- HTTPS enforcement
- PayMongo webhook verification

---

## Performance Optimization

- Vite for asset compilation
- Database query optimization
- Laravel caching
- Mobile app offline capability
- API pagination
- Lazy loading on mobile

---

## Maintenance & Monitoring

- **Logs:** `storage/logs/laravel.log`
- **Database Backups:** Regular exports
- **Asset Caching:** Vite handles versioning
- **API Rate Limiting:** Configured for security

---

## Future Enhancements

- SMS notifications
- Advanced analytics dashboard
- Machine learning for production predictions
- IoT sensor integration
- Multi-language support
- Enhanced mobile app features

---

**Last Updated:** March 23, 2026
**Version:** 2.0 (Web + Mobile)
