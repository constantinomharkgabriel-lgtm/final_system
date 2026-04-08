# Farm Owner Rejection Workflow - Testing Guide

## Overview
The farm owner rejection workflow has been fully implemented with the following features:
- ✅ Reject button in SuperAdmin Farm Owners page (next to Approve button)
- ✅ Modal dialog for entering rejection reason
- ✅ Email notification sent to farm owner with rejection reason
- ✅ Flash messages for success/error feedback
- ✅ Database status update to 'rejected'

## Implementation Details

### Frontend Components
**File**: `resources/views/superadmin/farm-owners.blade.php`

1. **Reject Button** 
   - Located in actions column next to Approve button
   - Only visible when farm_owner.permit_status === 'pending'
   - Triggers modal dialog with `openRejectModal(farmOwnerId, farmName)`

2. **Rejection Modal Dialog**
   - Modal ID: `#rejectModal`
   - Contains rejection reason textarea (max 500 characters)
   - Dynamically sets form action to: `/super-admin/farm-owners/{id}/reject`
   - Two action buttons: Cancel and Confirm Rejection
   - Closes on: ESC key, outside click, or Cancel button

3. **Flash Messages**
   - Success message: "Farm owner rejected and notification email sent."
   - Partial success: "Farm owner rejected, but notification email could not be delivered."
   - Error message: "Farm owner is not pending approval"
   - Messages auto-dismiss when user clicks X button

### Backend Components
**File**: `app/Http/Controllers/SuperAdminController.php`
**Method**: `reject_farm_owner($id, Request $request)`

**Process**:
1. **Validation**: Validates rejection reason (required, string, max 500 chars)
2. **Status Update**: Sets farm_owner.permit_status = 'rejected'
3. **Cache Clear**: Clears cache for farm statistics
4. **Email Notification**:
   - To: Farm owner's email (from user relationship)
   - Subject: "Farm Owner Registration Update"
   - Body: Includes farm name and rejection reason
   - Error handling with logging
5. **Logging**: Records action with farm owner ID and rejection reason

### Routes
**File**: `routes/web.php` (Line 128)

```php
Route::post('/super-admin/farm-owners/{id}/reject', 
    [SuperAdminController::class, 'reject_farm_owner'])
    ->name('superadmin.reject_farm_owner');
```

## Email Configuration
**File**: `.env`
- MAIL_MAILER: smtp
- MAIL_HOST: smtp.gmail.com
- MAIL_PORT: 587
- MAIL_ENCRYPTION: tls
- MAIL_FROM_ADDRESS: lawrencetabutol31@gmail.com
- MAIL_USERNAME: lawrencetabutol31@gmail.com

## Testing Checklist

### Prerequisites
- [ ] SuperAdmin user account logged in
- [ ] At least one Farm Owner with `permit_status = 'pending'`
- [ ] SMTP credentials configured in .env (Gmail configured ✅)
- [ ] Laravel development server running (`php artisan serve`)

### Test Steps

#### 1. Access Farm Owners Page
```
1. Go to SuperAdmin Dashboard
2. Click "Farm Owners" in sidebar
3. Verify page loads with table of farm owners
```

#### 2. Locate Pending Farm Owner
```
1. Look for a farm owner with "Pending" status badge (yellow)
2. Note the farm name for later verification
3. Verify Approve and Reject buttons are visible
```

#### 3. Click Reject Button
```
1. Click the "Reject" button for the pending farm owner
2. Verify modal dialog appears with:
   - Title: "Reject Farm Owner Registration"
   - Farm name displayed correctly
   - Textarea for "Rejection Reason"
   - Cancel and Confirm buttons
```

#### 4. Enter Rejection Reason
```
1. Click in the rejection reason textarea
2. Enter a test reason (e.g., "Invalid documentation provided")
3. Verify character count stays under 500
4. Check that submit button is enabled
```

#### 5. Submit Rejection
```
1. Click "Confirm Rejection" button
2. Monitor console for form submission (can take 2-5 seconds)
3. Verify modal closes
```

#### 6. Verify Success Message
```
1. Check page displays green success message:
   "Farm owner rejected and notification email sent."
   OR
   "Farm owner rejected, but notification email could not be delivered."
2. Verify message can be dismissed with X button
```

#### 7. Verify Status Change
```
1. Look for the farm owner in the table
2. Verify status badge changed to "Rejected" (red)
3. Verify Reject/Approve buttons no longer appear
```

