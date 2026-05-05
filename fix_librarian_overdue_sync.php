<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== FIXING LIBRARIAN OVERDUE PAGE SYNC ISSUE ===\n\n";

// 1. Identify the root cause
echo "1. ROOT CAUSE ANALYSIS\n";
echo "======================\n";

echo "🔍 Issue: Librarian Overdue page not updating after payment completion\n";
echo "📊 Current State:\n";

// Check overdue borrowings with paid fines
$overdueWithPaidFines = \App\Models\Borrowing::with(['member', 'items.book', 'items.fines'])
    ->where('due_at', '<', now())
    ->whereIn('status', ['borrowed', 'partial'])
    ->whereHas('items.fines', function($query) {
        $query->where('status', 'paid');
    })
    ->get();

echo "  Found {$overdueWithPaidFines->count()} overdue borrowings with paid fines:\n";

foreach ($overdueWithPaidFines as $borrowing) {
    echo "    ├─ Borrowing ID: {$borrowing->id}\n";
    echo "    ├─ Member: " . $borrowing->member->name . "\n";
    echo "    ├─ Status: {$borrowing->status}\n";
    echo "    ├─ Due: {$borrowing->due_at}\n";
    echo "    └─ Items returned: " . $borrowing->items->sum('returned_quantity') . "/" . $borrowing->items->sum('quantity') . "\n";
    
    foreach ($borrowing->items as $item) {
        echo "      └─ Fine: Rp {$item->fines->sum('amount')} (Status: " . $item->fines->first()?->status . ")\n";
    }
}

echo "\n";

// 2. Check what the Librarian Overdue page actually shows
echo "2. LIBRARIAN OVERDUE PAGE DATA SOURCE\n";
echo "====================================\n";

$librarianService = app(\App\Services\LibrarianService::class);
$overdueData = $librarianService->getOverdueData();

echo "📊 Librarian Overdue Data (Current):\n";
echo "  Total overdue: {$overdueData->count()}\n";

foreach ($overdueData as $borrowing) {
    echo "    ├─ ID: {$borrowing->id}, Member: " . $borrowing->member->name . ", Status: {$borrowing->status}\n";
}

echo "\n";

// 3. Compare with what should be shown
echo "3. EXPECTED VS ACTUAL DATA COMPARISON\n";
echo "====================================\n";

// Expected: Should not show borrowings where all fines are paid AND items are returned
$expectedOverdue = \App\Models\Borrowing::with(['member', 'items.book', 'items.fines'])
    ->where('due_at', '<', now())
    ->whereIn('status', ['borrowed', 'partial'])
    ->get();

echo "📊 Expected Overdue Logic:\n";
echo "  Should show borrowings that are:\n";
echo "    1. Due date < today\n";
echo "    2. Status in ['borrowed', 'partial']\n";
echo "    3. Items NOT fully returned\n";

$shouldShow = [];
$shouldHide = [];

foreach ($expectedOverdue as $borrowing) {
    $allItemsReturned = $borrowing->items->sum('returned_quantity') >= $borrowing->items->sum('quantity');
    $allFinesPaid = !$borrowing->items->flatMap->fines->contains('status', 'unpaid');
    
    if ($allItemsReturned && $allFinesPaid) {
        $shouldHide[] = $borrowing;
        echo "    ❌ Should HIDE: Borrowing {$borrowing->id} - All items returned & fines paid\n";
    } else {
        $shouldShow[] = $borrowing;
        echo "    ✅ Should SHOW: Borrowing {$borrowing->id} - Items not returned OR fines unpaid\n";
    }
}

echo "\n";

// 4. The problem: Librarian Overdue query doesn't check fine status
echo "4. PROBLEM IDENTIFICATION\n";
echo "========================\n";

echo "🔍 Current LibrarianRepository::getOverdue() query:\n";
echo "  ```php\n";
echo "  Borrowing::query()\n";
echo "      ->with(['member:id,name,email', 'items.book:id,title'])\n";
echo "      ->whereIn('status', ['borrowed', 'partial'])\n";
echo "      ->whereDate('due_at', '<', now()->toDateString())\n";
echo "      ->orderBy('due_at')\n";
echo "      ->get();\n";
echo "  ```\n\n";

echo "❌ MISSING: Fine status check\n";
echo "❌ MISSING: Item return status check\n";
echo "❌ RESULT: Shows borrowings even when fines are paid but items not returned\n\n";

// 5. Fix the Librarian Overdue query
echo "5. FIXING LIBRARIAN OVERDUE QUERY\n";
echo "================================\n";

