<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FineConfigController;
use App\Http\Controllers\FineController;
use App\Http\Controllers\LibrarianController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberProfileController;
use App\Http\Controllers\Admin\OverdueFineController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Universal dashboard redirect based on role
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
});

// Super Admin Dashboard
Route::middleware(['auth', 'role:Super Admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', function (\App\Services\ReportService $reportService) {
            return Inertia::render('Admin/Dashboard', $reportService->getReportData());
        })->name('dashboard');
    });

Route::middleware(['auth', 'role:Librarian|Super Admin'])
    ->prefix('librarian')
    ->name('librarian.')
    ->group(function () {
        Route::get('/dashboard', [LibrarianController::class, 'dashboard'])->name('dashboard');
        Route::get('/overdue', [LibrarianController::class, 'overdue'])->name('overdue');
        Route::get('/members', [LibrarianController::class, 'members'])->name('members.index');
        
        // Librarian Books Management
        Route::get('/books', [LibrarianController::class, 'booksIndex'])->name('books.index');
        Route::get('/books/create', [LibrarianController::class, 'booksCreate'])->name('books.create');
        Route::post('/books', [LibrarianController::class, 'booksStore'])->name('books.store');
        Route::get('/books/{book}/edit', [LibrarianController::class, 'booksEdit'])->name('books.edit');
        Route::put('/books/{book}', [LibrarianController::class, 'booksUpdate'])->name('books.update');
        Route::post('/books/{book}/update', [LibrarianController::class, 'booksUpdate'])->name('books.update.post');
        Route::delete('/books/{book}', [LibrarianController::class, 'booksDestroy'])->name('books.destroy');
        
        // Librarian Borrowings Management
        Route::get('/borrowings', [LibrarianController::class, 'borrowingsIndex'])->name('borrowings.index');
        Route::get('/borrowings/create', [LibrarianController::class, 'borrowingsCreate'])->name('borrowings.create');
        Route::post('/borrowings', [LibrarianController::class, 'borrowingsStore'])->name('borrowings.store');
        Route::get('/borrowings/{borrowing}', [LibrarianController::class, 'borrowingsShow'])->name('borrowings.show');
        Route::post('/borrowings/{borrowing}/return', [LibrarianController::class, 'borrowingsReturn'])->name('borrowings.return');
        
        // Librarian Reports
        Route::get('/reports', [LibrarianController::class, 'reports'])->name('reports.index');

        // Librarian Categories Management
        Route::get('/categories', [LibrarianController::class, 'categoriesIndex'])->name('categories.index');
        Route::get('/categories/create', [LibrarianController::class, 'categoriesCreate'])->name('categories.create');
        Route::post('/categories', [LibrarianController::class, 'categoriesStore'])->name('categories.store');
        Route::get('/categories/{category}/edit', [LibrarianController::class, 'categoriesEdit'])->name('categories.edit');
        Route::put('/categories/{category}', [LibrarianController::class, 'categoriesUpdate'])->name('categories.update');
        Route::delete('/categories/{category}', [LibrarianController::class, 'categoriesDestroy'])->name('categories.destroy');
        
        // Librarian Fines Management
        Route::get('/fines', [FineController::class, 'index'])->name('fines.index');
        Route::post('/fines/{fine}/payment', [FineController::class, 'processPayment'])->name('fines.payment');
        Route::post('/borrowings/{borrowing}/items/{borrowingItem}/report-lost', [FineController::class, 'reportLostBook'])->name('borrowings.report-lost');
        
        // Librarian Fine Configuration
        Route::get('/fine-config', [FineConfigController::class, 'index'])->name('fine-config.index');
        Route::put('/fine-config', [FineConfigController::class, 'update'])->name('fine-config.update');
    });

