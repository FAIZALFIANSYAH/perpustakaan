<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "Users table columns:\n";
$columns = Schema::getColumnListing('users');
foreach($columns as $column) {
    echo "- " . $column . "\n";
}

echo "\nDone.\n";
