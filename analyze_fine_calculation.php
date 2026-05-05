<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== ANALYZE FINE CALCULATION DISCREPANCY ===\n\n";

// 1. Get current fine configuration
echo "1. CURRENT FINE CONFIGURATION\n";
echo "==============================\n";

$fineService = app(\App\Services\FineService::class);
$config = $fineService->getFineConfig();

echo "Grace Period: {$config->grace_period_days} days\n";
echo "Fine Per Day: Rp {$config->fine_per_day}\n";
echo "Lost Book Fine: Rp {$config->lost_book_fine}\n";
echo "Max Fine Cap: " . ($config->max_fine_cap ? "Rp {$config->max_fine_cap}" : "No cap") . "\n\n";

// 2. Analyze overdue borrowings
echo "2. OVERDUE BORROWINGS ANALYSIS\n";
echo "==============================\n";

$overdueBorrowings = \App\Models\Borrowing::with(['items.book', 'member'])
    ->where('due_at', '<', now())
    ->whereNotIn('status', ['complete', 'cancelled', 'returned'])
    ->get();

foreach ($overdueBorrowings as $borrowing) {
    echo "Borrowing ID: {$borrowing->id}\n";
    echo "Member: {$borrowing->member->name} (ID: {$borrowing->member->id})\n";
    echo "Due Date: {$borrowing->due_at}\n";
    echo "Current Date: " . now()->toDateString() . "\n";
    
    $totalDaysLate = $borrowing->due_at->diffInDays(now());
    echo "Total Days Late: {$totalDaysLate}\n";
    
    // Calculate billable days after grace period
    $gracePeriod = $config->grace_period_days;
    $billableDays = max(0, $totalDaysLate - $gracePeriod);
    echo "Grace Period: {$gracePeriod} days\n";
    echo "Billable Days: {$billableDays}\n";
    
    // Expected fine calculation
    $expectedFine = $billableDays * (float) $config->fine_per_day;
    echo "Expected Fine: Rp {$expectedFine}\n";
    
    // Check current fines
    $existingFines = \App\Models\Fine::whereHas('borrowingItem', function($query) use ($borrowing) {
        $query->where('borrowing_id', $borrowing->id);
    })->get();
    
    echo "Existing Fines: {$existingFines->count()}\n";
    foreach ($existingFines as $fine) {
        echo "  - Fine ID: {$fine->id}, Amount: Rp {$fine->amount}, Status: {$fine->status}\n";
    }
    
    echo "\n";
}

// 3. Test fine calculation logic step by step
echo "3. FINE CALCULATION LOGIC TEST\n";
echo "===============================\n";

