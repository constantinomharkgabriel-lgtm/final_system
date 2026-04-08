# PayMongo Integration - Technical Implementation

## Overview
Integrated PayMongo payment gateway into the payroll disbursement workflow. Bank Transfer and GCash payments now redirect to PayMongo checkout instead of manual entry.

## Database Changes

### Migration File
**File**: `database/migrations/2026_04_04_add_paymongo_integration_to_payroll.php`

**Changes Made**:
```php
Schema::table('payroll', function (Blueprint $table) {
    $table->string('paymongo_session_id')->nullable()->after('workflow_status');
    $table->string('paymongo_payment_intent_id')->nullable()->after('paymongo_session_id');
    $table->json('payment_details')->nullable()->after('paymongo_payment_intent_id');
});
```

**Column Purposes**:
- `paymongo_session_id`: Stores the PayMongo checkout session ID (used as disbursement reference)
- `paymongo_payment_intent_id`: Stores PayMongo payment intent ID (for reference/debugging)
- `payment_details`: JSON field storing payment metadata (amount, method, timestamp, etc.)

**Status**: ✅ Executed successfully in 1 second

---

## Code Changes

### 1. PayrollController - executeDisbursement() Method

**File**: `app/Http/Controllers/PayrollController.php`

**Original Logic**:
```php
// All payments marked directly as paid
$payroll->status = 'paid';
$payroll->save();
```

**New Logic** (Branching based on payment method):
```php
// Check payment method
if (in_array($payroll->payment_method, ['bank_transfer', 'gcash'])) {
    // PayMongo checkout
    return $this->initiatePayMongoPayment($payroll, Auth::id(), Auth::user()->farm_owner_id);
} else {
    // Direct payment (cash/check)
    // Mark as paid directly
    $payroll->status = 'paid';
    $payroll->workflow_status = 'paid';
    $payroll->pay_date = now();
    $payroll->disbursed_by = Auth::id();
    $payroll->disbursed_at = now();
    $payroll->disbursement_reference = $reference;
    $payroll->save();
    
    // Create expense record...
}
```

**What Changed**:
- Payment method check with `in_array()` for bank_transfer and gcash
- Bank Transfer/GCash: Call new `initiatePayMongoPayment()` method
- Cash/Check: Continue with direct payment processing

---

### 2. PayrollController - initiatePayMongoPayment() Method (NEW)

**File**: `app/Http/Controllers/PayrollController.php`

**Purpose**: Create PayMongo checkout session and redirect customer

**Implementation**:
```php
private function initiatePayMongoPayment($payroll, $userId, $farmOwnerId)
{
    // Determine payment methods array based on payment method
    $paymentMethods = [];
    if ($payroll->payment_method === 'bank_transfer') {
        $paymentMethods = ['bank_transfer'];
    } elseif ($payroll->payment_method === 'gcash') {
        $paymentMethods = ['gcash'];
    }
    
    // Prepare PayMongo request parameters
    $params = [
        'line_items' => [
            [
                'currency' => 'PHP',
                'amount' => $payroll->net_pay * 100, // Convert to centavos
                'description' => "Payroll Disbursement - {$payroll->period_start} to {$payroll->period_end}",
                'quantity' => 1,
            ]
        ],
        'payment_method_types' => $paymentMethods,
        'metadata' => [
            'payroll_id' => $payroll->id,
            'payroll_period' => "{$payroll->period_start} to {$payroll->period_end}",
            'employee_id' => $payroll->employee_id,
            'farm_owner_id' => $farmOwnerId,
            'payment_type' => 'payroll_disbursement'
        ],
        'success_url' => route('payroll.paymongo-success', ['payroll' => $payroll->id]),
        'cancel_url' => route('payroll.show', ['payroll' => $payroll->id]),
    ];
    
    // Create checkout session via PayMongoService
    $session = $this->paymongoService->createCheckoutSession($params);
    
    // Store session details in payroll record
    $payroll->paymongo_session_id = $session['id'];
    $payroll->payment_details = [
        'payment_method' => $payroll->payment_method,
        'amount_php' => $payroll->net_pay,
        'initiated_at' => now(),
        'initiated_by' => $userId,
    ];
    $payroll->save();
    
    // Redirect to PayMongo checkout
    return redirect()->away($session['checkout_url']);
}
```

**Key Parameters**:
- **amount**: Converted to centavos (multiply by 100) because PayMongo API uses integer centavos
- **payment_method_types**: Array of allowed methods (bank_transfer or gcash)
- **metadata**: Custom data to track transaction (payroll_id, employee_id, etc.)
- **success_url**: Route to redirect to after successful payment
- **cancel_url**: Route to redirect to if payment cancelled

