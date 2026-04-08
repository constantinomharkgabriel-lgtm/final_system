# PAYROLL & PAYSLIP WORKFLOW - STEP-BY-STEP GUIDE

## CURRENT SETUP:
- ✅ Farm Owner: Lawrence Tabutol
- ✅ HR Employee: Fabian Reiner (₱690/day)
- ✅ Finance Employee: Ready
- ✅ Employees Created: Ready for payroll

---

## STEP 1: CREATE ATTENDANCE RECORDS (As HR or Admin)

**Location:** Admin Portal → Attendance

Before you can create payroll, employees need attendance records for the period.

### What to do:
1. Click "Attendance" in the sidebar
2. Click "Mark Attendance" or use bulk entry
3. Enter attendance for April 1-30, 2026:
   - Status: "Present" (or late/half-day)
   - Hours Worked: 8 hours per day
   - Skip Sundays (no work on Sundays)

### Expected Result:
- At least 22 working days recorded (Monday-Friday in April)
- Each day shows 8 hours worked
- No Sunday entries

---

## STEP 2: GENERATE PAYROLL BATCH (As HR)

**Login as:** Fabian Reiner (HR Manager) - Password: your_password

### What to do:

1. **Go to HR Dashboard**
   - URL: `http://127.0.0.1:8000/department/hr`
   - Click sidebar "🏠 HR Home"

2. **Navigate to Payroll**
   - Click "👔 Payroll Prep" in the sidebar (or "Payroll" section)
   - URL should be: `http://127.0.0.1:8000/farm-owner/payroll`

3. **Create Batch**
   - Click "➕ Create Batch" or "+ Create Batch" button
   - You should see a form with fields:
     * Period Start Date: **April 1, 2026**
     * Period End Date: **April 30, 2026**
     * Employee Selection: Check the box for employee(s) you want to include

4. **Submit the Form**
   - Click "Generate Batch" button

### What Happens Behind the Scenes:
✅ System calculates:
- Days worked from attendance records (excluding Sundays)
- Daily rate = Monthly salary ÷ 22 (Philippines standard)
- Basic Pay = Daily rate × Days worked
- Overtime Pay (if any overtime hours exist)
- Late Deductions (if any late minutes exist)
- **Net Pay = Basic Pay + Overtime - Deductions**

### Expected Result:
- ✅ Success message: "X payroll records generated"
- Payroll appears in the list with status: **"Pending"**
- Workflow Status: **"Pending Finance Approval"** (because HR created it)

**Example Calculation for Fabian Reiner:**
- Daily Rate: ₱690
- Working Days (April 2026): 22 days (Mon-Fri, no Sundays)
- Basic Pay = ₱690 × 22 = ₱15,180
- Overtime Pay: ₱0 (if no overtime)
- Deductions: ₱0 (if no late marks)
- **Net Pay = ₱15,180**

---

## STEP 3: FINANCE APPROVES PAYROLL (As Finance)

**Login as:** Finance Employee (or create new Finance user)

### What to do:

1. **Go to Finance Dashboard**
   - URL: `http://127.0.0.1:8000/department/finance`

2. **Navigate to Payroll**
   - Click "👔 Payroll" in the sidebar
   - URL: `http://127.0.0.1:8000/farm-owner/payroll`

3. **Find the Pending Payroll**
   - Look for the payroll record with:
     - Status: **"Pending"**
     - Workflow Status: **"Pending Finance Approval"** (shown in yellow)
   - Click the payroll record to open details

4. **Review Payroll Details**
   - Verify Employee Name, Period, Amount, Calculation
   - Check if calculation looks correct:
     - Basic Pay = Daily Rate × Days Worked
     - Net Pay = Basic Pay + Overtime - Deductions

5. **Click "Finance Approve" Button**
   - This confirms Finance has reviewed and approved the payroll
   - System saves who approved and timestamp

### What Happens:
- ✅ Workflow Status changes: **"Pending Finance Approval" → "Ready for Owner Approval"**
- ✅ Status remains: "Pending" (waiting for farm owner)
- System records: Finance approval user + timestamp

### Expected Result:
- ✅ Payroll now shows as "Finance Approved"
- Ready for Farm Owner's final approval

---

## STEP 4: FARM OWNER APPROVES PAYROLL (As Farm Owner)

**Login as:** Lawrence Tabutol (Farm Owner)

### What to do:

1. **Go to Farm Owner Dashboard**
   - URL: `http://127.0.0.1:8000/farm-owner/dashboard`

2. **Navigate to Payroll**
   - Click "👔 Payroll" in sidebar
   - Or go to: `http://127.0.0.1:8000/farm-owner/payroll`

3. **Find the Finance Approved Payroll**
   - Look for status: **"Finance Approved"** (orange/yellow badge)
   - This is the payroll Finance just approved
   - Click to open

4. **Review Final Numbers**
   - Double-check all calculations
   - Verify employee and amount are correct

5. **Click "Approve" Button**
   - Farm owner final authorization to pay
   - This is the final sign-off

### What Happens:
- ✅ Status changes: **"Pending" → "Approved"**
- ✅ Workflow Status: **"Ready for Payslip Release"**
- System records: Owner approval + timestamp

### Expected Result:
- ✅ Payroll status: **"Approved"** (green badge)
- New buttons appear: "Release Payslip", "Prepare for Disbursement"

---

## STEP 5: RELEASE PAYSLIP (As Farm Owner or Finance)

**Can be done by:** Farm Owner or Finance

### What to do:

1. **Stay on the Approved Payroll**
   - Or navigate back to Payroll → Find approved record

