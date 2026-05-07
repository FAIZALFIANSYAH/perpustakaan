<?php

namespace App\Services;

use App\Models\Borrowing;
use App\Models\BorrowingItem;
use App\Models\Fine;
use App\Models\FineConfig;
use App\Models\FinePayment;
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
            'type' => Fine::TYPE_LATE_RETURN,
            'amount' => $fineAmount,
            'paid_amount' => 0,
            'status' => Fine::STATUS_UNPAID,
            'due_date' => now()->addDays(7)->toDateString(),
            'reason' => "Late return: {$returnQuantity} book(s), " . 
                       $borrowing->due_at->diffInDays(now()) . " day(s) overdue",
        ]);
    }

    public function createLostBookFine(Borrowing $borrowing, BorrowingItem $item, int $lostQuantity, ?string $notes = null): Fine
    {
        $config = FineConfig::getOrCreateDefault();

        $fineAmount = (float) $config->lost_book_fine * $lostQuantity;

        // Lost-book fine follows lost-book rule and borrowing-level cap (not late-return per-item cap).
        if (! empty($config->max_fine_per_borrowing)) {
            $fineAmount = min($fineAmount, (float) $config->max_fine_per_borrowing);
        }

        if (! empty($config->max_fine_cap)) {
            $fineAmount = min($fineAmount, (float) $config->max_fine_cap);
        }

        // Use configurable payment deadline
        $dueDate = now()->addDays($config->lost_book_payment_deadline)->toDateString();

        return $this->fineRepository->createFine([
            'borrowing_item_id' => $item->id,
            'member_id' => $borrowing->member_id,
            'type' => Fine::TYPE_LOST_BOOK,
            'amount' => $fineAmount,
            'paid_amount' => 0,
            'status' => Fine::STATUS_UNPAID,
            'due_date' => $dueDate,
            'reason' => "Lost book: {$lostQuantity} book(s)",
            'notes' => $notes,
        ]);
    }

    public function processFinePayment(int $fineId, array $data): FinePayment
    {
        $fine = $this->fineRepository->findById($fineId);
        
        if (! $fine) {
            throw new \Exception('Fine not found');
        }

        if ($fine->status === Fine::STATUS_PAID) {
            throw ValidationException::withMessages([
                'fine' => 'Fine is already paid.',
            ]);
        }

        $amount = (float) ($data['amount'] ?? 0);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be greater than zero.',
            ]);
        }

        $remainingAmount = (float) $fine->amount - (float) $fine->paid_amount;
        if ($amount > $remainingAmount) {
            throw ValidationException::withMessages([
                'amount' => "Maximum payable amount is {$remainingAmount}.",
            ]);
        }

        return DB::transaction(function () use ($fine, $data, $amount) {
            $paidBy = $data['paid_by'] ?? $fine->member_id;

            $payment = $this->fineRepository->createPayment([
                'fine_id' => $fine->id,
                'amount' => $amount,
                'payment_method' => $data['payment_method'],
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'notes' => $data['notes'] ?? null,
                'processed_by' => $data['processed_by'] ?? null,
                'paid_by' => $paidBy,
            ]);

            $newPaidAmount = (float) $fine->paid_amount + $amount;
            $newStatus = $newPaidAmount >= (float) $fine->amount ? Fine::STATUS_PAID : Fine::STATUS_PARTIAL;

            $this->fineRepository->updateFine($fine, [
                'paid_amount' => $newPaidAmount,
                'status' => $newStatus,
                'paid_at' => $newStatus === 'paid' ? now()->toDateString() : null,
            ]);

            if ($fine->borrowingItem?->borrowing) {
                $borrowingService = app(BorrowingService::class);
                $borrowingService->updateBorrowingStatusAfterPayment($fine->borrowingItem->borrowing);
            }

            return $payment->fresh();
        });
    }

    public function handleLostBook(Borrowing $borrowing, BorrowingItem $item, int $lostQuantity, ?string $notes = null): array
    {
        return DB::transaction(function () use ($borrowing, $item, $lostQuantity, $notes) {
            if ((int) $item->borrowing_id !== (int) $borrowing->id) {
                throw ValidationException::withMessages([
                    'borrowing_item' => 'Borrowing item does not belong to selected borrowing.',
                ]);
            }

            $remaining = (int) $item->quantity - (int) $item->returned_quantity;
            if ($lostQuantity < 1 || $lostQuantity > $remaining) {
                throw ValidationException::withMessages([
                    'lost_quantity' => "Lost quantity must be between 1 and {$remaining}.",
                ]);
            }

            $fine = $this->createLostBookFine($borrowing, $item, $lostQuantity, $notes);

            // Lost book is treated as "accounted for", so returned_quantity is increased.
            $item->update([
                'returned_quantity' => (int) $item->returned_quantity + $lostQuantity,
                'notes' => trim(($item->notes ? $item->notes . ' | ' : '') . "Lost {$lostQuantity} book(s)." . ($notes ? " {$notes}" : '')),
            ]);

            $freshBorrowing = $borrowing->fresh('items');
            $allAccounted = $freshBorrowing->items->every(function (BorrowingItem $borrowingItem) {
                return (int) $borrowingItem->returned_quantity >= (int) $borrowingItem->quantity;
            });

            if ($allAccounted) {
                $borrowingService = app(BorrowingService::class);
                $borrowingService->updateBorrowingStatusBasedOnFines($freshBorrowing);
            } else {
                $freshBorrowing->update(['status' => Borrowing::STATUS_PARTIAL]);
            }

            return [
                'fine' => $fine->fresh(['borrowingItem.book', 'member']),
                'borrowing' => $borrowing->fresh(['items.book']),
            ];
        });
    }

    public function getAllFines(?string $search = null, ?string $status = null)
    {
        $this->applyPendingPenalties();
        return $this->fineRepository->getAllFines($search, $status);
    }

    public function getMemberFines(int $memberId)
    {
        $this->applyPendingPenalties($memberId);
        return $this->fineRepository->getMemberFines($memberId);
    }

    public function getMemberFinesWithVerification(int $memberId)
    {
        $this->applyPendingPenalties($memberId);
        return $this->fineRepository->getMemberFinesWithVerification($memberId);
    }

    public function getUnpaidFinesByMember(int $memberId)
    {
        $this->applyPendingPenalties($memberId);
        return $this->fineRepository->getUnpaidFinesByMember($memberId);
    }

    public function getTotalUnpaidFines(int $memberId): float
    {
        $this->applyPendingPenalties($memberId);
        return $this->fineRepository->getTotalUnpaidFines($memberId);
    }

    public function getFineStatistics(): array
    {
        $this->applyPendingPenalties();
        return $this->fineRepository->getFineStatistics();
    }

    public function getMemberFineStatistics(int $memberId): array
    {
        $this->applyPendingPenalties($memberId);
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


    /**
     * Calculate penalty amount for overdue fine
     */
    public function calculatePenaltyAmount(float $originalFine, int $daysOverdue): float
    {
        $penaltyConfig = \App\Models\PenaltyConfig::getActiveConfig();
        
        if (!$penaltyConfig || !$penaltyConfig->isPenaltyEnabled()) {
            return 0;
        }

        if (!$penaltyConfig->shouldApplyPenalty($daysOverdue)) {
            return 0;
        }

        return $originalFine * $penaltyConfig->penalty_multiplier;
    }

    /**
     * Check if penalty should be applied
     */
    public function shouldApplyPenalty(int $daysOverdue): bool
    {
        $penaltyConfig = \App\Models\PenaltyConfig::getActiveConfig();
        
        if (!$penaltyConfig || !$penaltyConfig->isPenaltyEnabled()) {
            return false;
        }

        return $penaltyConfig->shouldApplyPenalty($daysOverdue);
    }

    /**
     * Get penalty threshold day
     */
    public function getPenaltyThresholdDay(): int
    {
        $penaltyConfig = \App\Models\PenaltyConfig::getActiveConfig();
        
        if (!$penaltyConfig) {
            return 4; // Default
        }

        return $penaltyConfig->getPenaltyThresholdDay();
    }

    /**
     * Create penalty fine for late return
     */
    public function createPenaltyFine(\App\Models\Fine $originalFine, int $daysOverdue): ?\App\Models\Fine
    {
        if (!$this->shouldApplyPenalty($daysOverdue)) {
            return null;
        }

        $penaltyAmount = $this->calculatePenaltyAmount($originalFine->amount, $daysOverdue);

        if ($penaltyAmount <= 0) {
            return null;
        }

        $reasonTag = "[SRC_FINE_ID:{$originalFine->id}]";

        // Check if penalty fine already exists for this original fine
        $existingPenalty = \App\Models\Fine::where("borrowing_item_id", $originalFine->borrowing_item_id)
            ->where("type", "penalty")
            ->where('reason', 'like', "%{$reasonTag}%")
            ->whereIn('status', [Fine::STATUS_UNPAID, Fine::STATUS_PARTIAL])
            ->first();

        if ($existingPenalty) {
            if ((float) $existingPenalty->amount !== (float) $penaltyAmount) {
                $existingPenalty->update([
                    'amount' => $penaltyAmount,
                ]);
            }
            return $existingPenalty->fresh();
        }

        return $this->fineRepository->createFine([
            "borrowing_item_id" => $originalFine->borrowing_item_id,
            "member_id" => $originalFine->member_id,
            "type" => Fine::TYPE_PENALTY,
            "amount" => $penaltyAmount,
            "paid_amount" => 0,
            "status" => Fine::STATUS_UNPAID,
            "due_date" => now()->addDays(7)->toDateString(),
            "reason" => "{$reasonTag} Penalty applied: {$daysOverdue} day(s) overdue.",
            "notes" => "Original fine: Rp {$originalFine->amount}, Penalty multiplier: " . \App\Models\PenaltyConfig::getActiveConfig()->penalty_multiplier,
        ]);
    }

    public function applyPendingPenalties(?int $memberId = null): int
    {
        $query = Fine::query()
            ->with(['borrowingItem.borrowing'])
            ->whereIn('status', [Fine::STATUS_UNPAID, Fine::STATUS_PARTIAL])
            ->whereIn('type', [Fine::TYPE_LATE_RETURN, Fine::TYPE_LOST_BOOK]);

        if ($memberId !== null) {
            $query->where('member_id', $memberId);
        }

        $applied = 0;
        $fines = $query->get();

        foreach ($fines as $fine) {
            $borrowing = $fine->borrowingItem?->borrowing;
            if (! $borrowing) {
                continue;
            }

            $daysOverdue = match ($fine->type) {
                Fine::TYPE_LOST_BOOK => \Illuminate\Support\Carbon::parse($fine->due_date)->startOfDay()->diffInDays(now()->startOfDay(), false),
                default => $borrowing->due_at->startOfDay()->diffInDays(now()->startOfDay(), false),
            };

            if (! $this->shouldApplyPenalty($daysOverdue)) {
                continue;
            }

            $penaltyFine = $this->createPenaltyFine($fine, $daysOverdue);
            if ($penaltyFine) {
                $applied++;
            }
        }

        return $applied;
    }

    /**
     * Process payment with penalty consideration
     */
    public function processPaymentWithPenalty(int $fineId, array $paymentData): \App\Models\FinePayment
    {
        $fine = \App\Models\Fine::findOrFail($fineId);
        
        if ($fine->status === Fine::STATUS_PAID) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                "fine" => "Fine has already been paid."
            ]);
        }
        
        return \Illuminate\Support\Facades\DB::transaction(function () use ($fine, $paymentData) {
            // Calculate days overdue
            $borrowingItem = $fine->borrowingItem;
            $borrowing = $borrowingItem->borrowing;
            $daysOverdue = $borrowing->due_at->diffInDays(now(), false);
            
            // Check if penalty should be applied
            if ($this->shouldApplyPenalty($daysOverdue)) {
                // Create penalty fine if it doesn't exist
                $penaltyFine = $this->createPenaltyFine($fine, $daysOverdue);
                
                if ($penaltyFine) {
                    // Update borrowing status to indicate penalty
                    $borrowing->update(["status" => Borrowing::STATUS_COMPLETE_WITH_PENALTY]);
                }
            }
            
            // Process original payment
            $payment = \App\Models\FinePayment::create([
                "fine_id" => $fine->id,
                "paid_by" => $fine->member_id,
                "amount" => $paymentData["amount"],
                "payment_method" => $paymentData["payment_method"] ?? "cash",
                "payment_date" => $paymentData["payment_date"] ?? now(),
                "notes" => $paymentData["notes"] ?? null,
                "processed_by" => $paymentData["processed_by"] ?? null,
            ]);
            
            // Update fine status to paid
            $fine->update(["status" => Fine::STATUS_PAID]);
            
            // Update borrowing status based on penalty presence
            $hasPenalty = \App\Models\Fine::where("borrowing_item_id", $borrowingItem->id)
                ->where("type", Fine::TYPE_PENALTY)
                ->where("status", Fine::STATUS_UNPAID)
                ->exists();
            
            if ($hasPenalty) {
                $borrowing->update(["status" => Borrowing::STATUS_COMPLETE_WITH_PENALTY]);
            } else {
                $borrowing->update(["status" => Borrowing::STATUS_COMPLETE]);
            }
            
            return $payment->load(["fine", "fine.member", "fine.borrowingItem.book"]);
        });
    }
}
