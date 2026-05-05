<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PaymentVerification extends Model
{
    protected $fillable = [
        'fine_id',
        'member_id',
        'requested_amount',
        'payment_method',
        'reference_number',
        'notes',
        'status',
        'requested_at',
        'requested_by',
        'verified_at',
        'verified_by',
        'rejection_reason',
        'expires_at',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function fine(): BelongsTo
    {
        return $this->belongsTo(Fine::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(PaymentReceipt::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(PaymentAuditLog::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || 
               ($this->expires_at && $this->expires_at->isPast());
    }

    public function canBeVerified(): bool
    {
        return $this->isPending() && !$this->isExpired();
    }

    public function verify(User $verifier, ?string $notes = null): void
    {
        $this->status = 'verified';
        $this->verified_at = now();
        $this->verified_by = $verifier->id;
        $this->save();

        // Update fine status
        if ($this->fine) {
            $this->fine->update([
                'status' => 'verified',
                'payment_verification_id' => $this->id,
            ]);
        }

        // Create audit log
        $this->auditLogs()->create([
            'action' => 'verified',
            'performed_by' => $verifier->id,
            'old_status' => 'pending',
            'new_status' => 'verified',
            'notes' => $notes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function reject(User $verifier, string $reason): void
    {
        $this->status = 'rejected';
        $this->verified_at = now();
        $this->verified_by = $verifier->id;
        $this->rejection_reason = $reason;
        $this->save();

        // Update fine status back to unpaid
        if ($this->fine) {
            $this->fine->update([
                'status' => 'unpaid',
                'payment_verification_id' => null,
            ]);
        }

        // Create audit log
        $this->auditLogs()->create([
            'action' => 'rejected',
            'performed_by' => $verifier->id,
            'old_status' => 'pending',
            'new_status' => 'rejected',
            'notes' => $reason,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function expire(): void
    {
        $this->status = 'expired';
        $this->save();

        // Update fine status back to unpaid
        if ($this->fine) {
            $this->fine->update([
                'status' => 'unpaid',
                'payment_verification_id' => null,
            ]);
        }

        // Create audit log
        $this->auditLogs()->create([
            'action' => 'expired',
            'performed_by' => 1, // System user
            'old_status' => 'pending',
            'new_status' => 'expired',
            'notes' => 'Payment request expired automatically',
        ]);
    }
}