2. **Click "Release Payslip" Button**
   - This generates the actual payslip document
   - Payslip contains:
     * Employee name, ID, position
     * Pay period (April 1-30, 2026)
     * Basic Pay: ₱15,180
     * Overtime Pay: ₱0
     * Deductions: ₱0
     * **Net Pay: ₱15,180**
     * Approval signatures/dates

### What Happens:
- ✅ Payslip PDF/document is created
- ✅ Status might change to **"Released"** or **"Payslip Generated"**
- ✅ Employee can now view/download payslip

### Expected Result:
- ✅ Payslip appears in system
- ✅ Button changes to "View Payslip" (can download/print)

---

## STEP 6: PREPARE FOR DISBURSEMENT (As Farm Owner)

**Login as:** Farm Owner

### What to do:

1. **Go to Payroll Details**
   - Find the payroll with status: **"Released"** or **"Approved"**

2. **Click "Prepare for Disbursement" Button**
   - This means Farm Owner is ready to process payment
   - Creates a disbursement batch

### What Happens:
- ✅ Status changes: **"Released" → "Prepared for Disbursement"**
- ✅ System records: When and who prepared it

### Expected Result:
- ✅ Payroll ready for Finance to execute actual payment

---

## STEP 7: EXECUTE DISBURSEMENT (As Finance)

**Login as:** Finance Employee

### What to do:

1. **Go to Finance Dashboard → Payroll**

2. **Find Payroll with Status: "Prepared for Disbursement"**
   - This is the one Farm Owner just prepared

3. **Click to Open Details**

4. **Click "Execute Disbursement" Button**
   - Finance processes the actual payment
   - Transfers money from farm account to employee
   - Or marks as "payment executed"

### What Happens:
- ✅ Status: **"Prepared for Disbursement" → "Paid"** ✅
- ✅ System records:
     - Who executed it (Finance user)
     - When it was executed (timestamp)
     - Disbursement reference number (for bank tracking)

### Expected Result:
- ✅ Payroll now shows: **"Paid"** (green badge = completed!)
- ✅ No more action buttons (it's finalized)
- ✅ Shows all approver names and dates in audit trail

---

## COMPLETE WORKFLOW CHAIN:

```
HR Creates Batch
    ↓ (generates from attendance)
📋 Status: "Pending"
📊 Workflow: "Pending Finance Approval"
    ↓
Finance Reviews & Approves
    ↓
✅ Status: "Pending" (still)
📊 Workflow: "Ready for Owner Approval"
    ↓
Farm Owner Reviews & Approves
    ↓
✅✅ Status: "Approved" 🟢
📊 Workflow: "Ready for Payslip Release"
    ↓
Release Payslip (Generate Document)
    ↓
✅✅✅ Status: "Released"
📊 Payslip Generated (Employee can download)
    ↓
Farm Owner: Prepare for Disbursement
    ↓
✅✅✅✅ Status: "Prepared for Disbursement"
📊 Ready for actual payment processing
    ↓
Finance: Execute Disbursement (Process Payment)
    ↓
🎉 Status: "PAID" ✅ COMPLETE!
📊 All approvals recorded with timestamps
```

---

## WHAT YOU'LL SEE AT EACH STAGE:

### Payroll Details Screen Shows:

**Employee Section:**
- Name: Fabian Reiner
- Position: HR Manager
- Employee ID: EMP-XXXXX

**Pay Period:**
- Start Date: April 1, 2026
- End Date: April 30, 2026
- Days Worked: 22

**Earnings:**
- Basic Pay: ₱15,180 (₱690 × 22 days)
- Overtime Pay: ₱0
- Allowances/Bonuses: ₱0

**Deductions:**
- SSS: ₱0
- PhilHealth: ₱0
- Pag-IBIG: ₱0
- Tax: ₱0
- Late Deduction: ₱0

**Summary:**
- Gross Pay: ₱15,180
- Total Deductions: ₱0
- **NET PAY: ₱15,180** 💰

**Approvals:**
- ✅ Finance Approved By: [Finance User Name]
- ✅ Approved By: [Farm Owner Name]  
- ✅ Payslip Released By: [User]

---

## TEST CHECKLIST:

- [ ] HR creates attendance (22 working days in April)
- [ ] HR generates payroll batch
- [ ] Payroll shows "Pending Finance Approval"
- [ ] Finance approves payroll
- [ ] Status updates to show Finance approval ✅
- [ ] Farm Owner reviews and approves
- [ ] Status changes to "Approved" ✅✅
- [ ] Release payslip button appears
- [ ] Click Release Payslip
- [ ] Farm Owner prepares for disbursement
- [ ] Finance executes disbursement
- [ ] Final status = "PAID" ✅✅✅✅

---

## EXPECTED CALCULATION FOR YOUR TEST:

**Employee:** Fabian Reiner (HR Manager)
- Daily Rate: ₱690
- Period: April 1-30, 2026
- Working Days (M-F, no Sun): 22 days
- **Basic Pay: ₱690 × 22 = ₱15,180**
- Overtime: ₱0 (no OT hours)
- Deductions: ₱0 (no late marks)
- **NET PAY: ₱15,180**

This confirms the payroll calculation is working correctly!

---

## TROUBLESHOOTING:

**Problem:** "No payroll to generate" 
- **Solution:** Make sure attendance records exist for April 2026

**Problem:** Finance approve button not showing
- **Solution:** Make sure you're logged in as Finance user

**Problem:** "Only HR can create payroll"
- **Solution:** Make sure you're logged in as HR role

**Problem:** Buttons are greyed out
- **Solution:** Check the current workflow status - buttons only show at specific stages

---

Good luck with your testing! This workflow validates that the entire payroll system is working correctly. 🚀
