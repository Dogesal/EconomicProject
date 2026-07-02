<?php

namespace App\Domain\Models;

use App\Domain\Casts\MoneyCast;
use App\Domain\Enums\GoalStatus;
use App\Domain\ValueObjects\Money;
use Database\Factories\SavingsGoalFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property Money $target_amount
 * @property Money $current_amount
 * @property GoalStatus $status
 */
class SavingsGoal extends Model
{
    /** @use HasFactory<SavingsGoalFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'target_amount',
        'current_amount',
        'currency',
        'target_date',
        'account_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => MoneyCast::class,
            'current_amount' => MoneyCast::class,
            'target_date' => 'date',
            'status' => GoalStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function progressPercentage(): float
    {
        if ($this->target_amount->minorUnits <= 0) {
            return 0.0;
        }

        return round(min(($this->current_amount->minorUnits / $this->target_amount->minorUnits) * 100, 100), 1);
    }

    public function isReached(): bool
    {
        return $this->current_amount->minorUnits >= $this->target_amount->minorUnits;
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Applies a signed delta (minor units) to the saved amount, flooring at
     * zero, and keeps the status in sync. Used by contribute/withdraw and by
     * the observer when a linked transaction is deleted or restored.
     */
    public function adjustCurrent(int $deltaMinor): void
    {
        $this->current_amount = Money::fromMinor(
            max(0, $this->current_amount->minorUnits + $deltaMinor),
            $this->currency,
        );

        $this->status = $this->isReached() ? GoalStatus::Completed : GoalStatus::Active;
        $this->save();
    }

    protected static function newFactory(): SavingsGoalFactory
    {
        return SavingsGoalFactory::new();
    }
}