// Test with the first overdue borrowing
if ($overdueBorrowings->count() > 0) {
    $testBorrowing = $overdueBorrowings->first();
    $testItem = $testBorrowing->items->first();
    
    echo "Testing with Borrowing ID: {$testBorrowing->id}\n";
    echo "Member: {$testBorrowing->member->name}\n";
    echo "Book: {$testItem->book->title}\n\n";
    
    // Step 1: Calculate days late
    $dueDate = $testBorrowing->due_at;
    $returnDate = now();
    $daysLate = $dueDate->diffInDays($returnDate, false);
    
    echo "Step 1 - Calculate Days Late:\n";
    echo "  Due Date: {$dueDate}\n";
    echo "  Return Date: {$returnDate}\n";
    echo "  Days Late: {$daysLate}\n";
    echo "  (Using diffInDays with false parameter)\n\n";
    
    // Step 2: Apply grace period
    $gracePeriodDays = $config->grace_period_days;
    $billableDays = max(0, $daysLate - $gracePeriodDays);
    
    echo "Step 2 - Apply Grace Period:\n";
    echo "  Days Late: {$daysLate}\n";
    echo "  Grace Period: {$gracePeriodDays}\n";
    echo "  Billable Days: {$billableDays}\n";
    echo "  Formula: max(0, {$daysLate} - {$gracePeriodDays}) = {$billableDays}\n\n";
    
    // Step 3: Calculate fine amount
    $finePerDay = (float) $config->fine_per_day;
    $quantity = $testItem->quantity;
    $fineAmount = $billableDays * $finePerDay * $quantity;
    
    echo "Step 3 - Calculate Fine Amount:\n";
    echo "  Billable Days: {$billableDays}\n";
    echo "  Fine Per Day: Rp {$finePerDay}\n";
    echo "  Quantity: {$quantity}\n";
    echo "  Fine Amount: {$billableDays} × {$finePerDay} × {$quantity} = Rp {$fineAmount}\n\n";
    
    // Step 4: Check max cap
    if ($config->max_fine_cap && $fineAmount > $config->max_fine_cap) {
        $cappedAmount = (float) $config->max_fine_cap;
        echo "Step 4 - Apply Max Cap:\n";
        echo "  Original Amount: Rp {$fineAmount}\n";
        echo "  Max Cap: Rp {$config->max_fine_cap}\n";
        echo "  Final Amount: Rp {$cappedAmount}\n\n";
    } else {
        echo "Step 4 - Max Cap: Not applied\n\n";
    }
    
    // Compare with actual fine service calculation
    $actualFine = $fineService->calculateLateFine($testBorrowing, $testItem, $quantity);
    
    echo "Comparison:\n";
    echo "  Manual Calculation: Rp {$fineAmount}\n";
    echo "  Service Calculation: Rp {$actualFine}\n";
    echo "  Match: " . ($fineAmount == $actualFine ? "YES" : "NO") . "\n\n";
    
    // Check if this should be capped at 5 days max
    $maxBillableDays = 5; // According to user explanation
    $cappedBillableDays = min($billableDays, $maxBillableDays);
    $expectedFineWithCap = $cappedBillableDays * $finePerDay * $quantity;
    
    echo "User's Expected Calculation (5-day cap):\n";
    echo "  Billable Days with Cap: min({$billableDays}, {$maxBillableDays}) = {$cappedBillableDays}\n";
    echo "  Expected Fine: {$cappedBillableDays} × {$finePerDay} × {$quantity} = Rp {$expectedFineWithCap}\n\n";
}

// 4. Check Member (ID 18) specific issue
echo "4. MEMBER (ID 18) SPECIFIC ANALYSIS\n";
echo "====================================\n";

$member18 = \App\Models\User::find(18);
if ($member18) {
    echo "Member Found: {$member18->name} (ID: {$member18->id})\n";
    
    // Check member's fines
    $memberFines = \App\Models\Fine::where('member_id', 18)->get();
    echo "Member's Fines: {$memberFines->count()}\n";
    
    foreach ($memberFines as $fine) {
        echo "  - Fine ID: {$fine->id}, Type: {$fine->type}, Amount: Rp {$fine->amount}, Status: {$fine->status}\n";
        echo "    Created: {$fine->created_at}\n";
        echo "    Due Date: {$fine->due_date}\n";
    }
    
    // Check member's overdue borrowings
    $memberOverdue = \App\Models\Borrowing::where('member_id', 18)
        ->where('due_at', '<', now())
        ->whereNotIn('status', ['complete', 'cancelled', 'returned'])
        ->get();
    
    echo "Member's Overdue Borrowings: {$memberOverdue->count()}\n";
    foreach ($memberOverdue as $borrowing) {
        echo "  - Borrowing ID: {$borrowing->id}, Due: {$borrowing->due_at}, Status: {$borrowing->status}\n";
    }
    
    // Test fine service for this member
    $memberFinesFromService = $fineService->getMemberFines(18);
    echo "Service getMemberFines(18): {$memberFinesFromService->count()} fines\n";
    
} else {
    echo "Member ID 18 not found\n";
}

echo "\n=== ANALYSIS COMPLETE ===\n";
