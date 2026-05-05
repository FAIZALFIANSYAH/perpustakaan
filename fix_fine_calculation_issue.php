<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== FIXING FINE CALCULATION ISSUE ===\n\n";

// The issue is that diffInDays returns fractional days due to time differences
// We need to use startOfDay() to get whole days only

echo "1. IDENTIFYING THE ISSUE\n";
echo "========================\n";

$now = now();
$dueDate = now()->subDays(8);

echo "Current time: {$now}\n";
echo "Due date: {$dueDate}\n";
echo "diffInDays result: {$dueDate->diffInDays($now)}\n";
echo "diffInDays with startOfDay: " . $dueDate->startOfDay()->diffInDays($now->startOfDay()) . "\n\n";

// 2. Fix the FineService calculation
echo "2. FIXING FineService::calculateLateFine\n";
echo "======================================\n";

$fineServicePath = app_path('Services/FineService.php');
$content = file_get_contents($fineServicePath);

// Find and replace the problematic lines
$oldPattern = '/\$daysLate = \$dueDate->diffInDays\(\$returnDate, false\);/';
$newReplacement = '$daysLate = $dueDate->startOfDay()->diffInDays($returnDate->startOfDay(), false);';

if (preg_match($oldPattern, $content)) {
    $content = preg_replace($oldPattern, $newReplacement, $content);
    file_put_contents($fineServicePath, $content);
    echo "✅ Fixed diffInDays to use startOfDay()\n";
} else {
    echo "❌ Could not find the pattern to fix\n";
}

// 3. Test the fix
echo "\n3. TESTING THE FIX\n";
echo "==================\n";

$fineService = app(\App\Services\FineService::class);
$config = $fineService->getFineConfig();

// Test scenarios
$scenarios = [
    ['days_late' => 8, 'quantity' => 1, 'expected' => 6000],
    ['days_late' => 20, 'quantity' => 1, 'expected' => 10000],
    ['days_late' => 15, 'quantity' => 2, 'expected' => 20000],
    ['days_late' => 3, 'quantity' => 1, 'expected' => 0],
];

foreach ($scenarios as $scenario) {
    // Create mock borrowing
    $borrowing = new \App\Models\Borrowing();
    $borrowing->due_at = now()->subDays($scenario['days_late'])->startOfDay();
    $borrowing->member_id = 1;
    
    // Create mock borrowing item
    $item = new \App\Models\BorrowingItem();
    $item->quantity = $scenario['quantity'];
    
    // Calculate fine
    $fineAmount = $fineService->calculateLateFine($borrowing, $item, $scenario['quantity']);
    
    echo "Scenario: {$scenario['days_late']} days late, {$scenario['quantity']} book(s)\n";
    echo "  Expected: Rp {$scenario['expected']}\n";
    echo "  Calculated: Rp {$fineAmount}\n";
    echo "  Match: " . (abs($fineAmount - $scenario['expected']) < 0.01 ? "✅" : "❌") . "\n\n";
}

// 4. Update existing fines
echo "4. UPDATING EXISTING FINES\n";
echo "==========================\n";

$existingFines = \App\Models\Fine::where('type', 'late_return')->get();

foreach ($existingFines as $fine) {
    if ($fine->borrowingItem && $fine->borrowingItem->borrowing) {
        $borrowing = $fine->borrowingItem->borrowing;
        $item = $fine->borrowingItem;
        
        // Recalculate fine with new logic
        $newAmount = $fineService->calculateLateFine($borrowing, $item, $item->quantity);
        
        echo "Fine ID: {$fine->id}\n";
        echo "  Member: " . $fine->member->name . "\n";
        echo "  Book: " . $item->book->title . "\n";
        echo "  Old Amount: Rp {$fine->amount}\n";
        echo "  New Amount: Rp {$newAmount}\n";
        
        if ($fine->amount != $newAmount && $fine->status === 'unpaid') {
            $fine->update(['amount' => $newAmount]);
            echo "  Status: Updated ✅\n";
        } else {
            echo "  Status: No update needed\n";
        }
        echo "\n";
    }
}

echo "=== FIX COMPLETE ===\n";
