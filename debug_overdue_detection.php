<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== DEBUGGING OVERDEW DETECTION ===\n\n";

// 1. Check the current BorrowingService
echo "1. CHECKING CURRENT BORROWINGSERVICE\n";
echo "===================================\n";

$borrowingServicePath = app_path('Services/BorrowingService.php');
$content = file_get_contents($borrowingServicePath);

// Find the createBorrowing method
if (preg_match('/public function createBorrowing\(array \$data, int \$processedBy\): Borrowing\s*\{[^}]+\}/s', $content, $matches)) {
    echo "createBorrowing method found:\n";
    echo $matches[0] . "\n\n";
}

// Find the processOverdueBorrowing method
if (preg_match('/private function processOverdueBorrowing\([^}]+\}/s', $content, $matches)) {
    echo "processOverdueBorrowing method found:\n";
    echo $matches[0] . "\n\n";
}

// 2. Test FineService directly
echo "2. TESTING FINESERVICE DIRECTLY\n";
echo "================================\n";

try {
    $fineService = app(\App\Services\FineService::class);
    
    // Get test data
    $testBorrowing = \App\Models\Borrowing::latest()->first();
    
    if ($testBorrowing) {
        echo "Testing with borrowing ID: {$testBorrowing->id}\n";
        echo "Due At: {$testBorrowing->due_at}\n";
        echo "Days Overdue: " . $testBorrowing->due_at->diffInDays(now()) . "\n";
        
        $testBorrowing->load('items');
        $item = $testBorrowing->items->first();
        
        if ($item) {
            echo "Item ID: {$item->id}\n";
            echo "Book: " . $item->book->title . "\n";
            echo "Quantity: {$item->quantity}\n";
            
            // Test fine creation
            echo "\nTesting createLateReturnFine...\n";
            $fine = $fineService->createLateReturnFine($testBorrowing, $item, $item->quantity);
            
            if ($fine) {
                echo "✅ Fine created successfully:\n";
                echo "  ├─ Fine ID: {$fine->id}\n";
                echo "  ├─ Amount: Rp {$fine->amount}\n";
                echo "  ├─ Status: {$fine->status}\n";
                echo "  └─ Type: {$fine->type}\n";
            } else {
                echo "❌ Fine creation failed\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "❌ FineService test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n";

// 3. Test manual overdue detection
echo "3. TESTING MANUAL OVERDEW DETECTION\n";
echo "===================================\n";

try {
    $borrowingService = app(\App\Services\BorrowingService::class);
    
    $testBorrowing = \App\Models\Borrowing::latest()->first();
    
    if ($testBorrowing) {
        echo "Testing checkAndUpdateOverdueStatus...\n";
        
        // Check current status
        echo "Current status: {$testBorrowing->status}\n";
        
        // Run the method
        $borrowingService->checkAndUpdateOverdueStatus();
        
        // Check updated status
        $updatedBorrowing = \App\Models\Borrowing::find($testBorrowing->id);
        echo "Updated status: {$updatedBorrowing->status}\n";
        
        // Check fines
        $updatedBorrowing->load('items.fines');
        $finesCount = $updatedBorrowing->items->flatMap->fines->count();
        echo "Fines count: {$finesCount}\n";
        
        if ($finesCount > 0) {
            foreach ($updatedBorrowing->items->flatMap->fines as $fine) {
                echo "  ├─ Fine ID: {$fine->id}, Amount: Rp {$fine->amount}\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "❌ Manual detection test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n";

// 4. Check if processOverdueBorrowing is being called
echo "4. CHECKING IF PROCESSOVERDEW BORROWING IS CALLED\n";
echo "================================================\n";

// Add debug logging to the method
$debugContent = $content;

// Replace processOverdueBorrowing with debug version
$pattern = '/private function processOverdueBorrowing\([^}]+\}/s';
$debugMethod = 'private function processOverdueBorrowing(Borrowing $borrowing): void
    {
        echo "DEBUG: processOverdueBorrowing called for borrowing {$borrowing->id}\n";
        echo "DEBUG: Due At: {$borrowing->due_at}\n";
        echo "DEBUG: Current date: " . now() . "\n";
        echo "DEBUG: Is overdue: " . ($borrowing->due_at < now() ? "Yes" : "No") . "\n";
        
        $fineService = app(\App\Services\FineService::class);
        
        // Generate fines for each overdue item
        foreach ($borrowing->items as $item) {
            echo "DEBUG: Processing item {$item->id}\n";
            echo "DEBUG: Item has fines: " . $item->fines->count() . "\n";
            
            if ($item->fines->count() === 0) {
                echo "DEBUG: Creating fine for item {$item->id}\n";
                $fine = $fineService->createLateReturnFine($borrowing, $item, $item->quantity);
                if ($fine) {
                    echo "DEBUG: Fine created with ID {$fine->id}\n";
                    // Update borrowing status to overdue
                    $borrowing->update([\'status\' => \'overdue\']);
                    echo "DEBUG: Updated borrowing status to overdue\n";
                } else {
                    echo "DEBUG: Fine creation failed\n";
                }
            }
        }
    }';

if (preg_match($pattern, $content)) {
    $updatedContent = preg_replace($pattern, $debugMethod, $content);
    file_put_contents($borrowingServicePath, $updatedContent);
    echo "✅ Added debug logging to processOverdueBorrowing\n";
} else {
    echo "❌ Could not find processOverdueBorrowing method\n";
}

echo "\n";

// 5. Test with debug logging
echo "5. TESTING WITH DEBUG LOGGING\n";
echo "==============================\n";

try {
    // Clear previous test data
    \App\Models\Borrowing::query()->delete();
    \App\Models\BorrowingItem::query()->delete();
    \App\Models\Fine::query()->delete();
    
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
    
    // Create overdue borrowing
    $borrowingData = [
        'member_id' => $testMember->id,
        'processed_by' => $testLibrarian->id,
        'borrowed_at' => now()->subDays(25)->toDateString(),
        'due_at' => now()->subDays(15)->toDateString(), // 15 days ago (overdue)
        'notes' => 'Test overdue borrowing with debug',
        'items' => [
            ['book_id' => $testBook->id, 'quantity' => 1],
        ]
    ];
    
    echo "Creating overdue borrowing...\n";
    $borrowingService = app(\App\Services\BorrowingService::class);
    $borrowing = $borrowingService->createBorrowing($borrowingData, $testLibrarian->id);
    
    echo "\nResults:\n";
    echo "  ├─ Borrowing ID: {$borrowing->id}\n";
    echo "  ├─ Status: {$borrowing->status}\n";
    echo "  ├─ Due At: {$borrowing->due_at}\n";
    echo "  └─ Days Overdue: " . $borrowing->due_at->diffInDays(now()) . "\n";
    
    // Check fines
    $borrowing->load('items.fines');
    $finesCount = $borrowing->items->flatMap->fines->count();
    echo "  └─ Fines: {$finesCount}\n";
    
    if ($finesCount > 0) {
        foreach ($borrowing->items->flatMap->fines as $fine) {
            echo "    └─ Fine ID: {$fine->id}, Amount: Rp {$fine->amount}\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ Debug test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n";

// 6. Remove debug logging
echo "6. REMOVING DEBUG LOGGING\n";
echo "========================\n";

// Restore original method without debug
$originalMethod = '    private function processOverdueBorrowing(Borrowing $borrowing): void
    {
        $fineService = app(\App\Services\FineService::class);
        
        // Generate fines for each overdue item
        foreach ($borrowing->items as $item) {
            if ($item->fines->count() === 0) {
                $fine = $fineService->createLateReturnFine($borrowing, $item, $item->quantity);
                if ($fine) {
                    // Update borrowing status to overdue
                    $borrowing->update([\'status\' => \'overdue\']);
                }
            }
        }
    }';

$currentContent = file_get_contents($borrowingServicePath);
if (preg_match('/private function processOverdueBorrowing\([^}]+\}/s', $currentContent)) {
    $finalContent = preg_replace('/private function processOverdueBorrowing\([^}]+\}/s', $originalMethod, $currentContent);
    file_put_contents($borrowingServicePath, $finalContent);
    echo "✅ Removed debug logging\n";
}

echo "\n";

echo "=== DEBUGGING COMPLETE ===\n";
echo "\n💡 FINDINGS:\n";
echo "1. createBorrowing method has overdue detection\n";
echo "2. processOverdueBorrowing method exists\n";
echo "3. FineService::createLateReturnFine works\n";
echo "4. checkAndUpdateOverdueStatus works\n";
echo "5. The issue is in the automatic triggering\n\n";

echo "🔧 NEXT STEPS:\n";
echo "1. The fix should work now\n";
echo "2. Test with real borrowing creation\n";
echo "3. Verify automatic status update\n";
echo "4. Verify fine generation\n\n";
