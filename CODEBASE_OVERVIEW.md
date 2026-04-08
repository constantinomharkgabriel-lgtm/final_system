# Poultry System - Comprehensive Codebase Overview

## Executive Summary

The Poultry System is a **B2B2C e-commerce and farm management platform** built with:
- **Backend**: Laravel 12 with PHP 8.5.2
- **Database**: PostgreSQL (Supabase)
- **Frontend**: Blade templates + Tailwind CSS (Web)
- **Mobile**: Flutter (Consumer App)

The system manages farm operations, inventory, HR/payroll, finance, logistics, and sales across multiple user roles.

---

## 1. Overall System Architecture

### Architecture Pattern: Multi-Portal, Role-Based Access

The system implements a **Hub-and-spoke architecture** with multiple portals:

```
┌─────────────────────────────────────────┐
│      Laravel Backend (API + Web)        │
├─────────────────────────────────────────┤
│   Authentication & Authorization        │
│   (Role-based, Token-based, Session)    │
└──────────────┬──────────────────────────┘
       │
       ├──────────────────┬──────────────────┬──────────────────┐
       │                  │                  │                  │
   ┌────────────┐   ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
   │ Web Portal │   │ Mobile App   │  │  API Routes  │  │ Admin Portal │
   ├────────────┤   ├──────────────┤  ├──────────────┤  ├──────────────┤
   │ Blade      │   │ Flutter      │  │ JSON/REST    │  │ SuperAdmin   │
   │ Tailwind   │   │ Secure Token │  │ Bearer Auth  │  │ Dashboard    │
   │ Tailwind   │   │ Storage      │  │ Mobile Auth  │  │              │
   └────────────┘   └──────────────┘  └──────────────┘  └──────────────┘
```

### User Role Hierarchy

**1. SuperAdmin**
- System-wide management
- Approve/reject farm owner registrations
- Manage all subscriptions and users
- Dashboard: `/super-admin/dashboard`

**2. Farm Owner (Client)**
- Own farm management and operations
- Employee and payroll management
- Product/inventory management
- Dashboard: `/farm-owner/dashboard`

**3. Department Roles** (Farm Owner → Department Employees)
- `farm_operations`
- `hr`
- `finance`
- `logistics`
- `sales`
- `admin`

**4. Consumer**
- Purchase products from marketplace
- View order history
- Mobile app registration
- Dashboard: `/dashboard`

---

## 2. Mobile Consumer App Structure

### Location
`/poultry_consumer_app` - Flutter project

### Key Files & Directories

```
poultry_consumer_app/
├── lib/
│   ├── main.dart                     # App entry point
│   ├── src/
│   │   ├── app.dart                  # Material app configuration
│   │   ├── screens/
│   │   │   ├── consumer_login_screen.dart
│   │   │   ├── consumer_register_screen.dart      # (if implemented)
│   │   │   └── marketplace_screen.dart
│   │   ├── services/
│   │   │   ├── api_service.dart      # HTTP client with bearer token auth
│   │   │   ├── session_storage_service.dart
│   │   │   └── session_storage_service_mobile.dart
│   │   ├── models/
│   │   │   ├── consumer_session.dart
│   │   │   ├── product.dart
│   │   │   ├── order.dart
│   │   │   ├── cart_item.dart
│   │   │   └── notification.dart
│   │   ├── config/
│   │   │   └── app_config.dart       # API base URL configuration
│   │   ├── components/               # UI widgets
│   │   └── pages/                    # Full screen pages
├── pubspec.yaml                      # Flutter dependencies
├── android/                          # Android native config
├── ios/                              # iOS native config
└── README.md
```

### Dependencies
- `http: ^1.5.0` - HTTP client for API calls
- `flutter_secure_storage: ^9.2.4` - Secure token persistence
- `url_launcher: ^6.3.2` - Deep linking support
- `cupertino_icons: ^1.0.8` - iOS-style icons

### Authentication Flow (Mobile)

1. **Login** → `POST /api/mobile/auth/login`
   - Email + Password submitted
   - Returns: `bearer_token + 30-day expiration`
   - Token stored in `flutter_secure_storage`

2. **Session Persistence**
   - Token auto-restored on app launch
   - 30-day validity or until logout

3. **API Calls**
   - All authenticated requests include: `Authorization: Bearer {token}`
   - Middleware `mobile.auth` validates token

