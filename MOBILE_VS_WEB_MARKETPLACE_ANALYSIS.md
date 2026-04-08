# Flutter Mobile App vs Web Marketplace - Feature Parity Analysis

**Generated:** April 8, 2026  
**Scope:** Comparing poultry_consumer_app (Flutter) with web marketplace (Laravel Blade)

---

## Executive Summary

The Flutter mobile consumer app is **missing critical features** that exist in the web marketplace checkout flow. The most significant gaps are:
- **NO map-based location picking** (Web has full Leaflet integration)
- **NO delivery type selection** (pickup vs delivery - hardcoded to delivery only in mobile)
- **NO geolocation/coordinate capture** (latitude/longitude not sent)
- **NO address geocoding** (no auto-mapping of addresses to coordinates)

These gaps create an inconsistent user experience and limit logistics functionality on mobile.

---

## 1. Features Present in Mobile App

### A. Core Marketplace Features
| Feature | Status | Notes |
|---------|--------|-------|
| User Login/Authentication | ✅ YES | Token-based, stored locally |
| Product Browsing | ✅ YES | Grid view with farm filtering |
| Product Search | ✅ YES | Search by keywords/categories |
| Shopping Cart | ✅ YES | Add/remove/update quantities |
| Order Placement | ✅ YES | Basic checkout flow |
| Order History/Tracking | ✅ YES | List view with status tracking |
| Payment Methods (COD, GCash, PayMaya) | ✅ YES | PayMongo integration |
| Order Cancellation | ✅ YES | Unpaid orders only |
| Payment Retry | ✅ YES | For online payment methods |
| Profile Management | ✅ YES | Name, phone, location (text only) |
| Notifications | ✅ YES | In-app notifications |
| Ratings/Feedback | ✅ YES | Rate delivered orders |
| Complaints | ✅ YES | Submit complaints for orders |

### B. API Service Capabilities
```dart
// Implemented API endpoints:
- POST /api/mobile/auth/login
- GET /api/mobile/products
- GET/POST /api/mobile/profile
- GET /api/mobile/orders
- POST /api/mobile/orders (placeOrder)
- POST /api/mobile/orders/{id}/cancel
- POST /api/mobile/orders/{id}/retry-payment
- GET /api/mobile/notifications
- POST /api/mobile/complaints
- GET/POST /api/mobile/ratings
```

### C. UI Components
- Dark theme design (Material 3)
- BottomNavigationBar with tabs: Shop, Cart, Orders, Account
- Product grid with farm filtering
- Cart management with quantity controls
- Order list with status badges
- Profile form fields

---

## 2. Features MISSING from Mobile (Present in Web)

### CRITICAL GAPS - Delivery Location

| Feature | Web | Mobile | Impact |
|---------|-----|--------|--------|
| **Delivery Type Selection** | ✅ YES | ❌ NO | Mobile can't support pickup orders |
| **Map-based Location Picker** | ✅ YES (Leaflet) | ❌ NO | No visual location selection |
| **Latitude/Longitude Capture** | ✅ YES | ❌ NO | No precise coordinates for routing |
| **Address Auto-Geocoding** | ✅ YES (Nominatim API) | ❌ NO | No automatic coordinate lookup |
| **Geofencing/Location Validation** | ✅ YES (Cavite area check) | ❌ NO | No geographic bounds checking |

### A. Delivery Type Selection (Web has this)
**Web Implementation (checkout.blade.php):**
```html
<select name="delivery_type" id="delivery_type">
    <option value="delivery">Delivery</option>
    <option value="pickup">Pickup</option>
</select>

<!-- Conditional fields display based on delivery_type -->
<div id="delivery_fields" class="space-y-3">
    <!-- Address fields only show for delivery -->
</div>
```

**Mobile Implementation:**
```dart
// NO delivery type selector - hardcoded in API controller:
'delivery_type' => 'delivery', // HARDCODED - always delivery
```

**Result:** Mobile users CANNOT select "Pickup" option that web users have.

---

### B. Map-Based Location Picking (Web has this)
**Web Implementation:**
```javascript
// Initialize Leaflet map
deliveryMap = L.map('delivery_map').setView([14.3577, 120.8854], 15);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(deliveryMap);

// Add marker on click
deliveryMap.on('click', function(e) {
    addDeliveryMarker(e.latlng.lat, e.latlng.lng);
});
```

