<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== FIXING PAYMENT RELATIONSHIPS & DATA FLOW ===\n\n";

// 1. Check Fine model relationships
echo "1. CHECKING FINE MODEL RELATIONSHIPS\n";
echo "====================================\n";

$fineModel = new \App\Models\Fine();

echo "Fine model fillable:\n";
print_r($fineModel->getFillable());

echo "\nChecking relationships:\n";

// Check payments relationship
try {
    $paymentsRelation = $fineModel->payments();
    echo "✅ payments relationship: " . get_class($paymentsRelation) . "\n";
} catch (\Exception $e) {
    echo "❌ payments relationship: " . $e->getMessage() . "\n";
}

// Check paymentVerification relationship
try {
    $paymentVerificationRelation = $fineModel->paymentVerification();
    echo "✅ paymentVerification relationship: " . get_class($paymentVerificationRelation) . "\n";
} catch (\Exception $e) {
    echo "❌ paymentVerification relationship: " . $e->getMessage() . "\n";
}

echo "\n";

// 2. Check PaymentVerification model
echo "2. CHECKING PAYMENTVERIFICATION MODEL\n";
echo "====================================\n";

try {
    $verificationModel = new \App\Models\PaymentVerification();
    echo "✅ PaymentVerification model exists\n";
    
    echo "PaymentVerification fillable:\n";
    print_r($verificationModel->getFillable());
    
} catch (\Exception $e) {
    echo "❌ PaymentVerification model: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. Check FinePayment model
echo "3. CHECKING FINEPAYMENT MODEL\n";
echo "===============================\n";

try {
    $paymentModel = new \App\Models\FinePayment();
    echo "✅ FinePayment model exists\n";
    
    echo "FinePayment fillable:\n";
    print_r($paymentModel->getFillable());
    
} catch (\Exception $e) {
    echo "❌ FinePayment model: " . $e->getMessage() . "\n";
}

echo "\n";

// 4. Check actual data with proper relationships
echo "4. CHECKING ACTUAL DATA WITH RELATIONSHIPS\n";
echo "==========================================\n";

// Get fines with proper eager loading
$fines = \App\Models\Fine::with(['member', 'borrowingItem.book', 'payments', 'paymentVerifications'])->get();

echo "Total fines: {$fines->count()}\n\n";

foreach ($fines as $fine) {
    echo "Fine ID: {$fine->id}\n";
    echo "  Member: " . ($fine->member?->name ?? 'N/A') . "\n";
    echo "  Book: " . ($fine->borrowingItem?->book?->title ?? 'N/A') . "\n";
    echo "  Type: {$fine->type}\n";
    echo "  Amount: Rp {$fine->amount}\n";
    echo "  Paid Amount: Rp {$fine->paid_amount}\n";
    echo "  Status: {$fine->status}\n";
    echo "  Due Date: {$fine->due_date}\n";
    echo "  Paid At: " . ($fine->paid_at ?? 'Not paid') . "\n";
    
    // Check payments
    echo "  Payments: " . $fine->payments->count() . "\n";
    foreach ($fine->payments as $payment) {
        echo "    - Payment ID: {$payment->id}, Amount: Rp {$payment->amount}, Method: {$payment->payment_method}, Status: " . ($payment->status ?? 'N/A') . "\n";
    }
    
    // Check payment verifications (using correct relationship name)
    echo "  Payment Verifications: " . $fine->paymentVerifications->count() . "\n";
    foreach ($fine->paymentVerifications as $verification) {
        echo "    - Verification ID: {$verification->id}, Requested: Rp {$verification->requested_amount}, Status: {$verification->status}\n";
    }
    echo "\n";
}

// 5. Test payment processing and data flow
echo "5. TESTING PAYMENT PROCESSING FLOW\n";
echo "====================================\n";

// Get an unpaid fine
$unpaidFine = \App\Models\Fine::where('status', 'unpaid')->first();

if ($unpaidFine) {
    echo "Testing with Fine ID: {$unpaidFine->id}\n";
    echo "  Member: " . $unpaidFine->member->name . "\n";
    echo "  Amount: Rp {$unpaidFine->amount}\n";
    echo "  Status: {$unpaidFine->status}\n";
    
    $fineService = app(\App\Services\FineService::class);
    
    echo "\n📝 Processing Test Payment:\n";
    try {
        // Process full payment
        $paymentAmount = $unpaidFine->amount;
        echo "  Processing payment: Rp {$paymentAmount}\n";
        
        $processedFine = $fineService->processFinePayment(
            $unpaidFine,
            $paymentAmount,
            'cash',
            1, // Admin ID
            'Test payment integration'
        );
        
        echo "  ✅ Payment processed successfully\n";
        echo "  New Status: {$processedFine->status}\n";
        echo "  New Paid Amount: Rp {$processedFine->paid_amount}\n";
        echo "  Paid At: " . ($processedFine->paid_at ?? 'Not set') . "\n";
        
        // Check if payment record was created
        $paymentCount = \App\Models\FinePayment::where('fine_id', $processedFine->id)->count();
        echo "  Payment records created: {$paymentCount}\n";
        
        // Check member borrowing status
        $canBorrow = $fineService->canMemberBorrow($processedFine->member_id);
        echo "  Member can borrow: " . ($canBorrow ? "✅" : "❌") . "\n";
        
    } catch (\Exception $e) {
        echo "  ❌ Payment processing failed: " . $e->getMessage() . "\n";
        echo "  Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
} else {
    echo "No unpaid fines found for testing\n";
}

echo "\n";

// 6. Check role-specific data access
echo "6. CHECKING ROLE-SPECIFIC DATA ACCESS\n";
echo "=====================================\n";

// Super Admin access
echo "👑 Super Admin Access:\n";
$allFines = \App\Models\Fine::with(['member', 'borrowingItem.book'])->get();
echo "  Total fines accessible: {$allFines->count()}\n";

// Member access (simulate different members)
$membersWithFines = \App\Models\Fine::distinct('member_id')->pluck('member_id');

foreach ($membersWithFines as $memberId) {
    $member = \App\Models\User::find($memberId);
    $memberFines = \App\Models\Fine::where('member_id', $memberId)->get();
    
    echo "👤 Member {$member->name} (ID: {$memberId}):\n";
    echo "  Fines accessible: {$memberFines->count()}\n";
    foreach ($memberFines as $fine) {
        echo "    - Fine ID: {$fine->id}, Amount: Rp {$fine->amount}, Status: {$fine->status}\n";
    }
}

// Librarian access (payment verification)
echo "📚 Librarian Access:\n";
$paymentVerifications = \App\Models\PaymentVerification::with(['fine.member', 'fine.borrowingItem.book'])->get();
echo "  Payment verifications accessible: {$paymentVerifications->count()}\n";

echo "\n=== PAYMENT RELATIONSHIPS & DATA FLOW CHECK COMPLETE ===\n";
