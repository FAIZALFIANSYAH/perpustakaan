<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== FIXING PAYMENT FOREIGN KEY CONSTRAINT ISSUE ===\n\n";

// 1. Check existing users to find valid IDs
echo "1. CHECKING EXISTING USERS\n";
echo "==========================\n";

$users = \App\Models\User::all();
echo "Available Users:\n";
foreach ($users as $user) {
    echo "  - ID: {$user->id}, Name: {$user->name}, Role: " . ($user->roles->first()?->name ?? 'No role') . "\n";
}
echo "\n";

// 2. Check the payment processing issue
echo "2. PAYMENT PROCESSING ISSUE ANALYSIS\n";
echo "===================================\n";

$unpaidFine = \App\Models\Fine::where('status', 'unpaid')->first();

if ($unpaidFine) {
    echo "Testing with Fine ID: {$unpaidFine->id}\n";
    echo "  Member ID: {$unpaidFine->member_id}\n";
    echo "  Amount: Rp {$unpaidFine->amount}\n";
    
    // Check if user ID 1 exists
    $adminUser = \App\Models\User::find(1);
    echo "  Admin User ID 1 exists: " . ($adminUser ? "✅" : "❌") . "\n";
    
    if (!$adminUser) {
        echo "  Available admin user IDs:\n";
        $adminUsers = \App\Models\User::whereHas('roles', function($query) {
            $query->where('name', 'Super Admin');
        })->get();
        
        foreach ($adminUsers as $admin) {
            echo "    - ID: {$admin->id}, Name: {$admin->name}\n";
        }
        
        // Use the first available admin
        if ($adminUsers->count() > 0) {
            $adminId = $adminUsers->first()->id;
            echo "  Using admin ID: {$adminId}\n";
            
            // Test payment processing with valid admin ID
            $fineService = app(\App\Services\FineService::class);
            
            try {
                $processedFine = $fineService->processFinePayment(
                    $unpaidFine,
                    $unpaidFine->amount,
                    'cash',
                    $adminId, // Use valid admin ID
                    'Test payment with valid admin'
                );
                
                echo "  ✅ Payment processed successfully\n";
                echo "  New Status: {$processedFine->status}\n";
                echo "  Paid Amount: Rp {$processedFine->paid_amount}\n";
                echo "  Paid At: " . ($processedFine->paid_at ?? 'Not set') . "\n";
                
                // Check member borrowing status
                $canBorrow = $fineService->canMemberBorrow($unpaidFine->member_id);
                echo "  Member can borrow: " . ($canBorrow ? "✅" : "❌") . "\n";
                
            } catch (\Exception $e) {
                echo "  ❌ Payment still failed: " . $e->getMessage() . "\n";
            }
        }
    }
}

echo "\n";

// 3. Check FineService processFinePayment method
echo "3. CHECKING FINE SERVICE PAYMENT METHOD\n";
echo "========================================\n";

$fineServicePath = app_path('Services/FineService.php');
$fineServiceContent = file_get_contents($fineServicePath);

// Look for the processFinePayment method
if (preg_match('/public function processFinePayment\((.*?)\}/s', $fineServiceContent, $matches)) {
    echo "Found processFinePayment method:\n";
    echo substr($matches[0], 0, 500) . "...\n\n";
}

// 4. Check if there are any issues with the payment processing logic
echo "4. PAYMENT PROCESSING LOGIC CHECK\n";
echo "=================================\n";

// Check if the issue is with the processed_by field
echo "Checking processed_by field assignment:\n";

// Look for where processed_by is set in the FineService
if (strpos($fineServiceContent, 'processed_by') !== false) {
    echo "✅ processed_by field found in FineService\n";
    
    // Find the line where processed_by is set
    $lines = explode("\n", $fineServiceContent);
    foreach ($lines as $lineNumber => $line) {
        if (strpos($line, 'processed_by') !== false) {
            echo "  Line " . ($lineNumber + 1) . ": " . trim($line) . "\n";
        }
    }
} else {
    echo "❌ processed_by field not found in FineService\n";
}

echo "\n";

// 5. Fix the payment processing issue
echo "5. FIXING PAYMENT PROCESSING\n";
echo "===========================\n";

// Get a valid admin user
$adminUser = \App\Models\User::whereHas('roles', function($query) {
    $query->where('name', 'Super Admin');
})->first();

if ($adminUser && $unpaidFine) {
    echo "Using admin user: {$adminUser->name} (ID: {$adminUser->id})\n";
    
    $fineService = app(\App\Services\FineService::class);
    
    try {
        // Process payment with valid admin ID
        $processedFine = $fineService->processFinePayment(
            $unpaidFine,
            $unpaidFine->amount,
            'cash',
            $adminUser->id, // Use valid admin ID
            'Fixed payment processing test'
        );
        
        echo "✅ Payment processed successfully!\n";
        echo "  Fine ID: {$processedFine->id}\n";
        echo "  Status: {$processedFine->status}\n";
        echo "  Amount: Rp {$processedFine->amount}\n";
        echo "  Paid Amount: Rp {$processedFine->paid_amount}\n";
        echo "  Paid At: {$processedFine->paid_at}\n";
        
        // Verify payment record was created
        $paymentRecord = \App\Models\FinePayment::where('fine_id', $processedFine->id)->latest()->first();
        if ($paymentRecord) {
            echo "  Payment Record: ID {$paymentRecord->id}\n";
            echo "  Payment Amount: Rp {$paymentRecord->amount}\n";
            echo "  Processed By: {$paymentRecord->processed_by}\n";
        }
        
        // Check member status update
        $canBorrow = $fineService->canMemberBorrow($processedFine->member_id);
        echo "  Member can now borrow: " . ($canBorrow ? "✅" : "❌") . "\n";
        
        // Check if fine status is properly updated
        $updatedFine = \App\Models\Fine::find($processedFine->id);
        echo "  Fine status updated: {$updatedFine->status}\n";
        
    } catch (\Exception $e) {
        echo "❌ Payment processing failed: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
} else {
    echo "❌ Cannot fix - no admin user or unpaid fine found\n";
}

echo "\n";

// 6. Verify data synchronization after fix
echo "6. VERIFYING DATA SYNCHRONIZATION\n";
echo "==================================\n";

echo "📊 Updated Data State:\n";

// Check all fines again
$allFines = \App\Models\Fine::with(['member', 'payments'])->get();
echo "Total fines: {$allFines->count()}\n\n";

foreach ($allFines as $fine) {
    echo "Fine ID: {$fine->id}\n";
    echo "  Member: " . $fine->member->name . "\n";
    echo "  Amount: Rp {$fine->amount}\n";
    echo "  Paid Amount: Rp {$fine->paid_amount}\n";
    echo "  Status: {$fine->status}\n";
    echo "  Payments: " . $fine->payments->count() . "\n";
    echo "  Can Borrow: " . (app(\App\Services\FineService::class)->canMemberBorrow($fine->member_id) ? "✅" : "❌") . "\n";
    echo "\n";
}

echo "=== PAYMENT FOREIGN KEY FIX COMPLETE ===\n";
