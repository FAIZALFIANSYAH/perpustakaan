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
        
        return DB::transaction(function () use ($fine, $paymentData) {
            // Create payment record
            $payment = FinePayment::create([
                'fine_id' => $fine->id,
                'paid_by' => $fine->member_id,
                'amount' => $paymentData['amount'],
                'payment_method' => $paymentData['payment_method'] ?? 'cash',
                'payment_date' => $paymentData['payment_date'] ?? now(),
                'notes' => $paymentData['notes'] ?? null,
                'processed_by' => $paymentData['processed_by'] ?? null,
            ]);
            
            // Update fine status to paid
            $fine->update(['status' => 'paid']);
            
            // Update borrowing status to 'complete' (book considered returned)
            $borrowingItem = $fine->borrowingItem;
            $borrowing = $borrowingItem->borrowing;
            $borrowing->update(['status' => 'complete']);
            
            return $payment->load(['fine', 'fine.member', 'fine.borrowingItem.book']);
        });
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

        // Check if penalty fine already exists
        $existingPenalty = \App\Models\Fine::where("borrowing_item_id", $originalFine->borrowing_item_id)
            ->where("type", "penalty")
            ->where("status", "unpaid")
            ->first();

        if ($existingPenalty) {
            return $existingPenalty;
        }

        return $this->fineRepository->createFine([
            "borrowing_item_id" => $originalFine->borrowing_item_id,
            "member_id" => $originalFine->member_id,
            "type" => "penalty",
            "amount" => $penaltyAmount,
            "paid_amount" => 0,
            "status" => "unpaid",
            "due_date" => now()->addDays(7)->toDateString(),
            "reason" => "Penalty for late return: {$daysOverdue} days overdue, penalty multiplier applied",
            "notes" => "Original fine: Rp {$originalFine->amount}, Penalty multiplier: " . \App\Models\PenaltyConfig::getActiveConfig()->penalty_multiplier,
        ]);
    }

    /**
     * Process payment with penalty consideration
     */
    public function processPaymentWithPenalty(int $fineId, array $paymentData): \App\Models\FinePayment
    {
        $fine = \App\Models\Fine::findOrFail($fineId);
        
        if ($fine->status === "paid") {
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
                    $borrowing->update(["status" => "complete_with_penalty"]);
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
            $fine->update(["status" => "paid"]);
            
            // Update borrowing status based on penalty presence
            $hasPenalty = \App\Models\Fine::where("borrowing_item_id", $borrowingItem->id)
                ->where("type", "penalty")
                ->where("status", "unpaid")
                ->exists();
            
            if ($hasPenalty) {
                $borrowing->update(["status" => "complete_with_penalty"]);
            } else {
                $borrowing->update(["status" => "complete"]);
            }
            
            return $payment->load(["fine", "fine.member", "fine.borrowingItem.book"]);
        });
    }}