<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== FINE SYSTEM INTEGRITY CHECK ===\n\n";

// 1. Check Fine Configuration
echo "1. FINE CONFIGURATION CHECK\n";
echo "============================\n";

try {
    $fineService = app(\App\Services\FineService::class);
    $config = $fineService->getFineConfig();
    
    if ($config) {
        echo "✅ Fine config found:\n";
        echo "   - Grace Period: {$config->grace_period_days} days\n";
        echo "   - Fine Per Day: Rp {$config->fine_per_day}\n";
        echo "   - Lost Book Fine: Rp {$config->lost_book_fine}\n";
        echo "   - Max Fine Cap: " . ($config->max_fine_cap ? "Rp {$config->max_fine_cap}" : "No cap") . "\n";
        echo "   - Is Active: " . ($config->is_active ? "Yes" : "No") . "\n";
    } else {
        echo "❌ No fine config found\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking fine config: " . $e->getMessage() . "\n";
}

echo "\n";

// 2. Check Late Return Fine Calculation
echo "2. LATE RETURN FINE CALCULATION\n";
echo "================================\n";

try {
    // Create test borrowing scenario
    $borrowing = new \App\Models\Borrowing();
    $borrowing->due_at = now()->subDays(5); // Due 5 days ago
    $borrowing->member_id = 1;
    
    $item = new \App\Models\BorrowingItem();
    $item->quantity = 2;
    $item->returned_quantity = 0;
    
    // Test calculation
    $fineAmount = $fineService->calculateLateFine($borrowing, $item, 2);
    
    echo "✅ Late return fine calculation test:\n";
    echo "   - Due Date: " . $borrowing->due_at->toDateString() . "\n";
    echo "   - Return Date: " . now()->toDateString() . "\n";
    echo "   - Days Late: " . $borrowing->due_at->diffInDays(now()) . "\n";
    echo "   - Grace Period: " . $config->grace_period_days . " days\n";
    echo "   - Billable Days: " . max(0, $borrowing->due_at->diffInDays(now()) - $config->grace_period_days) . "\n";
    echo "   - Fine Per Day: Rp {$config->fine_per_day}\n";
    echo "   - Quantity: 2 books\n";
    echo "   - Total Fine: Rp {$fineAmount}\n";
    
    // Expected calculation
    $expectedDays = max(0, $borrowing->due_at->diffInDays(now()) - $config->grace_period_days);
    $expectedAmount = $expectedDays * (float) $config->fine_per_day * 2;
    echo "   - Expected: Rp {$expectedAmount}\n";
    echo "   - Match: " . ($fineAmount == $expectedAmount ? "✅" : "❌") . "\n";
    
} catch (Exception $e) {
    echo "❌ Error testing late return fine: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. Check Lost Book Fine Calculation
echo "3. LOST BOOK FINE CALCULATION\n";
echo "===============================\n";

try {
    $borrowing = new \App\Models\Borrowing();
    $borrowing->member_id = 1;
    
    $item = new \App\Models\BorrowingItem();
    $item->quantity = 1;
    
    // Test lost book fine creation
    $fine = $fineService->createLostBookFine($borrowing, $item, 1, "Test lost book");
    
    echo "✅ Lost book fine creation test:\n";
    echo "   - Lost Quantity: 1 book\n";
    echo "   - Lost Book Fine Rate: Rp {$config->lost_book_fine}\n";
    echo "   - Total Fine: Rp {$fine->amount}\n";
    echo "   - Due Date: " . $fine->due_date . "\n";
    echo "   - Status: " . $fine->status . "\n";
    echo "   - Type: " . $fine->type . "\n";
    
    $expectedAmount = (float) $config->lost_book_fine * 1;
    echo "   - Expected: Rp {$expectedAmount}\n";
    echo "   - Match: " . ($fine->amount == $expectedAmount ? "✅" : "❌") . "\n";
    
} catch (Exception $e) {
    echo "❌ Error testing lost book fine: " . $e->getMessage() . "\n";
}

echo "\n";

// 4. Check Member Blocking Logic
echo "4. MEMBER BLOCKING LOGIC\n";
echo "========================\n";

try {
    // Test member with no fines
    $canBorrowNoFines = $fineService->canMemberBorrow(999); // Assuming member 999 has no fines
    echo "✅ Member with no fines can borrow: " . ($canBorrowNoFines ? "✅" : "❌") . "\n";
    
    // Test member with unpaid fines (if exists)
    $unpaidFines = $fineService->getUnpaidFinesByMember(1);
    if ($unpaidFines->count() > 0) {
        $canBorrowWithFines = $fineService->canMemberBorrow(1);
        $blockReason = $fineService->getMemberBorrowingBlockReason(1);
        
        echo "✅ Member with unpaid fines cannot borrow: " . (!$canBorrowWithFines ? "✅" : "❌") . "\n";
        echo "   - Block Reason: " . ($blockReason ?? "None") . "\n";
        echo "   - Unpaid Fines Count: " . $unpaidFines->count() . "\n";
        echo "   - Total Unpaid Amount: Rp " . number_format($fineService->getTotalUnpaidFines(1), 0, ',', '.') . "\n";
    } else {
        echo "ℹ️  No unpaid fines found for member 1 to test blocking\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error testing member blocking: " . $e->getMessage() . "\n";
}

echo "\n";

// 5. Check Due Date Implementation
echo "5. DUE DATE IMPLEMENTATION\n";
echo "==========================\n";

try {
    // Check late return fine due date (should be 7 days)
    $borrowing = new \App\Models\Borrowing();
    $borrowing->due_at = now()->subDays(3);
    $borrowing->member_id = 1;
    
    $item = new \App\Models\BorrowingItem();
    $item->quantity = 1;
    
    $lateFine = $fineService->createLateReturnFine($borrowing, $item, 1);
    
    if ($lateFine) {
        $expectedDueDate = now()->addDays(7)->toDateString();
        echo "✅ Late return fine due date:\n";
        echo "   - Created Date: " . now()->toDateString() . "\n";
        echo "   - Due Date: " . $lateFine->due_date . "\n";
        echo "   - Expected: " . $expectedDueDate . "\n";
        echo "   - Match: " . ($lateFine->due_date === $expectedDueDate ? "✅" : "❌") . "\n";
    }
    
    // Check lost book fine due date (should be 14 days)
    $lostFine = $fineService->createLostBookFine($borrowing, $item, 1);
    $expectedLostDueDate = now()->addDays(14)->toDateString();
    
    echo "✅ Lost book fine due date:\n";
    echo "   - Created Date: " . now()->toDateString() . "\n";
    echo "   - Due Date: " . $lostFine->due_date . "\n";
    echo "   - Expected: " . $expectedLostDueDate . "\n";
    echo "   - Match: " . ($lostFine->due_date === $expectedLostDueDate ? "✅" : "❌") . "\n";
    
} catch (Exception $e) {
    echo "❌ Error testing due dates: " . $e->getMessage() . "\n";
}

echo "\n";

// 6. Check Payment Processing Integration
echo "6. PAYMENT PROCESSING INTEGRATION\n";
echo "==================================\n";

try {
    // Create a test fine for payment testing
    $borrowing = new \App\Models\Borrowing();
    $borrowing->member_id = 1;
    
    $item = new \App\Models\BorrowingItem();
    $item->quantity = 1;
    
    $testFine = $fineService->createLateReturnFine($borrowing, $item, 1);
    
    if ($testFine) {
        echo "✅ Test fine created for payment testing:\n";
        echo "   - Fine ID: " . $testFine->id . "\n";
        echo "   - Amount: Rp {$testFine->amount}\n";
        echo "   - Status: " . $testFine->status . "\n";
        echo "   - Paid Amount: Rp {$testFine->paid_amount}\n";
        echo "   - Remaining: Rp " . ($testFine->amount - $testFine->paid_amount) . "\n";
        
        // Test payment processing
        $paymentAmount = $testFine->amount / 2; // Partial payment
        $processedFine = $fineService->processFinePayment(
            $testFine, 
            $paymentAmount, 
            'cash', 
            1, 
            'Test payment'
        );
        
        echo "✅ Payment processing test:\n";
        echo "   - Payment Amount: Rp {$paymentAmount}\n";
        echo "   - New Status: " . $processedFine->status . "\n";
        echo "   - New Paid Amount: Rp {$processedFine->paid_amount}\n";
        echo "   - New Remaining: Rp " . ($processedFine->amount - $processedFine->paid_amount) . "\n";
        echo "   - Expected Status: partial\n";
        echo "   - Status Match: " . ($processedFine->status === 'partial' ? "✅" : "❌") . "\n";
        
        // Test full payment
        $remainingAmount = $processedFine->amount - $processedFine->paid_amount;
        $fullyPaidFine = $fineService->processFinePayment(
            $processedFine,
            $remainingAmount,
            'cash',
            1,
            'Final payment'
        );
        
        echo "✅ Full payment test:\n";
        echo "   - Final Payment Amount: Rp {$remainingAmount}\n";
        echo "   - Final Status: " . $fullyPaidFine->status . "\n";
        echo "   - Final Paid Amount: Rp {$fullyPaidFine->paid_amount}\n";
        echo "   - Expected Status: paid\n";
        echo "   - Status Match: " . ($fullyPaidFine->status === 'paid' ? "✅" : "❌") . "\n";
        echo "   - Paid At: " . ($fullyPaidFine->paid_at ?? 'Not set') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error testing payment processing: " . $e->getMessage() . "\n";
}

echo "\n";

// 7. Check Borrowing Service Integration
echo "7. BORROWING SERVICE INTEGRATION\n";
echo "=================================\n";

try {
    $borrowingService = app(\App\Services\BorrowingService::class);
    
    // Test if borrowing service checks for unpaid fines
    echo "✅ Borrowing Service Fine Integration:\n";
    echo "   - canMemberBorrow method exists: " . (method_exists($fineService, 'canMemberBorrow') ? "✅" : "❌") . "\n";
    echo "   - getMemberBorrowingBlockReason method exists: " . (method_exists($fineService, 'getMemberBorrowingBlockReason') ? "✅" : "❌") . "\n";
    
    // Check if borrowing service uses fine service
    $reflection = new ReflectionClass($borrowingService);
    $constructor = $reflection->getConstructor();
    $params = $constructor->getParameters();
    
    $hasFineService = false;
    foreach ($params as $param) {
        if ($param->getType() && $param->getType()->getName() === 'App\Services\FineService') {
            $hasFineService = true;
            break;
        }
    }
    
    echo "   - BorrowingService depends on FineService: " . ($hasFineService ? "✅" : "❌") . "\n";
    
} catch (Exception $e) {
    echo "❌ Error testing borrowing service integration: " . $e->getMessage() . "\n";
}

echo "\n";

// 8. Check Database Schema
echo "8. DATABASE SCHEMA CHECK\n";
echo "========================\n";

try {
    // Check fines table structure
    $finesColumns = \Illuminate\Support\Facades\Schema::getColumnListing('fines');
    echo "✅ Fines table columns:\n";
    foreach ($finesColumns as $column) {
        echo "   - {$column}\n";
    }
    
    // Check payment_verifications table structure
    $paymentColumns = \Illuminate\Support\Facades\Schema::getColumnListing('payment_verifications');
    echo "\n✅ Payment_verifications table columns:\n";
    foreach ($paymentColumns as $column) {
        echo "   - {$column}\n";
    }
    
    // Check fine_configs table structure
    if (\Illuminate\Support\Facades\Schema::hasTable('fine_configs')) {
        $configColumns = \Illuminate\Support\Facades\Schema::getColumnListing('fine_configs');
        echo "\n✅ Fine_configs table columns:\n";
        foreach ($configColumns as $column) {
            echo "   - {$column}\n";
        }
    } else {
        echo "\n❌ Fine_configs table not found\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error checking database schema: " . $e->getMessage() . "\n";
}

echo "\n=== INTEGRITY CHECK COMPLETE ===\n";