Route::middleware(['auth', 'role:Member'])
    ->prefix('member')
    ->name('member.')
    ->group(function () {
        Route::get('/dashboard', [MemberController::class, 'dashboard'])->name('dashboard');
        Route::get('/borrowings/history', [MemberController::class, 'borrowingHistory'])->name('borrowings.history');
        Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
        Route::get('/catalog/{book}', [CatalogController::class, 'show'])->name('catalog.show');
        Route::post('/catalog/{book}/borrow', [CatalogController::class, 'borrow'])->name('catalog.borrow');
        Route::post('/catalog/{book}/reserve', [CatalogController::class, 'reserve'])->name('catalog.reserve');
        Route::get('/profile', [MemberProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [MemberProfileController::class, 'update'])->name('profile.update');
        Route::get('/reservations', [MemberController::class, 'reservations'])->name('reservations.index');
        Route::get('/fines', [FineController::class, 'memberIndex'])->name('fines.index');
        Route::post('/fines/{fine}/payment', [FineController::class, 'memberProcessPayment'])->name('fines.payment');
    });

// Super Admin Only: Categories Management
Route::middleware(['auth', 'role:Super Admin'])
    ->prefix('admin/categories')
    ->name('admin.categories.')
    ->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('/create', [CategoryController::class, 'create'])->name('create');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::get('/{category}/edit', [CategoryController::class, 'edit'])->name('edit');
        Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
    });

// Overdue Fine Processing Routes (Admin)
Route::middleware(['auth', 'role:Super Admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/overdue-fines', [OverdueFineController::class, 'index'])->name('overdue-fines.index');
        Route::post('/overdue-fines/process', [OverdueFineController::class, 'process'])->name('overdue-fines.process');
        Route::get('/overdue-fines/statistics', [OverdueFineController::class, 'statistics'])->name('overdue-fines.statistics');
    });

// Super Admin Only: Books Management
Route::middleware(['auth', 'role:Super Admin'])
    ->prefix('admin/books')
    ->name('admin.books.')
    ->group(function () {
        Route::get('/', [BookController::class, 'index'])->name('index');
        Route::get('/create', [BookController::class, 'create'])->name('create');
        Route::post('/', [BookController::class, 'store'])->name('store');
        Route::get('/{book}/edit', [BookController::class, 'edit'])->name('edit');
        Route::put('/{book}', [BookController::class, 'update'])->name('update');
        Route::post('/{book}/update', [BookController::class, 'update'])->name('update.post');
        Route::delete('/{book}', [BookController::class, 'destroy'])->name('destroy');
    });

// Super Admin Only: Users Management
Route::middleware(['auth', 'role:Super Admin'])
    ->prefix('admin/users')
    ->name('admin.users.')
    ->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });

// Super Admin Only: Reports
Route::middleware(['auth', 'role:Super Admin'])
    ->prefix('admin/reports')
    ->name('admin.reports.')
    ->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
    });

// Super Admin Only: Fine Configuration
Route::middleware(['auth', 'role:Super Admin'])
    ->prefix('admin/fine-config')
    ->name('admin.fine-config.')
    ->group(function () {
        Route::get('/', [FineConfigController::class, 'index'])->name('index');
        Route::put('/', [FineConfigController::class, 'update'])->name('update');
    });

// Super Admin Only: Fines Management
Route::middleware(['auth', 'role:Super Admin'])
    ->prefix('admin/fines')
    ->name('admin.fines.')
    ->group(function () {
        Route::get('/', [FineController::class, 'index'])->name('index');
        Route::post('/{fine}/payment', [FineController::class, 'processPayment'])->name('payment');
        Route::post('/borrowings/{borrowing}/items/{borrowingItem}/report-lost', [FineController::class, 'reportLostBook'])->name('report-lost');
    });

// Super Admin Only: Borrowings Management
Route::middleware(['auth', 'role:Super Admin'])
    ->prefix('admin/borrowings')
    ->name('admin.borrowings.')
    ->group(function () {
        Route::get('/', [BorrowingController::class, 'index'])->name('index');
        Route::get('/create', [BorrowingController::class, 'create'])->name('create');
        Route::post('/{borrowing}/return', [BorrowingController::class, 'return'])->name('return');
        Route::get('/{borrowing}', [BorrowingController::class, 'show'])->name('show');
        Route::post('/', [BorrowingController::class, 'store'])->name('store');
    });

require __DIR__ . '/auth.php';

// Penalty Configuration Routes (Super Admin only)
Route::middleware(['auth', 'role:Super Admin'])->prefix('penalty-config')->name('penalty-config.')->group(function () {
    Route::get('/', [App\Http\Controllers\PenaltyConfigController::class, 'index'])->name('index');
    Route::post('/', [App\Http\Controllers\PenaltyConfigController::class, 'update'])->name('update');
});