**Mobile Implementation:**
```dart
// cart_screen.dart - ONLY text fields, NO map
TextField(controller: _addressCtrl, decoration: const InputDecoration(labelText: 'Delivery Address')),
TextField(controller: _cityCtrl, decoration: const InputDecoration(labelText: 'City')),
TextField(controller: _provinceCtrl, decoration: const InputDecoration(labelText: 'Province')),
TextField(controller: _postalCtrl, decoration: const InputDecoration(labelText: 'Postal Code')),
// NO map widget
// NO latitude/longitude fields
```

**Result:** Web users see a map and click to set location; Mobile users type text only.

---

### C. Coordinate Capture (Web has this)
**Web Form Fields (checkout.blade.php):**
```html
<input type="hidden" name="delivery_latitude" id="delivery_latitude">
<input type="hidden" name="delivery_longitude" id="delivery_longitude">

<!-- Populated by Leaflet marker placement -->
```

**Web API Validation (OrderController.php):**
```php
'delivery_latitude' => 'nullable|numeric|between:-90,90',
'delivery_longitude' => 'nullable|numeric|between:-180,180',
```

**Mobile API Call (api_service.dart):**
```dart
Future<Map<String, dynamic>> placeOrder({
    required String token,
    required List<CartItem> items,
    required String deliveryAddress,
    required String deliveryCity,
    required String deliveryProvince,
    required String deliveryPostalCode,
    required String paymentMethod,
    // ❌ NO: delivery_latitude
    // ❌ NO: delivery_longitude
    // ❌ NO: delivery_type
}) async {
    final payload = {
        'delivery_address': deliveryAddress,
        'delivery_city': deliveryCity,
        'delivery_province': deliveryProvince,
        'delivery_postal_code': deliveryPostalCode,
        'payment_method': paymentMethod,
        'items': items.map((item) => {...}).toList(),
        // Missing: 'delivery_latitude', 'delivery_longitude', 'delivery_type'
    };
}
```

**Result:** Mobile orders lack GPS coordinates needed for driver routing & logistics.

---

### D. Address Geocoding (Web has this)
**Web Implementation:**
```javascript
function geocodeAddress() {
    const fullAddress = `${address}, ${city}, ${province}, Philippines`;
    
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(fullAddress)}&limit=1`)
        .then(response => response.json())
        .then(data => {
            const lat = parseFloat(data[0].lat);
            const lng = parseFloat(data[0].lon);
            
            // Verify location is in Cavite area
            if (lat >= 14.0 && lat <= 14.7 && lng >= 120.5 && lng <= 121.3) {
                addDeliveryMarker(lat, lng);
            }
        });
}

// Auto-geocode on blur with debounce
addressInput.addEventListener('blur', function() {
    clearTimeout(geocodeTimeout);
    geocodeTimeout = setTimeout(geocodeAddress, 500);
});
```

**Mobile Implementation:**
```dart
// ❌ NO geocoding logic
// Just plain TextEditingController fields
final _addressCtrl = TextEditingController();
final _cityCtrl = TextEditingController();
```

**Result:** Web auto-maps addresses; Mobile requires manual coordinate entry (if form accepted it, which it doesn't).

---

### E. Geofencing/Location Validation (Web has this)
**Web Code:**
```javascript
// Validates location is within Cavite service area
if (lat >= 14.0 && lat <= 14.7 && lng >= 120.5 && lng <= 121.3) {
    addDeliveryMarker(lat, lng);
}
```

**Mobile Code:**
```dart
// ❌ NO geographic validation
// ❌ NO service area checking
// ❌ NO bounds verification
```

**Result:** Web enforces Cavite service area; Mobile has no location bounds checking.

---

### F. Delivery Type Conditional Logic (Web has this)
**Web (checkout.blade.php):**
```javascript
const deliveryType = document.getElementById('delivery_type');

function toggleDeliveryFields() {
    deliveryFields.style.display = deliveryType.value === 'delivery' ? 'block' : 'none';
}

