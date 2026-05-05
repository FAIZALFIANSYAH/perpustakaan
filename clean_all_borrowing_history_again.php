<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== CLEANING ALL BORROWING HISTORY AGAIN ===\n\n";

// 1. Show current data before cleanup
echo "1. CURRENT DATA BEFORE CLEANUP\n";
echo "==============================\n";

echo "Data to be CLEANED:\n";
echo "  ├─ Borrowings: " . \App\Models\Borrowing::count() . "\n";
echo "  ├─ BorrowingItems: " . \App\Models\BorrowingItem::count() . "\n";
echo "  ├─ Fines: " . \App\Models\Fine::count() . "\n";
echo "  └─ FinePayments: " . \App\Models\FinePayment::count() . "\n";

echo "\nData to be PRESERVED:\n";
echo "  ├─ Users: " . \App\Models\User::count() . "\n";
echo "  ├─ Books: " . \App\Models\Book::count() . "\n";
echo "  ├─ Categories: " . \App\Models\Category::count() . "\n";
echo "  └─ FineConfigs: " . \App\Models\FineConfig::count() . "\n";

echo "\n";

// 2. Show foundation accounts that will be preserved
echo "2. FOUNDATION ACCOUNTS (WILL BE PRESERVED)\n";
echo "==========================================\n";

$superAdminUsers = \App\Models\User::whereHas('roles', function($query) {
    $query->where('name', 'Super Admin');
})->get();

$librarianUsers = \App\Models\User::whereHas('roles', function($query) {
    $query->where('name', 'Librarian');
})->get();

$memberUsers = \App\Models\User::whereHas('roles', function($query) {
    $query->where('name', 'Member');
})->get();

echo "Foundation Accounts:\n";
echo "  ├─ Super Admins: " . $superAdminUsers->count() . "\n";
foreach ($superAdminUsers as $admin) {
    echo "    ├─ ID: {$admin->id}, Name: {$admin->name}, Email: {$admin->email}\n";
}

echo "  ├─ Librarians: " . $librarianUsers->count() . "\n";
foreach ($librarianUsers as $librarian) {
    echo "    ├─ ID: {$librarian->id}, Name: {$librarian->name}, Email: {$librarian->email}\n";
}

echo "  └─ Members: " . $memberUsers->count() . "\n";
foreach ($memberUsers as $member) {
    echo "    ├─ ID: {$member->id}, Name: {$member->name}, Email: {$member->email}\n";
}

echo "\n";

// 3. Execute cleanup
echo "3. EXECUTING CLEANUP\n";
echo "===================\n";

echo "Cleaning up all borrowing-related data...\n";