---

## 3. Web Marketplace Structure

### Location
`/resources/views/marketplace/` - Web marketplace views
`/app/Http/Controllers/ConsumerPortalController.php` - Web portal controller

### Key Components

**Web Marketplace Views:**
```
resources/views/marketplace/
├── notifications.blade.php      # Product notifications
├── ratings.blade.php            # Consumer ratings UI
├── profile-edit.blade.php       # Profile management
└── partials/                    # Reusable components
    └── product-card.blade.php
```

**Web Consumer Portal:**
- Routes: `/marketplace/*`
- Authentication: Session-based (Laravel Breeze)
- CSRF protection enabled
- Tailwind CSS styling (consistent with farm owner portal)

### Product Ordering Rules (Web & Mobile)
All enforced product-side:
- `is_bulk_order_enabled` - Enable bulk ordering
- `order_quantity_step` - Minimum quantity increments
- `minimum_order` - Minimum order quantity
- Payment methods: COD, GCash, PayMaya

---

## 4. Registration-Related Files & Controllers

### Consumer Registration

**Files:**
- Controller: `app/Http/Controllers/ConsumerRegistrationController.php`
- Request Validation: `app/Http/Requests/ConsumerRegistrationRequest.php`
- View: `resources/views/auth/consumer-register.blade.php`
- Verification Service: `app/Services/ConsumerVerificationService.php`

**Validations:**
```php
'full_name'    => ['required', 'string', 'max:255'],
'email'        => ['required', 'email:rfc,dns', 'unique:users,email'],
'phone_number' => ['required', new PhilippinePhoneNumber(), 'unique:users,phone'],
'address'      => ['nullable', 'string'],
'password'     => ['required', 'string', 'min:8', 'confirmed'],
```

### Farm Owner (Client) Registration

**Files:**
- Controller: `app/Http/Controllers/ClientRequestController.php`
- Request Validation: `app/Http/Requests/ClientRegistrationRequest.php`
- View: `resources/views/auth/client-register.blade.php`
- Model: `app/Models/ClientRequest.php`

**Features:**
- File uploads: Valid ID + Business Permit
- Status tracking: pending → accepted/rejected
- Automatic User account creation
- Farm Owner profile creation

**Workflow:**
1. Farm owner submits registration with documents
2. Files stored in `/storage/uploads/{ids,permits}/`
3. `ClientRequest` record created (status: pending)
4. Automatic `User` + `FarmOwner` records created
5. Superadmin must approve before farm owner can fully use system

### Key Models

**User Model**
- Location: `app/Models/User.php`
- Fields: name, email, phone, password, role, status, email_verified_at, etc.
- Roles: consumer, farm_owner, client, superadmin, department roles
- Relations: farmOwner, staff, orders, subscriptions, mobileAccessTokens

**ConsumerVerificationCode Model**
- Location: `app/Models/ConsumerVerificationCode.php`
- Fields: user_id, code (6-digit), expires_at, attempts
- Lifetime: 10 minutes from creation
- Max attempts: tracked for security

**ClientRequest Model**
- Location: `app/Models/ClientRequest.php`
- Fields: owner_name, farm_name, email, farm_location, id_path, permit_path, status
- Status values: pending, accepted, rejected

---

## 5. Registration Flow (Web & Mobile)

### Consumer Registration Flow (Unified)

```
Web/Mobile Registration
        │
        ↓
POST /consumer/register  (Web)  OR  API registration endpoint (Mobile TBD)
        │
        ├─→ Validation
        │   ├─ Email uniqueness
        │   ├─ Phone number format (Philippine)
        │   ├─ Password strength (min 8 chars)
        │   └─ Phone uniqueness
        │
        ├─→ Create User (email_verified_at = NULL)
        │   └─ role = 'consumer'
        │   └─ status = 'active'
        │
        ├─→ Issue Verification Code (Service)
        │   └─ Generate 6-digit random code
        │   └─ Store in ConsumerVerificationCode
        │   └─ Set expiry = now + 10 minutes
        │   └─ Send email with code
        │
        ├─→ Session Storage
        │   └─ Set consumer_verification_user_id in session
        │
        └─→ Redirect to verification form
            └─ Show consumer-verify-code.blade.php
```

