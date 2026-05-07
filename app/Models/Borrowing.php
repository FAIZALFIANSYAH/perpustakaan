<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Borrowing extends Model
{
    public const STATUS_BORROWED = 'borrowed';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_LATE_PAYMENT = 'late_payment';
    public const STATUS_LOST = 'lost';
    public const STATUS_COMPLETE = 'complete';
    public const STATUS_COMPLETE_WITH_PENALTY = 'complete_with_penalty';
    public const STATUS_AWAITING_FINE_PAYMENT = 'awaiting_fine_payment';

    protected $fillable = [
        'code',
        'member_id',
        'processed_by',
        'borrowed_at',
        'due_at',
        'returned_at',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'borrowed_at' => 'date',
            'due_at' => 'date',
            'returned_at' => 'date',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BorrowingItem::class);
    }
}
