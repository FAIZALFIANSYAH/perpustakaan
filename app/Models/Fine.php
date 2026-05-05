<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fine extends Model
{
    protected $fillable = [
        'borrowing_item_id',
        'member_id',
        'type',
        'amount',
        'paid_amount',
        'status',
        'due_date',
        'paid_at',
        'reason',
        'notes',
        'payment_verification_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'date',
        ];
    }

    public function borrowingItem(): BelongsTo
    {
        return $this->belongsTo(BorrowingItem::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FinePayment::class);
    }

    public function paymentVerification(): BelongsTo
    {
        return $this->belongsTo(PaymentVerification::class);
    }

    public function getRemainingAmountAttribute(): float
    {
        return (float) $this->amount - (float) $this->paid_amount;
    }

    public function isFullyPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now()->toDateString(),
            'paid_amount' => $this->amount,
        ]);
    }

    public function isUnpaid(): bool
    {
        return $this->status === 'unpaid';
    }

    public function isPendingPayment(): bool
    {
        return $this->status === 'pending_payment';
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function canRequestPayment(): bool
    {
        return $this->isUnpaid() && !$this->paymentVerification?->isPending();
    }

    public function hasPendingPaymentRequest(): bool
    {
        return $this->paymentVerification && $this->paymentVerification->isPending();
    }
}
