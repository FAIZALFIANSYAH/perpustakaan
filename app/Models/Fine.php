<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fine extends Model
{
    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_PAID = 'paid';
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_VERIFIED = 'verified';

    public const TYPE_LATE_RETURN = 'late_return';
    public const TYPE_LOST_BOOK = 'lost_book';
    public const TYPE_PENALTY = 'penalty';

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
        return $this->status === self::STATUS_PAID;
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now()->toDateString(),
            'paid_amount' => $this->amount,
        ]);
    }

    public function isUnpaid(): bool
    {
        return $this->status === self::STATUS_UNPAID;
    }

    public function isPendingPayment(): bool
    {
        return $this->status === self::STATUS_PENDING_PAYMENT;
    }

    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
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
