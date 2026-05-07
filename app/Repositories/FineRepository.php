<?php

namespace App\Repositories;

use App\Models\Fine;
use App\Models\FineConfig;
use App\Models\FinePayment;
use Illuminate\Database\Eloquent\Collection;

class FineRepository
{
    public function getActiveFineConfig(): ?FineConfig
    {
        return FineConfig::getActiveConfig();
    }

    public function createFineConfig(array $data): FineConfig
    {
        return FineConfig::create($data);
    }

    public function updateFineConfig(FineConfig $config, array $data): bool
    {
        return $config->update($data);
    }

    public function getAllFines(?string $search = null, ?string $status = null): Collection
    {
        $query = Fine::with(['member', 'borrowingItem.book', 'payments']);

        if ($search) {
            $query->whereHas('member', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->latest()->get();
    }

    public function getMemberFines(int $memberId): Collection
    {
        return Fine::with(['borrowingItem.book', 'payments'])
            ->where('member_id', $memberId)
            ->latest()
            ->get();
    }

    public function getMemberFinesWithVerification(int $memberId): Collection
    {
        return Fine::with(['borrowingItem.book', 'payments', 'paymentVerification'])
            ->where('member_id', $memberId)
            ->latest()
            ->get();
    }

    public function getUnpaidFinesByMember(int $memberId): Collection
    {
        return Fine::with(['borrowingItem.book'])
            ->where('member_id', $memberId)
            ->whereIn('status', [Fine::STATUS_UNPAID, Fine::STATUS_PARTIAL])
            ->get();
    }

    public function getTotalUnpaidFines(int $memberId): float
    {
        return (float) Fine::where('member_id', $memberId)
            ->whereIn('status', [Fine::STATUS_UNPAID, Fine::STATUS_PARTIAL])
            ->selectRaw('SUM(amount - paid_amount) as total')
            ->value('total') ?? 0;
    }

    public function createFine(array $data): Fine
    {
        return Fine::create($data);
    }

    public function updateFine(Fine $fine, array $data): bool
    {
        return $fine->update($data);
    }

    /**
     * Create a payment record.
     */
    public function createPayment(array $data): FinePayment
    {
        return FinePayment::create($data);
    }

    public function getFineStatistics(): array
    {
        $totalFines = Fine::count();
        $totalUnpaid = Fine::whereIn('status', [Fine::STATUS_UNPAID, Fine::STATUS_PARTIAL])->count();
        $totalPaid = Fine::where('status', Fine::STATUS_PAID)->count();
        
        $totalAmount = Fine::sum('amount');
        $totalPaidAmount = Fine::sum('paid_amount');
        $totalUnpaidAmount = (float) $totalAmount - (float) $totalPaidAmount;

        $lateReturnFines = Fine::where('type', Fine::TYPE_LATE_RETURN)->count();
        $lostBookFines = Fine::where('type', Fine::TYPE_LOST_BOOK)->count();

        return [
            'total_fines' => $totalFines,
            'total_unpaid' => $totalUnpaid,
            'total_paid' => $totalPaid,
            'total_amount' => $totalAmount,
            'total_paid_amount' => $totalPaidAmount,
            'total_unpaid_amount' => $totalUnpaidAmount,
            'late_return_fines' => $lateReturnFines,
            'lost_book_fines' => $lostBookFines,
        ];
    }

    public function getMemberFineStatistics(int $memberId): array
    {
        $totalFines = Fine::where('member_id', $memberId)->count();
        $totalUnpaid = Fine::where('member_id', $memberId)
            ->whereIn('status', [Fine::STATUS_UNPAID, Fine::STATUS_PARTIAL])
            ->count();
        $totalPaid = Fine::where('member_id', $memberId)
            ->where('status', Fine::STATUS_PAID)
            ->count();
        
        $totalAmount = Fine::where('member_id', $memberId)->sum('amount');
        $totalPaidAmount = Fine::where('member_id', $memberId)->sum('paid_amount');
        $totalUnpaidAmount = (float) $totalAmount - (float) $totalPaidAmount;

        return [
            'total_fines' => $totalFines,
            'total_unpaid' => $totalUnpaid,
            'total_paid' => $totalPaid,
            'total_amount' => $totalAmount,
            'total_paid_amount' => $totalPaidAmount,
            'total_unpaid_amount' => $totalUnpaidAmount,
        ];
    }


    /**
     * Find a fine by ID.
     */
    public function findById(int $id): ?Fine
    {
        return Fine::find($id);
    }}
