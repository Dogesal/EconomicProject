<?php

namespace App\Domain\Casts;

use App\Domain\ValueObjects\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Casts an integer minor-units column into a {@see Money} value object, reading
 * the shared currency column (default `currency`). Assigning a Money instance
 * also writes back the currency column, keeping both in sync.
 *
 * Usage: protected function casts(): array {
 *     return ['amount' => MoneyCast::class, 'initial_balance' => MoneyCast::class.':currency'];
 * }
 *
 * @implements CastsAttributes<Money|null, Money|int|null>
 */
class MoneyCast implements CastsAttributes
{
    public function __construct(private string $currencyColumn = 'currency') {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        $currency = $attributes[$this->currencyColumn] ?? 'USD';

        return Money::fromMinor((int) $value, (string) $currency);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [$key => null];
        }

        if ($value instanceof Money) {
            return [
                $key => $value->minorUnits,
                $this->currencyColumn => $value->currency,
            ];
        }

        return [$key => (int) $value];
    }
}
