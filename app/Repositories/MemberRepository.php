<?php

namespace App\Repositories;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class MemberRepository
{
    public function getActiveBorrowings(User $user): Collection
    {
        return Borrowing::query()
            ->with(['items.book:id,title,author,cover', 'processedBy:id,name'])
            ->where('member_id', $user->id)
            ->whereIn('status', [Borrowing::STATUS_BORROWED, Borrowing::STATUS_PARTIAL, Borrowing::STATUS_AWAITING_FINE_PAYMENT])
            ->orderBy('due_at')
            ->get();
    }

    public function getBorrowingHistory(User $user): Collection
    {
        return Borrowing::query()
            ->with(['items.book:id,title,author,cover', 'processedBy:id,name'])
            ->where('member_id', $user->id)
            ->latest('borrowed_at')
            ->get();
    }

    public function getBorrowingSummary(User $user): array
    {
        $query = Borrowing::query()->where('member_id', $user->id);

        return [
            'active' => (clone $query)->whereIn('status', [Borrowing::STATUS_BORROWED, Borrowing::STATUS_PARTIAL, Borrowing::STATUS_AWAITING_FINE_PAYMENT])->count(),
            'history' => (clone $query)->count(),
            'returned' => (clone $query)->where('status', Borrowing::STATUS_RETURNED)->count(),
            'late' => (clone $query)
                ->whereIn('status', [Borrowing::STATUS_BORROWED, Borrowing::STATUS_PARTIAL, Borrowing::STATUS_AWAITING_FINE_PAYMENT])
                ->whereDate('due_at', '<', now()->toDateString())
                ->count(),
        ];
    }

    public function getCatalogBooks(?string $search = null, ?int $categoryId = null): LengthAwarePaginator
    {
        return Book::query()
            ->with('category:id,name')
            ->select('id', 'category_id', 'title', 'author', 'cover', 'stock', 'is_active')
            ->when($search, function ($query, $search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%");
                });
            })
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->orderBy('title')
            ->paginate(9)
            ->withQueryString();
    }

    public function getCatalogBookById(int $id): ?Book
    {
        return Book::query()
            ->with('category:id,name')
            ->find($id);
    }

    public function getCatalogCategories(): Collection
    {
        return Category::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    public function hasActiveBorrowingForBook(User $user, int $bookId): bool
    {
        return Borrowing::query()
            ->where('member_id', $user->id)
            ->whereIn('status', [Borrowing::STATUS_BORROWED, Borrowing::STATUS_PARTIAL, Borrowing::STATUS_AWAITING_FINE_PAYMENT])
            ->whereHas('items', fn ($query) => $query->where('book_id', $bookId))
            ->exists();
    }

    public function getReservations(User $user): Collection
    {
        return Reservation::query()
            ->with('book:id,title,author')
            ->where('member_id', $user->id)
            ->latest()
            ->get();
    }

    public function hasPendingReservation(User $user, int $bookId): bool
    {
        return Reservation::query()
            ->where('member_id', $user->id)
            ->where('book_id', $bookId)
            ->where('status', 'pending')
            ->exists();
    }

    public function createReservation(User $user, int $bookId, ?string $notes = null): Reservation
    {
        return Reservation::create([
            'member_id' => $user->id,
            'book_id' => $bookId,
            'status' => 'pending',
            'notes' => $notes,
        ]);
    }
}
