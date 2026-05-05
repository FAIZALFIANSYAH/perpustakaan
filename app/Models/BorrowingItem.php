<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BorrowingItem extends Model
{
    protected $fillable = [
        'borrowing_id',
        'book_id',
        'quantity',
        'returned_quantity',
        'notes',
    ];

    public function borrowing(): BelongsTo
    {
        return $this->belongsTo(Borrowing::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function fines(): HasMany
    {
        return $this->hasMany(Fine::class, 'borrowing_item_id');
    }
}