deliveryType.addEventListener('change', toggleDeliveryFields);
```

**Mobile:**
```dart
// ❌ NO delivery_type field at all
// All checkout logic assumes delivery only
```

**Result:** Web users can switch between delivery/pickup dynamically; Mobile is stuck in delivery-only mode.

---

## 3. Specific Function/Parameter Gaps

### A. placeOrder() Function Signature Mismatch

**Mobile (Missing Parameters):**
```dart
placeOrder({
    required String token,
    required List<CartItem> items,
    required String deliveryAddress,          // ✅ Present
    required String deliveryCity,             // ✅ Present
    required String deliveryProvince,         // ✅ Present
    required String deliveryPostalCode,       // ✅ Present
    required String paymentMethod,            // ✅ Present
    // ❌ MISSING: String deliveryType (delivery|pickup)
    // ❌ MISSING: double? deliveryLatitude
    // ❌ MISSING: double? deliveryLongitude
})
```

**Web (controller validation):**
```php
'delivery_type' => 'required|in:delivery,pickup',                    // ✅ Web has
'delivery_latitude' => 'nullable|numeric|between:-90,90',           // ✅ Web has
'delivery_longitude' => 'nullable|numeric|between:-180,180',        // ✅ Web has
'delivery_address' => 'required_if:delivery_type,delivery',         // Conditional
```

**Backend API Accepts (MobileMarketplaceController.php):**
```php
$validated = $request->validate([
    'delivery_address' => 'required|string|max:500',        // ✅ Accepts
    'delivery_city' => 'nullable|string|max:255',          // ✅ Accepts
    'delivery_province' => 'nullable|string|max:255',      // ✅ Accepts
    'delivery_postal_code' => 'nullable|string|max:20',    // ✅ Accepts
    'payment_method' => 'required|in:cod,gcash,paymaya',   // ✅ Accepts
    'items' => 'required|array|min:1',                      // ✅ Accepts
    // ❌ API IGNORES delivery_latitude/longitude/delivery_type if sent!
]);

// Mobile API hardcodes delivery type:
'delivery_type' => 'delivery', // ❌ Always hardcoded
```

**Result:** Even if mobile sends lat/lng, the API endpoint IGNORES them!

---

### B. Cart Screen Parameter Comparison

| Parameter | Mobile | Web | Notes |
|-----------|--------|-----|-------|
| deliveryAddress | ✅ YES | ✅ YES | Text field |
| deliveryCity | ✅ YES | ✅ YES | Text field |
| deliveryProvince | ✅ YES | ✅ YES | Text field |
| deliveryPostalCode | ✅ YES | ✅ YES | Text field |
| paymentMethod | ✅ YES | ✅ YES | Chip selector |
| deliveryType | ❌ NO | ✅ YES (dropdown) | Pickup vs Delivery |
| deliveryLatitude | ❌ NO | ✅ YES (map) | Hidden field, map populated |
| deliveryLongitude | ❌ NO | ✅ YES (map) | Hidden field, map populated |
| addressGeocoding | ❌ NO | ✅ YES (Nominatim) | Auto-lookup via Nominatim API |

---

## 4. Geolocation/Map Status

### Web Marketplace Status: ✅ FULLY IMPLEMENTED
- **Map Library:** Leaflet.js v1.9.4
- **Tile Provider:** OpenStreetMap
- **Geocoding:** Nominatim API (free, OSM)
- **Marker Placement:** Click-to-place on map
- **Auto-Geocoding:** Address → Lat/Lng conversion
- **Service Area:** Cavite region (14.0-14.7°N, 120.5-121.3°E)
- **Implementation:** Full JavaScript implementation in checkout.blade.php (lines 459-618)

### Mobile App Status: ❌ NOT IMPLEMENTED
- **Map Library:** ❌ Not in pubspec.yaml
- **Geolocation:** ❌ Not in pubspec.yaml
- **Geocoding:** ❌ Not in pubspec.yaml
- **Location Picker:** ❌ Not implemented
- **Service Area Check:** ❌ Not implemented
- **Missing Packages:**
  ```yaml
  # NOT in pubspec.yaml - would be needed for maps:
  - geolocator                    # For GPS location
  - google_maps_flutter          # For map display
  - geocoding                     # For address->coords
  - flutter_map                   # For Leaflet alternative
  ```

---

### Required Dependencies for Feature Parity
```yaml
# pubspec.yaml should include:
dependencies:
  flutter:
    sdk: flutter
  
  # NEW: For map functionality
  flutter_map: ^4.0.0              # Leaflet alternative for Flutter
  geolocator: ^9.0.0               # Device location access
  geocoding: ^2.0.0                # Address geocoding service
  latlong2: ^0.9.0                 # Coordinate handling
  
  # For Nominatim API (free, like web)
  http: ^1.5.0                     # Already present
  
  # For UI/UX
  permission_handler: ^11.0.0      # Location permissions
