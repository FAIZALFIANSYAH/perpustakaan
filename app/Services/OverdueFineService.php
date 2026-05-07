<?php

namespace App\Services;

use App\Models\Borrowing;
use App\Models\BorrowingItem;
use App\Models\Fine;
use App\Repositories\FineRepository;
use Illuminate\Support\Facades\DB;

class OverdueFineService
{
    public function __construct(
        protected FineService $fineService,
        protected FineRepository $fineRepository
    ) {}

    /**
     * Create fines for all overdue borrowings that don't have fines yet
     */
    public function processOverdueFines(): array
    {
        $results = [
            'processed' => 0,
            'created' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        // Get all overdue borrowings without fines
        $overdueBorrowings = Borrowing::with(['items.book', 'member'])
            ->where('due_at', '<', now())
            ->whereNotIn('status', [Borrowing::STATUS_COMPLETE, 'cancelled', Borrowing::STATUS_RETURNED])
            ->whereDoesntHave('items', function ($query) {
                $query->whereHas('fines');
            })
            ->get();

        $results['processed'] = $overdueBorrowings->count();

        foreach ($overdueBorrowings as $borrowing) {
            try {
                $created = $this->createFinesForBorrowing($borrowing);
                $results['created'] += $created;
                
                if ($created === 0) {
                    $results['skipped']++;
                }
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'borrowing_id' => $borrowing->id,
                    'member_name' => $borrowing->member->name,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Create fines for a specific overdue borrowing
     */
    public function createFinesForBorrowing(Borrowing $borrowing): int
    {
        $createdCount = 0;

        foreach ($borrowing->items as $item) {
            // Check if fine already exists for this item
            $existingFine = Fine::where('borrowing_item_id', $item->id)->first();
            
            if ($existingFine) {
                continue; // Skip if fine already exists
            }

            // Calculate fine amount
            $fineAmount = $this->fineService->calculateLateFine($borrowing, $item, $item->quantity);

            if ($fineAmount > 0) {
                // Create the fine
                $fine = $this->fineRepository->createFine([
                    'borrowing_item_id' => $item->id,
                    'member_id' => $borrowing->member_id,
                    'type' => Fine::TYPE_LATE_RETURN,
                    'amount' => $fineAmount,
                    'paid_amount' => 0,
                    'status' => Fine::STATUS_UNPAID,
                    'due_date' => now()->addDays(7)->toDateString(),
                    'reason' => "Late return: {$item->quantity} book(s), " . 
                               $borrowing->due_at->diffInDays(now()) . " day(s) overdue",
                ]);

                $createdCount++;
            }
        }

        // Update borrowing status if fines were created
        if ($createdCount > 0) {
            $this->updateBorrowingStatusWithFines($borrowing);
        }

        return $createdCount;
    }

    /**
     * Update borrowing status when fines are created
     */
    protected function updateBorrowingStatusWithFines(Borrowing $borrowing): void
    {
        $totalQuantity = $borrowing->items->sum('quantity');
        $returnedQuantity = $borrowing->items->sum('returned_quantity');

        if ($returnedQuantity >= $totalQuantity) {
            // All items returned but there are unpaid fines
            $borrowing->update([
                'status' => Borrowing::STATUS_AWAITING_FINE_PAYMENT,
                'returned_at' => $borrowing->returned_at ?? now()->toDateString(),
            ]);
        }
        // If not all returned, keep current status (borrowed/partial)
    }

    /**
     * Check if borrowing needs fine processing
     */
    public function needsFineProcessing(Borrowing $borrowing): bool
    {
        // Check if borrowing is overdue
        if ($borrowing->due_at >= now()) {
            return false;
        }

        // Check if borrowing is in a status that allows fines
        if (in_array($borrowing->status, [Borrowing::STATUS_COMPLETE, 'cancelled', Borrowing::STATUS_RETURNED], true)) {
            return false;
        }

        // Check if fines already exist
        $hasFines = Fine::whereHas('borrowingItem', function ($query) use ($borrowing) {
            $query->where('borrowing_id', $borrowing->id);
        })->exists();

        return !$hasFines;
    }

    /**
     * Get statistics of overdue borrowings that need fine processing
     */
    public function getOverdueFineStatistics(): array
    {
        $totalOverdue = Borrowing::where('due_at', '<', now())
            ->whereNotIn('status', [Borrowing::STATUS_COMPLETE, 'cancelled', Borrowing::STATUS_RETURNED])
            ->count();

        $needProcessing = Borrowing::where('due_at', '<', now())
            ->whereNotIn('status', [Borrowing::STATUS_COMPLETE, 'cancelled', Borrowing::STATUS_RETURNED])
            ->whereDoesntHave('items', function ($query) {
                $query->whereHas('fines');
            })
            ->count();

        $alreadyProcessed = $totalOverdue - $needProcessing;

        return [
            'total_overdue' => $totalOverdue,
            'need_processing' => $needProcessing,
            'already_processed' => $alreadyProcessed,
        ];
    }
}
