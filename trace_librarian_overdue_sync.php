<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== TRACING LIBRARIAN OVERDUE PAGE SYNC ISSUE ===\n\n";

// 1. Check Librarian Overdue page data source
echo "1. LIBRARIAN OVERDUE PAGE DATA SOURCE\n";
echo "=====================================\n";

// Find the Librarian Overdue controller and route
echo "🔍 Finding Librarian Overdue route:\n";
$routes = \Illuminate\Support\Facades\Route::getRoutes();
$overdueRoute = null;

foreach ($routes as $route) {
    if (strpos($route->uri(), 'overdue') !== false && strpos($route->getActionName(), 'Librarian') !== false) {
        $overdueRoute = $route;
        echo "  Found: " . $route->methods()[0] . " " . $route->uri() . " → " . $route->getActionName() . "\n";
        break;
    }
}

if (!$overdueRoute) {
    echo "  ❌ No Librarian Overdue route found\n";
} else {
    echo "  ✅ Librarian Overdue route found\n";
}

echo "\n";

// 2. Check LibrarianController overdue methods
echo "2. LIBRARIANCONTROLLER OVERDUE METHODS\n";
echo "======================================\n";

$librarianControllerPath = app_path('Http/Controllers/LibrarianController.php');
if (file_exists($librarianControllerPath)) {
    $content = file_get_contents($librarianControllerPath);
    
    // Find overdue-related methods
    if (preg_match_all('/public function (\w+overdue\w*)\((.*?)\)/s', $content, $matches)) {
        echo "Found overdue methods:\n";
        foreach ($matches[1] as $index => $methodName) {
            echo "  - {$methodName}()\n";
        }
    } else {
        echo "  ❌ No overdue methods found\n";
    }
    
    // Look for overdue data logic
    if (strpos($content, 'overdue') !== false) {
        echo "  ✅ Overdue logic found in controller\n";
        
        // Extract overdue data queries
        $lines = explode("\n", $content);
        foreach ($lines as $lineNumber => $line) {
            if (strpos($line, 'overdue') !== false && strpos($line, 'Borrowing') !== false) {
                echo "    Line " . ($lineNumber + 1) . ": " . trim($line) . "\n";
            }
        }
    }
} else {
    echo "  ❌ LibrarianController not found\n";
}

echo "\n";

// 3. Check current overdue data
echo "3. CURRENT OVERDUE DATA STATE\n";
echo "============================\n";

// Get overdue borrowings
$overdueBorrowings = \App\Models\Borrowing::with(['items.book', 'member', 'items.fines'])
    ->where('due_at', '<', now())
    ->whereNotIn('status', ['complete', 'cancelled', 'returned'])
    ->get();

echo "📊 Overdue Borrowings: {$overdueBorrowings->count()}\n\n";

foreach ($overdueBorrowings as $borrowing) {
    echo "Borrowing ID: {$borrowing->id}\n";
    echo "  ├─ Member: " . $borrowing->member->name . " (ID: " . $borrowing->member_id . ")\n";
    echo "  ├─ Due Date: {$borrowing->due_at}\n";
    echo "  ├─ Status: {$borrowing->status}\n";
    echo "  ├─ Days Overdue: " . $borrowing->due_at->diffInDays(now()) . "\n";
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
    echo "\n";
}

// 4. Simulate payment completion and check what should update
echo "4. PAYMENT COMPLETION IMPACT ANALYSIS\n";
echo "====================================\n";

// Find a borrowing with paid fines
$paidFinesBorrowing = \App\Models\Borrowing::with(['items.book', 'member', 'items.fines'])
    ->whereHas('items.fines', function($query) {
        $query->where('status', 'paid');
    })
    ->where('due_at', '<', now())
    ->first();

if ($paidFinesBorrowing) {
    echo "🧪 Testing with Borrowing ID: {$paidFinesBorrowing->id}\n";
    echo "  ├─ Member: " . $paidFinesBorrowing->member->name . "\n";
    echo "  ├─ Due Date: {$paidFinesBorrowing->due_at}\n";
    echo "  ├─ Status: {$paidFinesBorrowing->status}\n";
    echo "  ├─ Days Overdue: " . $paidFinesBorrowing->due_at->diffInDays(now()) . "\n";
    
    // Check if this borrowing should still appear in overdue list
    $hasUnpaidFines = false;
    $allItemsReturned = true;
    
    foreach ($paidFinesBorrowing->items as $item) {
        $hasUnpaidFines = $item->fines->contains('status', 'unpaid') || $hasUnpaidFines;
        if ($item->returned_quantity < $item->quantity) {
            $allItemsReturned = false;
        }
    }
    
    echo "  ├─ Has Unpaid Fines: " . ($hasUnpaidFines ? "❌" : "✅") . "\n";
    echo "  ├─ All Items Returned: " . ($allItemsReturned ? "✅" : "❌") . "\n";
    
    // Determine if should appear in overdue
    $shouldAppearInOverdue = !$hasUnpaidFines && !$allItemsReturned;
    echo "  └─ Should Appear in Overdue: " . ($shouldAppearInOverdue ? "✅" : "❌") . "\n";
    
} else {
    echo "🧪 No overdue borrowing with paid fines found\n";
}

