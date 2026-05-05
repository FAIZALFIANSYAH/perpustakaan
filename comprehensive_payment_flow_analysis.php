<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== COMPREHENSIVE PAYMENT FLOW ANALYSIS ===\n\n";

// 1. Check current payment data state
echo "1. CURRENT PAYMENT DATA STATE\n";
echo "============================\n";

// Fines with their relationships
$fines = \App\Models\Fine::with(['member', 'borrowingItem.book', 'payments', 'paymentVerification'])->get();
echo "📊 Total Fines: {$fines->count()}\n\n";

foreach ($fines as $fine) {
    echo "Fine ID: {$fine->id}\n";
    echo "  ├─ Member: " . ($fine->member?->name ?? 'N/A') . " (ID: " . $fine->member_id . ")\n";
    echo "  ├─ Book: " . ($fine->borrowingItem?->book?->title ?? 'N/A') . "\n";
    echo "  ├─ Type: {$fine->type}\n";
    echo "  ├─ Amount: Rp {$fine->amount}\n";
    echo "  ├─ Paid Amount: Rp {$fine->paid_amount}\n";
    echo "  ├─ Status: {$fine->status}\n";
    echo "  ├─ Due Date: {$fine->due_date}\n";
    echo "  ├─ Paid At: " . ($fine->paid_at ?? 'Not paid') . "\n";
    echo "  ├─ Payments: " . $fine->payments->count() . " records\n";
    echo "  └─ Payment Verification: " . ($fine->paymentVerification ? "ID {$fine->paymentVerification->id}" : 'None') . "\n";
    
    // Show payment details
    foreach ($fine->payments as $payment) {
        echo "      └─ Payment ID: {$payment->id}, Amount: Rp {$payment->amount}, Method: {$payment->payment_method}\n";
    }
    
    // Show verification details
    if ($fine->paymentVerification) {
        $verification = $fine->paymentVerification;
        echo "      └─ Verification: Status {$verification->status}, Requested: Rp {$verification->requested_amount}\n";
    }
    echo "\n";
}

// 2. Check PaymentVerification data
echo "2. PAYMENT VERIFICATION DATA\n";
echo "===========================\n";

$verifications = \App\Models\PaymentVerification::with(['fine.member', 'fine.borrowingItem.book', 'requestedBy', 'verifiedBy'])->get();
echo "📊 Total Payment Verifications: {$verifications->count()}\n\n";

foreach ($verifications as $verification) {
    echo "Verification ID: {$verification->id}\n";
    echo "  ├─ Fine ID: {$verification->fine_id}\n";
    echo "  ├─ Member: " . ($verification->fine->member?->name ?? 'N/A') . "\n";
    echo "  ├─ Requested Amount: Rp {$verification->requested_amount}\n";
    echo "  ├─ Payment Method: {$verification->payment_method}\n";
    echo "  ├─ Status: {$verification->status}\n";
    echo "  ├─ Requested By: " . ($verification->requestedBy?->name ?? 'N/A') . "\n";
    echo "  ├─ Verified By: " . ($verification->verifiedBy?->name ?? 'Not verified') . "\n";
    echo "  ├─ Created: {$verification->created_at}\n";
    echo "  └─ Updated: {$verification->updated_at}\n";
    echo "\n";
}

// 3. Check FinePayment data
echo "3. FINE PAYMENT DATA\n";
echo "====================\n";

$payments = \App\Models\FinePayment::with(['fine.member', 'fine.borrowingItem.book'])->get();
echo "📊 Total Fine Payments: {$payments->count()}\n\n";

