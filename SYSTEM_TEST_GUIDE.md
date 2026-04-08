# 🐔 Poultry System - Full System Test & Verification Guide
**Status**: Live on localhost:8000  
**Date**: April 3, 2026  
**System Version**: Complete with all fixes applied

---

## ✅ System Health Check

### Servers Running
- ✅ Laravel Development Server: `http://localhost:8000`
- ✅ Database: PostgreSQL (configured)
- ✅ Mail: Laravel log driver (development)

### Recent Fixes Applied
1. ✅ Kernel.php middleware aliases corrected
2. ✅ Flock edit view with change detection (disabled save button until changes made)
3. ✅ Drivers & Deliveries routes accessible to farm owners
4. ✅ Philippine phone number validation system-wide
5. ✅ Vaccination view/edit templates fixed
6. ✅ Internal communication system restored
7. ✅ Employee verification email logic fixed
8. ✅ Department user dashboards isolated

---

## 🧪 Complete System Test Plan

### SECTION 1: PUBLIC & AUTH FLOWS (15 mins)

#### 1.1 Welcome Page
- **URL**: `http://localhost:8000`
- **Expected**: Welcome page with login/register options
- **Test**: Load page, verify all links present

#### 1.2 Farm Owner Registration
- **URL**: `/farm-owner/register`
- **Steps**:
  1. Click register
  2. Fill: Farm Name, Owner Name, Email, Phone (09123456789), Password
  3. Submit
- **Expected**: Account created, redirect to pending approval page

#### 1.3 Farm Owner Login
- **URL**: `/farm-owner/login`
- **Steps**:
  1. Enter email & password
  2. Click login
- **Expected**: Redirect to farm owner dashboard (if approved) or pending page

#### 1.4 Super Admin Login
- **Credentials**: superadmin@poultry.com / SuperAdmin@2026
- **URL**: `/dashboard`
- **Expected**: Redirect to SuperAdmin dashboard

---

### SECTION 2: FARM OWNER PORTAL (30 mins)

#### 2.1 Dashboard
- **URL**: `http://localhost:8000/farm-owner/dashboard`
- **Check**:
  - ✅ All sidebar sections visible (Flock Management, Inventory, Sales, Logistics, HR & Payroll, Finance, Analytics)
  - ✅ Stats cards showing (total flocks, total birds, mortality, layers, broilers)
  - ✅ Recent flocks table populated

#### 2.2 Flocks Management
- **URL**: `http://localhost:8000/farm-owner/flocks`
- **Test**:
  1. Click "View" on existing flock → See details
  2. Click "Edit" on flock
  3. Verify "Save Record" button is **DISABLED** (grayed out)
  4. Change batch name → Save button turns **GREEN** (enabled)
  5. Revert change → Save button turns **GRAY** (disabled)
  6. Make change and click Save → Updates successfully
- **Expected**: Change detection works, no errors on update

#### 2.3 Vaccinations
- **URL**: `http://localhost:8000/farm-owner/vaccinations`
- **Test**:
  1. View vaccination details → See full details with status badge
  2. Click "Edit" → Form loads with all fields
  3. Change a field → Click Save
  4. Verify update successful
- **Expected**: Vaccination CRUD operations work smoothly

#### 2.4 Drivers and Deliveries
- **URL**: `http://localhost:8000/farm-owner/drivers`
- **Test**:
  1. Add new driver with Philippine phone (+63 or 09 format)
  2. Verify phone is accepted and normalized to +63 format
  3. View driver details → See delivery stats
  4. Go to Deliveries → Create new delivery
  5. Assign driver → Select from dropdown
- **Expected**: 
  - Phone validation works (+63 and 09 format accepted)
  - Phone normalized in database
  - No 403 Forbidden errors

#### 2.5 Employees
- **URL**: `http://localhost:8000/farm-owner/employees`
- **Test**:
  1. Create new employee:
     - Name: John Doe
     - Email: john@test.com
     - Phone: 09987654321 (Philippine format)
     - Department: finance
     - Position: Finance Officer
     - Hire Date: April 1, 2026
  2. Submit form
  3. Verify success message with verification link
  4. Employee appears in list
- **Expected**: 
  - Employee created successfully
  - Phone normalized
  - Verification email triggered/link provided

#### 2.6 Payroll
- **URL**: `http://localhost:8000/farm-owner/payroll`
- **Test**:
  1. View payroll list
  2. Click "Create Payroll"
  3. Select employees
  4. Generate batch
  5. View details
