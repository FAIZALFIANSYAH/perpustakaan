<?php

namespace App\Services;

use App\Models\Borrowing;
use App\Models\BorrowingItem;
use App\Models\Fine;
use App\Models\User;
use App\Repositories\BorrowingRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class BorrowingService
{
    public function __construct(
        protected BorrowingRepository $borrowingRepository,
        protected FineService $fineService,
        protected BorrowingStatusPolicy $borrowingStatusPolicy
    ) {}

    public function getAllBorrowings(?string $search = null): Collection
    {
        return $this->borrowingRepository->getAll($search);
    }

    public function findBorrowingById(int $id): ?Borrowing
    {
        return $this->borrowingRepository->findById($id);
    }

    public function getBorrowingFormData(): array
    {
        return [
            'members' => $this->borrowingRepository->getMembers(),
            'books' => $this->borrowingRepository->getAvailableBooks(),
        ];
    }

    public function createBorrowing(array $data, int $processedBy): Borrowing
    {
        return DB::transaction(function () use ($data, $processedBy) {
            $this->ensureMemberBorrowLimit($data['member_id']);

            $this->ensureBookStocksAreAvailable($data['items']);

            $borrowing = $this->borrowingRepository->create($this->buildBorrowingPayload($data, $processedBy));

            $this->borrowingRepository->createItems($borrowing, $this->buildItemPayloads($data['items']));
            $this->decreaseBookStocks($data['items']);

            // Check if borrowing is overdue and process accordingly
            if ($borrowing->due_at < now()) {
                $this->processOverdueBorrowing($borrowing);
            }

            return $borrowing->load(['member', 'processedBy', 'items.book']);
        });
    }

    public function returnBorrowing(Borrowing $borrowing, array $items): Borrowing
    {
        return DB::transaction(function () use ($borrowing, $items) {
            $lockedBorrowing = $this->borrowingRepository->findByIdForUpdate($borrowing->id);

            if (! $lockedBorrowing) {
                throw ValidationException::withMessages([
                    'borrowing' => 'Borrowing transaction not found.',
                ]);
            }

            if ($lockedBorrowing->status === Borrowing::STATUS_RETURNED) {
                throw ValidationException::withMessages([
                    'borrowing' => 'This borrowing has already been fully returned.',
                ]);
            }

            $this->processReturnedItems($lockedBorrowing, $items);
            $this->syncBorrowingReturnStatus($lockedBorrowing);

            return $lockedBorrowing->load(['member', 'processedBy', 'items.book']);
        });
    }

    protected function ensureMemberBorrowLimit(int $memberId): void
    {
        $member = User::find($memberId);

        if (! $member) {
            throw ValidationException::withMessages([
                'member_id' => 'Selected member not found.',
            ]);
        }

        // Check for unpaid fines
        if (! $this->fineService->canMemberBorrow($memberId)) {
            $blockReason = $this->fineService->getMemberBorrowingBlockReason($memberId);
            throw ValidationException::withMessages([
                'member_id' => $blockReason ?? 'Member has unpaid fines.',
            ]);
        }

        $activeCount = $this->borrowingRepository->countActiveBorrowings($memberId);
        $limit = $member->borrow_limit ?? 3;

        if ($activeCount >= $limit) {
            throw ValidationException::withMessages([
                'member_id' => "Member has reached the borrowing limit of {$limit} book(s). Current active borrowings: {$activeCount}.",
            ]);
        }
    }

    protected function ensureBookStocksAreAvailable(array $items): void
    {
        $books = $this->borrowingRepository->getBooksByIdsForUpdate(array_column($items, 'book_id'));

        foreach ($items as $index => $item) {
            $book = $books->get($item['book_id']);

            if (! $book || ! $book->is_active) {
                throw ValidationException::withMessages([
                    "items.$index.book_id" => 'Selected book is not available for borrowing.',
                ]);
            }

            if ($item['quantity'] > $book->stock) {
                throw ValidationException::withMessages([
                    "items.$index.quantity" => "Available stock for {$book->title} is only {$book->stock}.",
                ]);
            }
        }
    }

    protected function decreaseBookStocks(array $items): void
    {
        $books = $this->borrowingRepository->getBooksByIdsForUpdate(array_column($items, 'book_id'));

        foreach ($items as $item) {
            $book = $books->get($item['book_id']);

            $this->borrowingRepository->updateBookStock(
                $book,
                $book->stock - $item['quantity'],
            );
        }
    }

    protected function processReturnedItems(Borrowing $borrowing, array $items): void
    {
        $itemsById = $borrowing->items->keyBy('id');
        $bookIds = $borrowing->items->pluck('book_id')->all();
        $books = $this->borrowingRepository->getBooksByIdsForUpdate($bookIds);

        foreach ($items as $itemData) {
            $returnQuantity = (int) $itemData['return_quantity'];

            if ($returnQuantity < 1) {
                continue;
            }

            /** @var BorrowingItem|null $borrowingItem */
            $borrowingItem = $itemsById->get($itemData['id']);

            if (! $borrowingItem) {
                continue;
            }

            $book = $books->get($borrowingItem->book_id);

            $this->borrowingRepository->updateBorrowingItem($borrowingItem, [
                'returned_quantity' => $borrowingItem->returned_quantity + $returnQuantity,
            ]);

            $this->borrowingRepository->updateBookStock(
                $book,
                $book->stock + $returnQuantity,
            );

            // Calculate and create fine for late return
            $fine = $this->fineService->createLateReturnFine($borrowing, $borrowingItem, $returnQuantity);

            // Store fine info in item notes if created
            if ($fine) {
                $existingNotes = $borrowingItem->notes ? $borrowingItem->notes . ' | ' : '';
                $this->borrowingRepository->updateBorrowingItem($borrowingItem, [
                    'notes' => $existingNotes . 'Fine created: Rp ' . number_format($fine->amount, 0, ',', '.') . ' (' . $fine->type . ')',
                ]);
            }
        }

        $borrowing->load('items');
    }

    protected function syncBorrowingReturnStatus(Borrowing $borrowing): void
    {
        $totalQuantity = $borrowing->items->sum('quantity');
        $returnedQuantity = $borrowing->items->sum('returned_quantity');

        if ($returnedQuantity === 0) {
            return;
        }

        if ($returnedQuantity >= $totalQuantity) {
            // Check fine status to determine final status
            $this->updateBorrowingStatusBasedOnFines($borrowing);
            return;
        }

        $this->borrowingRepository->updateBorrowing($borrowing, [
            'status' => Borrowing::STATUS_PARTIAL,
        ]);
    }

    public function updateBorrowingStatusBasedOnFines(Borrowing $borrowing): void
    {
        $borrowing->load('items.fines');
        
        $hasUnpaidFines = false;
        $hasLostBookFines = false;
        
        foreach ($borrowing->items as $item) {
            foreach ($item->fines as $fine) {
                if ($fine->status === Fine::STATUS_UNPAID) {
                    $hasUnpaidFines = true;
                }
                if ($fine->type === Fine::TYPE_LOST_BOOK) {
                    $hasLostBookFines = true;
                }
            }
        }
        
        $newStatus = $this->borrowingStatusPolicy->afterReturnSettlement(
            $borrowing,
            $hasUnpaidFines,
            $hasLostBookFines
        );
        
        $this->borrowingRepository->updateBorrowing($borrowing, [
            'status' => $newStatus,
            'returned_at' => now()->toDateString(),
        ]);
    }

    public function updateBorrowingStatusAfterPayment(Borrowing $borrowing): void
    {
        $borrowing->load('items.fines');
        
        $allItemsReturned = $borrowing->items->every(function ($item) {
            return $item->returned_quantity >= $item->quantity;
        });
        
        $hasUnpaidFines = $borrowing->items->flatMap->fines->contains('status', Fine::STATUS_UNPAID);
        
        $newStatus = $this->borrowingStatusPolicy->afterFinePayment($allItemsReturned, $hasUnpaidFines);
        
        $this->borrowingRepository->updateBorrowing($borrowing, [
            'status' => $newStatus,
        ]);
    }

    public function checkAndUpdateOverdueStatus(): void
    {
        $overdueBorrowings = \App\Models\Borrowing::where('due_at', '<', now())
            ->whereIn('status', [Borrowing::STATUS_BORROWED, Borrowing::STATUS_PARTIAL])
            ->get();
            
        foreach ($overdueBorrowings as $borrowing) {
            $borrowing->load('items.fines');
            $hasUnpaidFines = $borrowing->items->flatMap->fines->contains('status', Fine::STATUS_UNPAID);
            
            $nextStatus = $this->borrowingStatusPolicy->markOverdueForOpenBorrowing($borrowing, $hasUnpaidFines);
            if ($nextStatus !== null) {
                $this->borrowingRepository->updateBorrowing($borrowing, [
                    'status' => $nextStatus
                ]);
            }
        }
    }

    protected function buildBorrowingPayload(array $data, int $processedBy): array
    {
        return [
            'code' => $this->generateBorrowingCode(),
            'member_id' => $data['member_id'],
            'processed_by' => $processedBy,
            'borrowed_at' => $data['borrowed_at'],
            'due_at' => $data['due_at'],
            'status' => Borrowing::STATUS_BORROWED,
            'notes' => $data['notes'] ?? null,
        ];
    }

    protected function buildItemPayloads(array $items): array
    {
        return array_map(function (array $item) {
            return [
                'book_id' => $item['book_id'],
                'quantity' => $item['quantity'],
                'returned_quantity' => 0,
                'notes' => $item['notes'] ?? null,
            ];
        }, $items);
    }

    protected function generateBorrowingCode(): string
    {
        $datePrefix = now()->format('Ymd');
        $count = Borrowing::query()
            ->whereDate('created_at', now()->toDateString())
            ->count() + 1;

        return sprintf('BRW-%s-%04d', $datePrefix, $count);
    }

    private function processOverdueBorrowing(Borrowing $borrowing): void
    {
        $fineService = app(\App\Services\FineService::class);
        
        // Generate fines for each overdue item
        foreach ($borrowing->items as $item) {
            if ($item->fines()->count() === 0) {
                $fine = $fineService->createLateReturnFine($borrowing, $item, $item->quantity);
                if ($fine) {
                    // Update borrowing status to overdue
                    $borrowing->update(['status' => Borrowing::STATUS_OVERDUE]);
                }
            }
        }
    }


    /**
     * Check and apply penalty for overdue borrowing
     */
    public function checkAndApplyPenalty(\App\Models\Borrowing $borrowing): void
    {
        $daysOverdue = $borrowing->due_at->diffInDays(now(), false);
        
        if ($daysOverdue <= 0) {
            return;
        }

        $fineService = app(\App\Services\FineService::class);
        
        if ($fineService->shouldApplyPenalty($daysOverdue)) {
            // Get existing fines for this borrowing
            $fines = \App\Models\Fine::whereHas("borrowingItem", function ($query) use ($borrowing) {
                $query->where("borrowing_id", $borrowing->id);
            })->where("type", Fine::TYPE_LATE_RETURN)->get();

            foreach ($fines as $fine) {
                $penaltyFine = $fineService->createPenaltyFine($fine, $daysOverdue);
                
                if ($penaltyFine) {
                    $borrowing->update(["status" => Borrowing::STATUS_COMPLETE_WITH_PENALTY]);
                }
            }
        }
    }

    /**
     * Get penalty status for borrowing
     */
    public function getPenaltyStatus(\App\Models\Borrowing $borrowing): array
    {
        $daysOverdue = $borrowing->due_at->diffInDays(now(), false);
        $fineService = app(\App\Services\FineService::class);
        
        return [
            "days_overdue" => $daysOverdue,
            "penalty_threshold" => $fineService->getPenaltyThresholdDay(),
            "should_apply_penalty" => $fineService->shouldApplyPenalty($daysOverdue),
            "penalty_multiplier" => \App\Models\PenaltyConfig::getActiveConfig()->penalty_multiplier ?? 2.0,
        ];
    }}