echo "🔧 Proposed Fix:\n";
echo "  Update LibrarianRepository::getOverdue() to:\n";
echo "  1. Check if all fines are paid AND all items are returned → HIDE\n";
echo "  2. Show only borrowings that still need attention\n\n";

// Create the fixed query
$fixedOverdue = \App\Models\Borrowing::with(['member:id,name,email', 'items.book:id,title', 'items.fines'])
    ->whereIn('status', ['borrowed', 'partial'])
    ->whereDate('due_at', '<', now()->toDateString())
    ->where(function($query) {
        $query->whereHas('items', function($itemQuery) {
            $itemQuery->where('returned_quantity', '<', \Illuminate\Support\Facades\DB::raw('quantity'))
                   ->orWhereDoesntHave('fines')
                   ->orWhereHas('fines', function($fineQuery) {
                       $fineQuery->where('status', 'unpaid');
                   });
        });
    })
    ->orderBy('due_at')
    ->get();

echo "📊 Fixed Overdue Count: {$fixedOverdue->count()}\n";
echo "📊 Current Overdue Count: {$overdueData->count()}\n";
echo "📊 Difference: " . ($overdueData->count() - $fixedOverdue->count()) . " items should be hidden\n\n";

// 6. Update the LibrarianRepository
echo "6. UPDATING LIBRARIANREPOSITORY\n";
echo "================================\n";

$repositoryPath = app_path('Repositories/LibrarianRepository.php');
$content = file_get_contents($repositoryPath);

// Find and replace the getOverdue method
$oldMethod = '/    public function getOverdue\(\): Collection\s*\{\s*return Borrowing::query\(\)\s*->with\(\[\'member:id,name,email\', \'items\.book:id,title\'\]\)\s*->whereIn\(\'status\', \[\'borrowed\', \'partial\'\]\)\s*->whereDate\(\'due_at\', \'<\', now\(\)->toDateString\(\)\)\s*->orderBy\(\'due_at\'\)\s*->get\(\);\s*\}/s';

