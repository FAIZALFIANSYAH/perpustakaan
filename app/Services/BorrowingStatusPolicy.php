<?php

namespace App\Services;

use App\Models\Borrowing;

class BorrowingStatusPolicy
{
    public function afterReturnSettlement(Borrowing $borrowing, bool $hasUnpaidFines, bool $hasLostBookFines): string
    {
        if ($hasLostBookFines) {
            return $hasUnpaidFines ? Borrowing::STATUS_LOST : Borrowing::STATUS_COMPLETE;
        }

        if ($borrowing->due_at < now()) {
            return $hasUnpaidFines ? Borrowing::STATUS_OVERDUE : Borrowing::STATUS_LATE_PAYMENT;
        }

        return Borrowing::STATUS_RETURNED;
    }

    public function afterFinePayment(bool $allItemsReturned, bool $hasUnpaidFines): string
    {
        if ($allItemsReturned && ! $hasUnpaidFines) {
            return Borrowing::STATUS_COMPLETE;
        }

        if ($allItemsReturned && $hasUnpaidFines) {
            return Borrowing::STATUS_OVERDUE;
        }

        if (! $allItemsReturned && ! $hasUnpaidFines) {
            return Borrowing::STATUS_LATE_PAYMENT;
        }

        return Borrowing::STATUS_BORROWED;
    }

    public function markOverdueForOpenBorrowing(Borrowing $borrowing, bool $hasUnpaidFines): ?string
    {
        if (! in_array($borrowing->status, [Borrowing::STATUS_BORROWED, Borrowing::STATUS_PARTIAL], true)) {
            return null;
        }

        if (! $hasUnpaidFines) {
            return null;
        }

        return Borrowing::STATUS_OVERDUE;
    }
}
