<?php

namespace App\Repositories;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ReportRepository
{
    public function getSummary(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $borrowingQuery = $this->buildBorrowingDateQuery($dateFrom, $dateTo);

        return [
            'total_books' => Book::query()->count(),
            'total_members' => User::query()->role('Member')->count(),
            'total_borrowings' => (clone $borrowingQuery)->count(),
            'borrowed_active' => (clone $borrowingQuery)->whereIn('status', [Borrowing::STATUS_BORROWED, Borrowing::STATUS_PARTIAL, Borrowing::STATUS_AWAITING_FINE_PAYMENT])->count(),
            'returned_total' => (clone $borrowingQuery)->where('status', Borrowing::STATUS_RETURNED)->count(),
            'late_borrowings' => (clone $borrowingQuery)
                ->whereIn('status', [Borrowing::STATUS_BORROWED, Borrowing::STATUS_PARTIAL, Borrowing::STATUS_AWAITING_FINE_PAYMENT])
                ->whereDate('due_at', '<', Carbon::today()->toDateString())
                ->count(),
        ];
    }

    public function getRecentBorrowings(?string $dateFrom = null, ?string $dateTo = null): Collection
    {
        return $this->buildBorrowingDateQuery($dateFrom, $dateTo)
            ->with(['member:id,name,email', 'processedBy:id,name', 'items.book:id,title'])
            ->latest('borrowed_at')
            ->limit(10)
            ->get();
    }

    protected function buildBorrowingDateQuery(?string $dateFrom = null, ?string $dateTo = null): Builder
    {
        return Borrowing::query()
            ->when($dateFrom, fn (Builder $query) => $query->whereDate('borrowed_at', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $query) => $query->whereDate('borrowed_at', '<=', $dateTo));
    }
}
