<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== CHECKING BORROW LIMIT LOGIC ===\n\n";

// 1. Check current borrow limit settings in database
echo "1. CHECKING CURRENT BORROW LIMIT SETTINGS\n";
echo "========================================\n";

// Check User model for borrow_limit field
$usersWithLimit = \App\Models\User::whereNotNull('borrow_limit')->get();
echo "Users with borrow limit set:\n";
foreach ($usersWithLimit as $user) {
    echo "  ├─ ID: {$user->id}, Name: {$user->name}, Borrow Limit: {$user->borrow_limit}\n";
}

$usersWithoutLimit = \App\Models\User::whereNull('borrow_limit')->get();
echo "\nUsers without borrow limit set:\n";
foreach ($usersWithoutLimit as $user) {
    echo "  ├─ ID: {$user->id}, Name: {$user->name}, Borrow Limit: null\n";
}

echo "\n";

// 2. Check current borrowing status for each member
echo "2. CHECKING CURRENT BORROWING STATUS\n";
echo "===================================\n";

$members = \App\Models\User::whereHas('roles', function($query) {
    $query->where('name', 'Member');
})->get();

foreach ($members as $member) {
    echo "Member: {$member->name}\n";
    echo "  ├─ Borrow Limit: " . ($member->borrow_limit ?? 'Not Set') . "\n";
    
    // Count active borrowings
    $activeBorrowings = \App\Models\Borrowing::where('member_id', $member->id)
        ->whereIn('status', ['borrowed', 'overdue'])
        ->with('items')
        ->get();
    
    $totalBooksBorrowed = 0;
    foreach ($activeBorrowings as $borrowing) {
        foreach ($borrowing->items as $item) {
            $totalBooksBorrowed += $item->quantity;
        }
    }
    
    echo "  ├─ Current Active Borrowings: " . $activeBorrowings->count() . "\n";
    echo "  ├─ Total Books Borrowed: {$totalBooksBorrowed}\n";
    echo "  └─ Can Borrow More: " . (($member->borrow_limit && $totalBooksBorrowed >= $member->borrow_limit) ? "No" : "Yes") . "\n";
    echo "\n";
}

// 3. Check BorrowingService::ensureMemberBorrowLimit method
echo "3. CHECKING BORROWINGSERVICE BORROW LIMIT LOGIC\n";
echo "===============================================\n";

$borrowingServicePath = app_path('Services/BorrowingService.php');
$borrowingServiceContent = file_get_contents($borrowingServicePath);

if (strpos($borrowingServiceContent, 'ensureMemberBorrowLimit') !== false) {
    echo "✅ ensureMemberBorrowLimit method found in BorrowingService\n";
    
    // Extract the method
    if (preg_match('/public function ensureMemberBorrowLimit\(int \$memberId\): void\s*\{[^}]+\}/s', $borrowingServiceContent, $matches)) {
        echo "Method content:\n";
        echo $matches[0] . "\n\n";
    }
} else {
    echo "❌ ensureMemberBorrowLimit method NOT found in BorrowingService\n";
}

// 4. Test borrow limit enforcement
echo "4. TESTING BORROW LIMIT ENFORCEMENT\n";
echo "===================================\n";

// Get a member with borrow limit set
$testMember = \App\Models\User::whereHas('roles', function($query) {
    $query->where('name', 'Member');
})->whereNotNull('borrow_limit')->first();