**Returns**: Redirect to PayMongo checkout URL

---

### 3. PayrollController - handlePayMongoSuccess() Method (NEW)

**File**: `app/Http/Controllers/PayrollController.php`

**Purpose**: PayMongo success callback handler - Mark payroll as paid

**Implementation**:
```php
public function handlePayMongoSuccess(Payroll $payroll)
{
    // Verify user is authorized (Finance role)
    if (Auth::user()->farm_owner_id !== $payroll->employee->farm_owner_id) {
        return redirect()->route('payroll.show', $payroll)->with('error', 'Unauthorized');
    }
    
    // Update payroll status
    $payroll->status = 'paid';
    $payroll->workflow_status = 'paid';
    $payroll->pay_date = now();
    $payroll->disbursed_by = Auth::id();
    $payroll->disbursed_at = now();
    $payroll->disbursement_reference = $payroll->paymongo_session_id; // Use session ID as reference
    $payroll->save();
    
    // Create expense record for accounting
    Expense::create([
        'farm_owner_id' => $payroll->farm_owner_id,
        'payroll_id' => $payroll->id,
        'description' => "Payroll Disbursement - {$payroll->employee->user->name}",
        'amount' => $payroll->net_pay,
        'expense_date' => now(),
        'category' => 'payroll',
        'reference' => $payroll->paymongo_session_id,
        'payment_method' => $payroll->payment_method,
        'notes' => json_encode($payroll->payment_details),
        'recorded_by' => Auth::id(),
    ]);
    
    // Clear caches
    Cache::forget("payroll_stats_{$payroll->farm_owner_id}");
    Cache::forget("payroll_monthly_stats_{$payroll->farm_owner_id}");
    
    return redirect()->route('payroll.show', $payroll)
        ->with('success', "Payroll disbursed successfully via {$payroll->payment_method}!");
}
```

**What Happens**:
1. Verifies user is Finance and authorized
2. Updates payroll status to 'paid'
3. Sets dates: pay_date, disbursed_at
4. Stores PayMongo session ID as disbursement_reference
5. Creates Expense record with payment details
6. Clears cache for statistics
7. Redirects with success message

---

### 4. Payroll Model Changes

**File**: `app/Models/Payroll.php`

**Added to $fillable**:
```php
protected $fillable = [
    // ... existing fields ...
    'paymongo_session_id',
    'paymongo_payment_intent_id',
    'payment_details',
];
```

**Added Casts**:
```php
protected $casts = [
    // ... existing casts ...
    'payment_details' => 'json',
];
```

**Why**: Allow mass assignment of new columns and automatic JSON serialization/deserialization

---

### 5. Routes Configuration

**File**: `routes/web.php`

**Added Route**:
```php
Route::middleware(['auth', 'role:finance'])->group(function () {
    Route::prefix('payroll')->group(function () {
        // ... existing routes ...
        
        // PayMongo success callback
        Route::get('{payroll}/paymongo-success', 
            [PayrollController::class, 'handlePayMongoSuccess']
        )->name('payroll.paymongo-success');
    });
});
```

**Middleware**:
- `auth`: Only authenticated users
- `role:finance`: Only Finance users can access success callback (security)

**Why**: Route handles the redirect back from PayMongo after successful payment

---

## Payment Flow Diagrams

### Direct Payment (Cash/Check)
```
Finance clicks "Execute Disbursement"
↓
Controller: executeDisbursement()
↓
payment_method check: cash OR check?
↓
YES → Direct payment logic
     ↓
     Set status = 'paid'
     ↓
     Create Expense record
     ↓
     Update caches
     ↓
     Show success page ✓
```

### PayMongo Payment (Bank Transfer/GCash)
```
Finance clicks "Execute Disbursement"
↓
Controller: executeDisbursement()
↓
payment_method check: bank_transfer OR gcash?
↓
YES → initiatePayMongoPayment()
     ↓
     Create checkout session via PayMongoService
     ↓
     Store session ID in payroll_paymongo_session_id
     ↓
     Redirect to PayMongo checkout URL
     ↓
[Customer on PayMongo]
↓
Customer completes payment
↓
PayMongo redirects to /payroll/{id}/paymongo-success
↓
Controller: handlePayMongoSuccess()
↓
Set status = 'paid'
↓
Use session ID as disbursement_reference
↓
Create Expense record
↓
Show success page ✓
```

---

## Environment Configuration

### .env Variables (Use Your Own Test Keys)
```
PAYMONGO_PUBLIC_KEY=pk_test_your_public_key_here
PAYMONGO_SECRET_KEY=sk_test_your_secret_key_here
```