foreach ($payments as $payment) {
    echo "Payment ID: {$payment->id}\n";
    echo "  ├─ Fine ID: {$payment->fine_id}\n";
    echo "  ├─ Member: " . ($payment->fine->member?->name ?? 'N/A') . "\n";
    echo "  ├─ Amount: Rp {$payment->amount}\n";
    echo "  ├─ Payment Method: {$payment->payment_method}\n";
    echo "  ├─ Status: " . ($payment->status ?? 'N/A') . "\n";
    echo "  ├─ Paid By: {$payment->paid_by}\n";
    echo "  ├─ Processed By: {$payment->processed_by}\n";
    echo "  ├─ Notes: " . ($payment->notes ?? 'None') . "\n";
    echo "  └─ Created: {$payment->created_at}\n";
    echo "\n";
}

// 4. Trace payment flow from each role perspective
echo "4. PAYMENT FLOW BY ROLE\n";
echo "=======================\n";

// Super Admin perspective
echo "👑 Super Admin Perspective:\n";
echo "  - Can see all fines: /admin/fines\n";
echo "  - Can manage fine config: /admin/fine-config\n";
echo "  - Can process overdue fines: /admin/overdue-fines\n";
$superAdminFines = \App\Models\Fine::count();
echo "  - Total fines visible: {$superAdminFines}\n\n";

// Librarian perspective
echo "📚 Librarian Perspective:\n";
echo "  - Can verify payments: /librarian/payment-verification\n";
echo "  - Can see payment requests\n";
$pendingVerifications = \App\Models\PaymentVerification::where('status', 'pending')->count();
echo "  - Pending verifications: {$pendingVerifications}\n\n";

// Member perspective
echo "👤 Member Perspective:\n";
$membersWithFines = \App\Models\Fine::distinct('member_id')->pluck('member_id');
foreach ($membersWithFines as $memberId) {
    $member = \App\Models\User::find($memberId);
    $memberFines = \App\Models\Fine::where('member_id', $memberId)->get();
    $unpaidCount = $memberFines->where('status', 'unpaid')->count();
    $paidCount = $memberFines->where('status', 'paid')->count();
    
    echo "  - Member {$member->name} (ID: {$memberId}):\n";
    echo "    ├─ Total fines: {$memberFines->count()}\n";
    echo "    ├─ Unpaid: {$unpaidCount}\n";
    echo "    ├─ Paid: {$paidCount}\n";
    echo "    └─ Can borrow: " . ($unpaidCount > 0 ? "❌" : "✅") . "\n";
}
echo "\n";

// 5. Test payment processing flow
echo "5. PAYMENT PROCESSING FLOW TEST\n";
echo "===============================\n";

// Find an unpaid fine to test
$unpaidFine = \App\Models\Fine::where('status', 'unpaid')->first();