```

---

## 5. Delivery Coordinate Capture Status

### Web Marketplace
| Aspect | Status | Details |
|--------|--------|---------|
| Captures Latitude | ✅ YES | Via map click, Nominatim geocoding |
| Captures Longitude | ✅ YES | Via map click, Nominatim geocoding |
| Stores in DB | ✅ YES | Order table has delivery_latitude, delivery_longitude |
| Used for Routing | ✅ YES | Driver app uses coordinates for navigation |
| Validates Bounds | ✅ YES | Ensures Cavite service area (14.0-14.7°N, 120.5-121.3°E) |
| User-Friendly | ✅ YES | Visual map picker, auto-geocoding |

### Mobile App
| Aspect | Status | Details |
|--------|--------|---------|
| Captures Latitude | ❌ NO | No coordinate fields |
| Captures Longitude | ❌ NO | No coordinate fields |
| Stores in DB | ❌ NO | API doesn't accept lat/lng |
| Used for Routing | ❌ NO | No coordinates available |
| Validates Bounds | ❌ NO | No area validation |
| User-Friendly | ❌ NO | Text fields only, no visual aid |

---

## 6. Critical Issues Blocking Feature Parity

### Priority 1: Critical - Blocks Core Functionality

#### Issue 1.1: No Map/Geolocation in Mobile
**Severity:** 🔴 CRITICAL  
**Impact:** Mobile users cannot pick delivery locations visually; API has no coordinate data for routing/logistics

**Current State:**
```dart
// cart_screen.dart - text fields only
_addressCtrl = TextEditingController();
_cityCtrl = TextEditingController();
_provinceCtrl = TextEditingController();
_postalCtrl = TextEditingController();
// NO latitude/longitude fields
// NO map widget
```

**Required Fix:**
- [ ] Add flutter_map package
- [ ] Add geolocator package  
- [ ] Add geocoding package
- [ ] Add LocationPickerWidget to cart_screen.dart
- [ ] Update placeOrder() to include latitude/longitude parameters
- [ ] Update mobile API endpoint validation to accept coordinates
- [ ] Add location permission handling

**Estimated Effort:** 40-60 hours

---

#### Issue 1.2: No Delivery Type Selection (Pickup vs Delivery)
**Severity:** 🔴 CRITICAL  
**Impact:** Mobile cannot support pickup orders; feature parity broken with web

**Current State:**
```dart
// cart_screen.dart - no delivery_type selector
String _paymentMethod = 'cod'; // Only payment method selector exists

// api_service.dart - hardcoded in backend
'delivery_type' => 'delivery', // Backend hardcodes for mobile
```

**Required Fix:**
- [ ] Add delivery_type selector in CartScreen UI (Dropdown or Radio buttons)
- [ ] Add conditional field visibility (address fields only for delivery)
- [ ] Update placeOrder() signature to accept deliveryType parameter
- [ ] Update API call to send delivery_type
- [ ] Update backend MobileMarketplaceController to accept deliveryType

**Estimated Effort:** 8-12 hours

---

#### Issue 1.3: API Endpoint Doesn't Accept Coordinates
**Severity:** 🔴 CRITICAL  
**Impact:** Even if mobile sends lat/lng, backend ignores them

**Current State (MobileMarketplaceController.php):**
```php
$validated = $request->validate([
    'delivery_address' => 'required|string|max:500',
    'delivery_city' => 'nullable|string|max:255',
    'delivery_province' => 'nullable|string|max:255',
    'delivery_postal_code' => 'nullable|string|max:20',
    'payment_method' => 'required|in:cod,gcash,paymaya',
    'items' => 'required|array|min:1',
    // ❌ NO delivery_latitude validation
    // ❌ NO delivery_longitude validation
]);

