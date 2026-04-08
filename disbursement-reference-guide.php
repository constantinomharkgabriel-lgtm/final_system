<?php
echo "=== DISBURSEMENT REFERENCE NUMBERS ===\n\n";

echo "The reference number is based on the PAYMENT METHOD selected by Farm Owner:\n\n";

echo "1️⃣  CASH PAYMENT\n";
echo "   Payment Method: Cash\n";
echo "   Reference Number: Receipt number or your own reference\n";
echo "   Examples:\n";
echo "      - CASH-2026-04-001\n";
echo "      - RECEIPT-2026-0002\n";
echo "      - APR-2026-CASH-001\n";
echo "   Source: Generate your own or from receipt\n\n";

echo "2️⃣  BANK TRANSFER\n";
echo "   Payment Method: Bank Transfer\n";
echo "   Reference Number: Bank transaction ID or reference code\n";
echo "   Examples:\n";
echo "      - TXN20260400123456\n";
echo "      - BDO-2026-0004521\n";
echo "      - TRANSFER-APR-001\n";
echo "   Source: Your bank statement or online banking\n\n";

echo "3️⃣  CHECK PAYMENT\n";
echo "   Payment Method: Check\n";
echo "   Reference Number: Check number\n";
echo "   Examples:\n";
echo "      - CHK-001234\n";
echo "      - 0001001\n";
echo "      - BDO-CHECK-5678\n";
echo "   Source: The check itself\n\n";

echo "4️⃣  GCASH (Mobile Payment)\n";
echo "   Payment Method: GCash\n";
echo "   Reference Number: GCash transaction ID (RRN)\n";
echo "   Examples:\n";
echo "      - 123456789012\n";
echo "      - GCASH-2026-04-001\n";
echo "      - RRN20260400098765\n";
echo "   Source: GCash app receipt/confirmation\n\n";

echo "=== HOW TO GET THE REFERENCE NUMBER ===\n\n";

echo "BEFORE executing disbursement:\n";
echo "1. Check what payment method was selected (Farm Owner set this)\n";
echo "2. Actually process the payment using that method\n";
echo "3. Get the reference/transaction ID from the payment\n";
echo "4. Enter it when executing disbursement in the system\n\n";

echo "WORKFLOW:\n";
echo "   Farm Owner: Selects payment method (Cash/Bank/Check/GCash)\n";
echo "   Farm Owner: Clicks 'Prepare Disbursement'\n";
echo "   ↓\n";
echo "   You (Finance): Actually process the payment\n";
echo "   You (Finance): Get the reference number from the payment\n";
echo "   You (Finance): Go to system → Enter reference → Click 'Execute Disbursement'\n\n";

echo "=== FOR TESTING/DEMO ===\n\n";
echo "You can use any reference number:\n";
echo "   - TEST-2026-001\n";
echo "   - DEMO-PAYROLL-001\n";
echo "   - APR-001-DISBURSEMENT\n\n";

echo "=== THE FULL PROCESS ===\n\n";
echo "1. Farm Owner selects: Cash / Bank Transfer / Check / GCash\n";
echo "2. Farm Owner clicks 'Prepare Disbursement'\n";
echo "3. You (Finance) actually send the money using that method\n";
echo "   - If CASH: Give cash and get receipt\n";
echo "   - If BANK: Make transfer and get transaction ID\n";
echo "   - If CHECK: Write check and get check number\n";
echo "   - If GCASH: Send via GCash and get confirmation ID\n";
echo "4. You enter the reference number in the system\n";
echo "5. Click 'Execute Disbursement'\n";
echo "6. Payroll marked as PAID\n";
?>
