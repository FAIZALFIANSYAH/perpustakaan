<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== CHECKING PAYMENT FLOW FOR OVERDUE BOOKS ===\n\n";

// 1. Check current payment methods
echo "1. CHECKING CURRENT PAYMENT METHODS\n";
echo "===================================\n";

$fineServicePath = app_path('Services/FineService.php');
$fineServiceContent = file_get_contents($fineServicePath);

echo "FineService payment methods:\n";

if (strpos($fineServiceContent, 'processFinePayment') !== false) {
    echo "  ├─ processFinePayment: ✅ Found\n";
} else {
    echo "  ├─ processFinePayment: ❌ Not found\n";
}

if (strpos($fineServiceContent, 'payFine') !== false) {
    echo "  ├─ payFine: ✅ Found\n";
} else {
    echo "  ├─ payFine: ❌ Not found\n";
}

if (strpos($fineServiceContent, 'updateFineStatus') !== false) {
    echo "  ├─ updateFineStatus: ✅ Found\n";
} else {
    echo "  ├─ updateFineStatus: ❌ Not found\n";
}

echo "\n";

// 2. Check BorrowingService return methods
echo "2. CHECKING BORROWINGSERVICE RETURN METHODS\n";
echo "==========================================\n";

$borrowingServicePath = app_path('Services/BorrowingService.php');
$borrowingServiceContent = file_get_contents($borrowingServicePath);

echo "BorrowingService return methods:\n";

if (strpos($borrowingServiceContent, 'returnBorrowing') !== false) {
    echo "  ├─ returnBorrowing: ✅ Found\n";
} else {
    echo "  ├─ returnBorrowing: ❌ Not found\n";
}

if (strpos($borrowingServiceContent, 'updateBorrowingStatusBasedOnFines') !== false) {
    echo "  ├─ updateBorrowingStatusBasedOnFines: ✅ Found\n";
} else {
    echo "  ├─ updateBorrowingStatusBasedOnFines: ❌ Not found\n";
}

echo "\n";

// 3. Check current overdue borrowings
echo "3. CHECKING CURRENT OVERDUE BORROWINGS\n";
echo "=====================================\n";

$overdueBorrowings = \App\Models\Borrowing::where('status', 'overdue')
    ->with(['member', 'items.fines'])
    ->get();

echo "Current overdue borrowings: " . $overdueBorrowings->count() . "\n";

foreach ($overdueBorrowings as $borrowing) {
    echo "Borrowing ID: {$borrowing->id}\n";
    echo "  ├─ Member: " . $borrowing->member->name . "\n";
    echo "  ├─ Status: {$borrowing->status}\n";
    echo "  ├─ Due At: {$borrowing->due_at}\n";
    echo "  ├─ Items: " . $borrowing->items->count() . "\n";
    
    foreach ($borrowing->items as $item) {
        echo "  │  ├─ Book: " . $item->book->title . "\n";
        echo "  │  ├─ Quantity: {$item->quantity}\n";
        echo "  │  └─ Fines: " . $item->fines->count() . "\n";
        
        foreach ($item->fines as $fine) {
            echo "  │     ├─ Fine ID: {$fine->id}\n";
            echo "  │     ├─ Amount: Rp {$fine->amount}\n";
            echo "  │     └─ Status: {$fine->status}\n";
        }
    }
    echo "\n";
}

// 4. Test payment flow
echo "4. TESTING PAYMENT FLOW\n";
echo "======================\n";