#### 8. Verify Email Delivery
```
1. Check farm owner's email inbox
2. Look for email from: lawrencetabutol31@gmail.com
3. Subject should be: "Farm Owner Registration Update"
4. Body should contain:
   - Farm name
   - "registration for {farm_name} has been denied"
   - The rejection reason you entered
5. If email doesn't arrive:
   - Check spam/junk folder
   - Check Laravel logs: storage/logs/laravel.log
```

#### 9. Test Error Scenarios
```
1. Try rejecting an already rejected farm owner:
   Expected: Error message "Farm owner is not pending approval"

2. Try rejecting an approved farm owner:
   Expected: Error message "Farm owner is not pending approval"

3. Try submitting modal without rejection reason:
   Expected: Form validation error in textarea
```

#### 10. Test Modal Close Behaviors
```
1. Click Reject button again
2. Press ESC key on keyboard
   Expected: Modal closes
3. Click Reject button
4. Click outside modal (on dark overlay)
   Expected: Modal closes
5. Click Reject button
6. Click Cancel button
   Expected: Modal closes
```

### Expected Email Template

**Subject**: Farm Owner Registration Update

**Body**:
```
Your farm owner registration for {FARM_NAME} has been denied by the Super Admin. 
Reason: {REJECTION_REASON}
```

**Example**:
```
Your farm owner registration for Happy Chickens Farm has been denied by the Super Admin. 
Reason: Invalid documentation provided
```

## Troubleshooting

### Issue: Modal doesn't appear when clicking Reject
**Solution**: 
- Check browser console for JavaScript errors (F12)
- Verify modal HTML exists in page source
- Ensure Tailwind CSS classes are loading (check styling)

### Issue: Form doesn't submit when clicking Confirm
**Solution**:
- Check browser console for JavaScript errors
- Verify form action URL is correct: `/super-admin/farm-owners/{id}/reject`
- Check that textarea has text entered (required field)
- Check network tab to see if POST request is sent

### Issue: Success message doesn't appear after rejection
**Solution**:
- Check if page is redirecting properly
- Verify session/CSRF token is valid
- Check Laravel logs for PHP exceptions
- Ensure database write was successful

### Issue: Email not received
**Solution**:
1. Check `storage/logs/laravel.log` for email sending errors
2. Verify farm owner has valid email address in database
3. Check that SMTP credentials are correct in .env
4. Test Gmail account: Try sending test email manually
5. Check spam/junk folder
6. If using Gmail: Enable "Less secure app access" or use app password

### Issue: "Mail delivery failed" message appears
**Solution**:
- Farm owner is still marked as 'rejected' (this is correct)
- Email was not delivered but status was updated
- Check Laravel logs for specific SMTP error
- Verify MAIL credentials in .env
- Test SMTP connection manually if needed

## Files Modified

### 1. resources/views/superadmin/farm-owners.blade.php
- Added flash message display (success/error)
- Added Reject button to pending actions
- Added rejection modal dialog
- Added JavaScript functions: openRejectModal(), closeRejectModal()
- Added form submission handling

### 2. app/Http/Controllers/SuperAdminController.php
- ✅ ALREADY IMPLEMENTED: reject_farm_owner() method with:
  - Request validation
  - Status update
  - Email notification
  - Error handling
  - Logging

### 3. routes/web.php
- ✅ ALREADY IMPLEMENTED: POST route for reject_farm_owner

## Success Criteria

✅ All of the following should be true:
1. Reject button appears next to Approve for pending farm owners
2. Clicking Reject button opens modal dialog
3. Modal shows correct farm name
4. Can type rejection reason in textarea
5. Clicking Confirm submits form without errors
6. Modal closes after submission
7. Success message appears indicating email was sent
8. Farm owner status changes from "pending" to "rejected"
9. Reject/Approve buttons disappear for rejected farm owners
10. Farm owner receives email with rejection reason
11. Email content includes farm name and rejection reason
12. All previous approvals still work normally

## Notes

- The rejection system is designed to be fail-safe: even if email delivery fails, the farm owner is still marked as rejected
- Farm owners cannot be re-approved after rejection (new constraint can be added if needed)
- All actions are logged in Laravel logs for audit trail
- Modal prevents double-submission with loading state

---

**Status**: ✅ READY FOR TESTING

Run the server with: `php artisan serve`
Navigate to: http://localhost:8000
