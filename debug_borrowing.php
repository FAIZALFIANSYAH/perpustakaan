<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== DEBUG BORROWING IMPACT ON STOCK ===\n\n";

// Get a sample book
$book = \App\Models\Book::with('borrowingItems')->first();
if (!$book) {
    echo "No books found in database\n";
    exit;
}

echo "Book Info:\n";
echo "ID: {$book->id}\n";
echo "Title: {$book->title}\n";
echo "Current Stock: {$book->stock}\n";
echo "Borrowing Items Count: " . $book->borrowingItems->count() . "\n\n";

if ($book->borrowingItems->count() > 0) {
    echo "Borrowing Items:\n";
    foreach ($book->borrowingItems as $item) {
        echo "- ID: {$item->id}, Quantity: {$item->quantity}, Returned: {$item->returned_quantity}\n";
    }
    echo "\n";
}

// Test if there's any calculation happening
echo "Testing stock calculation...\n";
$originalStock = $book->stock;
echo "Original stock: {$originalStock}\n";

// Simulate update
$testData = [
    'title' => $book->title,
    'category_id' => $book->category_id,
    'author' => $book->author,
    'stock' => 19,
    'is_active' => $book->is_active,
];

echo "Updating stock to 19...\n";

// Check if there are any active borrowings
$activeBorrowings = \App\Models\BorrowingItem::where('book_id', $book->id)
    ->whereHas('borrowing', function($query) {
        $query->whereIn('status', ['active', 'overdue']);
    })
    ->get();

echo "Active borrowings for this book: " . $activeBorrowings->count() . "\n";
if ($activeBorrowings->count() > 0) {
    foreach ($activeBorrowings as $borrowing) {
        echo "- Borrowing ID: {$borrowing->id}, Quantity: {$borrowing->quantity}, Returned: {$borrowing->returned_quantity}\n";
    }
}

echo "\n=== DEBUG COMPLETE ===\n";