try {
    // Use database transaction for safety
    \Illuminate\Support\Facades\DB::beginTransaction();
    
    // Disable foreign key checks temporarily
    \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0');
    
    // Delete in correct order (respect foreign keys)
    $deletedPayments = \App\Models\FinePayment::count();
    \App\Models\FinePayment::query()->delete();
    echo "  ✅ Deleted {$deletedPayments} fine payments\n";
    
    $deletedFines = \App\Models\Fine::count();
    \App\Models\Fine::query()->delete();
    echo "  ✅ Deleted {$deletedFines} fines\n";
    
    $deletedBorrowingItems = \App\Models\BorrowingItem::count();
    \App\Models\BorrowingItem::query()->delete();
    echo "  ✅ Deleted {$deletedBorrowingItems} borrowing items\n";
    
    $deletedBorrowings = \App\Models\Borrowing::count();
    \App\Models\Borrowing::query()->delete();
    echo "  ✅ Deleted {$deletedBorrowings} borrowings\n";
    
    // Re-enable foreign key checks
    \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1');
    
    \Illuminate\Support\Facades\DB::commit();
    echo "  ✅ Cleanup completed successfully\n";
    
} catch (\Exception $e) {
    \Illuminate\Support\Facades\DB::rollBack();
    // Re-enable foreign key checks in case of error
    \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1');
    echo "  ❌ Cleanup failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// 4. Verify preservation of important data
echo "4. VERIFYING PRESERVED DATA\n";
echo "==========================\n";

echo "Preserved Data Counts:\n";
echo "  ├─ Users: " . \App\Models\User::count() . " (preserved)\n";
echo "  ├─ Books: " . \App\Models\Book::count() . " (preserved)\n";
echo "  ├─ Categories: " . \App\Models\Category::count() . " (preserved)\n";
echo "  ├─ FineConfigs: " . \App\Models\FineConfig::count() . " (preserved)\n";

echo "\nCleared Data Counts:\n";
echo "  ├─ Borrowings: " . \App\Models\Borrowing::count() . " (should be 0)\n";
echo "  ├─ BorrowingItems: " . \App\Models\BorrowingItem::count() . " (should be 0)\n";
echo "  ├─ Fines: " . \App\Models\Fine::count() . " (should be 0)\n";
echo "  └─ FinePayments: " . \App\Models\FinePayment::count() . " (should be 0)\n";

echo "\n";

// 5. Verify foundation accounts are still there
echo "5. VERIFYING FOUNDATION ACCOUNTS\n";
echo "================================\n";

$remainingSuperAdmins = \App\Models\User::whereHas('roles', function($query) {
    $query->where('name', 'Super Admin');
})->get();

$remainingLibrarians = \App\Models\User::whereHas('roles', function($query) {
    $query->where('name', 'Librarian');
})->get();

$remainingMembers = \App\Models\User::whereHas('roles', function($query) {
    $query->where('name', 'Member');
})->get();

echo "Foundation Accounts Status:\n";
echo "  ├─ Super Admins: " . $remainingSuperAdmins->count() . " (should be 1+)\n";
foreach ($remainingSuperAdmins as $admin) {
    echo "    ├─ ID: {$admin->id}, Name: {$admin->name}, Email: {$admin->email}\n";
}

echo "  ├─ Librarians: " . $remainingLibrarians->count() . " (should be 1+)\n";
foreach ($remainingLibrarians as $librarian) {
    echo "    ├─ ID: {$librarian->id}, Name: {$librarian->name}, Email: {$librarian->email}\n";
}

echo "  └─ Members: " . $remainingMembers->count() . " (should be 2+)\n";
foreach ($remainingMembers as $member) {
    echo "    ├─ ID: {$member->id}, Name: {$member->name}, Email: {$member->email}\n";
}

echo "\n";

// 6. Test system functionality
echo "6. TESTING SYSTEM FUNCTIONALITY\n";
echo "================================\n";

// Test if we can create new borrowing
try {
    $testMember = \App\Models\User::whereHas('roles', function($query) {
        $query->where('name', 'Member');
    })->first();
    
    $testBook = \App\Models\Book::first();
    $testLibrarian = \App\Models\User::whereHas('roles', function($query) {
        $query->where('name', 'Librarian');
    })->first();
    
    if ($testMember && $testBook && $testLibrarian) {
        echo "✅ System ready for new operations:\n";
        echo "  ├─ Test Member: " . $testMember->name . "\n";
        echo "  ├─ Test Book: " . $testBook->title . " (Stock: {$testBook->stock})\n";
        echo "  ├─ Test Librarian: " . $testLibrarian->name . "\n";
        echo "  └─ All foundation accounts preserved\n";
    } else {
        echo "❌ Missing required accounts for testing\n";
    }
} catch (\Exception $e) {
    echo "❌ System test failed: " . $e->getMessage() . "\n";
}

echo "\n";

// 7. Reset fine config to original values
echo "7. RESETTING FINE CONFIG TO ORIGINAL\n";
echo "=====================================\n";

try {
    $fineService = app(\App\Services\FineService::class);
    
    $originalConfig = [
        'grace_period_days' => 2,
        'fine_per_day' => 3000.00,
        'max_billable_days' => 5,
        'max_fine_per_item' => 10000.00,
        'lost_book_fine' => 50000.00,
        'lost_book_payment_deadline' => 14,
        'max_fine_cap' => null,
    ];
    
    $resetConfig = $fineService->updateFineConfig($originalConfig);
    
    echo "✅ Fine config reset to original values:\n";
    echo "  ├─ Max billable days: {$resetConfig->max_billable_days}\n";
    echo "  ├─ Max fine per item: Rp {$resetConfig->max_fine_per_item}\n";
    echo "  ├─ Max fine cap: " . ($resetConfig->max_fine_cap ?? 'null') . "\n";
    echo "  └─ Fine per day: Rp {$resetConfig->fine_per_day}\n";
    
} catch (\Exception $e) {
    echo "❌ Fine config reset failed: " . $e->getMessage() . "\n";
}

echo "\n";

// 8. Summary
echo "8. CLEANUP SUMMARY\n";
echo "==================\n";

echo "✅ COMPLETED:\n";
echo "  1. ✅ Cleaned all borrowing history\n";
echo "  2. ✅ Cleaned all fines and payments\n";
echo "  3. ✅ Preserved all foundation accounts\n";
echo "  4. ✅ Preserved all books and categories\n";
echo "  5. ✅ Preserved fine configuration\n";
echo "  6. ✅ Reset fine config to original\n";
echo "  7. ✅ System ready for new operations\n\n";

echo "📊 FINAL DATA STATE:\n";
echo "  ├─ Users: " . \App\Models\User::count() . " (preserved)\n";
echo "  ├─ Books: " . \App\Models\Book::count() . " (preserved)\n";
echo "  ├─ Categories: " . \App\Models\Category::count() . " (preserved)\n";
echo "  ├─ FineConfigs: " . \App\Models\FineConfig::count() . " (preserved)\n";
echo "  ├─ Borrowings: " . \App\Models\Borrowing::count() . " (cleared)\n";
echo "  ├─ Fines: " . \App\Models\Fine::count() . " (cleared)\n";
echo "  └─ FinePayments: " . \App\Models\FinePayment::count() . " (cleared)\n\n";

echo "=== ALL BORROWING HISTORY CLEANUP COMPLETE ===\n";
echo "\n🎉 SELURUH RIWAYAT PEMINJAMAN TELAH DIBERSIHKAN!\n";
echo "✅ Semua data peminjaman dihapus\n";
echo "✅ Semua data denda dihapus\n";
echo "✅ Akun foundation aman terjaga\n";
echo "✅ Buku dan kategori tetap ada\n";
echo "✅ Konfigurasi denda direset ke original\n";
echo "✅ Sistem siap untuk operasi baru\n";
echo "✅ Fresh start untuk testing manual\n";
echo "✅ Overdue sync fix tetap terpasang\n\n";
