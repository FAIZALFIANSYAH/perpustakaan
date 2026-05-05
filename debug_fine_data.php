<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== DEBUG FINE DATA INTEGRATION ===\n\n";

// 1. Check if there are any overdue borrowings
echo "1. CHECKING OVERDUE BORROWINGS\n";
echo "===============================\n";

$overdueBorrowings = \App\Models\Borrowing::with(['items.book', 'member'])
    ->where('due_at', '<', now())
    ->whereNotIn('status', ['complete', 'cancelled'])
    ->get();

echo "Found {$overdueBorrowings->count()} overdue borrowings:\n\n";

foreach ($overdueBorrowings as $borrowing) {
    echo "Borrowing ID: {$borrowing->id}\n";
    echo "Member: {$borrowing->member->name} (ID: {$borrowing->member->id})\n";
    echo "Due Date: {$borrowing->due_at}\n";
    echo "Status: {$borrowing->status}\n";
    echo "Days Overdue: " . $borrowing->due_at->diffInDays(now()) . "\n";
    
    foreach ($borrowing->items as $item) {
        echo "  - Book: {$item->book->title}\n";
        echo "    Quantity: {$item->quantity}\n";
        echo "    Returned: {$item->returned_quantity}\n";
    }
    echo "\n";
}

// 2. Check if fines exist for these overdue borrowings
echo "2. CHECKING EXISTING FINES\n";
echo "========================\n";

$fines = \App\Models\Fine::with(['borrowingItem.book', 'member'])
    ->get();

echo "Found {$fines->count()} total fines in system:\n\n";

foreach ($fines as $fine) {
    echo "Fine ID: {$fine->id}\n";
    echo "Member: {$fine->member->name} (ID: {$fine->member->id})\n";
    echo "Type: {$fine->type}\n";
    echo "Amount: Rp {$fine->amount}\n";
    echo "Status: {$fine->status}\n";
    echo "Due Date: {$fine->due_date}\n";
    echo "Created: {$fine->created_at}\n";
    echo "Borrowing Item ID: " . ($fine->borrowingItem->id ?? 'N/A') . "\n";
    echo "Book: " . ($fine->borrowingItem->book->title ?? 'N/A') . "\n";
    echo "\n";
}

// 3. Check specific member fines
echo "3. CHECKING MEMBER FINE DATA\n";
echo "===========================\n";

if ($overdueBorrowings->count() > 0) {
    $firstBorrowing = $overdueBorrowings->first();
    $memberId = $firstBorrowing->member_id;
    
    echo "Checking fines for Member ID: {$memberId}\n";
    echo "Member Name: {$firstBorrowing->member->name}\n\n";
    
    // Using FineService
    $fineService = app(\App\Services\FineService::class);
    $memberFines = $fineService->getMemberFines($memberId);
    
    echo "FineService->getMemberFines() result:\n";
    echo "Found {$memberFines->count()} fines\n\n";
    
    foreach ($memberFines as $fine) {
        echo "- Fine ID: {$fine->id}, Type: {$fine->type}, Amount: Rp {$fine->amount}, Status: {$fine->status}\n";
    }
    
    // Check statistics
    $stats = $fineService->getMemberFineStatistics($memberId);
    echo "\nMember Statistics:\n";
    echo "- Total Fines: {$stats['total_fines']}\n";
    echo "- Total Unpaid: {$stats['total_unpaid']}\n";
    echo "- Total Amount: Rp {$stats['total_amount']}\n";
    echo "- Unpaid Amount: Rp {$stats['total_unpaid_amount']}\n";
    
    // Check unpaid amount
    $totalUnpaid = $fineService->getTotalUnpaidFines($memberId);
    echo "- Total Unpaid (Service): Rp {$totalUnpaid}\n";
    
} else {
    echo "No overdue borrowings found to test member fines\n";
}

echo "\n";

// 4. Check if fines should be created for overdue borrowings
echo "4. CHECKING FINE CREATION LOGIC\n";
echo "================================\n";

foreach ($overdueBorrowings as $borrowing) {
    echo "Borrowing ID: {$borrowing->id}\n";
    
    // Check if this borrowing should have fines
    $hasFines = \App\Models\Fine::whereHas('borrowingItem', function($query) use ($borrowing) {
        $query->where('borrowing_id', $borrowing->id);
    })->exists();
    
    echo "- Has fines: " . ($hasFines ? "Yes" : "No") . "\n";
    
    // Check if all items are returned
    $totalQuantity = $borrowing->items->sum('quantity');
    $returnedQuantity = $borrowing->items->sum('returned_quantity');
    
    echo "- Total Quantity: {$totalQuantity}\n";
    echo "- Returned Quantity: {$returnedQuantity}\n";
    echo "- All Returned: " . ($returnedQuantity >= $totalQuantity ? "Yes" : "No") . "\n";
    
    if ($returnedQuantity >= $totalQuantity && !$hasFines) {
        echo "- ISSUE: All items returned but no fines created!\n";
        
        // Calculate what fine should be
        $fineService = app(\App\Services\FineService::class);
        foreach ($borrowing->items as $item) {
            $fineAmount = $fineService->calculateLateFine($borrowing, $item, $item->quantity);
            echo "- Expected fine for this item: Rp {$fineAmount}\n";
        }
    }
    
    echo "\n";
}

// 5. Test manual fine creation
echo "5. TESTING MANUAL FINE CREATION\n";
echo "=================================\n";

if ($overdueBorrowings->count() > 0) {
    $testBorrowing = $overdueBorrowings->first();
    $testItem = $testBorrowing->items->first();
    
    echo "Testing fine creation for:\n";
    echo "- Borrowing ID: {$testBorrowing->id}\n";
    echo "- Book: {$testItem->book->title}\n";
    echo "- Due Date: {$testBorrowing->due_at}\n";
    echo "- Days Late: " . $testBorrowing->due_at->diffInDays(now()) . "\n";
    
    try {
        $fineService = app(\App\Services\FineService::class);
        $fineAmount = $fineService->calculateLateFine($testBorrowing, $testItem, $testItem->quantity);
        
        echo "- Calculated Fine Amount: Rp {$fineAmount}\n";
        
        if ($fineAmount > 0) {
            echo "- Creating fine...\n";
            $fine = $fineService->createLateReturnFine($testBorrowing, $testItem, $testItem->quantity);
            
            if ($fine) {
                echo "- Fine Created Successfully!\n";
                echo "- Fine ID: {$fine->id}\n";
                echo "- Amount: Rp {$fine->amount}\n";
                echo "- Status: {$fine->status}\n";
            } else {
                echo "- Fine creation returned null (no fine needed)\n";
            }
        } else {
            echo "- No fine needed (amount is 0)\n";
        }
        
    } catch (Exception $e) {
        echo "- Error creating fine: " . $e->getMessage() . "\n";
    }
}

echo "\n=== DEBUG COMPLETE ===\n";