echo "\n";

// 5. Check Librarian Overdue UI component
echo "5. LIBRARIAN OVERDUE UI COMPONENT\n";
echo "=================================\n";

$overdueUIPath = resource_path('js/Pages/Librarian/Overdue/Index.tsx');
if (file_exists($overdueUIPath)) {
    echo "✅ Found Librarian Overdue UI: " . basename($overdueUIPath) . "\n";
    
    $content = file_get_contents($overdueUIPath);
    
    // Check data fetching logic
    if (strpos($content, 'useEffect') !== false) {
        echo "  ✅ Uses useEffect for data fetching\n";
    }
    
    // Check if it has refresh/reload logic
    if (strpos($content, 'reload') !== false || strpos($content, 'refresh') !== false) {
        echo "  ✅ Has refresh/reload logic\n";
    } else {
        echo "  ❌ No refresh/reload logic found\n";
    }
    
    // Check if it polls for updates
    if (strpos($content, 'setInterval') !== false) {
        echo "  ✅ Has polling logic\n";
    } else {
        echo "  ❌ No polling logic found\n";
    }
    
} else {
    echo "❌ Librarian Overdue UI not found\n";
    
    // Look for alternative locations
    $possiblePaths = [
        resource_path('js/Pages/Librarian/Overdue.tsx'),
        resource_path('js/Pages/Librarian/Dashboard.tsx'),
        resource_path('js/Pages/Librarian/Index.tsx'),
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            echo "  📁 Found alternative: " . basename($path) . "\n";
        }
    }
}

echo "\n";

// 6. Test what data the Librarian Overdue page should show
echo "6. LIBRARIAN OVERDUE DATA REQUIREMENTS\n";
echo "======================================\n";

echo "📋 Expected Overdue Data Criteria:\n";
echo "  1. Due date < current date\n";
echo "  2. Status NOT in (complete, cancelled, returned)\n";
echo "  3. Should show even if fines are paid (if items not returned)\n";
echo "  4. Should show fine status and payment information\n";
echo "  5. Should update when payment status changes\n\n";

// Test current data against expected criteria
$expectedOverdue = \App\Models\Borrowing::with(['items.book', 'member', 'items.fines'])
    ->where('due_at', '<', now())
    ->whereNotIn('status', ['complete', 'cancelled', 'returned'])
    ->get();

echo "📊 Expected Overdue Count: {$expectedOverdue->count()}\n";
echo "📊 Actual Overdue Count: {$overdueBorrowings->count()}\n";
echo "📊 Match: " . ($expectedOverdue->count() === $overdueBorrowings->count() ? "✅" : "❌") . "\n\n";

// 7. Identify the sync issue
echo "7. SYNC ISSUE IDENTIFICATION\n";
echo "===========================\n";

echo "🔍 Potential Sync Issues:\n";

// Check if paid fines are properly handled
$paidOverdueBorrowings = \App\Models\Borrowing::whereHas('items.fines', function($query) {
        $query->where('status', 'paid');
    })
    ->where('due_at', '<', now())
    ->whereNotIn('status', ['complete', 'cancelled', 'returned'])
    ->get();

if ($paidOverdueBorrowings->count() > 0) {
    echo "  ⚠️  Found {$paidOverdueBorrowings->count()} overdue borrowings with paid fines\n";
    echo "      These might not be updating correctly in Librarian UI\n";
    
    foreach ($paidOverdueBorrowings as $borrowing) {
        echo "      └─ Borrowing ID: {$borrowing->id}, Member: " . $borrowing->member->name . "\n";
    }
} else {
    echo "  ✅ No overdue borrowings with paid fines found\n";
}

// Check borrowing status updates
$shouldBeReturned = \App\Models\Borrowing::whereHas('items.fines', function($query) {
        $query->where('status', 'paid');
    })
    ->where('due_at', '<', now())
    ->whereNotIn('status', ['complete', 'cancelled', 'returned'])
    ->get();

if ($shouldBeReturned->count() > 0) {
    echo "  ⚠️  Found {$shouldBeReturned->count()} borrowings that should be marked as returned\n";
    echo "      These might need status updates after payment\n";
} else {
    echo "  ✅ All borrowings have correct status\n";
}

echo "\n";

// 8. Proposed solution
echo "8. PROPOSED SOLUTION\n";
echo "===================\n";

echo "🔧 Recommended Fixes:\n";
echo "  1. Add real-time data refresh to Librarian Overdue UI\n";
echo "  2. Update borrowing status when all fines are paid\n";
echo "  3. Add WebSocket or polling for instant updates\n";
echo "  4. Implement event-driven UI updates after payment\n";
echo "  5. Add manual refresh button to Librarian Overdue page\n\n";

echo "📋 Implementation Plan:\n";
echo "  1. Check Librarian Overdue UI data source\n";
echo "  2. Add refresh logic to UI component\n";
echo "  3. Update borrowing service to handle status changes\n";
echo "  4. Test payment → overdue UI update flow\n";
echo "  5. Verify all role UIs sync correctly\n\n";

echo "=== LIBRARIAN OVERDUE SYNC ANALYSIS COMPLETE ===\n";
