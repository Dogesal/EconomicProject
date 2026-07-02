<?php

namespace App\Domain\Models;

use App\Domain\Casts\MoneyCast;
use App\Domain\Enums\RecurrenceFrequency;
use App\Domain\Enums\TransactionType;
use App\Domain\ValueObjects\Money;
use Database\Factories\RecurringTransactionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property Money $amount
 * @property TransactionType $type
 * @property RecurrenceFrequency $frequency
 */
class RecurringTransaction extends Model
{
    /** @use HasFactory<RecurringTransactionFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'account_id',
        'category_id',
        'type',
        'amount',
        'currency',
        'description',
        'frequency',
        'interval',
        'next_run_on',
        'end_on',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => MoneyCast::class,
            'frequency' => RecurrenceFrequency::class,
            'interval' => 'integer',
            'next_run_on' => 'date',
            'end_on' => 'date',
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

    protected static function newFactory(): RecurringTransactionFactory
    {
        return RecurringTransactionFactory::new();
    }
}
