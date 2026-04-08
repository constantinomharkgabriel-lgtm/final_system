# PayMongo Integration - Complete Testing Guide

## Current System State

**Payroll Being Used for Testing**: PAY-2026-00002
- Employee: Lawrence Tabutol
- Net Pay: ₱20,020.00
- Status: approved
- Workflow Status: ready_for_disbursement
- Payment Method: cash (set by Farm Owner in previous step)

---

## Testing Option 1: Direct Cash Payment (Current Setup)

**Current Configuration**:
- Payment method: **cash** (already selected)
- Workflow: **ready_for_disbursement** ✓
- Status: **approved** ✓

### Steps to Execute Cash Disbursement:

1. **Login as Finance**
   ```
   Email: finance@example.com
   Password: password
   ```

2. **Navigate to Payroll**
   - URL: http://localhost:8000/farm-owner/payroll
   - Look for payroll PAY-2026-00002

3. **View the Payroll**
   - Click "View" button or payroll ID
   - You should see:
     - Status: approved
     - Workflow Status: ready_for_disbursement
     - Payment Method: cash
     - Employee: Lawrence Tabutol
     - Net Pay: ₱20,020.00

4. **Execute Disbursement**
   - Look for "Execute Disbursement" button (in header-actions)
   - There will be a form with:
     - Input field: "Disbursement Reference"
     - Submit button: "Execute Disbursement"
   
5. **Enter Reference Number**
   - Type: `CASH-001` (or any cash reference format)
   - Example: `CASH-2026-420` or `PAYMENT-001`

