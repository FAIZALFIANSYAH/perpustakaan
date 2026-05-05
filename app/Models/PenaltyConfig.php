<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenaltyConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'penalty_enabled',
        'grace_period_penalty_days',
        'penalty_multiplier',
        'is_active',
    ];

    protected $casts = [
        'penalty_enabled' => 'boolean',
        'grace_period_penalty_days' => 'integer',
        'penalty_multiplier' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the active penalty configuration
     */
    public static function getActiveConfig(): ?self
    {
        return static::where('is_active', true)->first();
    }

    /**
     * Get or create the default penalty configuration
     */
    public static function getOrCreateDefault(): self
    {
        $config = static::getActiveConfig();
        
        if (!$config) {
            $config = static::create([
                'penalty_enabled' => true,
                'grace_period_penalty_days' => 3,
                'penalty_multiplier' => 2.00,
                'is_active' => true,
            ]);
        }

        return $config;
    }

    /**
     * Check if penalty is enabled
     */
    public function isPenaltyEnabled(): bool
    {
        return $this->penalty_enabled && $this->is_active;
    }

    /**
     * Calculate penalty amount
     */
    public function calculatePenaltyAmount(float $originalFine): float
    {
        if (!$this->isPenaltyEnabled()) {
            return 0;
        }

        return $originalFine * $this->penalty_multiplier;
    }

    /**
     * Check if penalty should be applied based on days overdue
     */
    public function shouldApplyPenalty(int $daysOverdue): bool
    {
        if (!$this->isPenaltyEnabled()) {
            return false;
        }

        return $daysOverdue > $this->grace_period_penalty_days;
    }

    /**
     * Get the penalty threshold day
     */
    public function getPenaltyThresholdDay(): int
    {
        return $this->grace_period_penalty_days + 1;
    }
}