<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== DEBUGGING OVERDUE DISPLAY ISSUE ===\n\n";

// 1. Check current overdue borrowings
echo "1. CURRENT OVERDUE BORROWINGS ANALYSIS\n";
echo "====================================\n";

$overdueBorrowings = \App\Models\Borrowing::with(['member', 'items.book', 'items.fines'])
    ->where('due_at', '<', now())
    ->get();

echo "Total overdue borrowings (due_date < today): {$overdueBorrowings->count()}\n\n";

foreach ($overdueBorrowings as $borrowing) {
    echo "Borrowing ID: {$borrowing->id}\n";
    echo "  ├─ Member: " . $borrowing->member->name . "\n";
    echo "  ├─ Due Date: {$borrowing->due_at}\n";
    echo "  ├─ Days Overdue: " . $borrowing->due_at->diffInDays(now()) . " days\n";
    echo "  ├─ Current Status: {$borrowing->status}\n";
    echo "  ├─ Items: " . $borrowing->items->count() . "\n";
    
    foreach ($borrowing->items as $item) {
        echo "    ├─ Book: " . $item->book->title . "\n";
        echo "    ├─ Quantity: {$item->quantity}\n";
        echo "    ├─ Returned: {$item->returned_quantity}\n";
        echo "    └─ Fines: " . $item->fines->count() . "\n";
        
        foreach ($item->fines as $fine) {
            echo "      └─ Fine ID: {$fine->id}, Amount: Rp {$fine->amount}, Status: {$fine->status}\n";
        }
    }
    
    // Check if should be in overdue
    $hasUnpaidFines = $borrowing->items->flatMap->fines->contains('status', 'unpaid');
    $allItemsReturned = $borrowing->items->every(function ($item) {
        return $item->returned_quantity >= $item->quantity;
    });
    
    echo "  ├─ Has Unpaid Fines: " . ($hasUnpaidFines ? "Yes" : "No") . "\n";
    echo "  ├─ All Items Returned: " . ($allItemsReturned ? "Yes" : "No") . "\n";
    echo "  └─ Should Show in Overdue: " . (($hasUnpaidFines || !$allItemsReturned) ? "Yes" : "No") . "\n";
    echo "\n";
}

// 2. Check what Librarian Overdue currently shows
echo "2. LIBRARIAN OVERDUE CURRENT DISPLAY\n";
echo "=====================================\n";

$librarianService = app(\App\Services\LibrarianService::class);
$librarianOverdue = $librarianService->getOverdueData();

echo "Librarian Overdue shows: {$librarianOverdue->count()} borrowings\n";

foreach ($librarianOverdue as $borrowing) {
    echo "  ├─ ID: {$borrowing->id}, Member: " . $borrowing->member->name . ", Status: {$borrowing->status}\n";
}

echo "\n";

// 3. Check the current LibrarianRepository query
echo "3. CURRENT LIBRARIANREPOSITORY QUERY\n";
echo "====================================\n";

echo "Current getOverdue() query:\n";
echo "```php\n";
echo "Borrowing::query()\n";
echo "    ->with(['member', 'items.book', 'items.fines'])\n";
echo "    ->where('status', 'overdue')\n";
echo "    ->orderBy('due_at')\n";
echo "    ->get();\n";
echo "```\n\n";

echo "❌ PROBLEM: Query only shows borrowings with status 'overdue'\n";
echo "❌ ISSUE: Borrowings with unpaid fines but status 'borrowed' are not shown\n";
echo "❌ EXPECTED: Show overdue borrowings regardless of status if they have unpaid fines\n\n";

// 4. Check what should be the correct logic
echo "4. EXPECTED OVERDUE LOGIC\n";
echo "========================\n";

echo "Expected behavior:\n";
echo "  ├─ Show borrowings where due_date < today\n";
echo "  ├─ Show borrowings with unpaid fines\n";
echo "  ├─ Show regardless of borrowing status\n";
echo "  └─ Only hide when all fines are paid AND all items returned\n\n";

echo "Expected query logic:\n";
echo "```php\n";
echo "Borrowing::query()\n";
echo "    ->with(['member', 'items.book', 'items.fines'])\n";
echo "    ->where('due_at', '<', now())\n";
echo "    ->where(function(\$query) {\n";
echo "        \$query->whereHas('items', function(\$itemQuery) {\n";
echo "            \$itemQuery->where('returned_quantity', '<', DB::raw('quantity'))\n";
echo "                   ->orWhereHas('fines', function(\$fineQuery) {\n";
echo "                       \$fineQuery->where('status', 'unpaid');\n";
echo "                   });\n";
echo "        });\n";
echo "    })\n";
echo "    ->orderBy('due_at')\n";
echo "    ->get();\n";
echo "```\n\n";

// 5. Check borrowing status consistency
echo "5. BORROWING STATUS CONSISTENCY\n";
echo "===============================\n";

echo "Current borrowing statuses:\n";
$statusCounts = [];
foreach ($overdueBorrowings as $borrowing) {
    $statusCounts[$borrowing->status] = ($statusCounts[$borrowing->status] ?? 0) + 1;
}

foreach ($statusCounts as $status => $count) {
    echo "  ├─ {$status}: {$count}\n";
}

echo "\n";

echo "Expected status updates needed:\n";
foreach ($overdueBorrowings as $borrowing) {
    $hasUnpaidFines = $borrowing->items->flatMap->fines->contains('status', 'unpaid');
    $allItemsReturned = $borrowing->items->every(function ($item) {
        return $item->returned_quantity >= $item->quantity;
    });
    
    $expectedStatus = 'borrowed'; // default
    
    if ($borrowing->due_at < now()) {
        if ($hasUnpaidFines) {
            $expectedStatus = 'overdue';
        } elseif (!$allItemsReturned) {
            $expectedStatus = 'late_payment';
        } else {
            $expectedStatus = 'complete';
        }
    }
    
    if ($borrowing->status !== $expectedStatus) {
        echo "  ├─ Borrowing {$borrowing->id}: {$borrowing->status} → should be {$expectedStatus}\n";
    }
}

echo "\n";

// 6. Proposed fix
echo "6. PROPOSED FIX\n";
echo "===============\n";

echo "🔧 STEP 1: Fix LibrarianRepository::getOverdue() query\n";
echo "   - Change query to show overdue based on due_date + unpaid fines\n";
echo "   - Don't rely on borrowing status alone\n\n";

echo "🔧 STEP 2: Update borrowing statuses\n";
echo "   - Update overdue borrowings to have correct status\n";
echo "   - Ensure status reflects actual condition\n\n";

echo "🔧 STEP 3: Verify UI displays\n";
echo "   - Check UI shows correct statuses\n";
echo "   - Ensure overdue list shows expected items\n\n";

echo "=== DEBUG ANALYSIS COMPLETE ===\n";
echo "\n💡 ROOT CAUSE:\n";
echo "LibrarianRepository::getOverdue() query terlalu restrictive.\n";
echo "Hanya menampilkan borrowing dengan status 'overdue',\n";
echo "tapi borrowing dengan unpaid fines tetap status 'borrowed'.\n\n";

echo "🎯 SOLUTION:\n";
echo "Update query untuk menampilkan overdue berdasarkan:\n";
echo "1. Due date < today\n";
echo "2. Has unpaid fines OR items not returned\n";
echo "3. Tidak peduli borrowing status saat ini\n\n";