**Verification Form** (`/consumer/verify`):
```
Verify Email form
        │
        ├─→ Input 6-digit code
        │
        ├─→ POST /consumer/verify
        │   ├─ Retrieve pending user from session
        │   ├─ Fetch ConsumerVerificationCode record
        │   ├─ Validate:
        │   │   ├─ Code matches
        │   │   ├─ Not expired (10 min window)
        │   │   └─ Attempts < limit (tracked)
        │   │
        │   ├─ If valid:
        │   │   └─ Set user.email_verified_at = now()
        │   │   └─ Delete verification code record
        │   │   └─ Clear session
        │   │   └─ Redirect to home with success
        │   │
        │   └─ If invalid:
        │       ├─ Increment attempts
        │       └─ Show error + resend option
        │
        ├─→ Resend Code (POST /consumer/verify/resend)
        │   └─ Reissue new code with same service
```

### Farm Owner (Client) Registration Flow

```
Farm Owner Registration
        │
        ├─→ GET /farm-owner/register (show form)
        │
        ├─→ POST /farm-owner/register
        │   │
        │   ├─→ Validate inputs
        │   │   ├─ owner_name (required, string)
        │   │   ├─ farm_name (required, string)
        │   │   ├─ email (required, unique, email)
        │   │   ├─ farm_location (required)
        │   │   ├─ valid_id (file: jpg, png, pdf, max 5MB)
        │   │   └─ business_permit (file: jpg, png, pdf, max 5MB)
        │   │
        │   ├─→ Upload Files
        │   │   ├─ Store valid_id → /storage/uploads/ids/{filename}
        │   │   └─ Store permit → /storage/uploads/permits/{filename}
        │   │
        │   ├─→ Create ClientRequest (status: pending)
        │   │   ├─ owner_name, farm_name, email, farm_location
        │   │   ├─ valid_id_path, business_permit_path
        │   │   └─ hashed password (for later use)
        │   │
        │   ├─→ Create User (status: active)
        │   │   ├─ name = owner_name
        │   │   ├─ email
        │   │   ├─ role = 'farm_owner'
        │   │   └─ password (hashed)
        │   │
        │   ├─→ Create FarmOwner
        │   │   ├─ user_id link
        │   │   ├─ farm_name, farm_address
        │   │   ├─ valid_id_path, permit_status = 'pending'
        │   │   └─ subscription_status = 'inactive'
        │   │
        │   ├─→ Send Confirmation Email
        │   │   └─ "Registration pending Super Admin review"
        │   │
        │   └─→ Auto-login as farm_owner
        │       └─ Redirect to /farm-owner/dashboard
        │
    ┌───┴─────────────────────────────────────┐
    │  SuperAdmin Approval Process            │
    │                                          │
    ├─→ SuperAdmin views /superadmin/...     │
    ├─→ Sees pending ClientRequest           │
    ├─→ POST /superadmin/approve/{id}        │
    ├─→ ClientRequest status = 'accepted'    │
    └─→ Farm owner unlock → full access
```

---

## 6. Email Verification & OTP Workflows

### ConsumerVerificationService

**Location**: `app/Services/ConsumerVerificationService.php`

**Method: `issueCode(User $user)`**
```php
Steps:
1. Generate 6-digit random code (100000-999999)
2. Create/update ConsumerVerificationCode record
   ├─ user_id
   ├─ code
   ├─ expires_at = now() + 10 minutes
   └─ attempts = 0
3. Send verification email (Mail::raw)
   ├─ Template: "Your code is: {code}"
   ├─ Subject: "Your Consumer Verification Code - Poultry System"
   └─ From: config('mail.from.address')
4. Log email sent success
5. Fallback: Log code if email fails (for testing)
```

### Verification Code Details

**Database Table**: `consumer_verification_codes`
```sql
CREATE TABLE consumer_verification_codes (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNIQUE (one code per user),
  code VARCHAR(6) (6-digit numeric string),
  expires_at TIMESTAMP (10 minute expiry),
  attempts UNSIGNED SMALLINT (tracks failed attempts),
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);

Indexes:
  - UNIQUE(user_id) - one active code per user
  - INDEX(user_id, code) - quick verification lookup
```

### Email Verification Workflow

**Standard Laravel EmailVerified Middleware:**
- Routes protected by `verified` middleware
- Check: `user->email_verified_at IS NOT NULL`
- Redirect to `/verify-email` if not verified

