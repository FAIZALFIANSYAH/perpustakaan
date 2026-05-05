<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== PAYMENT DATA FLOW INTEGRATION TRACE ===\n\n";

// 1. Check payment-related tables and their data
echo "1. PAYMENT DATA TABLES ANALYSIS\n";
echo "================================\n";

// Check fines table
echo "📊 Fines Table:\n";
$fines = \App\Models\Fine::with(['member', 'borrowingItem.book', 'payments', 'paymentVerification'])->get();
echo "Total fines: {$fines->count()}\n\n";

foreach ($fines as $fine) {
    echo "Fine ID: {$fine->id}\n";
    echo "  Member: {$fine->member->name} (ID: {$fine->member->id})\n";
    echo "  Book: " . ($fine->borrowingItem->book->title ?? 'N/A') . "\n";
    echo "  Type: {$fine->type}\n";
    echo "  Amount: Rp {$fine->amount}\n";
    echo "  Paid Amount: Rp {$fine->paid_amount}\n";
    echo "  Status: {$fine->status}\n";
    echo "  Due Date: {$fine->due_date}\n";
    echo "  Paid At: " . ($fine->paid_at ?? 'Not paid') . "\n";
    
    // Check payments
    echo "  Payments: {$fine->payments->count()}\n";
    foreach ($fine->payments as $payment) {
        echo "    - Payment ID: {$payment->id}, Amount: Rp {$payment->amount}, Method: {$payment->payment_method}, Status: {$payment->status}\n";
    }
    
    // Check payment verification
    echo "  Payment Verifications: {$fine->paymentVerification->count()}\n";
    foreach ($fine->paymentVerification as $verification) {
        echo "    - Verification ID: {$verification->id}, Requested: Rp {$verification->requested_amount}, Status: {$verification->status}\n";
    }
    echo "\n";
}

// Check payment_verifications table
echo "📊 Payment Verifications Table:\n";
$verifications = \App\Models\PaymentVerification::with(['fine.member', 'fine.borrowingItem.book', 'requestedBy', 'verifiedBy'])->get();
echo "Total verifications: {$verifications->count()}\n\n";

foreach ($verifications as $verification) {
    echo "Verification ID: {$verification->id}\n";
    echo "  Fine ID: {$verification->fine_id}\n";
    echo "  Member: " . ($verification->fine->member->name ?? 'N/A') . "\n";
    echo "  Requested Amount: Rp {$verification->requested_amount}\n";
    echo "  Payment Method: {$verification->payment_method}\n";
    echo "  Status: {$verification->status}\n";
    echo "  Requested By: " . ($verification->requestedBy->name ?? 'N/A') . "\n";
    echo "  Verified By: " . ($verification->verifiedBy->name ?? 'Not verified') . "\n";
    echo "  Created: {$verification->created_at}\n";
    echo "  Updated: {$verification->updated_at}\n";
    echo "\n";
}

// Check fine_payments table
echo "📊 Fine Payments Table:\n";
$payments = \App\Models\FinePayment::with(['fine.member', 'fine.borrowingItem.book'])->get();
echo "Total payments: {$payments->count()}\n\n";

foreach ($payments as $payment) {
    echo "Payment ID: {$payment->id}\n";
    echo "  Fine ID: {$payment->fine_id}\n";
    echo "  Member: " . ($payment->fine->member->name ?? 'N/A') . "\n";
    echo "  Amount: Rp {$payment->amount}\n";
    echo "  Payment Method: {$payment->payment_method}\n";
    echo "  Status: {$payment->status}\n";
    echo "  Notes: " . ($payment->notes ?? 'None') . "\n";
    echo "  Created: {$payment->created_at}\n";
    echo "\n";
}

// 2. Trace payment controllers and routes
echo "2. PAYMENT CONTROLLERS & ROUTES\n";
echo "===============================\n";

// Check FineController routes
echo "🔗 FineController Routes:\n";
$routes = \Illuminate\Support\Facades\Route::getRoutes();
foreach ($routes as $route) {
    if (strpos($route->getActionName(), 'FineController') !== false) {
        echo "  - " . $route->methods()[0] . " " . $route->uri() . " → " . $route->getActionName() . "\n";
    }
}
echo "\n";

// Check PaymentVerificationController routes
echo "🔗 PaymentVerificationController Routes:\n";
foreach ($routes as $route) {
    if (strpos($route->getActionName(), 'PaymentVerificationController') !== false) {
        echo "  - " . $route->methods()[0] . " " . $route->uri() . " → " . $route->getActionName() . "\n";
    }
}
echo "\n";

// 3. Check role-specific UI components
echo "3. ROLE-SPECIFIC UI COMPONENTS\n";
echo "===============================\n";

// Super Admin fine management
echo "👑 Super Admin Fine Management:\n";
echo "  - Route: /admin/fines\n";
echo "  - Controller: FineController@index\n";
echo "  - UI: resources/js/Pages/Admin/Fines/Index.tsx\n";
echo "  - Data: All fines with full details\n\n";

