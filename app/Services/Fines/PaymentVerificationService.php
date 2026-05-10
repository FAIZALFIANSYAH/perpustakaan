<?php

namespace App\Services;

use App\Models\Fine;
use App\Models\PaymentVerification;
use App\Models\PaymentReceipt;
use App\Models\PaymentAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentVerificationService
{
    public function initiatePaymentRequest(Fine $fine, User $member, array $data): PaymentVerification
    {
        // Validate fine can be requested for payment
        if ($fine->status !== Fine::STATUS_UNPAID) {
            throw new \InvalidArgumentException('Fine is not eligible for payment request');
        }

        if ($fine->member_id !== $member->id) {
            throw new \InvalidArgumentException('Fine does not belong to this member');
        }

        // Check if there's already a pending payment request
        if ($fine->paymentVerification && $fine->paymentVerification->isPending()) {
            throw new \InvalidArgumentException('Payment request already pending for this fine');
        }

        return DB::transaction(function () use ($fine, $member, $data) {
            // Create payment verification
            $verification = PaymentVerification::create([
                'fine_id' => $fine->id,
                'member_id' => $member->id,
                'requested_amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'requested_at' => now(),
                'requested_by' => $member->id,
                'expires_at' => now()->addHours(24), // Expires in 24 hours
            ]);

            // Update fine status
            $fine->update([
                'status' => Fine::STATUS_PENDING_PAYMENT,
                'payment_verification_id' => $verification->id,
            ]);

            // Create audit log
            $verification->auditLogs()->create([
                'action' => 'requested',
                'performed_by' => $member->id,
                'old_status' => Fine::STATUS_UNPAID,
                'new_status' => Fine::STATUS_PENDING_PAYMENT,
                'notes' => 'Payment request initiated',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $verification;
        });
    }

    public function verifyPayment(PaymentVerification $verification, User $librarian, ?string $notes = null): PaymentReceipt
    {
        // Validate verification can be verified
        if (!$verification->canBeVerified()) {
            throw new \InvalidArgumentException('Payment verification cannot be verified');
        }

        if (!$librarian->hasAnyRole(['Super Admin', 'Librarian'])) {
            throw new \InvalidArgumentException('User is not authorized to verify payments');
        }

        return DB::transaction(function () use ($verification, $librarian, $notes) {
            // Verify the payment
            $verification->verify($librarian, $notes);

            // Update fine to paid status
            $verification->fine->update([
                'status' => Fine::STATUS_PAID,
                'paid_amount' => $verification->fine->paid_amount + $verification->requested_amount,
                'paid_at' => now(),
            ]);

            // Generate receipt
            $receipt = $this->generateReceipt($verification);

            // Create receipt audit log
            $verification->auditLogs()->create([
                'action' => 'receipt_generated',
                'performed_by' => $librarian->id,
                'notes' => 'Receipt generated automatically',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $receipt;
        });
    }

    public function rejectPayment(PaymentVerification $verification, User $librarian, string $reason): void
    {
        // Validate verification can be rejected
        if (!$verification->canBeVerified()) {
            throw new \InvalidArgumentException('Payment verification cannot be rejected');
        }

        if (!$librarian->hasAnyRole(['Super Admin', 'Librarian'])) {
            throw new \InvalidArgumentException('User is not authorized to reject payments');
        }

        DB::transaction(function () use ($verification, $librarian, $reason) {
            $verification->reject($librarian, $reason);
        });
    }

    public function getPendingPayments(): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentVerification::with(['fine', 'member', 'requestedBy'])
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getExpiredPayments(): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentVerification::with(['fine', 'member'])
            ->where('status', 'pending')
            ->where('expires_at', '<', now())
            ->get();
    }

    public function processExpiredPayments(): int
    {
        $expiredPayments = $this->getExpiredPayments();
        $processedCount = 0;

        foreach ($expiredPayments as $verification) {
            $verification->expire();
            $processedCount++;
        }

        return $processedCount;
    }

    public function getMemberPaymentHistory(User $member): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentVerification::with(['fine', 'verifiedBy', 'receipt'])
            ->where('member_id', $member->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getPaymentStatistics(): array
    {
        $today = now()->toDateString();
        
        return [
            'pending_count' => PaymentVerification::where('status', 'pending')
                ->where('expires_at', '>', now())
                ->count(),
            'expired_count' => PaymentVerification::where('status', 'pending')
                ->where('expires_at', '<', now())
                ->count(),
            'verified_today' => PaymentVerification::whereDate('verified_at', $today)
                ->where('status', 'verified')
                ->count(),
            'rejected_today' => PaymentVerification::whereDate('verified_at', $today)
                ->where('status', 'rejected')
                ->count(),
            'total_amount_pending' => PaymentVerification::where('status', 'pending')
                ->where('expires_at', '>', now())
                ->sum('requested_amount'),
        ];
    }

    private function generateReceipt(PaymentVerification $verification): PaymentReceipt
    {
        $receiptData = [
            'receipt_number' => (new PaymentReceipt())->generateReceiptNumber(),
            'member_name' => $verification->member->name,
            'member_email' => $verification->member->email,
            'fine_id' => $verification->fine_id,
            'fine_type' => $verification->fine->type,
            'amount' => $verification->requested_amount,
            'payment_method' => $verification->payment_method,
            'reference_number' => $verification->reference_number,
            'verified_at' => $verification->verified_at->format('Y-m-d H:i:s'),
            'verified_by' => $verification->verifiedBy->name,
            'notes' => $verification->notes,
        ];

        $receipt = PaymentReceipt::create([
            'payment_verification_id' => $verification->id,
            'receipt_number' => $receiptData['receipt_number'],
            'receipt_data' => $receiptData,
            'qr_code' => $this->generateQRCode($receiptData['receipt_number']),
        ]);

        return $receipt;
    }

    private function generateQRCode(string $receiptNumber): string
    {
        // Simple QR code generation - in real implementation, use a QR library
        return base64_encode($receiptNumber);
    }

    public function searchPayments(?string $search = null, ?string $status = null): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = PaymentVerification::with(['fine', 'member', 'verifiedBy']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('member', function ($memberQuery) use ($search) {
                    $memberQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhere('reference_number', 'like', "%{$search}%")
                ->orWhere('receipt_number', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();
    }
}
