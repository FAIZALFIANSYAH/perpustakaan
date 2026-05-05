<?php

namespace App\Services;

use App\Models\Borrowing;
use App\Models\BorrowingItem;
use App\Models\Fine;
use App\Models\FineConfig;
use App\Repositories\FineRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FineService
{
    public function __construct(
        protected FineRepository $fineRepository
    ) {}

    public function getFineConfig(): ?FineConfig
    {
        return $this->fineRepository->getActiveFineConfig();
    }

    public function updateFineConfig(array $data): FineConfig
    {
        $config = FineConfig::getOrCreateDefault();

        $this->fineRepository->updateFineConfig($config, $data);

        return $config->fresh();
    }

    public function calculateLateFine(Borrowing $borrowing, BorrowingItem $item, int $returnQuantity): float
    {
        $config = FineConfig::getOrCreateDefault();

        $dueDate = $borrowing->due_at;
        $returnDate = now();

        // Calculate days late (considering grace period)
        $daysLate = $dueDate->startOfDay()->diffInDays($returnDate->startOfDay(), false);
        $daysLate = max(0, $daysLate - $config->grace_period_days);

        if ($daysLate <= 0) {
            return 0;
        }

        // Apply maximum billable days cap (capped system)
        $daysLate = min($daysLate, $config->max_billable_days);

        $fineAmount = $daysLate * (float) $config->fine_per_day * $returnQuantity;

        // Apply per-item maximum
        $maxPerItem = (float) $config->max_fine_per_item * $returnQuantity;
        $fineAmount = min($fineAmount, $maxPerItem);

        // Apply legacy max cap if configured
        if ($config->max_fine_cap && $fineAmount > $config->max_fine_cap) {
            $fineAmount = (float) $config->max_fine_cap;
        }

        return $fineAmount;
    }

    public function createLateReturnFine(Borrowing $borrowing, BorrowingItem $item, int $returnQuantity): ?Fine
    {
        $fineAmount = $this->calculateLateFine($borrowing, $item, $returnQuantity);

        if ($fineAmount <= 0) {
            return null;
        }

        return $this->fineRepository->createFine([
            'borrowing_item_id' => $item->id,
            'member_id' => $borrowing->member_id,
            'type' => 'late_return',
            'amount' => $fineAmount,
            'paid_amount' => 0,
            'status' => 'unpaid',
            'due_date' => now()->addDays(7)->toDateString(),
            'reason' => "Late return: {$returnQuantity} book(s), " . 
                       $borrowing->due_at->diffInDays(now()) . " day(s) overdue",
        ]);
    }

    public function createLostBookFine(Borrowing $borrowing, BorrowingItem $item, int $lostQuantity, ?string $notes = null): Fine
    {
        $config = FineConfig::getOrCreateDefault();

        $fineAmount = (float) $config->lost_book_fine * $lostQuantity;

        // Apply per-item maximum
        $maxPerItem = (float) $config->max_fine_per_item * $lostQuantity;
        $fineAmount = min($fineAmount, $maxPerItem);

        // Use configurable payment deadline
        $dueDate = now()->addDays($config->lost_book_payment_deadline)->toDateString();

        return $this->fineRepository->createFine([
            'borrowing_item_id' => $item->id,
            'member_id' => $borrowing->member_id,
            'type' => 'lost_book',
            'amount' => $fineAmount,
            'paid_amount' => 0,
            'status' => 'unpaid',
            'due_date' => $dueDate,
            'reason' => "Lost book: {$lostQuantity} book(s)",
            'notes' => $notes,
        ]);
    }

    public function processFinePayment(int $fineId, array $paymentData): FinePayment
    {
        $fine = Fine::findOrFail($fineId);
        
        if ($fine->status === 'paid') {
            throw ValidationException::withMessages([
                'fine' => 'Fine has already been paid.'
            ]);
        }
        
        // Create payment record
        $payment = FinePayment::create([
            'fine_id' => $fineId,
            'amount' => $paymentData['amount'],
            'payment_method' => $paymentData['payment_method'] ?? 'cash',
            'payment_date' => $paymentData['payment_date'] ?? now(),
            'notes' => $paymentData['notes'] ?? null,
            'processed_by' => $paymentData['processed_by'] ?? null,
        ]);
        
        // Update fine status to paid
        $fine->update(['status' => 'paid']);
        
        return $payment->load(['fine', 'fine.member', 'fine.borrowingItem.book']);
    }

    }

            // Create payment record
            $this->fineRepository->createFinePayment([
                'fine_id' => $fine->id,
                'paid_by' => $fine->member_id,
                'processed_by' => $processedBy,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'notes' => $notes,
            ]);

            // Update fine paid amount
            $newPaidAmount = (float) $fine->paid_amount + $amount;
            $newStatus = $newPaidAmount >= (float) $fine->amount ? 'paid' : 'partial';

            $updateData = [
                'paid_amount' => $newPaidAmount,
                'status' => $newStatus,
            ];

            if ($newStatus === 'paid') {
                $updateData['paid_at'] = now()->toDateString();
            }

            $this->fineRepository->updateFine($fine, $updateData);

            // If fine is fully paid, check if borrowing status should be updated
            if ($newStatus === 'paid' && $fine->borrowingItem) {
                $borrowing = $fine->borrowingItem->borrowing;
                if ($borrowing) {
                    // Use BorrowingService to update status based on new logic
                    $borrowingService = app(\App\Services\BorrowingService::class);
                    $borrowingService->updateBorrowingStatusAfterPayment($borrowing);
                }
            }

            return $fine->fresh(['payments', 'borrowingItem.book', 'member']);
        });
    }

    public function handleLostBook(Borrowing $borrowing, BorrowingItem $item, int $lostQuantity, ?string $notes = null): array
    {
        return DB::transaction(function () use ($borrowing, $item, $lostQuantity, $notes) {
            // Create lost book fine
            $fine = $this->createLostBookFine($borrowing, $item, $lostQuantity, $notes);

            // Update borrowing item quantity
            $item->update([
                'lost_quantity' => ($item->lost_quantity ?? 0) + $lostQuantity,
                'quantity' => $item->quantity - $lostQuantity,
                'returned_quantity' => $item->returned_quantity + $lostQuantity, // Mark as returned for lost tracking
            ]);

            // Use BorrowingService to update status based on new logic
            $borrowingService = app(\App\Services\BorrowingService::class);
            $borrowingService->updateBorrowingStatusBasedOnFines($borrowing);
            // Check if all items are now accounted for (returned or lost)
            $this->syncBorrowingStatus($borrowing);

            return [
                'fine' => $fine->fresh(['borrowingItem.book', 'member']),
                'borrowing' => $borrowing->fresh(['items.book']),
            ];
        });
    }

    protected function syncBorrowingStatus(Borrowing $borrowing): void
    {
        $totalQuantity = $borrowing->items->sum('quantity');
        $returnedQuantity = $borrowing->items->sum('returned_quantity');

        // Check if there are any unpaid fines for this borrowing
        $hasUnpaidFines = Fine::whereHas('borrowingItem', function ($query) use ($borrowing) {
            $query->where('borrowing_id', $borrowing->id);
        })->whereIn('status', ['unpaid', 'partial'])->exists();

        if ($returnedQuantity >= $totalQuantity) {
            // All items returned or marked as lost
            if ($hasUnpaidFines) {
                // If there are unpaid fines, mark as awaiting_fine_payment
                $borrowing->update([
                    'status' => 'awaiting_fine_payment',
                    'returned_at' => $borrowing->returned_at ?? now()->toDateString(),
                ]);
            } else {
                // No fines or all fines paid, mark as complete
                $borrowing->update([
                    'status' => 'complete',
                    'returned_at' => $borrowing->returned_at ?? now()->toDateString(),
                ]);
            }
        } elseif ($returnedQuantity > 0) {
            $borrowing->update(['status' => 'partial']);
        }
    }

    public function getAllFines(?string $search = null, ?string $status = null)
    {
        return $this->fineRepository->getAllFines($search, $status);
    }

    public function getMemberFines(int $memberId)
    {
        return $this->fineRepository->getMemberFines($memberId);
    }

    public function getMemberFinesWithVerification(int $memberId)
    {
        return $this->fineRepository->getMemberFinesWithVerification($memberId);
    }

    public function getUnpaidFinesByMember(int $memberId)
    {
        return $this->fineRepository->getUnpaidFinesByMember($memberId);
    }

    public function getTotalUnpaidFines(int $memberId): float
    {
        return $this->fineRepository->getTotalUnpaidFines($memberId);
    }

    public function getFineStatistics(): array
    {
        return $this->fineRepository->getFineStatistics();
    }

    public function getMemberFineStatistics(int $memberId): array
    {
        return $this->fineRepository->getMemberFineStatistics($memberId);
    }

    public function canMemberBorrow(int $memberId): bool
    {
        // Member cannot borrow if they have unpaid fines
        return $this->getTotalUnpaidFines($memberId) <= 0;
    }

    public function getMemberBorrowingBlockReason(int $memberId): ?string
    {
        $unpaidFines = $this->getTotalUnpaidFines($memberId);

        if ($unpaidFines > 0) {
            return "You have unpaid fines totaling Rp " . number_format($unpaidFines, 0, ',', '.');
        }

        return null;
    }
}
