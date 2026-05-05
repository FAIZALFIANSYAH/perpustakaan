<?php

namespace App\Repositories;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\BorrowingItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class BorrowingRepository
{
    public function getAll(?string $search = null): Collection
    {
        return Borrowing::query()
            ->with(['member', 'processedBy', 'items.book'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhereHas('member', function ($mq) use ($search) {
                            $mq->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('items.book', function ($bq) use ($search) {
                            $bq->where('title', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->get();
    }

    public function findById(int $id): ?Borrowing
    {
        return Borrowing::query()
            ->with(['member', 'processedBy', 'items.book'])
            ->find($id);
    }

    public function findByIdForUpdate(int $id): ?Borrowing
    {
        return Borrowing::query()
            ->with(['member', 'processedBy', 'items.book'])
            ->lockForUpdate()
            ->find($id);
    }

    public function getMembers(): Collection
    {
        return User::query()
            ->role('Member')
            ->select('id', 'name', 'email', 'borrow_limit')
            ->orderBy('name')
            ->get();
    }

    public function countActiveBorrowings(int $memberId): int
    {
        return Borrowing::query()
            ->where('member_id', $memberId)
            ->whereIn('status', ['borrowed', 'partial'])
            ->count();
    }

    public function getAvailableBooks(): Collection
    {
        return Book::query()
            ->with('category:id,name')
            ->select('id', 'category_id', 'title', 'author', 'stock', 'is_active')
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->orderBy('title')
            ->get();
    }

    public function getBooksByIdsForUpdate(array $bookIds): SupportCollection
    {
        return Book::query()
            ->select('id', 'title', 'stock', 'is_active')
            ->whereIn('id', $bookIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    public function create(array $data): Borrowing
    {
        return Borrowing::create($data);
    }

    public function createItems(Borrowing $borrowing, array $items): void
    {
        $borrowing->items()->createMany($items);
    }

    public function updateBookStock(Book $book, int $stock): bool
    {
        return $book->update([
            'stock' => $stock,
        ]);
    }

    public function updateBorrowing(Borrowing $borrowing, array $data): bool
    {
        return $borrowing->update($data);
    }

    public function updateBorrowingItem(BorrowingItem $item, array $data): bool
    {
        return $item->update($data);
    }
}
