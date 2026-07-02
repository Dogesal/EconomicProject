<?php

namespace App\Domain\Models;

use App\Domain\Casts\MoneyCast;
use App\Domain\Enums\AccountType;
use App\Domain\ValueObjects\Money;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property Money $initial_balance
 * @property Money $current_balance
 */
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'currency',
        'initial_balance',
        'current_balance',
        'color',
        'is_archived',
    ];

    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'initial_balance' => MoneyCast::class,
            'current_balance' => MoneyCast::class,
            'is_archived' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Recompute and persist the cached balance from the account's transactions.
     * Balance = initial_balance + Σ(is_inflow ? +amount : -amount).
     */
    public function recalculateBalance(): void
    {
        $net = (int) $this->transactions()
            ->selectRaw('COALESCE(SUM(CASE WHEN is_inflow = 1 THEN amount ELSE -amount END), 0) as net')
            ->value('net');

        $this->forceFill([
            'current_balance' => $this->initial_balance->minorUnits + $net,
        ])->saveQuietly();
    }

    protected static function newFactory(): AccountFactory
    {
        return AccountFactory::new();
    }
}
