<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== ANALYZING MIGRATION FRESH --SEED IMPACT ===\n\n";

// 1. Current database analysis
echo "1. CURRENT DATABASE ANALYSIS\n";
echo "============================\n";

// Check current data counts
echo "Current Data Counts:\n";
echo "  ├─ Users: " . \App\Models\User::count() . "\n";
echo "  ├─ Books: " . \App\Models\Book::count() . "\n";
echo "  ├─ Categories: " . \App\Models\Category::count() . "\n";
echo "  ├─ Borrowings: " . \App\Models\Borrowing::count() . "\n";
echo "  ├─ BorrowingItems: " . \App\Models\BorrowingItem::count() . "\n";
echo "  ├─ Fines: " . \App\Models\Fine::count() . "\n";
echo "  ├─ FinePayments: " . \App\Models\FinePayment::count() . "\n";
echo "  └─ FineConfigs: " . \App\Models\FineConfig::count() . "\n";

echo "\n";

// 2. Check foundation accounts
echo "2. CHECKING FOUNDATION ACCOUNTS\n";
echo "===============================\n";

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
foreach ($memberUsers->take(3) as $member) {
    echo "    ├─ ID: {$member->id}, Name: {$member->name}, Email: {$member->email}\n";
}
if ($memberUsers->count() > 3) {
    echo "    └─ ... and " . ($memberUsers->count() - 3) . " more members\n";
}

echo "\n";

// 3. Check seed files
echo "3. CHECKING SEED FILES\n";
echo "====================\n";

$seedFiles = [
    'users' => database_path('seeders/UserSeeder.php'),
    'books' => database_path('seeders/BookSeeder.php'),
    'categories' => database_path('seeders/CategorySeeder.php'),
];

foreach ($seedFiles as $type => $path) {
    if (file_exists($path)) {
        echo "  ├─ {$type} Seeder: ✅ Found\n";
        
        if ($type === 'users') {
            $content = file_get_contents($path);
            if (strpos($content, 'Super Admin') !== false) {
                echo "    └─ Contains Super Admin: ✅\n";
            }
            if (strpos($content, 'Librarian') !== false) {
                echo "    └─ Contains Librarian: ✅\n";
            }
            if (strpos($content, 'Member') !== false) {
                echo "    └─ Contains Member: ✅\n";
            }
        }
    } else {
        echo "  ├─ {$type} Seeder: ❌ Not found\n";
    }
}

echo "\n";

// 4. What will be lost
echo "4. WHAT WILL BE LOST WITH MIGRATE FRESH\n";
echo "========================================\n";

echo "Data that will be DELETED:\n";
echo "  ❌ All borrowing history (" . \App\Models\Borrowing::count() . " records)\n";
echo "  ❌ All borrowing items (" . \App\Models\BorrowingItem::count() . " records)\n";
echo "  ❌ All fines (" . \App\Models\Fine::count() . " records)\n";
echo "  ❌ All fine payments (" . \App\Models\FinePayment::count() . " records)\n";
echo "  ❌ All users (" . \App\Models\User::count() . " records)\n";
echo "  ❌ All books (" . \App\Models\Book::count() . " records)\n";
echo "  ❌ All categories (" . \App\Models\Category::count() . " records)\n";
echo "  ❌ All fine configs (" . \App\Models\FineConfig::count() . " records)\n";

echo "\n";

// 5. What will be restored
echo "5. WHAT WILL BE RESTORED FROM SEEDERS\n";
echo "=====================================\n";

echo "Data that will be RESTORED:\n";
echo "  ✅ Foundation users (from UserSeeder)\n";
echo "  ✅ Sample books (from BookSeeder)\n";
echo "  ✅ Sample categories (from CategorySeeder)\n";
echo "  ✅ Default fine configuration (if exists)\n";

echo "\n";

// 6. Risk assessment
echo "6. RISK ASSESSMENT\n";
echo "==================\n";

echo "Risks:\n";
echo "  🔴 HIGH: All current data will be permanently deleted\n";
echo "  🟡 MEDIUM: Custom configurations may be lost\n";
echo "  🟢 LOW: Foundation accounts should be restored from seeders\n";

echo "\n";

// 7. Recommendations
echo "7. RECOMMENDATIONS\n";
echo "==================\n";

echo "Before running migrate fresh --seed:\n";
echo "  1. ✅ BACKUP DATABASE (CRITICAL)\n";
echo "  2. ✅ Document current fine configuration\n";
echo "  3. ✅ Note any custom books/categories added\n";
echo "  4. ✅ Verify seeders contain all foundation accounts\n";

echo "\n";

// 8. Foundation account safety check
echo "8. FOUNDATION ACCOUNT SAFETY CHECK\n";
echo "==================================\n";

// Check if UserSeeder exists and what it contains
$userSeederPath = database_path('seeders/UserSeeder.php');
if (file_exists($userSeederPath)) {
    $content = file_get_contents($userSeederPath);
    
    echo "UserSeeder Analysis:\n";
    
    // Check for specific foundation accounts
    $foundationAccounts = [
        'Super Admin' => 'Muh Faiza',
        'Librarian' => 'Librarian User',
    ];
    
    foreach ($foundationAccounts as $role => $expectedName) {
        if (strpos($content, $expectedName) !== false) {
            echo "  ✅ {$role} ({$expectedName}): Found in seeder\n";
        } else {
            echo "  ❌ {$role} ({$expectedName}): NOT found in seeder\n";
        }
    }
    
    // Check if seeder creates roles
    if (strpos($content, 'Role::create') !== false) {
        echo "  ✅ Role creation: Found in seeder\n";
    } else {
        echo "  ❌ Role creation: NOT found in seeder\n";
    }
} else {
    echo "❌ UserSeeder not found - foundation accounts at risk!\n";
}

echo "\n";

// 9. Alternative approach
echo "9. ALTERNATIVE APPROACH (SAFER)\n";
echo "=================================\n";

echo "Instead of migrate fresh --seed, consider:\n";
echo "  1. ✅ Create specific cleanup script\n";
echo "  2. ✅ Only delete borrowing-related data\n";
echo "  3. ✅ Keep users, books, and configurations\n";
echo "  4. ✅ Preserve foundation accounts\n";
echo "  5. ✅ More controlled and safer\n";

echo "\n";

echo "=== MIGRATION IMPACT ANALYSIS COMPLETE ===\n";
echo "\n⚠️  WARNING: migrate fresh --seed will DELETE ALL current data!\n";
echo "🔒 Foundation accounts should be safe IF seeders contain them\n";
echo "💡 RECOMMENDATION: Create backup before proceeding\n";
echo "🛡️  SAFER OPTION: Use targeted cleanup script instead\n\n";
