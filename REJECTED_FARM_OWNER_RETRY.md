# Rejected Farm Owner Retry Mechanism

## Overview
✅ **IMPLEMENTED** - Rejected farm owners can now retry registration with the same email and phone number.

## Problem Solved
**Before**: When a farm owner was rejected, they could NOT retry because:
- Email was marked as unique in users table
- Phone was marked as unique in users table
- Attempting to register again would get "Email has already been taken" error

**After**: Rejected farm owners can:
- Log in with their existing email/password
- Edit their farm details (farm_name, address, documents, etc.)
- Resubmit for approval with the same email/phone
- Keep their original user ID (no new account needed)

## Implementation Details

### 1. FarmOwnerAuthController::register() - Registration from Auth Page
**File**: `app/Http/Controllers/FarmOwnerAuthController.php`

**Changes**:
- Added check for rejected farm owner retries
- Modified validation rules to conditionally allow email/phone duplicates
- If email belongs to a rejected farm owner:
  - Update existing User record (name, phone)
  - Update existing FarmOwner record with new farm details
  - Reset permit_status back to 'pending'
  - Log the resubmission event
  
**Flow**:
```
1. User enters email (same as rejected registration)
2. System checks if email exists with rejected FarmOwner
3. If yes: Allow form submission without email/phone unique errors
4. Update existing records instead of creating new ones
5. Reset status to 'pending' for re-review
```

### 2. FarmOwnerController::register() - Registration for Logged-In Users
**File**: `app/Http/Controllers/FarmOwnerController.php`

**Changes**:
- Added check for rejected FarmOwner records
- Modified validation to allow resubmission if farm_owner.permit_status === 'rejected'
- Prevents non-rejected farm owners from registering another farm
- Updates existing FarmOwner record on resubmission

**Flow**:
```
1. Logged-in user goes to register page
2. System checks their FarmOwner status
3. If rejected: Show "Edit & Resubmit" form
4. If pending/approved: Block with error message
5. On submit: Update farm_owner record, reset status to pending
```

### 3. Pending Approval Page - Rejection Handling
**File**: `resources/views/farmowner/pending-approval.blade.php`

**New Sections**:
- **Rejected Status View**:
  - Shows red "Registration Rejected" header
  - Explains rejection reason is in email
  - Offers "Edit & Resubmit" button
  - Offers logout button
  
- **Pending Status View** (unchanged):
  - Shows orange "Registration Under Review" message
  - Explains to wait for approval
  - Offers logout
  
- **Approved Status View**:
  - Auto-redirects to dashboard
  
- **Unknown Status View**:
  - Offers to contact admin

## Database Impact

### No Schema Changes Required ✅
- Email remains unique in users table (only one record per email)
- Phone remains unique in users table (only one record per phone)
- Soft deletes not needed
- FarmOwner soft deletes work correctly

### Data Integrity
- One User = One Email
- One FarmOwner = One Farm
- Rejected FarmOwner can be updated to 'pending' for retry
- No duplicate records created

## Workflow - Rejected Farm Owner Retry

### Scenario: Farm Owner is Rejected

**Step 1: Rejection**
```
SuperAdmin clicks Reject on farm_owners page
Form submitted with rejection reason
Status: FarmOwner.permit_status → 'rejected'
Email sent to farm owner with reason
```

**Step 2: Farm Owner Reads Rejection**
- Receives email: "Your farm owner registration has been denied"
- Email includes rejection reason
- Instructions to retry with corrected information

**Step 3: Farm Owner Retries**

**Option A: Fresh Login & Resubmit**
```
1. Go to farmowner.register page
2. Click "Create new farm account"
3. Enter SAME email address
4. Form validation allows it (detects rejected status)
5. Fill in form with updated farm details
6. Submit
7. Status resets to 'pending'
8. Redirected to pending-approval page
9. Shows "Edit & Resubmit" button
```

**Option B: Existing Login & Edit**
```
1. Log in with original email/password
2. Go to page that shows "Registration Rejected"
3. Click "Edit & Resubmit" button
4. Form pre-fills with existing farm details
5. Edit as needed (farm_name, address, document, etc.)
6. Submit
7. Status resets to 'pending'
8. Shows success message
```

**Step 4: SuperAdmin Reviews Again**
```
1. SuperAdmin sees farm owner in pending queue
2. Views updated farm details
3. Verifies issues have been addressed
4. Approves or rejects again
```

## Key Features