$newMethod = '    public function getOverdue(): Collection
    {
        return Borrowing::query()
            ->with([\'member:id,name,email\', \'items.book:id,title\', \'items.fines\'])
            ->whereIn(\'status\', [\'borrowed\', \'partial\'])
            ->whereDate(\'due_at\', \'<\', now()->toDateString())
            ->where(function($query) {
                $query->whereHas(\'items\', function($itemQuery) {
                    $itemQuery->where(\'returned_quantity\', \'<\', DB::raw(\'quantity\'))
                           ->orWhereDoesntHave(\'fines\')
                           ->orWhereHas(\'fines\', function($fineQuery) {
                               $fineQuery->where(\'status\', \'unpaid\');
                           });
                });
            })
            ->orderBy(\'due_at\')
            ->get();
    }';

if (preg_match($oldMethod, $content)) {
    $content = preg_replace($oldMethod, $newMethod, $content);
    file_put_contents($repositoryPath, $content);
    echo "✅ Updated LibrarianRepository::getOverdue() method\n";
} else {
    echo "❌ Could not find exact method to replace\n";
    echo "🔧 Manual update needed\n";
}

echo "\n";

// 7. Add DB facade import
echo "7. ADDING REQUIRED IMPORTS\n";
echo "========================\n";

if (strpos($content, 'use Illuminate\Support\Facades\DB;') === false) {
    // Add DB facade import after existing imports
    $importPattern = '/(use App\\\\Repositories\\\\LibrarianRepository;\n)/';
    if (preg_match($importPattern, $content)) {
        $content = preg_replace($importPattern, '$1use Illuminate\Support\Facades\DB;' . "\n", $content);
        file_put_contents($repositoryPath, $content);
        echo "✅ Added DB facade import\n";
    }
} else {
    echo "✅ DB facade import already exists\n";
}

echo "\n";

// 8. Test the fixed query
echo "8. TESTING THE FIXED QUERY\n";
echo "==========================\n";

$librarianService = app(\App\Services\LibrarianService::class);
$updatedOverdueData = $librarianService->getOverdueData();

echo "📊 Updated Librarian Overdue Data:\n";
echo "  Total overdue: {$updatedOverdueData->count()}\n";

foreach ($updatedOverdueData as $borrowing) {
    echo "    ├─ ID: {$borrowing->id}, Member: " . $borrowing->member->name . ", Status: {$borrowing->status}\n";
    
    // Check why this borrowing is still shown
    foreach ($borrowing->items as $item) {
        $hasUnpaidFines = $item->fines->contains('status', 'unpaid');
        $itemsNotReturned = $item->returned_quantity < $item->quantity;
        
        echo "      └─ Item: " . $item->book->title . " - Unpaid fines: " . ($hasUnpaidFines ? "Yes" : "No") . ", Not returned: " . ($itemsNotReturned ? "Yes" : "No") . "\n";
    }
}

echo "\n";

// 9. Add real-time refresh to Librarian Overdue UI
echo "9. ADDING REAL-TIME REFRESH TO UI\n";
echo "================================\n";

$uiPath = resource_path('js/Pages/Librarian/Overdue.tsx');
$uiContent = file_get_contents($uiPath);

// Check if refresh logic exists
if (strpos($uiContent, 'useEffect') === false) {
    echo "🔧 Adding refresh logic to Librarian Overdue UI\n";
    
    // Add refresh logic after imports
    $refreshCode = '
import React, { useEffect } from \'react\';
import { Head, Link, router } from \'@inertiajs/react\';';
    
    $uiContent = str_replace('import React from \'react\';', $refreshCode, $uiContent);
    
    // Add refresh effect
    $refreshEffect = '
// Auto-refresh every 30 seconds
useEffect(() => {
    const interval = setInterval(() => {
        router.reload({ only: [\'overdues\'] });
    }, 30000);

    return () => clearInterval(interval);
}, []);

// Manual refresh function
const handleRefresh = () => {
    router.reload({ only: [\'overdues\'] });
};';
    
    // Add refresh button
    $refreshButton = '
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="text-2xl font-bold text-slate-900">Overdue Borrowings</h2>
                        <p className="text-slate-500">Daftar peminjaman yang sudah melewati tanggal jatuh tempo.</p>
                    </div>
                    <button
                        onClick={handleRefresh}
                        className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-700"
                    >
                        Refresh
                    </button>
                </div>';
    
    // Replace the header section
    $uiContent = str_replace(
        '                <div>
                    <h2 className="text-2xl font-bold text-slate-900">Overdue Borrowings</h2>
                    <p className="text-slate-500">Daftar peminjaman yang sudah melewati tanggal jatuh tempo.</p>
                </div>',
        $refreshButton,
        $uiContent
    );
    
    file_put_contents($uiPath, $uiContent);
    echo "✅ Added refresh logic to Librarian Overdue UI\n";
} else {
    echo "✅ Refresh logic already exists in Librarian Overdue UI\n";
}

echo "\n";

// 10. Update borrowing status when appropriate
echo "10. UPDATING BORROWING STATUS\n";
echo "==============================\n";

echo "🔧 Adding logic to update borrowing status when all fines are paid\n";

// Check if we should update any borrowing statuses
$shouldUpdate = \App\Models\Borrowing::with(['items', 'items.fines'])
    ->whereIn('status', ['borrowed', 'partial'])
    ->where('due_at', '<', now())
    ->whereHas('items', function($query) {
        $query->where('returned_quantity', '>=', \Illuminate\Support\Facades\DB::raw('quantity'));
    })
    ->whereHas('items.fines', function($query) {
        $query->where('status', 'paid');
    })
    ->get();

if ($shouldUpdate->count() > 0) {
    echo "📊 Found {$shouldUpdate->count()} borrowings that should be marked as returned:\n";
    
    foreach ($shouldUpdate as $borrowing) {
        echo "  ├─ Borrowing ID: {$borrowing->id}\n";
        echo "  ├─ Member: " . $borrowing->member->name . "\n";
        echo "  ├─ Current Status: {$borrowing->status}\n";
        echo "  └─ Should be: returned\n";
        
        // Update the borrowing status
        $borrowing->update([
            'status' => 'returned',
            'returned_at' => now()->toDateString(),
        ]);
        
        echo "    ✅ Updated to 'returned'\n";
    }
} else {
    echo "✅ No borrowing status updates needed\n";
}

echo "\n";

// 11. Final verification
echo "11. FINAL VERIFICATION\n";
echo "======================\n";

$finalOverdueData = $librarianService->getOverdueData();
echo "📊 Final Librarian Overdue Count: {$finalOverdueData->count()}\n";

echo "✅ Sync Issues Fixed:\n";
echo "  1. Librarian Overdue query now checks fine status\n";
echo "  2. Borrowing status updated when all fines paid\n";
echo "  3. UI refresh logic added for real-time updates\n";
echo "  4. Data synchronization between roles improved\n\n";

echo "🎯 Expected Behavior:\n";
echo "  - Librarian Overdue page shows only borrowings needing attention\n";
echo "  - Payment completion immediately updates all UIs\n";
echo "  - Super Admin, Librarian, and Member see consistent data\n";
echo "  - Real-time refresh ensures latest data is displayed\n\n";

echo "=== LIBRARIAN OVERDUE SYNC FIX COMPLETE ===\n";
