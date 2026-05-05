<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAuditLog extends Model
{
    protected $fillable = [
        'payment_verification_id',
        'action',
        'performed_by',
        'old_status',
        'new_status',
        'notes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function paymentVerification(): BelongsTo
    {
        return $this->belongsTo(PaymentVerification::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function getActionDescription(): string
    {
        return match($this->action) {
            'requested' => 'Payment request created',
            'verified' => 'Payment verified',
            'rejected' => 'Payment rejected',
            'expired' => 'Payment request expired',
            'receipt_generated' => 'Receipt generated',
            default => 'Unknown action',
        };
    }

    public function isSystemAction(): bool
    {
        return in_array($this->action, ['expired', 'receipt_generated']);
    }

    public function getPerformerName(): string
    {
        if ($this->isSystemAction()) {
            return 'System';
        }
        
        return $this->performedBy?->name ?? 'Unknown User';
    }
}