// Order creation hardcodes:
$order = Order::create([
    // ... other fields
    'delivery_type' => 'delivery', // ❌ Always delivery
    'delivery_latitude' => null,   // ❌ Not captured
    'delivery_longitude' => null,  // ❌ Not captured
]);
```

**Required Fix:**
- [ ] Update MobileMarketplaceController validation to accept coordinates:
```php
'delivery_latitude' => 'nullable|numeric|between:-90,90',
'delivery_longitude' => 'nullable|numeric|between:-180,180',
'delivery_type' => 'required|in:delivery,pickup',
```
- [ ] Update Order creation to use validated coordinates
- [ ] Add service area validation (Cavite bounds)
- [ ] Sync with web OrderController.php validation rules

**Estimated Effort:** 6-10 hours

---

### Priority 2: High - Important for UX

#### Issue 2.1: No Address Auto-Geocoding
**Severity:** 🟠 HIGH  
**Impact:** Users can't auto-map addresses to coordinates; must manually type/enter

**Current State:**
```dart
// cart_screen.dart - no geocoding
_addressCtrl.text = widget.session.location ?? '';
// No auto-lookup when user types address
```

**Required Fix:**
- [ ] Implement Nominatim geocoding API calls (like web)
- [ ] Add debounced address field listeners
- [ ] Auto-populate coordinates from address
- [ ] Add visual feedback (loading, success/error states)

**Estimated Effort:** 12-18 hours

---

#### Issue 2.2: No Service Area Validation
**Severity:** 🟠 HIGH  
**Impact:** Mobile can accept orders outside service area; web rejects them

**Current State:**
```dart
// cart_screen.dart - no bounds checking
// No validation that location is in Cavite
```

**Required Fix:**
- [ ] Add geofencing logic based on Cavite bounds (14.0-14.7°N, 120.5-121.3°E)
- [ ] Validate coordinates before order submission
- [ ] Show user-friendly error if outside service area

**Estimated Effort:** 4-8 hours

---

### Priority 3: Medium - Nice-to-Have

#### Issue 3.1: Update pubspec.yaml Dependencies
**Severity:** 🟡 MEDIUM  
**Impact:** Cannot implement map/geo features without dependencies

**Current State:**
```yaml
# pubspec.yaml - missing packages
dependencies:
  flutter: sdk: flutter
  cupertino_icons: ^1.0.8
  http: ^1.5.0
  url_launcher: ^6.3.2
  flutter_secure_storage: ^9.2.4
  # ❌ Missing map packages
```

**Required Fix:**
```yaml
dependencies:
  flutter: sdk: flutter
  cupertino_icons: ^1.0.8
  http: ^1.5.0
  url_launcher: ^6.3.2
  flutter_secure_storage: ^9.2.4
  flutter_map: ^4.0.0              # NEW - Map widget
  geolocator: ^9.0.0               # NEW - GPS location
  geocoding: ^2.0.0                # NEW - Address geocoding
  latlong2: ^0.9.0                 # NEW - Coordinate type
  permission_handler: ^11.0.0      # NEW - Location permissions
