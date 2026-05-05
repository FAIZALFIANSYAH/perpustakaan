<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== DEBUG STOCK UPDATE ISSUE ===\n\n";

// Get a sample book
$book = \App\Models\Book::first();
if (!$book) {
    echo "No books found in database\n";
    exit;
}

echo "Current Book:\n";
echo "ID: {$book->id}\n";
echo "Title: {$book->title}\n";
echo "Stock: {$book->stock}\n\n";

// Simulate the data that comes from frontend
$frontendData = [
    'title' => $book->title,
    'category_id' => $book->category_id,
    'author' => $book->author,
    'stock' => '19', // String from frontend
    'is_active' => true,
];

echo "Frontend Data (string stock):\n";
var_dump($frontendData);
echo "\n";

// Test UpdateBookRequest processing
echo "UpdateBookRequest processing:\n";
echo "Original stock: " . $frontendData['stock'] . " (type: " . gettype($frontendData['stock']) . ")\n";

// Simulate prepareForValidation logic
$processedData = $frontendData;
$processedData['stock'] = (int) $processedData['stock'];
echo "After prepareForValidation: " . $processedData['stock'] . " (type: " . gettype($processedData['stock']) . ")\n\n";


// Test LibrarianController processing
$librarianRequest = new \Illuminate\Http\Request();
$librarianRequest->merge($frontendData);

echo "LibrarianController processing:\n";
echo "Original stock: " . $librarianRequest->input('stock') . " (type: " . gettype($librarianRequest->input('stock')) . ")\n";

// Simulate the validation
$validated = $librarianRequest->validate([
    'stock' => 'required|numeric|min:0',
]);

echo "After validation: " . $validated['stock'] . " (type: " . gettype($validated['stock']) . ")\n\n";

echo "=== DEBUG COMPLETE ===\n";
