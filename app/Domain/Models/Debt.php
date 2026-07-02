<?php

namespace App\Domain\Models;

use App\Domain\Casts\MoneyCast;
use App\Domain\Enums\DebtDirection;
use App\Domain\Enums\DebtStatus;
use App\Domain\ValueObjects\Money;
use Database\Factories\DebtFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property Money $original_amount
 * @property Money $paid_amount
 * @property DebtDirection $direction
 * @property DebtStatus $status
 */
class Debt extends Model
{
    /** @use HasFactory<DebtFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'direction',
        'original_amount',
        'paid_amount',
        'currency',
        'due_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'direction' => DebtDirection::class,
            'original_amount' => MoneyCast::class,
            'paid_amount' => MoneyCast::class,
            'due_date' => 'date',
            'status' => DebtStatus::class,
        ];
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function remaining(): Money
    {
        if ($this->paid_amount->minorUnits >= $this->original_amount->minorUnits) {
            return Money::zero($this->currency);
        }

        return $this->original_amount->minus($this->paid_amount);
    }

    public function progressPercentage(): float
    {
        if ($this->original_amount->minorUnits <= 0) {
            return 0.0;
        }

        return round(min(($this->paid_amount->minorUnits / $this->original_amount->minorUnits) * 100, 100), 1);
    }

    public function isSettled(): bool
    {
        return $this->paid_amount->minorUnits >= $this->original_amount->minorUnits;
    }

    public function isOverdue(): bool
    {
        return $this->status === DebtStatus::Active
            && $this->due_date !== null
            && $this->due_date->isPast()
            && ! $this->due_date->isToday();
    }

    /**
     * Registers a payment amount against the debt and syncs the status.
     */
    public function applyPayment(Money $amount): void
    {
        $this->paid_amount = $this->paid_amount->plus($amount->absolute());
        $this->status = $this->isSettled() ? DebtStatus::Settled : DebtStatus::Active;
        $this->save();
    }

    /**
     * Undoes a payment (e.g. its linked transaction was deleted), clamping
     * at zero and reopening the debt when it is no longer fully paid.
     */
    public function revertPayment(Money $amount): void
    {
        $reverted = $this->paid_amount->minus($amount->absolute());
        $this->paid_amount = $reverted->minorUnits > 0 ? $reverted : Money::zero($this->currency);
        $this->status = $this->isSettled() ? DebtStatus::Settled : DebtStatus::Active;
        $this->save();
    }

    protected static function newFactory(): DebtFactory
    {
        return DebtFactory::new();
    }
}
