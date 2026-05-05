<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FineConfig extends Model
{
    protected $fillable = [
        'grace_period_days',
        'max_borrowing_days',
        'fine_per_day',
        'max_billable_days',
        'lost_book_fine',
        'max_fine_per_item',
        'max_fine_per_borrowing',
        'lost_book_payment_deadline',
        'max_fine_cap',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'grace_period_days' => 'integer',
            'max_borrowing_days' => 'integer',
            'fine_per_day' => 'decimal:2',
            'max_billable_days' => 'integer',
            'lost_book_fine' => 'decimal:2',
            'max_fine_per_item' => 'integer',
            'max_fine_per_borrowing' => 'integer',
            'lost_book_payment_deadline' => 'integer',
            'max_fine_cap' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public static function getActiveConfig(): ?FineConfig
    {
        return static::where('is_active', true)
            ->latest()
            ->first();
    }

    public static function getOrCreateDefault(): FineConfig
    {
        $config = static::getActiveConfig();

        if (! $config) {
            $config = static::create([
                'grace_period_days' => 5,
                'max_borrowing_days' => 7,
                'fine_per_day' => 2000,
                'max_billable_days' => 5,
                'lost_book_fine' => 50000,
                'max_fine_per_item' => 10000,
                'max_fine_per_borrowing' => 50000,
                'lost_book_payment_deadline' => 14,
                'max_fine_cap' => null,
                'is_active' => true,
            ]);
        }

        return $config;
    }
}
