<?php

namespace App\Domain\Models;

use App\Domain\Casts\MoneyCast;
use App\Domain\Enums\TransactionType;
use App\Domain\Observers\TransactionObserver;
use App\Domain\ValueObjects\Money;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property Money $amount
 * @property TransactionType $type
 */
#[ObservedBy([TransactionObserver::class])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'account_id',
        'category_id',
        'type',
        'amount',
        'currency',
        'is_inflow',
        'description',
        'occurred_on',
        'transfer_group_id',
        'recurring_transaction_id',
        'debt_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => MoneyCast::class,
            'is_inflow' => 'boolean',
            'occurred_on' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Debt, $this>
     */
    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }

    /**
     * Signed effect this transaction has on its account balance, in minor units.
     */
    public function balanceEffect(): int
    {
        return $this->is_inflow ? $this->amount->minorUnits : -$this->amount->minorUnits;
    }

    /**
     * @param  Builder<Transaction>  $query
     */
    public function scopeForMonth(Builder $query, int $year, int $month): void
    {
        $query->whereYear('occurred_on', $year)->whereMonth('occurred_on', $month);
    }

    protected static function newFactory(): TransactionFactory
    {
        return TransactionFactory::new();
    }
}
