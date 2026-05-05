<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== ANALYZING FINE CONFIG PERSISTENCE ISSUE ===\n\n";

// 1. Check current fine config in database
echo "1. CURRENT FINE CONFIG IN DATABASE\n";
echo "=================================\n";

$fineConfig = \App\Models\FineConfig::first();

if ($fineConfig) {
    echo "FineConfig ID: {$fineConfig->id}\n";
    echo "  ├─ Fine per day: Rp {$fineConfig->fine_per_day}\n";
    echo "  ├─ Grace period: {$fineConfig->grace_period_days} days\n";
    echo "  ├─ Max billable days: {$fineConfig->max_billable_days}\n";
    echo "  ├─ Max fine per item: Rp {$fineConfig->max_fine_per_item}\n";
    echo "  ├─ Lost book fine: Rp {$fineConfig->lost_book_fine}\n";
    echo "  ├─ Lost book payment deadline: {$fineConfig->lost_book_payment_deadline} days\n";
    echo "  ├─ Created at: {$fineConfig->created_at}\n";
    echo "  └─ Updated at: {$fineConfig->updated_at}\n";
} else {
    echo "❌ No FineConfig found in database\n";
}

echo "\n";

// 2. Check FineConfig model
echo "2. CHECKING FINECONFIG MODEL\n";
echo "===========================\n";

$fineConfigModelPath = app_path('Models/FineConfig.php');
if (file_exists($fineConfigModelPath)) {
    echo "✅ FineConfig.php found\n";
    
    $content = file_get_contents($fineConfigModelPath);
    
    echo "Model contains:\n";
    if (strpos($content, 'fillable') !== false) {
        echo "  ├─ fillable property\n";
    }
    if (strpos($content, 'getOrCreateDefault') !== false) {
        echo "  ├─ getOrCreateDefault() method\n";
    }
    if (strpos($content, 'boot') !== false) {
        echo "  ├─ boot() method (observers)\n";
    }
} else {
    echo "❌ FineConfig.php not found\n";
}

echo "\n";

// 3. Check FineService update logic
echo "3. CHECKING FINESERVICE UPDATE LOGIC\n";
echo "===================================\n";

$fineServicePath = app_path('Services/FineService.php');
if (file_exists($fineServicePath)) {
    echo "✅ FineService.php found\n";
    
    $content = file_get_contents($fineServicePath);
    
    echo "Service contains:\n";
    if (strpos($content, 'updateFineConfig') !== false) {
        echo "  ├─ updateFineConfig() method\n";
    }
    if (strpos($content, 'getFineConfig') !== false) {
        echo "  ├─ getFineConfig() method\n";
    }
    if (strpos($content, 'FineConfig::getOrCreateDefault') !== false) {
        echo "  ├─ Uses getOrCreateDefault()\n";
    }
} else {
    echo "❌ FineService.php not found\n";
}

echo "\n";

// 4. Check Super Admin FineConfig controller
echo "4. CHECKING SUPER ADMIN CONTROLLER\n";
echo "==================================\n";

$superAdminControllerPath = app_path('Http/Controllers/Admin/FineConfigController.php');
if (file_exists($superAdminControllerPath)) {
    echo "✅ FineConfigController.php found\n";
    
    $content = file_get_contents($superAdminControllerPath);
    
    echo "Controller contains:\n";
    if (strpos($content, 'edit') !== false) {
        echo "  ├─ edit() method\n";
    }
    if (strpos($content, 'update') !== false) {
        echo "  ├─ update() method\n";
    }
    if (strpos($content, 'updateFineConfig') !== false) {
        echo "  ├─ Uses updateFineConfig()\n";
    }
    
    // Check for validation
    if (strpos($content, 'validate') !== false) {
        echo "  ├─ Validation rules\n";
    }
    
    // Check for redirect
    if (strpos($content, 'redirect') !== false) {
        echo "  ├─ Redirect logic\n";
    }
} else {
    echo "❌ FineConfigController.php not found\n";
}

echo "\n";

