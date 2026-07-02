<?php

namespace App\Domain\Models;

use App\Domain\Casts\MoneyCast;
use App\Domain\ValueObjects\Money;
use Database\Factories\BudgetFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property Money $amount
 */
class Budget extends Model
{
    /** @use HasFactory<BudgetFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'category_id',
        'period_year',
        'period_month',
        'amount',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'period_year' => 'integer',
            'period_month' => 'integer',
            'amount' => MoneyCast::class,
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    protected static function newFactory(): BudgetFactory
    {
        return BudgetFactory::new();
    }
}