if ($unpaidFine) {
    echo "🧪 Testing with Fine ID: {$unpaidFine->id}\n";
    echo "  ├─ Member: " . $unpaidFine->member->name . "\n";
    echo "  ├─ Amount: Rp {$unpaidFine->amount}\n";
    echo "  └─ Current Status: {$unpaidFine->status}\n";
    
    $fineService = app(\App\Services\FineService::class);
    
    echo "\n📝 Step 1: Process Payment\n";
    try {
        $processedFine = $fineService->processFinePayment(
            $unpaidFine,
            $unpaidFine->amount, // Full payment
            'cash',
            1, // Admin ID
            'Integration test payment'
        );
        
        echo "  ✅ Payment processed successfully\n";
        echo "  ├─ New Status: {$processedFine->status}\n";
        echo "  ├─ Paid Amount: Rp {$processedFine->paid_amount}\n";
        echo "  ├─ Paid At: " . ($processedFine->paid_at ?? 'Not set') . "\n";
        
        // Check if payment record was created
        $paymentRecord = \App\Models\FinePayment::where('fine_id', $processedFine->id)->first();
        if ($paymentRecord) {
            echo "  ├─ Payment Record Created: ID {$paymentRecord->id}\n";
            echo "  └─ Payment Amount: Rp {$paymentRecord->amount}\n";
        } else {
            echo "  └─ ❌ No payment record found\n";
        }
        
    } catch (\Exception $e) {
        echo "  ❌ Payment processing failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n📝 Step 2: Check Member Borrowing Status\n";
    $canBorrow = $fineService->canMemberBorrow($unpaidFine->member_id);
    echo "  ├─ Member can borrow: " . ($canBorrow ? "✅" : "❌") . "\n";
    echo "  └─ Block reason: " . ($fineService->getMemberBorrowingBlockReason($unpaidFine->member_id) ?? 'None') . "\n";
    
} else {
    echo "🧪 No unpaid fines found for testing\n";
}

echo "\n";

// 6. Check UI data synchronization
echo "6. UI DATA SYNCHRONIZATION CHECK\n";
echo "=================================\n";

// Check if data is consistent across all access points
echo "🔍 Data Consistency Check:\n";

// Total fines from different perspectives
$totalFinesDirect = \App\Models\Fine::count();
$totalFinesWithRelations = \App\Models\Fine::with(['member', 'borrowingItem'])->count();
echo "  ├─ Direct count: {$totalFinesDirect}\n";
echo "  ├─ With relations: {$totalFinesWithRelations}\n";
echo "  └─ Consistent: " . ($totalFinesDirect === $totalFinesWithRelations ? "✅" : "❌") . "\n";

// Payment counts
$totalPayments = \App\Models\FinePayment::count();
$totalVerifications = \App\Models\PaymentVerification::count();
echo "  ├─ Total payments: {$totalPayments}\n";
echo "  ├─ Total verifications: {$totalVerifications}\n";

// Check fine status consistency
$paidFines = \App\Models\Fine::where('status', 'paid')->count();
$finesWithPayments = \App\Models\Fine::whereHas('payments')->count();
echo "  ├─ Fines marked as paid: {$paidFines}\n";
echo "  ├─ Fines with payment records: {$finesWithPayments}\n";
echo "  └─ Consistent: " . ($paidFines === $finesWithPayments ? "✅" : "❌") . "\n";

echo "\n";

// 7. Identify potential issues
echo "7. POTENTIAL ISSUES IDENTIFICATION\n";
echo "===================================\n";

$issues = [];

// Check for fines without proper member relationships
$finesWithoutMember = \App\Models\Fine::whereDoesntHave('member')->count();
if ($finesWithoutMember > 0) {
    $issues[] = "{$finesWithoutMember} fines without member relationship";
}

// Check for fines without borrowing item relationships
$finesWithoutBorrowingItem = \App\Models\Fine::whereDoesntHave('borrowingItem')->count();
if ($finesWithoutBorrowingItem > 0) {
    $issues[] = "{$finesWithoutBorrowingItem} fines without borrowing item relationship";
}

// Check for payment verifications without fines
$verificationsWithoutFine = \App\Models\PaymentVerification::whereDoesntHave('fine')->count();
if ($verificationsWithoutFine > 0) {
    $issues[] = "{$verificationsWithoutFine} payment verifications without fine relationship";
}

// Check for payments without fines
$paymentsWithoutFine = \App\Models\FinePayment::whereDoesntHave('fine')->count();
if ($paymentsWithoutFine > 0) {
    $issues[] = "{$paymentsWithoutFine} payments without fine relationship";
}

// Check for inconsistent fine statuses
$inconsistentStatuses = \App\Models\Fine::where('status', 'paid')
    ->where('paid_amount', '<>', \Illuminate\Support\Facades\DB::raw('amount'))
    ->count();
if ($inconsistentStatuses > 0) {
    $issues[] = "{$inconsistentStatuses} fines with inconsistent status/amount";
}

if (empty($issues)) {
    echo "✅ No data integrity issues found\n";
} else {
    echo "❌ Issues found:\n";
    foreach ($issues as $issue) {
        echo "  ├─ {$issue}\n";
    }
}

echo "\n=== COMPREHENSIVE PAYMENT FLOW ANALYSIS COMPLETE ===\n";
