<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== TEST STOCK UPDATE BUG ===\n\n";

// Get a book with borrowing items
$book = \App\Models\Book::with('borrowingItems')->first();
if (!$book) {
    echo "No books found\n";
    exit;
}

echo "BEFORE UPDATE:\n";
echo "Book ID: {$book->id}\n";
echo "Title: {$book->title}\n";
echo "Stock: {$book->stock}\n";
echo "Borrowing Items: " . $book->borrowingItems->count() . "\n";

foreach ($book->borrowingItems as $item) {
    echo "  - Item ID: {$item->id}, Qty: {$item->quantity}, Returned: {$item->returned_quantity}\n";
}

// Test update with stock 19
echo "\nUPDATING STOCK TO 19...\n";

$bookService = app(\App\Services\BookService::class);
$updateData = [
    'title' => $book->title,
    'category_id' => $book->category_id,
    'author' => $book->author,
    'stock' => 19,
    'is_active' => $book->is_active,
];

$result = $bookService->updateBook($book, $updateData);

// Refresh the book to see the actual result
$book->refresh();

echo "\nAFTER UPDATE:\n";
echo "Stock: {$book->stock}\n";
echo "Expected: 19\n";
echo "Difference: " . (19 - $book->stock) . "\n";

if ($book->stock != 19) {
    echo "❌ STOCK CHANGED UNEXPECTEDLY!\n";
    
    // Check if borrowing items changed
    $book->load('borrowingItems');
    echo "Borrowing Items after update: " . $book->borrowingItems->count() . "\n";
    foreach ($book->borrowingItems as $item) {
        echo "  - Item ID: {$item->id}, Qty: {$item->quantity}, Returned: {$item->returned_quantity}\n";
    }
} else {
    echo "✅ Stock updated correctly\n";
}

echo "\n=== TEST COMPLETE ===\n";
