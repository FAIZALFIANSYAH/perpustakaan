<?php

namespace App\Repositories;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\Fine;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class LibrarianRepository
{
    public function getDashboardStats(): array
    {
        $today = now()->toDateString();

        return [
            'borrowings_today' => Borrowing::query()
                ->whereDate('borrowed_at', $today)
                ->count(),
            'returns_today' => Borrowing::query()
                ->whereDate('returned_at', $today)
                ->whereIn('status', [Borrowing::STATUS_RETURNED, Borrowing::STATUS_COMPLETE, Borrowing::STATUS_LATE_PAYMENT])
                ->count(),
            'active_borrowings' => Borrowing::query()
                ->whereIn('status', [Borrowing::STATUS_BORROWED, Borrowing::STATUS_PARTIAL, Borrowing::STATUS_AWAITING_FINE_PAYMENT])
                ->count(),
            'overdue_count' => Borrowing::query()
                ->where('status', Borrowing::STATUS_OVERDUE)
                ->count(),
            'unpaid_fines' => Fine::query()
                ->whereIn('status', [Fine::STATUS_UNPAID, Fine::STATUS_PARTIAL])
                ->count(),
            'total_unpaid_amount' => Fine::query()
                ->whereIn('status', [Fine::STATUS_UNPAID, Fine::STATUS_PARTIAL])
                ->selectRaw('SUM(amount - paid_amount) as total')
                ->value('total') ?? 0,
        ];
    }

    public function getRecentTransactions(int $limit = 10): Collection
    {
        return Borrowing::query()
            ->with(['member:id,name,email', 'items.book:id,title'])
            ->latest('borrowed_at')
            ->limit($limit)
            ->get();
    }

    public function getDueToday(): Collection
    {
        return Borrowing::query()
            ->with(['member:id,name,email', 'items.book:id,title'])
            ->whereIn('status', [Borrowing::STATUS_BORROWED, Borrowing::STATUS_PARTIAL, Borrowing::STATUS_AWAITING_FINE_PAYMENT])
            ->whereDate('due_at', now()->toDateString())
            ->get();
    }

    public function getOverdue(): Collection
    {
        return Borrowing::query()
            ->with(['member:id,name,email', 'items.book:id,title', 'items.fines'])
            ->whereDate('due_at', '<', now()->toDateString())
            ->where(function($query) {
                $query->whereHas('items', function($itemQuery) {
                    $itemQuery->where('returned_quantity', '<', DB::raw('quantity'))
                           ->orWhereHas('fines', function($fineQuery) {
                               $fineQuery->where('status', Fine::STATUS_UNPAID);
                           });
                });
            })
            ->orderBy('due_at')
            ->get();
    }

    public function searchMembers(?string $search = null): LengthAwarePaginator
    {
        return User::query()
            ->role('Member')
            ->with(['borrowings' => function ($query) {
                $query->whereIn('status', [Borrowing::STATUS_BORROWED, Borrowing::STATUS_PARTIAL, Borrowing::STATUS_AWAITING_FINE_PAYMENT]);
            }])
            ->when($search, function ($query, $search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->select('id', 'name', 'email', 'email_verified_at', 'created_at')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();
    }

    public function getLibrarianReports(): array
    {
        $today = now()->toDateString();
        $thisMonth = now()->format('Y-m');

        // Book Statistics
        $totalBooks = Book::count();
        $availableBooks = Book::where('stock', '>', 0)->count();
        $lowStockBooks = Book::where('stock', '<=', 5)->count();
        $outOfStockBooks = Book::where('stock', 0)->count();

        // Category Statistics
        $categoriesWithBooks = Category::withCount('books')
            ->whereHas('books')
            ->orderBy('books_count', 'desc')
            ->limit(10)
            ->get();

        // Member Statistics
        $totalMembers = User::role('Member')->count();
        $activeMembersThisMonth = User::role('Member')
            ->whereHas('borrowings', function ($query) use ($thisMonth) {
                $query->where('borrowed_at', 'like', "{$thisMonth}%");
            })
            ->count();

        // Borrowing Statistics
        $borrowingsThisMonth = Borrowing::where('borrowed_at', 'like', "{$thisMonth}%")->count();
        $returnsThisMonth = Borrowing::where('returned_at', 'like', "{$thisMonth}%")
            ->where('status', Borrowing::STATUS_RETURNED)
            ->count();
        $overdueCount = Borrowing::query()
            ->whereIn('status', [Borrowing::STATUS_BORROWED, Borrowing::STATUS_PARTIAL, Borrowing::STATUS_AWAITING_FINE_PAYMENT])
            ->whereDate('due_at', '<', $today)
            ->count();

        // Most Borrowed Books
        $mostBorrowedBooks = Book::withCount(['borrowingItems as times_borrowed'])
            ->orderBy('times_borrowed', 'desc')
            ->limit(10)
            ->get(['id', 'title', 'author', 'isbn', 'stock']);

        return [
            'books' => [
                'total' => $totalBooks,
                'available' => $availableBooks,
                'low_stock' => $lowStockBooks,
                'out_of_stock' => $outOfStockBooks,
            ],
            'categories' => $categoriesWithBooks,
            'members' => [
                'total' => $totalMembers,
                'active_this_month' => $activeMembersThisMonth,
            ],
            'borrowings' => [
                'this_month' => $borrowingsThisMonth,
                'returns_this_month' => $returnsThisMonth,
                'overdue' => $overdueCount,
            ],
            'most_borrowed' => $mostBorrowedBooks,
        ];
    }
}