```

**Estimated Effort:** 2-4 hours (documentation + testing)

---

#### Issue 3.2: Current Location Detection
**Severity:** 🟡 MEDIUM  
**Impact:** Users must manually enter address; web doesn't auto-detect but can click map instead

**Recommended:** Add "Use Current Location" button
- Requests GPS permission
- Gets current coordinates  
- Reverse-geocodes to address
- Fills address fields automatically

**Estimated Effort:** 8-12 hours

---

## 7. Alignment Gaps - Detailed Comparison

### A. Checkout Flow Comparison

| Step | Web Flow | Mobile Flow | Gap |
|------|----------|------------|-----|
| **1. View Cart** | Show items table | Show card list | ✅ Similar |
| **2. Select Delivery Type** | Dropdown (Delivery/Pickup) | ❌ MISSING | 🔴 Critical |
| **3. Pick Location** | Leaflet map + click | Text field address | 🔴 Critical |
| **4. Enter Address** | Text fields OR pick map | Text fields only | 🔴 Important |
| **5. Auto-Geocode** | Nominatim on blur | ❌ MISSING | 🔴 Important |
| **6. Select Payment** | Dropdown | Chip selector | ✅ Similar |
| **7. Review Summary** | Amount breakdown | Cost summary | ✅ Similar |
| **8. Place Order** | POST with all fields | POST incomplete fields | 🔴 Critical |

---

### B. Model/Data Structure Comparison

**Web Order Creation (OrderController.php::place_order):**
```php
$validated = $request->validate([
    'delivery_type' => 'required|in:delivery,pickup',
    'delivery_address' => 'required_if:delivery_type,delivery|string|max:500',
    'delivery_city' => 'required_if:delivery_type,delivery|string|max:255',
    'delivery_province' => 'required_if:delivery_type,delivery|string|max:255',
    'delivery_postal_code' => 'nullable|string|max:10',
    'delivery_latitude' => 'nullable|numeric|between:-90,90',       // ✅ Web
    'delivery_longitude' => 'nullable|numeric|between:-180,180',    // ✅ Web
    'payment_method' => 'required|in:cod,gcash,paymaya',
]);
```

**Mobile Order Creation (MobileMarketplaceController.php::placeOrder):**
```php
$validated = $request->validate([
    'delivery_address' => 'required|string|max:500',
    'delivery_city' => 'nullable|string|max:255',
    'delivery_province' => 'nullable|string|max:255',
    'delivery_postal_code' => 'nullable|string|max:20',
    'payment_method' => 'required|in:cod,gcash,paymaya',
    'items' => 'required|array|min:1',
    // ❌ NO delivery_type
    // ❌ NO delivery_latitude
    // ❌ NO delivery_longitude
]);
```

---

### C. UI Component Comparison

| Component | Web | Mobile | Notes |
|-----------|-----|--------|-------|
| **Map Widget** | ✅ Leaflet.js | ❌ None | Critical for location picking |
| **Location Input** | Map + Text fields | Text fields only | Web has visual picker |
| **Delivery Type Selector** | ✅ Select dropdown | ❌ None | Mobile can't support pickup |
| **Payment Selector** | ✅ Select dropdown | ✅ Chip buttons | Mobile uses better UX |
| **Address Autocomplete** | ✅ Nominatim API | ❌ None | Web auto-geocodes |
| **Location Validation** | ✅ Bounds check (Cavite) | ❌ None | Web enforces service area |
| **Order Summary** | ✅ Table + totals | ✅ Summary section | Similar functionality |

---

## 8. Implementation Roadmap

### Phase 1: Prepare Architecture (Week 1)
```
[ ] Add missing packages to pubspec.yaml
[ ] Test package installation (flutter pub get)
[ ] Create LocationPickerWidget wrapper component
[ ] Create delivery_type dropdown/radio selector
[ ] Update ConsumerSession model to include delivery_type
```

### Phase 2: Update Mobile UI (Week 2-3)
```
[ ] Add delivery_type selector to CartScreen
[ ] Add LocationPickerWidget to CartScreen
[ ] Implement conditional visibility (fields hidden for pickup)
[ ] Add "Use Current Location" button with GPS
[ ] Add address auto-geocoding on field blur
[ ] Add service area validation UI feedback
[ ] Update cart_screen layout for map widget
```

### Phase 3: Update Mobile API Layer (Week 3)
```
[ ] Update placeOrder() function signature:
  - Add deliveryType parameter
  - Add deliveryLatitude parameter
  - Add deliveryLongitude parameter
[ ] Update API call payload to include new fields
[ ] Add error handling for geocoding failures
[ ] Add retry logic for location services
```

### Phase 4: Update Backend API (Week 4)
```
[ ] Update MobileMarketplaceController::placeOrder() validation
[ ] Add delivery_latitude, delivery_longitude, delivery_type validation
[ ] Add service area bounds checking
[ ] Update Order creation to capture coordinates
[ ] Add new database columns if needed (already exist in web)
[ ] Sync validation with web OrderController
```

### Phase 5: Tes & QA (Week 4-5)
```
[ ] Unit tests for location services
[ ] Integration tests for geocoding
[ ] E2E tests for checkout flow (delivery vs pickup)
[ ] Test service area boundary conditions
[ ] Test with and without GPS permission
[ ] Cross-platform testing (iOS/Android)
```

---

## 9. Recommended Code Changes Summary

### Mobile Changes Needed

**1. pubspec.yaml**
```yaml
dependencies:
  flutter_map: ^4.0.0
  geolocator: ^9.0.0
  geocoding: ^2.0.0
  latlong2: ^0.9.0
  permission_handler: ^11.0.0
```

**2. cart_screen.dart**
```dart
// Add delivery type selector
String _deliveryType = 'delivery'; // NEW

// Add in placeOrder():
delivery_type: _deliveryType,
delivery_latitude: _selectedLatitude,  // NEW
delivery_longitude: _selectedLongitude, // NEW
```

**3. api_service.dart**
```dart
Future<Map<String, dynamic>> placeOrder({
    required String token,
    required List<CartItem> items,
    required String deliveryType,        // NEW
    required double? deliveryLatitude,   // NEW
    required double? deliveryLongitude,  // NEW
    // ... existing parameters
}) async {
    final payload = {
        'delivery_type': deliveryType,
        'delivery_latitude': deliveryLatitude,
        'delivery_longitude': deliveryLongitude,
        // ... existing fields
    };
}
```

### Backend Changes Needed

**MobileMarketplaceController.php**
```php
$validated = $request->validate([
    'delivery_type' => 'required|in:delivery,pickup',
    'delivery_address' => 'required_if:delivery_type,delivery',
    'delivery_latitude' => 'nullable|numeric|between:-90,90',
    'delivery_longitude' => 'nullable|numeric|between:-180,180',
    // ... rest
]);

