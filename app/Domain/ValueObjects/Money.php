<?php

namespace App\Domain\ValueObjects;

use InvalidArgumentException;
use Stringable;

/**
 * Immutable money value object stored as integer minor units (e.g. cents) plus
 * an ISO-4217 currency code. Never use floats for money.
 */
final class Money implements Stringable
{
    /**
     * Currency metadata: decimals and display symbol.
     *
     * @var array<string, array{decimals: int, symbol: string}>
     */
    private const CURRENCIES = [
        'USD' => ['decimals' => 2, 'symbol' => '$'],
        'EUR' => ['decimals' => 2, 'symbol' => '€'],
        'ARS' => ['decimals' => 2, 'symbol' => '$'],
        'MXN' => ['decimals' => 2, 'symbol' => '$'],
        'COP' => ['decimals' => 2, 'symbol' => '$'],
        'CLP' => ['decimals' => 0, 'symbol' => '$'],
        'BRL' => ['decimals' => 2, 'symbol' => 'R$'],
        'GBP' => ['decimals' => 2, 'symbol' => '£'],
        'JPY' => ['decimals' => 0, 'symbol' => '¥'],
        'PEN' => ['decimals' => 2, 'symbol' => 'S/'],
        'UYU' => ['decimals' => 2, 'symbol' => '$U'],
    ];

    public function __construct(
        public readonly int $minorUnits,
        public readonly string $currency,
    ) {
        if (strlen($currency) !== 3) {
            throw new InvalidArgumentException("Invalid currency code: {$currency}");
        }
    }

    public static function fromMinor(int $minorUnits, string $currency): self
    {
        return new self($minorUnits, strtoupper($currency));
    }

    public static function fromDecimal(int|float|string $amount, string $currency): self
    {
        $currency = strtoupper($currency);
        $decimals = self::decimalsFor($currency);
        $minor = (int) round(((float) $amount) * (10 ** $decimals));

        return new self($minor, $currency);
    }

    public static function zero(string $currency): self
    {
        return new self(0, strtoupper($currency));
    }

    public function plus(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorUnits + $other->minorUnits, $this->currency);
    }

    public function minus(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorUnits - $other->minorUnits, $this->currency);
    }

    public function negate(): self
    {
        return new self(-$this->minorUnits, $this->currency);
    }

    public function absolute(): self
    {
        return new self(abs($this->minorUnits), $this->currency);
    }

    public function isNegative(): bool
    {
        return $this->minorUnits < 0;
    }

    public function isZero(): bool
    {
        return $this->minorUnits === 0;
    }

    public function equals(self $other): bool
    {
        return $this->minorUnits === $other->minorUnits && $this->currency === $other->currency;
    }

    public function toDecimal(): float
    {
        return $this->minorUnits / (10 ** $this->decimals());
    }

    public function decimals(): int
    {
        return self::decimalsFor($this->currency);
    }

    public function symbol(): string
    {
        return self::CURRENCIES[$this->currency]['symbol'] ?? $this->currency;
    }

    /**
     * Human-readable formatting done manually (no ext-intl: ICU is disabled on
     * the Android runtime by default). Uses '.' thousands and ',' decimals (es).
     */
    public function format(): string
    {
        $decimals = $this->decimals();
        $value = number_format(abs($this->toDecimal()), $decimals, ',', '.');
        $sign = $this->isNegative() ? '-' : '';

        return "{$sign}{$this->symbol()} {$value}";
    }

    public function __toString(): string
    {
        return $this->format();
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Cannot operate on different currencies: {$this->currency} vs {$other->currency}"
            );
        }
    }

    private static function decimalsFor(string $currency): int
    {
        return self::CURRENCIES[$currency]['decimals'] ?? 2;
    }
}