**Custom Consumer Email Verification:**
1. After registration → user redirected to `/consumer/verify`
2. User checks email for 6-digit code
3. User enters code on form
4. System validates code:
   ```php
   - Record exists & hasn't expired
   - Code matches (case-sensitive numeric)
   - Attempts not exceeded
   ```
5. If valid:
   - `user->email_verified_at = now()`
   - Code record deleted
   - Redirect to home / app launcher

**For Mobile:**
- Mobile app may skip web verification flow
- Direct mobile authentication requires:
  - Email address (from registration)
  - Password
  - Email must be verified first

---

## 7. Mobile API Endpoints Structure

### Base URL Configuration
- Configured in Flutter: `lib/src/config/app_config.dart`
- Environment-specific (dev/staging/prod)
- Passed via dart-define or .env equivalent

### Authentication API

**POST /api/mobile/auth/login**
```json
Request: {
  "email": "consumer@example.com",
  "password": "password"
}

Response (200): {
  "message": "Login successful.",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "consumer@example.com",
    "phone": "+639171234567",
    "location": "Manila",
    "role": "consumer",
    "token": "eyJ0eXAi...",
    "token_type": "Bearer",
    "expires_at": "2026-05-04T12:00:00Z"
  }
}

Errors (422/403):
  - Invalid credentials (422)
  - Email not verified (403)
  - Wrong role (403)
```

**POST /api/mobile/auth/logout**
```
Header: Authorization: Bearer {token}
Response: { "message": "Logged out successfully." }
```

### Protected Endpoints (require `mobile.auth` middleware)

**GET /api/mobile/profile**
```json
Response: {
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "consumer@example.com",
    "phone": "+639171234567",
    "location": "Manila",
    "role": "consumer"
  }
}
```

**PATCH /api/mobile/profile**
```json
Request: {
  "name": "Jane Doe",
  "phone": "+639181234567",
  "location": "Quezon City"
}
```

**GET /api/mobile/products**
```json
Query params: ?q=eggs (search filter)

Response: [
  {
    "id": 1,
    "name": "Brown Eggs (30 pieces)",
    "description": "Fresh brown eggs",
    "price": 450.00,
    "unit": "tray",
    "farm_name": "Happy Farm",
    "is_bulk_order_enabled": true,
    "order_quantity_step": 5,
    "minimum_order": 10,
    ...
  }
]
```

**GET /api/mobile/orders**
```json
Response: [
  {
    "id": 1,
    "order_number": "ORD-20260404-001",
    "farm_name": "Happy Farm",
    "status": "pending_delivery",
    "total": 2250.00,
    "items": [ {...} ],
    "delivery": { "status": "on_the_way", ... },
    "can_retry_payment": false,
    ...
  }
]
```

**POST /api/mobile/orders**
```json
Request: {
  "product_id": 1,
  "quantity": 15,
  "payment_method": "cod|gcash|paymaya"
}

Response (success): {
  "message": "Order created.",
  "data": { order_id, checkout_url (if online payment), ... }
}
```

**POST /api/mobile/orders/{order}/cancel**
**POST /api/mobile/orders/{order}/retry-payment**
**GET /api/mobile/notifications**
**POST /api/mobile/complaints**
**GET /api/mobile/ratings**
**POST /api/mobile/ratings/{delivery}**

---

## 8. Database Schema Highlights

### Key Tables

**users**
```
id, name, email, phone, password, role, status,
email_verified_at, phone_verified_at, kyc_verified,
last_login_at, remember_token, created_at, updated_at
```

**consumer_verification_codes**
```
id, user_id (unique), code, expires_at, attempts
```

**client_requests**
```
id, owner_name, farm_name, email, farm_location,
valid_id_path, business_permit_path, password, status
```

**mobile_access_tokens**
```
id, user_id, name, token_hash (sha256),
last_used_at, expires_at, created_at, updated_at
```

**orders**
```
id, order_number, consumer_id, farm_owner_id,
status, total_amount, payment_method, created_at
```

**order_items**
```
id, order_id, product_id, quantity, price_per_unit
```

**products**
```
id, farm_owner_id, name, description, price, unit,
is_bulk_order_enabled, order_quantity_step, minimum_order,
created_at
```

---

## 9. Directory Structure Summary