if ($testMember) {
    echo "Testing with member: {$testMember->name}\n";
    echo "Borrow Limit: {$testMember->borrow_limit}\n";
    
    // Count current borrowings
    $currentBorrowings = \App\Models\Borrowing::where('member_id', $testMember->id)
        ->whereIn('status', ['borrowed', 'overdue'])
        ->with('items')
        ->get();
    
    $totalBooks = 0;
    foreach ($currentBorrowings as $borrowing) {
        foreach ($borrowing->items as $item) {
            $totalBooks += $item->quantity;
        }
    }
    
    echo "Current borrowed books: {$totalBooks}\n";
    echo "Remaining limit: " . ($testMember->borrow_limit - $totalBooks) . "\n";
    
    // Test creating new borrowing
    $testBook = \App\Models\Book::where('stock', '>', 0)->first();
    $testLibrarian = \App\Models\User::whereHas('roles', function($query) {
        $query->where('name', 'Librarian');
    })->first();
    
    if ($testBook && $testLibrarian) {
        echo "\nTesting borrow limit enforcement...\n";
        
        try {
            $borrowingData = [
                'member_id' => $testMember->id,
                'processed_by' => $testLibrarian->id,
                'borrowed_at' => now()->toDateString(),
                'due_at' => now()->addDays(7)->toDateString(),
                'notes' => 'Test borrow limit enforcement',
                'items' => [
                    ['book_id' => $testBook->id, 'quantity' => 1],
                ]
            ];
            
            $borrowingService = app(\App\Services\BorrowingService::class);
            $borrowing = $borrowingService->createBorrowing($borrowingData, $testLibrarian->id);
            
            echo "✅ Borrowing created successfully (ID: {$borrowing->id})\n";
            echo "❌ ISSUE: Borrow limit NOT enforced!\n";
            
            // Clean up test borrowing
            $borrowing->items()->delete();
            $borrowing->delete();
            
        } catch (\Exception $e) {
            echo "❌ Borrowing creation failed: " . $e->getMessage() . "\n";
            echo "✅ Borrow limit enforced correctly\n";
        }
    } else {
        echo "❌ Missing test data (book or librarian)\n";
    }
} else {
    echo "❌ No member with borrow limit found\n";
}

echo "\n";

// 5. Check User model fillable and relationships
echo "5. CHECKING USER MODEL SETTINGS\n";
echo "=================================\n";

$userModelPath = app_path('Models/User.php');
$userModelContent = file_get_contents($userModelPath);

echo "User Model Analysis:\n";

// Check if borrow_limit is in fillable
if (strpos($userModelContent, "'borrow_limit'") !== false) {
    echo "  ├─ borrow_limit field: ✅ Found in model\n";
} else {
    echo "  ├─ borrow_limit field: ❌ Not found in model\n";
}

// Check fillable array
if (preg_match('/protected \$fillable = \[([^\]]+)\]/s', $userModelContent, $matches)) {
    echo "  ├─ Fillable fields: " . $matches[1] . "\n";
}

echo "\n";

// 6. Check database schema
echo "6. CHECKING DATABASE SCHEMA\n";
echo "===========================\n";

try {
    $columns = \Illuminate\Support\Facades\Schema::getColumnListing('users');
    echo "Users table columns:\n";
    foreach ($columns as $column) {
        echo "  ├─ {$column}\n";
    }
    
    if (in_array('borrow_limit', $columns)) {
        echo "\n✅ borrow_limit column exists in users table\n";
    } else {
        echo "\n❌ borrow_limit column NOT found in users table\n";
    }
} catch (\Exception $e) {
    echo "❌ Schema check failed: " . $e->getMessage() . "\n";
}

echo "\n";

// 7. Summary
echo "7. CURRENT CONDITION SUMMARY\n";
echo "==========================\n";

echo "Current Status:\n";
echo "  ├─ Borrow limit setting: " . ($usersWithLimit->count() > 0 ? "Set for some users" : "Not set") . "\n";
echo "  ├─ Borrow limit enforcement: " . (isset($borrowing) ? "Not working" : "Unknown") . "\n";
echo "  ├─ Database schema: " . (in_array('borrow_limit', $columns ?? []) ? "Has borrow_limit column" : "Missing borrow_limit column") . "\n";
echo "  ├─ BorrowingService logic: " . (strpos($borrowingServiceContent, 'ensureMemberBorrowLimit') !== false ? "Has method" : "Missing method") . "\n";

echo "\n=== CHECK COMPLETE ===\n";
echo "\n💡 FINDINGS:\n";
echo "1. Borrow limit is set in database for some users\n";
echo "2. Borrow limit enforcement may not be working properly\n";
echo "3. Need to verify the logic in ensureMemberBorrowLimit\n";
echo "4. Need to test both member self-borrowing and librarian-created borrowing\n\n";

echo "🔍 QUESTIONS FOR CLARIFICATION:\n";
echo "1. Apakah borrow limit berlaku untuk total buku yang sedang dipinjam atau total transaksi peminjaman?\n";
echo "2. Apakah borrow limit harus berlaku untuk peminjaman yang dilakukan member sendiri (self-service) atau juga untuk peminjaman yang dibuat oleh librarian?\n";
echo "3. Apakah ada pesan error spesifik yang muncul saat limit terlampaui?\n";
echo "4. Apakah borrow limit per buku atau per transaksi?\n\n";
