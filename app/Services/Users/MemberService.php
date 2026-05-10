<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use App\Repositories\MemberRepository;
use App\Services\FineService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class MemberService
{
    public function __construct(
        protected MemberRepository $memberRepository,
        protected BorrowingService $borrowingService,
        protected FineService $fineService,
    ) {}

    public function getDashboardData(User $user): array
    {
        $activeBorrowings = $this->memberRepository->getActiveBorrowings($user);
        $reservations = $this->memberRepository->getReservations($user);
        $totalUnpaidFines = $this->fineService->getTotalUnpaidFines($user->id);

        return [
            'summary' => [
                ...$this->memberRepository->getBorrowingSummary($user),
                'reservations' => $reservations->where('status', 'pending')->count(),
                'unpaid_fines' => $totalUnpaidFines,
            ],
            'activeBorrowings' => $activeBorrowings,
            'notifications' => $this->buildBorrowingNotifications($activeBorrowings),
            'hasUnpaidFines' => $totalUnpaidFines > 0,
            'fineBlockMessage' => $this->fineService->getMemberBorrowingBlockReason($user->id),
        ];
    }

    public function getBorrowingHistory(User $user): Collection
    {
        return $this->memberRepository->getBorrowingHistory($user);
    }

    public function getCatalogData(?string $search = null, ?int $categoryId = null): array
    {
        return [
            'books' => $this->memberRepository->getCatalogBooks($search, $categoryId),
            'categories' => $this->memberRepository->getCatalogCategories(),
            'filters' => [
                'search' => $search,
                'category_id' => $categoryId,
            ],
        ];
    }

    public function getCatalogBookDetail(int $bookId): ?Book
    {
        return $this->memberRepository->getCatalogBookById($bookId);
    }

    public function borrowBook(User $user, Book $book): Borrowing
    {
        if (! $book->is_active) {
            throw ValidationException::withMessages([
                'book' => 'This book is currently unavailable.',
            ]);
        }

        if ($book->stock < 1) {
            throw ValidationException::withMessages([
                'book' => 'This book is out of stock.',
            ]);
        }

        if ($this->memberRepository->hasActiveBorrowingForBook($user, $book->id)) {
            throw ValidationException::withMessages([
                'book' => 'You already have an active borrowing for this book.',
            ]);
        }

        return $this->borrowingService->createBorrowing([
            'member_id' => $user->id,
            'borrowed_at' => now()->toDateString(),
            'due_at' => now()->addDays(7)->toDateString(),
            'notes' => 'Self-service borrowing by member',
            'items' => [
                [
                    'book_id' => $book->id,
                    'quantity' => 1,
                    'notes' => null,
                ],
            ],
        ], $user->id);
    }

    public function reserveBook(User $user, Book $book): void
    {
        if (! $book->is_active) {
            throw ValidationException::withMessages([
                'book' => 'This book is currently unavailable.',
            ]);
        }

        if ($this->memberRepository->hasActiveBorrowingForBook($user, $book->id)) {
            throw ValidationException::withMessages([
                'book' => 'You already have an active borrowing for this book.',
            ]);
        }

        if ($this->memberRepository->hasPendingReservation($user, $book->id)) {
            throw ValidationException::withMessages([
                'book' => 'You already have a pending reservation for this book.',
            ]);
        }

        $this->memberRepository->createReservation($user, $book->id);
    }

    public function getReservations(User $user): Collection
    {
        return $this->memberRepository->getReservations($user);
    }

    public function hasActiveBorrowingForBook(User $user, int $bookId): bool
    {
        return $this->memberRepository->hasActiveBorrowingForBook($user, $bookId);
    }

    public function hasPendingReservationForBook(User $user, int $bookId): bool
    {
        return $this->memberRepository->hasPendingReservation($user, $bookId);
    }

    protected function buildBorrowingNotifications(Collection $activeBorrowings): array
    {
        return $activeBorrowings
            ->map(function ($borrowing) {
                $daysUntilDue = now()->startOfDay()->diffInDays($borrowing->due_at, false);

                if ($daysUntilDue < 0) {
                    return [
                        'type' => 'late',
                        'message' => "Borrowing {$borrowing->code} is overdue since {$borrowing->due_at->format('Y-m-d')}.",
                    ];
                }

                if ($daysUntilDue <= 3) {
                    return [
                        'type' => 'warning',
                        'message' => "Borrowing {$borrowing->code} is due in {$daysUntilDue} day(s).",
                    ];
                }

                return null;
            })
            ->filter()
            ->values()
            ->all();
    }
}