if ($overdueBorrowings->count() > 0) {
    $testBorrowing = $overdueBorrowings->first();
    $testFine = $testBorrowing->items->flatMap->fines->first();
    
    if ($testFine) {
        echo "Testing payment flow with:\n";
        echo "  ├─ Borrowing ID: {$testBorrowing->id}\n";
        echo "  ├─ Fine ID: {$testFine->id}\n";
        echo "  ├─ Fine Amount: Rp {$testFine->amount}\n";
        echo "  ├─ Fine Status: {$testFine->status}\n";
        echo "  ├─ Borrowing Status: {$testBorrowing->status}\n";
        
        // Test fine payment
        try {
            $fineService = app(\App\Services\FineService::class);
            
            echo "\nProcessing fine payment...\n";
            
            // Create payment record
            $paymentData = [
                'fine_id' => $testFine->id,
                'amount' => $testFine->amount,
                'payment_method' => 'cash',
                'payment_date' => now(),
                'notes' => 'Test payment for overdue book'
            ];
            
            $payment = $fineService->processFinePayment($testFine->id, $paymentData);
            
            if ($payment) {
                echo "✅ Payment processed successfully\n";
                echo "  ├─ Payment ID: {$payment->id}\n";
                echo "  ├─ Amount: Rp {$payment->amount}\n";
                echo "  ├─ Payment Method: {$payment->payment_method}\n";
                echo "  ├─ Payment Date: {$payment->payment_date}\n";
                
                // Check fine status after payment
                $updatedFine = \App\Models\Fine::find($testFine->id);
                echo "  └─ Fine Status After Payment: {$updatedFine->status}\n";
                
                // Check borrowing status after payment
                $updatedBorrowing = \App\Models\Borrowing::find($testBorrowing->id);
                echo "  └─ Borrowing Status After Payment: {$updatedBorrowing->status}\n";
                
                // Analyze the flow
                echo "\nFlow Analysis:\n";
                echo "  ├─ Before Payment: Overdue → {$testFine->status}\n";
                echo "  ├─ After Payment: {$updatedBorrowing->status} → {$updatedFine->status}\n";
                
                if ($updatedBorrowing->status === 'borrowed') {
                    echo "  ❌ ISSUE: Status changed to 'borrowed' after payment\n";
                } elseif ($updatedBorrowing->status === 'returned') {
                    echo "  ✅ CORRECT: Status changed to 'returned' after payment\n";
                } elseif ($updatedBorrowing->status === 'overdue') {
                    echo "  ❌ ISSUE: Status still 'overdue' after payment\n";
                } else {
                    echo "  ⚠️  UNEXPECTED: Status changed to '{$updatedBorrowing->status}' after payment\n";
                }
                
            } else {
                echo "❌ Payment processing failed\n";
            }
            
        } catch (\Exception $e) {
            echo "❌ Payment test failed: " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "❌ No overdue borrowings found to test\n";
}

echo "\n";

// 5. Check what should happen according to business logic
echo "5. EXPECTED FLOW ANALYSIS\n";
echo "==========================\n";

echo "Expected Flow (based on your question):\n";
echo "  1. Borrowing Status: overdue\n";
echo "  2. Fine Status: unpaid\n";
echo "  3. Payment Processed → Fine Status: paid\n";
echo "  4. After Payment → Borrowing Status: borrowed\n";
echo "  5. Librarian/Super Admin processes return → Borrowing Status: returned/complete\n\n";

echo "Current Issues:\n";
echo "  ❌ Payment might change status incorrectly\n";
echo "  ❌ No automatic status update after payment\n";
echo "  ❌ Manual intervention required for return processing\n\n";

echo "\n";

// 6. Summary
echo "6. PAYMENT FLOW SUMMARY\n";
echo "======================\n";

echo "Current Status:\n";
echo "  ├─ Payment Methods: " . (strpos($fineServiceContent, 'processFinePayment') !== false ? "Available" : "Missing") . "\n";
echo "  ├─ Return Methods: " . (strpos($borrowingServiceContent, 'returnBorrowing') !== false ? "Available" : "Missing") . "\n";
echo "  ├─ Overdue Borrowings: " . $overdueBorrowings->count() . "\n";
echo "  ├─ Payment Flow Status: " . (isset($payment) ? "Tested" : "Not Tested") . "\n";

echo "\n🔍 QUESTIONS FOR CLARIFICATION:\n";
echo "1. Apakah setelah pembayaran denda, status borrowing harus berubah dari 'overdue' ke 'borrowed'?\n";
echo "2. Atau status borrowing harus tetap 'overdue' sampai buku dikembalikan?\n";
echo "3. Apakah pembayaran denda otomatis mengubah status borrowing?\n";
echo "4. Atau perlu ada langkah tambahan (librarian/super admin menyelesaikan peminjaman)?\n";
echo "5. Apa status final yang diinginkan setelah pembayaran dan pengembalian buku?\n\n";

echo "=== PAYMENT FLOW CHECK COMPLETE ===\n";