- **Expected**: Payroll workflow accessible, no errors

#### 2.7 Expenses & Income
- **URL**: `http://localhost:8000/farm-owner/expenses`
- **Test**:
  1. Create expense
  2. Add amount, category, date
  3. Submit
  4. Verify in list
- **Expected**: CRUD operations work

#### 2.8 Reports
- **URL**: `http://localhost:8000/farm-owner/reports`
- **Test**:
  1. View dashboard report
  2. View financial report
  3. View production report
  4. Check all charts load
- **Expected**: Reports display without errors

---

### SECTION 3: DEPARTMENT USER PORTAL (20 mins)

#### 3.1 Create Finance Department User
- **As**: Farm Owner logged in
- **Steps**:
  1. Go to Employees → Create new
  2. Fill in details:
     - Name: Finance Manager
     - Email: finance_mgr@test.com
     - Password: Test1234!
     - Department: finance
     - Position: Finance Manager
  3. Submit
- **Expected**: User created, verification email triggered

#### 3.2 Login as Finance Employee
- **Steps**:
  1. Logout farm owner
  2. Login as finance_mgr@test.com / Test1234!
  3. Verify email (use verification link)
  4. Observe where redirected
- **Expected**: 
  - NOT redirected to farm owner dashboard
  - **Redirected to**: `/department/finance` dashboard
  - Shows Finance-specific sidebar menu

#### 3.3 Finance Dashboard
- **URL**: `http://localhost:8000/department/finance`
- **Check**:
  - ✅ Header: "Finance Dashboard"
  - ✅ Sidebar shows: Dashboard, Expenses, Income, Payroll, Communication
  - ✅ Stats show: Total Expenses, Pending Expenses, Total Income, Pending Income
  - ✅ Recent expenses table populated
- **Expected**: All elements visible, no farm owner pages accessible

#### 3.4 Test Other Department Dashboards
- **Logistics**: `/department/logistics` (deliveries, drivers, schedule)
- **Farm Operations**: `/department/farm-operations` (flocks, vaccinations)
- **Sales**: `/department/sales` (orders, revenue)
- **Admin**: `/department/admin` (employees, suppliers)

**Test each**:
1. Create employee with that department
2. Login as employee (verify email first)
3. Verify correct dashboard loads
4. Check navigation menu is role-specific
5. Verify data is only for the farm owner

#### 3.5 Data Isolation Test
- **Test**: Two farms, two finance employees
- **Steps**:
  1. Create Farm A (with Finance Employee A)
  2. Create Farm B (with Finance Employee B)
  3. Login as Employee A → Check expense totals
  4. Logout
  5. Login as Employee B → Check expense totals
- **Expected**: 
  - Each employee sees only their farm's data
  - Totals are different if different expenses exist

---

### SECTION 4: INTERNAL COMMUNICATION (15 mins)

#### 4.1 Farm Owner Send Message to Finance
- **As**: Farm Owner
- **Steps**:
  1. Sidebar → Contact Finance
  2. Write message
  3. Click Send
- **Expected**: 
  - No 419 errors
  - Message sent successfully
  - Success notification appears

#### 4.2 Finance Receive & Reply
- **As**: Finance Employee
- **Steps**:
  1. Sidebar → Communication / Messages
  2. View inbox
  3. Click message from Farm Owner
  4. Reply to message
  5. Click Send
- **Expected**:
  - Message visible in inbox
  - Can reply successfully
  - No CSRF errors

#### 4.3 Farm Owner Check Reply
- **As**: Farm Owner
- **Steps**:
  1. Sidebar → Contact Finance
  2. View conversation history
  3. See Finance reply
- **Expected**: Thread shows all messages in order

---

### SECTION 5: MULTI-DEPARTMENT COORDINATION (10 mins)

#### 5.1 HR Send Message to Logistics
- **As**: HR Employee
- **Steps**:
  1. Go to Communication
  2. Create message to Logistics department
  3. Send
- **Expected**: Message delivered without 419 errors

#### 5.2 Logistics Receive & Reply
- **As**: Logistics Employee
- **Steps**:
  1. Check department messages
  2. Reply to HR
- **Expected**: Two-way communication works

---

### SECTION 6: PHONE NUMBER VALIDATION (10 mins)

#### 6.1 Test Philippine Phone Formats
- **Test in Employee form**:
  1. Try format: `09123456789` (local) → Should accept and normalize to +63
  2. Try format: `+639123456789` (international) → Should accept
  3. Try format: `01234567890` (invalid) → Should reject
  4. Try format: `123456` (too short) → Should reject
