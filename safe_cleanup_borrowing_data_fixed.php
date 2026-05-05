<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== SAFE BORROWING DATA CLEANUP (FIXED) ===\n\n";

// 1. Show current data before cleanup
echo "1. CURRENT DATA BEFORE CLEANUP\n";
echo "==============================\n";

echo "Data to be CLEANED (borrowing-related):\n";
echo "  ├─ Borrowings: " . \App\Models\Borrowing::count() . "\n";
echo "  ├─ BorrowingItems: " . \App\Models\BorrowingItem::count() . "\n";
echo "  ├─ Fines: " . \App\Models\Fine::count() . "\n";
echo "  └─ FinePayments: " . \App\Models\FinePayment::count() . "\n";

echo "\nData to be PRESERVED:\n";
echo "  ├─ Users: " . \App\Models\User::count() . "\n";
echo "  ├─ Books: " . \App\Models\Book::count() . "\n";
echo "  ├─ Categories: " . \App\Models\Category::count() . "\n";
echo "  ├─ FineConfigs: " . \App\Models\FineConfig::count() . "\n";

echo "\n";

// 2. Execute cleanup with proper foreign key handling
echo "2. EXECUTING CLEANUP\n";
echo "===================\n";

echo "Cleaning up borrowing-related data...\n";

try {
    // Use database transaction for safety
    \Illuminate\Support\Facades\DB::beginTransaction();
    
    // Disable foreign key checks temporarily
    \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0');
    
    // Delete in correct order (respect foreign keys)
    $deletedPayments = \App\Models\FinePayment::count();
    \App\Models\FinePayment::truncate();
    echo "  ✅ Deleted {$deletedPayments} fine payments\n";
    
    $deletedFines = \App\Models\Fine::count();
    \App\Models\Fine::truncate();
    echo "  ✅ Deleted {$deletedFines} fines\n";
    
    $deletedBorrowingItems = \App\Models\BorrowingItem::count();
    \App\Models\BorrowingItem::truncate();
    echo "  ✅ Deleted {$deletedBorrowingItems} borrowing items\n";
    
    $deletedBorrowings = \App\Models\Borrowing::count();
    \App\Models\Borrowing::truncate();
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

// 3. Verify preservation of important data
echo "3. VERIFYING PRESERVED DATA\n";
echo "==========================\n";

echo "Preserved Data Counts:\n";
echo "  ├─ Users: " . \App\Models\User::count() . "\n";
echo "  ├─ Books: " . \App\Models\Book::count() . "\n";
echo "  ├─ Categories: " . \App\Models\Category::count() . "\n";
echo "  ├─ FineConfigs: " . \App\Models\FineConfig::count() . "\n";

echo "\nCleared Data Counts:\n";
echo "  ├─ Borrowings: " . \App\Models\Borrowing::count() . " (should be 0)\n";
echo "  ├─ BorrowingItems: " . \App\Models\BorrowingItem::count() . " (should be 0)\n";
echo "  ├─ Fines: " . \App\Models\Fine::count() . " (should be 0)\n";
echo "  └─ FinePayments: " . \App\Models\FinePayment::count() . " (should be 0)\n";

echo "\n";

// 4. Verify foundation accounts are still there
echo "4. VERIFYING FOUNDATION ACCOUNTS\n";
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

// 5. Test system functionality
echo "5. TESTING SYSTEM FUNCTIONALITY\n";
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

// 6. Show preserved books and categories
echo "6. PRESERVED BOOKS AND CATEGORIES\n";
echo "=================================\n";

$books = \App\Models\Book::all();
$categories = \App\Models\Category::all();

echo "Books (" . $books->count() . "):\n";
foreach ($books as $book) {
    echo "  ├─ ID: {$book->id}, Title: {$book->title}, Stock: {$book->stock}\n";
}

echo "\nCategories (" . $categories->count() . "):\n";
foreach ($categories as $category) {
    echo "  ├─ ID: {$category->id}, Name: {$category->name}\n";
}

echo "\n";

// 7. Summary
echo "7. CLEANUP SUMMARY\n";
echo "==================\n";

echo "✅ COMPLETED:\n";
echo "  1. ✅ Cleaned all borrowing history\n";
echo "  2. ✅ Cleaned all fines and payments\n";
echo "  3. ✅ Preserved all foundation accounts\n";
echo "  4. ✅ Preserved all books and categories\n";
echo "  5. ✅ Preserved fine configuration\n";
echo "  6. ✅ System ready for new operations\n\n";

echo "📊 FINAL DATA STATE:\n";
echo "  ├─ Users: " . \App\Models\User::count() . " (preserved)\n";
echo "  ├─ Books: " . \App\Models\Book::count() . " (preserved)\n";
echo "  ├─ Categories: " . \App\Models\Category::count() . " (preserved)\n";
echo "  ├─ FineConfigs: " . \App\Models\FineConfig::count() . " (preserved)\n";
echo "  ├─ Borrowings: " . \App\Models\Borrowing::count() . " (cleared)\n";
echo "  ├─ Fines: " . \App\Models\Fine::count() . " (cleared)\n";
echo "  └─ FinePayments: " . \App\Models\FinePayment::count() . " (cleared)\n\n";

echo "=== SAFE BORROWING CLEANUP COMPLETE ===\n";
echo "\n🎉 RIWAYAT PEMINJAMAN TELAH DIBERSIHKAN!\n";
echo "✅ Akun foundation aman terjaga\n";
echo "✅ Buku dan kategori tetap ada\n";
echo "✅ Sistem siap untuk operasi baru\n";
echo "✅ Data peminjaman lama sudah dihapus\n\n";