// Librarian payment verification
echo "📚 Librarian Payment Verification:\n";
echo "  - Route: /librarian/payment-verification\n";
echo "  - Controller: PaymentVerificationController@index\n";
echo "  - UI: resources/js/Pages/Librarian/PaymentVerification/Dashboard.tsx\n";
echo "  - Data: Payment verification requests\n\n";

// Member fine view
echo "👤 Member Fine View:\n";
echo "  - Route: /member/fines\n";
echo "  - Controller: FineController@memberIndex\n";
echo "  - UI: resources/js/Pages/Member/Fines/Index.tsx\n";
echo "  - Data: Member's own fines only\n\n";

// 4. Test payment processing flow
echo "4. PAYMENT PROCESSING FLOW TEST\n";
echo "===============================\n";

// Get an unpaid fine for testing
$unpaidFine = \App\Models\Fine::where('status', 'unpaid')->first();

if ($unpaidFine) {
    echo "Testing with Fine ID: {$unpaidFine}\n";
    echo "  Member: " . $unpaidFine->member->name . "\n";
    echo "  Amount: Rp {$unpaidFine->amount}\n";
    echo "  Status: {$unpaidFine->status}\n";
    
    // Test fine service payment processing
    $fineService = app(\App\Services\FineService::class);
    
    echo "\n📝 Testing Payment Processing:\n";
    try {
        // Test partial payment
        $partialAmount = $unpaidFine->amount / 2;
        echo "  Processing partial payment: Rp {$partialAmount}\n";
        
        $processedFine = $fineService->processFinePayment(
            $unpaidFine,
            $partialAmount,
            'cash',
            1, // Admin ID
            'Test partial payment'
        );
        
        echo "  ✅ Partial payment processed\n";
        echo "  New Status: {$processedFine->status}\n";
        echo "  New Paid Amount: Rp {$processedFine->paid_amount}\n";
        echo "  Remaining: Rp " . ($processedFine->amount - $processedFine->paid_amount) . "\n";
        
        // Test full payment
        $remainingAmount = $processedFine->amount - $processedFine->paid_amount;
        echo "\n  Processing full payment: Rp {$remainingAmount}\n";
        
        $fullyPaidFine = $fineService->processFinePayment(
            $processedFine,
            $remainingAmount,
            'cash',
            1, // Admin ID
            'Test full payment'
        );
        
        echo "  ✅ Full payment processed\n";
        echo "  Final Status: {$fullyPaidFine->status}\n";
        echo "  Final Paid Amount: Rp {$fullyPaidFine->paid_amount}\n";
        echo "  Paid At: " . ($fullyPaidFine->paid_at ?? 'Not set') . "\n";
        
    } catch (\Exception $e) {
        echo "  ❌ Payment processing failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "No unpaid fines found for testing\n";
}

echo "\n";

// 5. Check member borrowing status after payment
echo "5. MEMBER BORROWING STATUS CHECK\n";
echo "=================================\n";

$membersWithFines = \App\Models\Fine::distinct('member_id')->pluck('member_id');

foreach ($membersWithFines as $memberId) {
    $member = \App\Models\User::find($memberId);
    $fineService = app(\App\Services\FineService::class);
    
    $totalUnpaid = $fineService->getTotalUnpaidFines($memberId);
    $canBorrow = $fineService->canMemberBorrow($memberId);
    $blockReason = $fineService->getMemberBorrowingBlockReason($memberId);
    
    echo "Member: {$member->name} (ID: {$memberId})\n";
    echo "  Total Unpaid: Rp {$totalUnpaid}\n";
    echo "  Can Borrow: " . ($canBorrow ? "✅" : "❌") . "\n";
    echo "  Block Reason: " . ($blockReason ?? 'None') . "\n";
    echo "\n";
}

// 6. Check database relationships
echo "6. DATABASE RELATIONSHIPS CHECK\n";
echo "================================\n";

echo "🔗 Fine Model Relationships:\n";
$fineModel = new \App\Models\Fine();
$relationships = ['member', 'borrowingItem', 'borrowingItem.book', 'payments', 'paymentVerification'];

foreach ($relationships as $relationship) {
    try {
        $relation = $fineModel->$relationship();
        echo "  - {$relationship}: " . get_class($relation) . " ✅\n";
    } catch (\Exception $e) {
        echo "  - {$relationship}: ❌ " . $e->getMessage() . "\n";
    }
}

echo "\n🔗 PaymentVerification Model Relationships:\n";
$verificationModel = new \App\Models\PaymentVerification();
$verificationRelationships = ['fine', 'fine.member', 'fine.borrowingItem', 'fine.borrowingItem.book', 'requestedBy', 'verifiedBy'];

foreach ($verificationRelationships as $relationship) {
    try {
        $relation = $verificationModel->$relationship();
        echo "  - {$relationship}: " . get_class($relation) . " ✅\n";
    } catch (\Exception $e) {
        echo "  - {$relationship}: ❌ " . $e->getMessage() . "\n";
    }
}

echo "\n=== PAYMENT DATA FLOW TRACE COMPLETE ===\n";