6. **Click Execute**
   - Button: "Execute Disbursement"
   - ✓ Should NOT redirect to PayMongo (it's cash)
   - ✓ Should mark as PAID immediately
   - ✓ Should show success message

### Expected Result:
```
✓ Payroll marked as PAID
✓ Status changed to: paid
✓ Workflow status changed to: paid
✓ Disbursement reference: CASH-001 (stored)
✓ pay_date: today
✓ Expense record created
✓ Success message displayed
```

---

## Testing Option 2: Bank Transfer via PayMongo

**Prerequisites**:
- PayMongo test account activated (test mode)
- PayMongo public/secret keys in .env (already set)

### Steps to Set Up Bank Transfer:

1. **Login as Farm Owner**
   ```
   Email: farmowner@example.com
   Password: password
   ```

2. **Go to Payroll Management**
   - URL: http://localhost:8000/payroll
   - Filter for PAY-2026-00002

3. **View the Payroll**
   - Click on PAY-2026-00002
   - Status should be: ready_for_disbursement
   - Current payment method: cash

4. **Change Payment Method to Bank Transfer**
   - Look for "Prepare Disbursement" button
   - Select payment method: **Bank Transfer**
   - Click "Prepare Disbursement"
   - ✓ Payroll updated with payment_method = bank_transfer

5. **Now Switch to Finance Role**
   ```
   Email: finance@example.com
   Password: password
   ```

6. **Execute Disbursement**
   - Navigate to Payroll → View PAY-2026-00002
   - Click "Execute Disbursement" button
   
7. **What Happens Next**:
   - ✓ System detects payment_method = bank_transfer
   - ✓ Creates PayMongo checkout session
   - ✓ Redirects to PayMongo checkout page
   - ✓ You see "Bank Transfer" payment option

### On PayMongo Checkout:

1. **Select Bank Option**
   - Click on a bank (e.g., BDO, Manila Bank, etc.)

2. **Complete Payment**
   - Scan QR code or submit bank details
   - For testing: Use PayMongo test bank credentials
   - (Ask PayMongo support for test bank credentials)

3. **After Successful Payment**
   - ✓ Redirected back to: `/payroll/{id}/paymongo-success`
   - ✓ handlePayMongoSuccess() method processes payment
   - ✓ Payroll marked as PAID
   - ✓ Success message displayed

### Expected Result:
```
✓ Payroll redirected to PayMongo checkout
✓ Payment method shows: Bank Transfer
✓ PayMongo session created
✓ User completes payment on PayMongo
✓ Redirected back to system
✓ Payroll status: paid
✓ paymongo_session_id stored in database
✓ Disbursement reference: (PayMongo session ID)
✓ Expense record created with payment details
```

---

## Testing Option 3: GCash via PayMongo

**Prerequisites**:
- Same as Bank Transfer
- GCash test wallet (from PayMongo)

### Steps:

1. **As Farm Owner**: Change payment method to GCash
2. **As Finance**: Execute Disbursement
3. **Get Redirected to PayMongo** (GCash option shown)
4. **Complete Payment** via GCash
5. **Get Redirected Back** and marked as PAID

---

## How to Check Results

### Check #1: Database Verification

```bash
# SSH into server or use DB client
# Connect to Supabase PostgreSQL

# Query payroll status
SELECT 
    id, 
    payment_method, 
    status, 
    workflow_status,
    paymongo_session_id,
    disbursement_reference,
    payment_details
FROM payroll 
WHERE id = (SELECT id FROM payroll WHERE employee_id = 1 ORDER BY created_at DESC LIMIT 1);

# Should show:
# id | payment_method | status | workflow_status | paymongo_session_id | disbursement_reference | payment_details
# 2  | cash           | paid   | paid            | NULL                | CASH-001               | NULL
# OR
# 2  | bank_transfer  | paid   | paid            | sess_xxxxx...       | sess_xxxxx...          | {"payment_method": "bank_transfer", ...}
```

### Check #2: Browser Console

After success page loads:
```javascript
// Check if payroll ID is visible
console.log(document.body.innerText);
// Should contain success message and payroll ID
```

### Check #3: Logs

```bash
# Check Laravel logs for any errors
tail -f storage/logs/laravel.log

# Look for entries like:
# "PayMongo checkout session created: sess_xxxxx"
# "Payroll marked as paid: PAY-2026-00002"
```

### Check #4: PayMongo Dashboard

1. Go to https://dashboard.paymongo.com/
2. Go to Live Transactions or Test Transactions
3. Look for recent transaction for "Payroll Disbursement"
4. Should show:
   - Amount: ₱20,020.00
   - Status: Completed/Successful
   - Payment Method: Bank Transfer or GCash
   - Metadata: payroll_id, employee_id, etc.

---

## Troubleshooting

### Issue: "Unauthorized" on Success Callback

**Cause**: User's farm_owner_id doesn't match payroll's farm_owner_id

**Solution**: 
- Make sure Finance user is disbursing their own farm's payroll
- Check user role is "finance"
- Verify farm_owner_id relationship

### Issue: Not Redirected to PayMongo

**Cause**: Payment method is not bank_transfer or gcash

**Solution**:
- Go back as Farm Owner
- Explicitly change payment method
- Make sure it's selected and saved

### Issue: "PayMongoService not found"

**Cause**: Class not imported or doesn't exist

**Solution**:
- Check if `app/Services/PayMongoService.php` exists
- Verify Paymongo service provider is registered
- Run: `php composer dump-autoload`
- Run: `php artisan config:cache`

### Issue: PayMongo Checkout Not Loading

**Cause**: Invalid PayMongo keys or API down

**Solution**:
- Verify PAYMONGO_SECRET_KEY in .env
- Test PayMongo status: https://status.paymongo.com/
- Check PayMongo Dashboard is accessible
- Verify test account is active for test mode

### Issue: Redirect Loop (Keep Going Back to Form)

**Cause**: Validation error or permission issue

**Solution**:
- Check browser console for JS errors
- Check Laravel logs: `tail -f storage/logs/laravel.log`
- Verify payment_method is correctly set in database
- Ensure workflow_status = ready_for_disbursement

---

## Step-by-Step Video Script (For Your Reference)

### For Cash Payment Demo:
```
"This is Finance. I'm viewing payroll PAY-2026-00002 in approved status, 
ready for disbursement, with cash as payment method.

I click 'Execute Disbursement' button.

A form appears asking for disbursement reference. I enter 'CASH-001'.

I click the Execute button.

The system processes the payment and marks the payroll as PAID. 
The page now shows 'Payment Successfully Processed'.

Let me check the database to confirm..."
```

### For Bank Transfer Demo:
```
"First, let me switch to Farm Owner role and change payment method to Bank Transfer.

I'm now on the payroll edit page. I select 'Bank Transfer' from payment method dropdown.

I click 'Prepare Disbursement' to save the change.

Now switching to Finance role...

I navigate back to the payroll and click 'Execute Disbursement'.

The system detects Bank Transfer and redirects me to PayMongo checkout page.

On PayMongo, I can see the payment amount ₱20,020.00 and bank transfer option.

I click select a bank and complete the payment simulation.

PayMongo processes the payment and redirects me back to the success page.

The payroll is now marked as PAID with the PayMongo session ID as reference."
```

---

## Key Differences: Before vs After

### Before PayMongo Integration:
```
Finance:
- Execute Disbursement
- Enter payment reference manually
- Mark as paid (in-app)
- No payment gateway
- No payment verification
```

### After PayMongo Integration:
```
Finance (for bank_transfer/gcash):
- Execute Disbursement
- Redirected to PayMongo checkout
- Customer completes payment via bank/gcash
- PayMongo confirms payment
- Automatically marked as paid
- Payment reference = PayMongo session ID

Finance (for cash/check):
- Execute Disbursement
- Enter payment reference
- Mark as paid (same as before)
```

---

## What to Verify After Each Test

- [ ] Payroll status changed to "paid"
- [ ] Workflow status changed to "paid"
- [ ] Disbursement reference is recorded
- [ ] Expense record created
- [ ] Payment method stored correctly
- [ ] paymongo_session_id stored (if PayMongo used)
- [ ] Payment details JSON populated (if PayMongo used)
- [ ] Stats cache cleared (loading feels fresh)
- [ ] No errors in browser console
- [ ] No errors in Laravel logs
- [ ] Email notification sent (if configured)

---

## Next Steps After Testing

1. **If Cash/Check Works** ✓
   - System is ready for manual payments
   - Can disburse immediately

2. **If PayMongo Bank Transfer Works** ✓
   - Can receive payments via bank transfer
   - No manual entry needed
   - Payment automatically verified

3. **If PayMongo GCash Works** ✓
   - Can receive mobile wallet payments
   - Lower fees than bank transfer
   - Instant settlement

4. **If Everything Works** ✓✓✓
   - System ready for production
   - All payment methods operational
   - Complete workflow functional

5. **Next Phase**:
   - Implement webhook signature verification (production safety)
   - Add payment status polling for edge cases
   - Setup automatic email notifications
   - Configure refund/reversal handling

---

## Support Information

**Having Issues?**
1. Check logs: `tail -f storage/logs/laravel.log`
2. Check database: Query the payroll table
3. Check PayMongo Dashboard for transaction status
4. Review this document's Troubleshooting section

**Need Help?**
- PayMongo Support: support@paymongo.com
- Laravel Issues: Review code comments in PayrollController
- Database Issues: Query the payroll table directly