// 5. Check routes for fine config
echo "5. CHECKING ROUTES\n";
echo "==================\n";

$routesPath = base_path('routes/web.php');
if (file_exists($routesPath)) {
    $content = file_get_contents($routesPath);
    
    echo "Routes containing fine-config:\n";
    if (strpos($content, 'fine-config') !== false) {
        echo "  ├─ fine-config routes found\n";
    }
    
    // Look for specific routes
    if (strpos($content, 'fine-config.edit') !== false) {
        echo "  ├─ fine-config.edit route\n";
    }
    if (strpos($content, 'fine-config.update') !== false) {
        echo "  ├─ fine-config.update route\n";
    }
} else {
    echo "❌ web.php not found\n";
}

echo "\n";

// 6. Test update logic
echo "6. TESTING UPDATE LOGIC\n";
echo "=======================\n";

if ($fineConfig) {
    echo "Testing update...\n";
    
    // Simulate update
    $originalMaxBillableDays = $fineConfig->max_billable_days;
    $originalMaxFinePerItem = $fineConfig->max_fine_per_item;
    
    echo "Original values:\n";
    echo "  ├─ Max billable days: {$originalMaxBillableDays}\n";
    echo "  ├─ Max fine per item: Rp {$originalMaxFinePerItem}\n";
    
    // Test update
    $fineService = app(\App\Services\FineService::class);
    
    try {
        $testData = [
            'max_billable_days' => 10,
            'max_fine_per_item' => 30000.00,
        ];
        
        $updatedConfig = $fineService->updateFineConfig($testData);
        
        echo "Update test:\n";
        echo "  ├─ New max billable days: {$updatedConfig->max_billable_days}\n";
        echo "  ├─ New max fine per item: Rp {$updatedConfig->max_fine_per_item}\n";
        echo "  ├─ Update timestamp: {$updatedConfig->updated_at}\n";
        
        // Test retrieval
        $retrievedConfig = $fineService->getFineConfig();
        
        echo "Retrieval test:\n";
        echo "  ├─ Retrieved max billable days: {$retrievedConfig->max_billable_days}\n";
        echo "  ├─ Retrieved max fine per item: Rp {$retrievedConfig->max_fine_per_item}\n";
        
        // Check if values persist
        if ($retrievedConfig->max_billable_days == 10 && $retrievedConfig->max_fine_per_item == 30000) {
            echo "  ✅ Values persist correctly\n";
        } else {
            echo "  ❌ Values do not persist\n";
        }
        
    } catch (\Exception $e) {
        echo "  ❌ Update failed: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// 7. Check for caching issues
echo "7. CHECKING FOR CACHING ISSUES\n";
echo "==============================\n";

echo "Potential caching issues:\n";
echo "  ├─ Model caching: " . (strpos($content ?? '', 'cache') !== false ? "Possible" : "Unlikely") . "\n";
echo "  ├─ Config caching: " . (strpos($content ?? '', 'config:cache') !== false ? "Possible" : "Unlikely") . "\n";
echo "  ├─ Response caching: " . (strpos($content ?? '', 'remember') !== false ? "Possible" : "Unlikely") . "\n";

echo "\n";

// 8. Identify root cause
echo "8. ROOT CAUSE ANALYSIS\n";
echo "=====================\n";

echo "Potential issues:\n";
echo "  1. ❌ Update not actually saving to database\n";
echo "  2. ❌ Retrieval method not getting latest data\n";
echo "  3. ❌ Caching preventing fresh data\n";
echo "  4. ❌ Form validation preventing update\n";
echo "  5. ❌ Transaction rollback\n";
echo "  6. ❌ Permission issues\n";

echo "\n";

echo "=== FINE CONFIG ANALYSIS COMPLETE ===\n";
echo "\n💡 NEXT STEPS:\n";
echo "1. Check actual update logic in controller\n";
echo "2. Verify database update is working\n";
echo "3. Check for caching issues\n";
echo "4. Fix any identified issues\n";
echo "5. Test persistence across page reloads\n\n";