### For Production
1. Get live keys from PayMongo dashboard: https://dashboard.paymongo.com/
2. Update .env with live keys:
   ```
   PAYMONGO_PUBLIC_KEY=pk_live_xxxxx...
   PAYMONGO_SECRET_KEY=sk_live_xxxxx...
   ```
3. Update PayMongo webhook endpoints to point to production URL
4. Test thoroughly before going live

---

## Data Storage

### In Database (payroll table)
```sql
SELECT 
    id,
    payment_method,        -- 'cash', 'check', 'bank_transfer', 'gcash'
    paymongo_session_id,   -- 'sess_xxxxx' (PayMongo session ID)
    payment_details,       -- JSON: {"payment_method": "gcash", "amount_php": 20020, ...}
    disbursement_reference,-- Same as paymongo_session_id for PayMongo payments
    status,                -- 'paid' after successful payment
    workflow_status        -- 'paid' after successful payment
FROM payroll
WHERE payment_method IN ('bank_transfer', 'gcash');
```

### payment_details JSON Structure
```json
{
    "payment_method": "gcash",
    "amount_php": 20020,
    "initiated_at": "2026-04-04T10:30:00",
    "initiated_by": 5
}
```

---

## Error Handling

### Missing PayMongoService
If you get "PayMongoService not found" error:
1. Ensure `PayMongoService` exists at `app/Services/PayMongoService.php`
2. Ensure it has `createCheckoutSession(array $params)` method
3. Check `config/services.php` has PayMongo configuration

### PayMongo API Errors
If checkout session creation fails:
1. Check PAYMONGO_SECRET_KEY is valid
2. Verify amount is in centavos (net_pay * 100)
3. Check PayMongo account is activated for test mode
4. Review PayMongo Dashboard → Webhooks for API logs

### Authorization Fails
If "Unauthorized" error on success callback:
1. Verify user is Finance role
2. Verify user's farm_owner_id matches payroll's farm_owner_id
3. Check middleware configuration

---

## Testing Checklist

- [ ] Farm Owner selects Bank Transfer as payment method
- [ ] Farm Owner completes "Prepare Disbursement" (workflow = ready_for_disbursement)
- [ ] Finance clicks "Execute Disbursement"
- [ ] System redirects to PayMongo checkout
- [ ] PayMongo shows Bank Transfer option
- [ ] User completes test payment
- [ ] System redirects back to success page
- [ ] payrol.status = 'paid' in database
- [ ] paymongo_session_id is stored
- [ ] Expense record created with PayMongo details
- [ ] Repeat for GCash payment method

---

## Future Enhancements

1. **Webhook Verification**
   - Verify PayMongo webhook signatures
   - Handle payment events from server-side

2. **Payment Status Polling**
   - Query PayMongo API for payment status
   - Handle delayed confirmations

3. **Failed Payment Handling**
   - Retry logic for failed payments
   - Customer notification on payment failure

4. **Batch Disbursement**
   - Disburse multiple employees in one batch
   - Single payment entry instead of per-employee

5. **Refund Processing**
   - Reverse disbursements if needed
   - Generate credit notes

6. **Audit Trail**
   - Log all payment attempts
   - Store PayMongo webhook responses
   - Compliance records

---

## Security Considerations

1. ✅ **Authorization** - Only Finance users can execute disbursement (role:finance middleware)
2. ✅ **Verification** - Verify farm_owner_id matches on success callback
3. ✅ **Data Integrity** - Store payroll state before PayMongo redirect
4. ❌ **Webhook Security** - NOT YET IMPLEMENTED
   - Should verify PayMongo webhook signatures
   - Should only trust signed webhooks
   - Add when moving to production

5. ✅ **Amount Safety** - Always use net_pay from database, never from user input
6. ✅ **Reference Tracking** - Store PayMongo session ID for audit trail

---

## Related Files

- `app/Http/Controllers/PayrollController.php` - Main controller with new methods
- `app/Models/Payroll.php` - Model with new fillable/casts
- `app/Services/PayMongoService.php` - Existing PayMongo service (createCheckoutSession method)
- `database/migrations/2026_04_04_add_paymongo_integration_to_payroll.php` - Database migration
- `routes/web.php` - PayMongo success route
- `resources/views/department/payroll/show.blade.php` - Finance view (Execute button)
- `resources/views/farmowner/payroll/show.blade.php` - Farm Owner view (Prepare button)

---

## Support

For PayMongo API issues:
- PayMongo Dashboard: https://dashboard.paymongo.com/
- PayMongo API Docs: https://developers.paymongo.com/
- PayMongo Support: support@paymongo.com

For Laravel integration issues:
- Check `storage/logs/laravel.log` for errors
- Run `php artisan migrate:status` to verify migrations
- Run `php artisan route:list` to verify routes
