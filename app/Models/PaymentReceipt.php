<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReceipt extends Model
{
    protected $fillable = [
        'payment_verification_id',
        'receipt_number',
        'receipt_data',
        'qr_code',
        'pdf_path',
        'sent_via',
        'sent_at',
    ];

    protected $casts = [
        'receipt_data' => 'array',
        'sent_at' => 'datetime',
    ];

    public function paymentVerification(): BelongsTo
    {
        return $this->belongsTo(PaymentVerification::class);
    }

    public function generateReceiptNumber(): string
    {
        $prefix = 'RCP';
        $date = now()->format('Ymd');
        $sequence = str_pad(static::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);
        
        return "{$prefix}{$date}{$sequence}";
    }

    public function markAsSent(string $via): void
    {
        $this->sent_via = $via;
        $this->sent_at = now();
        $this->save();
    }

    public function isSent(): bool
    {
        return $this->sent_at !== null;
    }

    public function getReceiptData(): array
    {
        return $this->receipt_data ?? [];
    }

    public function getMemberName(): string
    {
        return $this->paymentVerification?->member?->name ?? 'Unknown';
    }

    public function getAmount(): float
    {
        return $this->paymentVerification?->requested_amount ?? 0;
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentVerification?->payment_method ?? 'Unknown';
    }

    public function getReferenceNumber(): ?string
    {
        return $this->paymentVerification?->reference_number;
    }
}