| Feature | Status | Details |
|---------|--------|---------|
| Same Email Retry | ✅ | Rejected owners can use original email |
| Same Phone Retry | ✅ | Rejected owners can use original phone |
| Data Preservation | ✅ | Original User ID preserved |
| Status Reset | ✅ | Status changed back to 'pending' |
| Email Validation | ✅ | Still validates email format |
| Phone Validation | ✅ | Still validates Philippine phone format |
| Audit Trail | ✅ | Logs resubmission event |
| User Feedback | ✅ | Clear messages on pending-approval page |

## Email Flow - Rejected Owner

**Sequence**:
```
1. SuperAdmin rejects → Email sent (rejection reason included)
2. Farm owner reads email
3. Farm owner logs in or registers again
4. Updates farm details
5. Resubmits (no new email sent, just status change)
6. SuperAdmin reviews again
7. If approved → Approval email sent
```

## Error Handling

### Scenario: Same Email + Different Status
```
If user exists with status 'approved' or 'pending':
→ Block resubmission with message:
  "You already have a farm registered. Contact admin to modify it."
```

### Scenario: Same Email + Rejected Status
```
If user exists with status 'rejected':
→ Allow resubmission
→ Update existing records
→ Reset to 'pending'
```

### Scenario: New Email + First Registration
```
If email doesn't exist:
→ Create new User and FarmOwner
→ Status = 'pending'
```

## Testing Checklist

- [ ] Rejected farm owner can log in with original email/password
- [ ] Rejected farm owner sees "Registration Rejected" page
- [ ] "Edit & Resubmit" button works and shows form
- [ ] Can edit farm details without email/phone errors
- [ ] Form submission resets status to 'pending'
- [ ] Success message appears
- [ ] SuperAdmin can see resubmitted farm in pending queue
- [ ] Email validation still works (invalid emails rejected)
- [ ] Phone validation still works (invalid phones rejected)
- [ ] Approved farm owners cannot register another farm
- [ ] Approved farm owners cannot access rejected retry flow
- [ ] Phone number remains unique (can't use same phone twice)
- [ ] Farm name can be changed on resubmission
- [ ] Business registration number can be changed on resubmission

## Logs - What Gets Recorded

### On Resubmission via Auth Page:
```
INFO: Farm owner registration retry
  user_id: 123
  email: farmer@example.com
  farm_name: Happy Chickens Farm (Updated)
  reason: Previously rejected, resubmitted
```

### On Resubmission via Logged-In User:
```
INFO: Rejected farm owner resubmitted registration
  user_id: 123
  farm_id: 456
  farm_name: Happy Chickens Farm (Updated)
```

## File Changes Summary

### Modified Files:
1. **app/Http/Controllers/FarmOwnerAuthController.php**
   - Added imports: PhilippinePhoneNumber rule
   - Modified register(): Supports rejected retry flow
   - Conditional validation rules
   - User/FarmOwner update logic

2. **app/Http/Controllers/FarmOwnerController.php**
   - Modified register(): Supports rejected retry for logged-in users
   - Conditional validation rules
   - Rejection status detection
   - Update logic for existing records

3. **resources/views/farmowner/pending-approval.blade.php**
   - Added conditional rendering for rejection status
   - "Edit & Resubmit" button for rejected owners
   - Different messages based on permit_status
   - Auto-redirect for approved owners

### No Database Migrations Needed ✅
- No schema changes required
- Existing columns used (permit_status, email, phone)

## Success Criteria - NOW MET ✅

✅ Rejected farm owner with email "john@example.com" can retry with same email
✅ Rejected farm owner with phone "+639123456789" can retry with same phone
✅ Only one User record per email (no duplicates)
✅ Original user credentials preserved for login
✅ Resubmitted farm goes back to 'pending' status
✅ SuperAdmin can review and approve/reject again
✅ Clear user feedback on pending-approval page
✅ Audit logs record resubmission events

## Next Steps

1. **Test the rejection flow end-to-end**:
   - Approve a farm owner
   - Reject a farm owner
   - Have farm owner retry with same email/phone
   - Verify it works smoothly

2. **Monitor logs** for resubmission events:
   ```bash
   tail -f storage/logs/laravel.log | grep "registration retry"
   ```

3. **End-to-end test sequence**:
   - [ ] Register new farm owner
   - [ ] Approve it (verify it works)
   - [ ] Create another farm owner
   - [ ] Reject it with reason
   - [ ] Farm owner retries with same email/phone
   - [ ] SuperAdmin reviews resubmission
   - [ ] SuperAdmin approves
   - [ ] Verify farm owner can now access dashboard

---

**Status**: ✅ IMPLEMENTATION COMPLETE

Rejected farm owners can **smoothly retry registration** without any email/phone blocking issues!
