<?php

// Add these routes to routes/web.php

use App\Http\Controllers\Admin\OverdueFineController;

// Overdue Fine Processing Routes (Admin)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:Super Admin'])->group(function () {
    Route::get('/overdue-fines', [OverdueFineController::class, 'index'])->name('overdue-fines.index');
    Route::post('/overdue-fines/process', [OverdueFineController::class, 'process'])->name('overdue-fines.process');
    Route::get('/overdue-fines/statistics', [OverdueFineController::class, 'statistics'])->name('overdue-fines.statistics');
});

echo "Add the following routes to your routes/web.php file:\n\n";
echo "// Overdue Fine Processing Routes (Admin)\n";
echo "Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:Super Admin'])->group(function () {\n";
echo "    Route::get('/overdue-fines', [OverdueFineController::class, 'index'])->name('overdue-fines.index');\n";
echo "    Route::post('/overdue-fines/process', [OverdueFineController::class, 'process'])->name('overdue-fines.process');\n";
echo "    Route::get('/overdue-fines/statistics', [OverdueFineController::class, 'statistics'])->name('overdue-fines.statistics');\n";
echo "});\n\n";
echo "Also add this menu item to your Admin sidebar/navigation:\n";
echo '<li><Link href="{{ route(\'admin.overdue-fines.index\') }}">Overdue Fines</Link></li>' . "\n";