// In Order creation:
'delivery_type' => $validated['delivery_type'],
'delivery_latitude' => $validated['delivery_latitude'] ?? null,
'delivery_longitude' => $validated['delivery_longitude'] ?? null,
```

---

## 10. Testing Checklist

### Mobile App Testing
- [ ] Launch with Map widget displayed
- [ ] Click map to place marker
- [ ] Verify latitude/longitude capture
- [ ] Test address auto-geocoding
- [ ] Validate service area boundaries
- [ ] Test delivery vs pickup toggle
- [ ] Test conditional field visibility
- [ ] Verify API receives all parameters
- [ ] Test offline geocoding fallback
- [ ] Check GPS permission handling

### Backend API Testing  
- [ ] POST /api/mobile/orders accepts new fields
- [ ] validation rejects out-of-bounds coordinates
- [ ] Service area check works correctly
- [ ] Order stores lat/lng in database
- [ ] Web checkout still works (no breaking changes)
- [ ] Validate delivery_type field

### Database Testing
- [ ] Orders table has delivery_latitude column
- [ ] Orders table has delivery_longitude column
- [ ] Historical orders not affected
- [ ] New columns indexed for queries

---

## 11. Database Schema Check

**Orders Table - Current Columns (should already exist for web):**
```sql
-- These should already exist from web:
ALTER TABLE orders ADD COLUMN delivery_type ENUM('delivery', 'pickup') DEFAULT 'delivery';
ALTER TABLE orders ADD COLUMN delivery_latitude DECIMAL(10, 8) NULL;
ALTER TABLE orders ADD COLUMN delivery_longitude DECIMAL(11, 8) NULL;

-- Verify:
SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME='orders' AND COLUMN_NAME IN ('delivery_type', 'delivery_latitude', 'delivery_longitude');
```

---

## Summary TABLE: Feature Status

| Feature | Web | Mobile | Status | Priority |
|---------|-----|--------|--------|----------|
| Product Browse | ✅ | ✅ | PARITY | N/A |
| Shopping Cart | ✅ | ✅ | PARITY | N/A |
| Payment Methods | ✅ | ✅ | PARITY | N/A |
| Delivery Type Selection | ✅ | ❌ | GAP | 1-CRITICAL |
| Map Location Picker | ✅ | ❌ | GAP | 1-CRITICAL |
| Coordinate Capture | ✅ | ❌ | GAP | 1-CRITICAL |
| Address Auto-Geocoding | ✅ | ❌ | GAP | 2-HIGH |
| Service Area Validation | ✅ | ❌ | GAP | 2-HIGH |
| Current Location Detection | ❌ | ❌ | N/A | 3-MEDIUM |
| Order Tracking | ✅ | ✅ | PARITY | N/A |
| Order Cancellation | ✅ | ✅ | PARITY | N/A |
| Ratings & Feedback | ✅ | ✅ | PARITY | N/A |
| Notifications | ✅ | ✅ | PARITY | N/A |

---

## Conclusion

The Flutter mobile app lacks **5 critical delivery/logistics features** that exist in the web marketplace:

1. ❌ **Delivery Type Selection** (Pickup vs Delivery)
2. ❌ **Map-based Location Picker** (Leaflet integration)
3. ❌ **Coordinate Capture** (Latitude/Longitude)
4. ❌ **Auto-Geocoding** (Address → Coordinates)
5. ❌ **Service Area Validation** (Geographic bounds)

These gaps prevent:
- Pickup orders on mobile (web supports them)
- Accurate driver route optimization
- GPS-based delivery tracking
- Geographic service area enforcement

**Recommended Action:** Implement Phase 1-4 roadmap to achieve feature parity with web marketplace. Estimated total effort: **80-140 hours** across mobile and backend.

---

**Document prepared for:** Development Team  
**Status:** READY FOR IMPLEMENTATION  
**Last Updated:** April 8, 2026