```
poultry-system/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── ConsumerRegistrationController.php
│   │   │   ├── ClientRequestController.php
│   │   │   ├── ConsumerVerificationController.php
│   │   │   ├── Api/ (Mobile API)
│   │   │   │   ├── MobileAuthController.php
│   │   │   │   ├── MobileMarketplaceController.php
│   │   │   │   └── MobileProductController.php
│   │   │   └── ... (other controllers)
│   │   ├── Requests/
│   │   │   ├── ConsumerRegistrationRequest.php
│   │   │   ├── ClientRegistrationRequest.php
│   │   │   └── ...
│   │   └── Middleware/
│   ├── Models/
│   │   ├── User.php
│   │   ├── ConsumerVerificationCode.php
│   │   ├── ClientRequest.php
│   │   ├── MobileAccessToken.php
│   │   └── ... (other models)
│   ├── Services/
│   │   ├── ConsumerVerificationService.php
│   │   ├── PayMongoService.php
│   │   └── AttendanceAutomationService.php
│   ├── Policies/
│   └── Providers/
├── routes/
│   ├── web.php (Web portal routes)
│   ├── api.php (Mobile API routes)
│   ├── auth.php (Auth routes - mostly disabled)
│   └── console.php
├── resources/
│   ├── views/
│   │   ├── auth/
│   │   │   ├── register-select.blade.php
│   │   │   ├── consumer-register.blade.php
│   │   │   ├── consumer-verify-code.blade.php
│   │   │   ├── client-register.blade.php
│   │   │   └── ...
│   │   ├── marketplace/
│   │   ├── dashboard.blade.php
│   │   └── ...
│   ├── css/
│   └── js/
├── database/
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 2026_03_07_120000_create_consumer_verification_codes_table.php
│   │   ├── 2026_03_07_130000_create_client_requests_table.php
│   │   ├── 2026_03_11_170000_create_mobile_access_tokens_table.php
│   │   └── ...
│   ├── seeders/
│   └── factories/
├── bootstrap/
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── database.php
│   └── services.php (PayMongo config)
├── storage/
│   ├── uploads/ids/
│   └── uploads/permits/
├── poultry_consumer_app/ (Flutter)
│   ├── lib/
│   │   ├── main.dart
│   │   └── src/
│   │       ├── screens/
│   │       ├── services/
│   │       ├── models/
│   │       ├── config/
│   │       └── components/
│   ├── pubspec.yaml
│   └── ...
└── composer.json
```

---

## 10. Key Integration Points

### Web-to-Mobile Sync
- Same `User` table for both platforms
- Email verification required before mobile login
- Mobile token expiry: 30 days
- Orders visible on both web & mobile

### Payment Integration (PayMongo)
- Webhook: `POST /webhooks/paymongo`
- Subscription creation
- Order payment processing
- Payroll disbursement

### Session Management
- Web: Laravel session (file-based, 120 min)
- Mobile: Bearer token (sqlite_secure_storage)

### Email Notifications
- Verification codes (10 min expiry)
- Registration confirmations
- Order updates
- Payroll notifications

---

## 11. Security Notes

- ✅ Password hashing: Laravel's default hasher
- ✅ Email verification: 6-digit OTP with 10 min expiry
- ✅ Mobile auth: Bearer tokens with SHA256 hash storage
- ✅ CSRF protection: Enabled on web routes
- ✅ File uploads: Restricted to ID & permit types
- ✅ Phone validation: Philippine format (09xxxxxxxxx → +639xxxxxxxxx)
- ⚠️ Token expiry: 30 days (mobile) - consider shorter for production

---

## 12. Testing References

- Feature tests in `/tests/Feature/Auth/`
- Consumer registration test: Validates flow end-to-end
- Email verification test: Tests signed URL validation
- Note: Custom OTP uses session + ConsumerVerificationCode

---

## Summary

This system implements a **comprehensive B2B2C platform** with:

1. **Web marketplace** for consumers (session-based)
2. **Mobile app** for on-the-go consumers (token-based)
3. **Farm owner portal** for business operations
4. **Super admin portal** for system management
5. **Unified authentication** with email verification via OTP
6. **Multi-channel** registration (web consumer, farm owner, mobile consumer)
7. **Role-based access control** with department employees
8. **Modular design** (flocks, HR, finance, logistics, sales modules)

The registration flow emphasizes email verification security and farm owner documentation requirements, ensuring a trustworthy marketplace platform.
