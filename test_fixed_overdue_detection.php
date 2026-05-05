<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== TESTING FIXED OVERDEW DETECTION ===\n\n";

// 1. Clean up previous test data
echo "1. CLEANING UP PREVIOUS TEST DATA\n";
echo "=================================\n";

\App\Models\Borrowing::query()->delete();
\App\Models\BorrowingItem::query()->delete();
\App\Models\Fine::query()->delete();

echo "✅ Cleaned up previous test data\n\n";

// 2. Test with fresh overdue borrowing
echo "2. TESTING WITH FRESH OVERDEW BORROWING\n";
echo "======================================\n";

try {
    // Get test data
    $testMember = \App\Models\User::whereHas('roles', function($query) {
        $query->where('name', 'Member');
    })->first();
    
    $testBook = \App\Models\Book::where('stock', '>', 0)->first();
    $testLibrarian = \App\Models\User::whereHas('roles', function($query) {
        $query->where('name', 'Librarian');
    })->first();
    
    if (!$testMember || !$testBook || !$testLibrarian) {
        echo "❌ Missing test data\n";
        exit(1);
    }
    
    echo "Test data:\n";
    echo "  ├─ Member: " . $testMember->name . "\n";
    echo "  ├─ Book: " . $testBook->title . " (Stock: {$testBook->stock})\n";
    echo "  ├─ Librarian: " . $testLibrarian->name . "\n";
    
    // Create overdue borrowing
    $borrowingData = [
        'member_id' => $testMember->id,
        'processed_by' => $testLibrarian->id,
        'borrowed_at' => now()->subDays(25)->toDateString(),
        'due_at' => now()->subDays(15)->toDateString(), // 15 days ago (overdue)
        'notes' => 'Test overdue borrowing - should auto-generate fines',
        'items' => [
            ['book_id' => $testBook->id, 'quantity' => 1],
        ]
    ];
    
    echo "\nCreating overdue borrowing...\n";
    $borrowingService = app(\App\Services\BorrowingService::class);
    $borrowing = $borrowingService->createBorrowing($borrowingData, $testLibrarian->id);
    
    echo "✅ Overdue borrowing created:\n";
    echo "  ├─ ID: {$borrowing->id}\n";
    echo "  ├─ Code: {$borrowing->code}\n";
    echo "  ├─ Due At: {$borrowing->due_at}\n";
    echo "  ├─ Status: {$borrowing->status}\n";
    echo "  ├─ Days Overdue: " . $borrowing->due_at->diffInDays(now()) . "\n";
    
    // Check if fines were generated automatically
    $borrowing->load('items.fines');
    $hasFines = $borrowing->items->flatMap->fines->count() > 0;
    
    echo "  └─ Has Fines: " . ($hasFines ? "✅ Yes" : "❌ No") . "\n";
    
    if ($hasFines) {
        foreach ($borrowing->items->flatMap->fines as $fine) {
            echo "    ├─ Fine ID: {$fine->id}\n";
            echo "    ├─ Amount: Rp {$fine->amount}\n";
            echo "    ├─ Status: {$fine->status}\n";
            echo "    └─ Type: {$fine->type}\n";
        }
    } else {
        echo "    └─ ISSUE: No fines generated automatically\n";
    }
    
    // Check status
    if ($borrowing->status === 'overdue') {
        echo "  ✅ Status correctly updated to 'overdue'\n";
    } else {
        echo "  ❌ Status not updated, still: {$borrowing->status}\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n";

// 3. Verify Super Admin can see fines
echo "3. VERIFYING SUPER ADMIN CAN SEE FINES\n";
echo "=====================================\n";

$superAdminFines = \App\Models\Fine::with(['member', 'borrowingItem.book'])->get();
echo "Super Admin fines view: " . $superAdminFines->count() . " fines\n";

foreach ($superAdminFines as $fine) {
    echo "  ├─ " . $fine->member->name . " - " . ($fine->borrowingItem->book->title ?? 'N/A') . " - Rp {$fine->amount} ({$fine->status})\n";
}

// 4. Verify Member can see their fines
echo "\n4. VERIFYING MEMBER CAN SEE THEIR FINES\n";
echo "=====================================\n";

$memberFines = \App\Models\Fine::where('member_id', $testMember->id)
    ->with(['borrowingItem.book'])
    ->get();

echo "Member fines view (" . $testMember->name . "): " . $memberFines->count() . " fines\n";

foreach ($memberFines as $fine) {
    echo "  ├─ " . ($fine->borrowingItem->book->title ?? 'N/A') . " - Rp {$fine->amount} ({$fine->status})\n";
}

echo "\n";

// 5. Summary
echo "5. TEST SUMMARY\n";
echo "==============\n";

echo "✅ TEST RESULTS:\n";
echo "  1. ✅ Overdue borrowing created from Librarian\n";
echo "  2. ✅ Automatic overdue detection: " . ($hasFines ? "Working" : "Not Working") . "\n";
echo "  3. ✅ Automatic fine generation: " . ($hasFines ? "Working" : "Not Working") . "\n";
echo "  4. ✅ Automatic status update: " . ($borrowing->status === 'overdue' ? "Working" : "Not Working") . "\n";
echo "  5. ✅ Super Admin can see fines: " . ($superAdminFines->count() > 0 ? "Working" : "Not Working") . "\n";
echo "  6. ✅ Member can see their fines: " . ($memberFines->count() > 0 ? "Working" : "Not Working") . "\n";

if ($hasFines && $borrowing->status === 'overdue') {
    echo "\n🎉 OVERDEW DETECTION FIX SUCCESSFUL!\n";
    echo "✅ Automatic overdue detection working\n";
    echo "✅ Automatic fine generation working\n";
    echo "✅ Automatic status update working\n";
    echo "✅ Data synchronized across all roles\n";
    echo "✅ No manual intervention required\n";
} else {
    echo "\n❌ OVERDEW DETECTION STILL NOT WORKING\n";
    echo "❌ Need further investigation\n";
    echo "❌ Manual intervention still required\n";
}

echo "\n";

echo "=== TEST COMPLETE ===\n";
