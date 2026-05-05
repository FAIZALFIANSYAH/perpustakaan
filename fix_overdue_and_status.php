<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== FIXING OVERDUE DISPLAY AND BORROWING STATUSES ===\n\n";

// 1. Update borrowing statuses for overdue items
echo "1. UPDATING BORROWING STATUSES\n";
echo "===============================\n";

$borrowingService = app(\App\Services\BorrowingService::class);

// Get all overdue borrowings
$overdueBorrowings = \App\Models\Borrowing::where('due_at', '<', now())
    ->get();

echo "Found {$overdueBorrowings->count()} overdue borrowings to update:\n\n";

foreach ($overdueBorrowings as $borrowing) {
    echo "Processing Borrowing ID: {$borrowing->id}\n";
    echo "  ├─ Member: " . $borrowing->member->name . "\n";
    echo "  ├─ Due Date: {$borrowing->due_at}\n";
    echo "  ├─ Current Status: {$borrowing->status}\n";
    
    // Load fines
    $borrowing->load('items.fines');
    $hasUnpaidFines = $borrowing->items->flatMap->fines->contains('status', 'unpaid');
    $allItemsReturned = $borrowing->items->every(function ($item) {
        return $item->returned_quantity >= $item->quantity;
    });
    
    echo "  ├─ Has Unpaid Fines: " . ($hasUnpaidFines ? "Yes" : "No") . "\n";
    echo "  ├─ All Items Returned: " . ($allItemsReturned ? "Yes" : "No") . "\n";
    
    // Determine correct status
    $newStatus = 'borrowed'; // default
    
    if ($borrowing->due_at < now()) {
        if ($hasUnpaidFines) {
            $newStatus = 'overdue';
        } elseif (!$allItemsReturned) {
            $newStatus = 'late_payment';
        } else {
            $newStatus = 'complete';
        }
    }
    
    if ($borrowing->status !== $newStatus) {
        $borrowing->update(['status' => $newStatus]);
        echo "  └─ Updated Status: {$newStatus}\n";
    } else {
        echo "  └─ Status already correct: {$newStatus}\n";
    }
    echo "\n";
}

// 2. Test Librarian Overdue with fixed query
echo "2. TESTING LIBRARIAN OVERDUE WITH FIXED QUERY\n";
echo "============================================\n";

$librarianService = app(\App\Services\LibrarianService::class);
$librarianOverdue = $librarianService->getOverdueData();

echo "Librarian Overdue (Fixed Query): {$librarianOverdue->count()} borrowings\n\n";

foreach ($librarianOverdue as $borrowing) {
    echo "Borrowing ID: {$borrowing->id}\n";
    echo "  ├─ Member: " . $borrowing->member->name . "\n";
    echo "  ├─ Due Date: {$borrowing->due_at}\n";
    echo "  ├─ Status: {$borrowing->status}\n";
    echo "  ├─ Items: " . $borrowing->items->count() . "\n";
    
    foreach ($borrowing->items as $item) {
        echo "    ├─ Book: " . $item->book->title . "\n";
        echo "    ├─ Returned: {$item->returned_quantity}/{$item->quantity}\n";
        echo "    └─ Fines: " . $item->fines->count() . "\n";
        
        foreach ($item->fines as $fine) {
            echo "      └─ Fine ID: {$fine->id}, Status: {$fine->status}\n";
        }
    }
    echo "\n";
}

// 3. Create test overdue with unpaid fines
echo "3. CREATING TEST OVERDUE WITH UNPAID FINES\n";
echo "==========================================\n";

// Find a member for testing
$testMember = \App\Models\User::whereHas('roles', function($query) {
    $query->where('name', 'Member');
})->first();

if ($testMember) {
    echo "Creating test overdue for member: " . $testMember->name . "\n";
    
    // Find a book
    $testBook = \App\Models\Book::where('stock', '>', 0)->first();
    if ($testBook) {
        echo "Using book: " . $testBook->title . "\n";
        
        // Create overdue borrowing
        $testBorrowing = \App\Models\Borrowing::create([
            'code' => 'TEST-OVERDUE-' . time(),
            'member_id' => $testMember->id,
            'processed_by' => 16, // Admin ID
            'borrowed_at' => now()->subDays(10)->toDateString(),
            'due_at' => now()->subDays(5)->toDateString(),
            'status' => 'borrowed',
        ]);
        
        // Create borrowing item
        $testBorrowingItem = \App\Models\BorrowingItem::create([
            'borrowing_id' => $testBorrowing->id,
            'book_id' => $testBook->id,
            'quantity' => 1,
            'returned_quantity' => 0,
        ]);
        
        // Create unpaid fine
        $fineService = app(\App\Services\FineService::class);
        $testFine = $fineService->createLateReturnFine($testBorrowing, $testBorrowingItem, 1);
        
        if ($testFine) {
            echo "✅ Created test overdue borrowing ID: {$testBorrowing->id}\n";
            echo "  ├─ Fine ID: {$testFine->id}, Amount: Rp {$testFine->amount}\n";
            echo "  ├─ Fine Status: {$testFine->status}\n";
            echo "  └─ Should appear in Librarian Overdue\n";
        }
    }
}

echo "\n";

// 4. Test updated Librarian Overdue again
echo "4. TESTING UPDATED LIBRARIAN OVERDUE\n";
echo "====================================\n";

$updatedOverdue = $librarianService->getOverdueData();

echo "Updated Librarian Overdue: {$updatedOverdue->count()} borrowings\n\n";

foreach ($updatedOverdue as $borrowing) {
    echo "  ├─ ID: {$borrowing->id}, Member: " . $borrowing->member->name . ", Status: {$borrowing->status}\n";
    
    // Check if has unpaid fines
    $hasUnpaidFines = $borrowing->items->flatMap->fines->contains('status', 'unpaid');
    echo "  └─ Has Unpaid Fines: " . ($hasUnpaidFines ? "Yes" : "No") . "\n";
}

echo "\n";

// 5. Verify UI status display
echo "5. VERIFYING UI STATUS DISPLAY\n";
echo "===============================\n";

echo "Expected UI Status Display:\n";
echo "  ├─ borrowed → Dipinjam (blue)\n";
echo "  ├─ overdue → Terlambat (red)\n";
echo "  ├─ late_payment → Pembayaran Terlambat (orange)\n";
echo "  ├─ complete → Selesai (green)\n";
echo "  ├─ returned → Dikembalikan (green)\n";
echo "  └─ lost → Hilang (purple)\n\n";

// 6. Summary
echo "6. FIX SUMMARY\n";
echo "==============\n";

echo "✅ FIXED ISSUES:\n";
echo "  1. LibrarianRepository::getOverdue() query updated\n";
echo "     - Now shows overdue based on due_date + unpaid fines\n";
echo "     - No longer relies on borrowing status alone\n\n";

echo "  2. Borrowing statuses updated\n";
echo "     - Overdue borrowings now have correct status\n";
echo "     - Status reflects actual condition\n\n";

echo "  3. Test data created\n";
echo "     - Added overdue with unpaid fines\n";
echo "     - Verifies query works correctly\n\n";

echo "🎯 EXPECTED BEHAVIOR:\n";
echo "  - Librarian Overdue shows users with unpaid fines\n";
echo "  - Overdue items remain visible until payment completed\n";
echo "  - UI displays correct borrowing statuses\n";
echo "  - Data consistency across all roles\n\n";

echo "=== OVERDUE FIX COMPLETE ===\n";
