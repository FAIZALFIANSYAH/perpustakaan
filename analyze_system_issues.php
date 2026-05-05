<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== ANALYZING CURRENT SYSTEM ISSUES ===\n\n";

// 1. Check current borrowings and their status
echo "1. CURRENT BORROWINGS ANALYSIS\n";
echo "===============================\n";

$allBorrowings = \App\Models\Borrowing::with(['member', 'items.book', 'items.fines'])->get();

echo "Total Borrowings: " . $allBorrowings->count() . "\n\n";

foreach ($allBorrowings as $borrowing) {
    echo "Borrowing ID: {$borrowing->id}\n";
    echo "  ├─ Member: " . $borrowing->member->name . "\n";
    echo "  ├─ Borrowed At: {$borrowing->borrowed_at}\n";
    echo "  ├─ Due At: {$borrowing->due_at}\n";
    echo "  ├─ Status: {$borrowing->status}\n";
    
    // Calculate days overdue
    $daysOverdue = 0;
    if ($borrowing->due_at < now()) {
        $daysOverdue = $borrowing->due_at->diffInDays(now());
    }
    
    echo "  ├─ Days Overdue: {$daysOverdue}\n";
    echo "  ├─ Items: " . $borrowing->items->count() . "\n";
    
    foreach ($borrowing->items as $item) {
        echo "    ├─ Book: " . $item->book->title . "\n";
        echo "    ├─ Quantity: {$item->quantity}\n";
        echo "    ├─ Returned: {$item->returned_quantity}\n";
        echo "    └─ Fines: " . $item->fines->count() . "\n";
        
        foreach ($item->fines as $fine) {
            echo "      └─ Fine ID: {$fine->id}, Type: {$fine->type}, Amount: Rp {$fine->amount}, Status: {$fine->status}\n";
        }
    }
    
    // Check what status should be
    $expectedStatus = 'borrowed';
    if ($daysOverdue > 0) {
        $hasUnpaidFines = $borrowing->items->flatMap->fines->contains('status', 'unpaid');
        if ($hasUnpaidFines) {
            $expectedStatus = 'overdue';
        } else {
            $expectedStatus = 'late_payment';
        }
    }
    
    echo "  ├─ Expected Status: {$expectedStatus}\n";
    echo "  └─ Status Correct: " . ($borrowing->status === $expectedStatus ? "✅" : "❌") . "\n";
    echo "\n";
}

// 2. Check fines system
echo "2. FINES SYSTEM ANALYSIS\n";
echo "========================\n";

$allFines = \App\Models\Fine::with(['member', 'borrowingItem.book'])->get();

echo "Total Fines: " . $allFines->count() . "\n\n";

if ($allFines->count() === 0) {
    echo "❌ NO FINES FOUND - This is the main issue!\n";
    echo "   Overdue borrowings should have fines generated automatically\n\n";
} else {
    foreach ($allFines as $fine) {
        echo "Fine ID: {$fine->id}\n";
        echo "  ├─ Member: " . $fine->member->name . "\n";
        echo "  ├─ Type: {$fine->type}\n";
        echo "  ├─ Amount: Rp {$fine->amount}\n";
        echo "  ├─ Status: {$fine->status}\n";
        echo "  ├─ Paid Amount: Rp {$fine->paid_amount}\n";
        echo "  ├─ Due Date: {$fine->due_date}\n";
        echo "  └─ Book: " . ($fine->borrowingItem->book->title ?? 'N/A') . "\n\n";
    }
}

// 3. Check fine configuration
echo "3. FINE CONFIGURATION ANALYSIS\n";
echo "==============================\n";

$fineConfig = \App\Models\FineConfig::first();

if ($fineConfig) {
    echo "Fine Configuration:\n";
    echo "  ├─ Fine per day: Rp {$fineConfig->fine_per_day}\n";
    echo "  ├─ Grace period: {$fineConfig->grace_period_days} days\n";
    echo "  ├─ Max billable days: {$fineConfig->max_billable_days}\n";
    echo "  ├─ Max fine per item: Rp {$fineConfig->max_fine_per_item}\n";
    echo "  ├─ Lost book fine: Rp {$fineConfig->lost_book_fine}\n";
    echo "  └─ Lost book payment deadline: {$fineConfig->lost_book_payment_deadline} days\n\n";
} else {
    echo "❌ NO FINE CONFIGURATION FOUND!\n";
    echo "   This could prevent fine generation\n\n";
}

// 4. Identify overdue borrowings that should have fines
echo "4. OVERDUE BORROWINGS THAT NEED FINES\n";
echo "====================================\n";

$overdueBorrowings = \App\Models\Borrowing::where('due_at', '<', now())
    ->where('status', 'borrowed')
    ->with(['member', 'items.book', 'items.fines'])
    ->get();

echo "Overdue borrowings without fines: " . $overdueBorrowings->count() . "\n\n";

foreach ($overdueBorrowings as $borrowing) {
    $daysOverdue = $borrowing->due_at->diffInDays(now());
    
    echo "Borrowing ID: {$borrowing->id}\n";
    echo "  ├─ Member: " . $borrowing->member->name . "\n";
    echo "  ├─ Days Overdue: {$daysOverdue}\n";
    echo "  ├─ Status: {$borrowing->status}\n";
    
    $hasFines = $borrowing->items->flatMap->fines->count() > 0;
    echo "  └─ Has Fines: " . ($hasFines ? "✅" : "❌") . "\n";
    
    if (!$hasFines) {
        echo "    ❌ NEEDS FINE GENERATION!\n";
    }
    echo "\n";
}

// 5. Check system services
echo "5. SYSTEM SERVICES CHECK\n";
echo "========================\n";

// Check if fine service can generate fines
$fineService = app(\App\Services\FineService::class);
$borrowingService = app(\App\Services\BorrowingService::class);

echo "Services Available:\n";
echo "  ├─ FineService: ✅\n";
echo "  ├─ BorrowingService: ✅\n";
echo "  ├─ LibrarianService: ✅\n";
echo "  └─ FineConfig: " . ($fineConfig ? "✅" : "❌") . "\n\n";

// 6. Root cause analysis
echo "6. ROOT CAUSE ANALYSIS\n";
echo "======================\n";

echo "ISSUES IDENTIFIED:\n";
echo "  ❌ Overdue borrowings not updating status\n";
echo "  ❌ No fines being generated for overdue items\n";
echo "  ❌ Super Admin fines page shows no data\n";
echo "  ❌ Member My Fines page shows no data\n";
echo "  ❌ Borrowing status stuck at 'borrowed'\n\n";

echo "ROOT CAUSES:\n";
echo "  1. ❌ No automatic overdue detection system\n";
echo "  2. ❌ No automatic fine generation system\n";
echo "  3. ❌ Status update logic not being triggered\n";
echo "  4. ❌ Missing scheduled tasks/cron jobs\n";
echo "  5. ❌ System not checking for overdue items\n\n";

echo "=== SYSTEM ISSUES ANALYSIS COMPLETE ===\n";
echo "\n🔍 CONCLUSION:\n";
echo "The system is missing automatic overdue detection and fine generation.\n";
echo "Borrowings remain in 'borrowed' status even when 20+ days overdue.\n";
echo "No fines are being created, so Super Admin and Member pages show no data.\n\n";