- **Expected**: Only valid PH numbers accepted

#### 6.2 Test in Driver form
- **URL**: `/farm-owner/drivers`
- **Test**:
  1. Create driver with +639123456789
  2. Verify in database → stored as +639123456789
  3. Update driver phone
  4. Verify validation applies on update
- **Expected**: Phone validation consistent across all forms

---

### SECTION 7: SUPER ADMIN PORTAL (10 mins)

#### 7.1 Super Admin Dashboard
- **URL**: `/super-admin/dashboard`
- **Check**:
  - ✅ Dashboard loads
  - ✅ Sidebar shows all admin options
  - ✅ Stats cards visible

#### 7.2 Farm Owner Management
- **URL**: `/super-admin/farm-owners`
- **Test**:
  1. View pending farm owners
  2. Click on farm owner registration
  3. Approve/Reject
- **Expected**: Farm owner status changes

#### 7.3 Department User Management
- **URL**: `/hr/users`
- **Test**:
  1. View all department users
  2. Create new department user (HR role)
  3. User appears in list
- **Expected**: CRUD operations work

---

### SECTION 8: LOGOUT & SESSION (5 mins)

#### 8.1 Farm Owner Logout
- **Test**: Click logout button
- **Expected**: 
  - Redirects to login page
  - Session cleared
  - Cannot access authenticated pages

#### 8.2 Department User Logout
- **Test**: Click logout button
- **Expected**: 
  - Redirects to login page
  - Cannot access department dashboard

#### 8.3 Re-login After Logout
- **Test**: Login again with same credentials
- **Expected**: Logs in successfully, redirected to correct dashboard

---

## 🎯 Quick Smoke Test (5 mins)

If you just want to verify the system is working:

1. **Open**: `http://localhost:8000`
2. **Login as SuperAdmin**: superadmin@poultry.com / SuperAdmin@2026
3. **Go to**: `/super-admin/farm-owners`
4. **Select** an approved farm owner
5. **View dashboard** → Should work
6. **Create** an employee (department: finance)
7. **Logout** farm owner
8. **Login** as new employee (must verify email first)
9. **Verify** you're in finance dashboard (not farm owner portal)
10. **Check sidebar** shows finance-specific options only

---

## 📋 Test Results Log

**Tester Name**: _______________  
**Test Date**: _______________  
**System Status**: _____________ (PASS/FAIL)

### Critical Tests
- [ ] Farm owner can login and see dashboard
- [ ] Employee can login and gets correct department dashboard
- [ ] Flock update with change detection works
- [ ] Phone validation accepts PH formats
- [ ] Internal communication works without 419 errors
- [ ] Drivers & Deliveries accessible (no 403)
- [ ] Department data isolation working (no cross-farm data)
- [ ] Logout clears session properly

### Optional Tests
- [ ] All reports load without errors
- [ ] Payroll workflow functional
- [ ] Attendance tracking works
- [ ] Two-way messaging between departments works
- [ ] Department user creation/management works

---

## 🚨 Common Issues & Solutions

### Issue: 403 Access Denied
**Solution**: 
- Check middleware aliases in Kernel.php are correct
- Verify user role is set properly (farm_operations, finance, etc.)
- Check farm_owner_id is present in Employee record

### Issue: 419 Page Expired
**Solution**:
- Clear browser cookies
- Verify VerifyCsrfToken is in web middleware group
- Check session configuration

### Issue: Phone validation fails
**Solution**:
- Use format: `09123456789` or `+639123456789`
- Check PhilippinePhoneNumber rule is applied to field
- Verify normalization is enabled

### Issue: Employee not getting email
**Solution**:
- System uses LOG driver in development (check terminal)
- Verification link provided as fallback
- Check email config in .env

### Issue: Redirect to farm owner dashboard
**Solution**:
- Verify departmentDashboardRouteName() is mapping correctly
- Check employee's department field matches role in User table
- Verify email is verified before accessing dashboard

---

## ✅ System Ready!

**All systems are operational and ready for comprehensive testing.**

Use the sections above to validate each component works as expected. Most common user flows should be smooth with no errors.

**Questions?** Check the logs:
- Browser console: Right-click → Inspect → Console tab
- Server logs: Watch the terminal where `php artisan serve` is running
- Database logs: Check PostgreSQL error logs if needed

**Start testing now on**: `http://localhost:8000`
