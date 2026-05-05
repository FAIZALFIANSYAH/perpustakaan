<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== SIMPLE BORROWING DATA CLEANUP ===\n\n";

// 1. Show current data
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

// 2. Execute cleanup using delete instead of truncate
echo "2. EXECUTING CLEANUP\n";
echo "===================\n";

try {
    // Delete fine payments first
    $deletedPayments = \App\Models\FinePayment::count();
    \App\Models\FinePayment::query()->delete();
    echo "  ✅ Deleted {$deletedPayments} fine payments\n";
    
    // Delete fines
    $deletedFines = \App\Models\Fine::count();
    \App\Models\Fine::query()->delete();
    echo "  ✅ Deleted {$deletedFines} fines\n";
    
    // Delete borrowing items
    $deletedBorrowingItems = \App\Models\BorrowingItem::count();
    \App\Models\BorrowingItem::query()->delete();
    echo "  ✅ Deleted {$deletedBorrowingItems} borrowing items\n";
    
    // Delete borrowings
    $deletedBorrowings = \App\Models\Borrowing::count();
    \App\Models\Borrowing::query()->delete();
    echo "  ✅ Deleted {$deletedBorrowings} borrowings\n";
    
    echo "  ✅ Cleanup completed successfully\n";
    
} catch (\Exception $e) {
    echo "  ❌ Cleanup failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// 3. Verify results
echo "3. VERIFYING CLEANUP RESULTS\n";
echo "============================\n";

echo "Data after cleanup:\n";
echo "  ├─ Borrowings: " . \App\Models\Borrowing::count() . "\n";
echo "  ├─ BorrowingItems: " . \App\Models\BorrowingItem::count() . "\n";
echo "  ├─ Fines: " . \App\Models\Fine::count() . "\n";
echo "  ├─ FinePayments: " . \App\Models\FinePayment::count() . "\n";
echo "  ├─ Users: " . \App\Models\User::count() . " (preserved)\n";
echo "  ├─ Books: " . \App\Models\Book::count() . " (preserved)\n";
echo "  ├─ Categories: " . \App\Models\Category::count() . " (preserved)\n";
echo "  └─ FineConfigs: " . \App\Models\FineConfig::count() . " (preserved)\n";

echo "\n";

// 4. Verify foundation accounts
echo "4. VERIFYING FOUNDATION ACCOUNTS\n";
echo "================================\n";

$superAdmins = \App\Models\User::whereHas('roles', function($query) {
    $query->where('name', 'Super Admin');
})->get();

$librarians = \App\Models\User::whereHas('roles', function($query) {
    $query->where('name', 'Librarian');
})->get();

$members = \App\Models\User::whereHas('roles', function($query) {
    $query->where('name', 'Member');
})->get();

echo "Foundation Accounts Status:\n";
echo "  ├─ Super Admins: " . $superAdmins->count() . "\n";
foreach ($superAdmins as $admin) {
    echo "    └─ ID: {$admin->id}, Name: {$admin->name}\n";
}

echo "  ├─ Librarians: " . $librarians->count() . "\n";
foreach ($librarians as $librarian) {
    echo "    └─ ID: {$librarian->id}, Name: {$librarian->name}\n";
}

echo "  └─ Members: " . $members->count() . "\n";
foreach ($members as $member) {
    echo "    └─ ID: {$member->id}, Name: {$member->name}\n";
}

echo "\n";

// 5. Test system readiness
echo "5. SYSTEM READINESS TEST\n";
echo "=======================\n";

$testMember = \App\Models\User::whereHas('roles', function($query) {
    $query->where('name', 'Member');
})->first();

$testBook = \App\Models\Book::first();

if ($testMember && $testBook) {
    echo "✅ System ready for new operations:\n";
    echo "  ├─ Member available: " . $testMember->name . "\n";
    echo "  ├─ Book available: " . $testBook->title . " (Stock: {$testBook->stock})\n";
    echo "  └─ Foundation accounts preserved\n";
} else {
    echo "❌ System not ready for operations\n";
}

echo "\n";

echo "=== CLEANUP COMPLETE ===\n";
echo "\n🎉 RIWAYAT PEMINJAMAN TELAH DIBERSIHKAN!\n";
echo "✅ Semua data peminjaman dihapus\n";
echo "✅ Akun foundation aman\n";
echo "✅ Buku dan kategori tetap ada\n";
echo "✅ Sistem siap digunakan kembali\n\n";
