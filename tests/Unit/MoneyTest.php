<?php

namespace Tests\Unit;

use App\Domain\ValueObjects\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_it_builds_from_decimal_into_minor_units(): void
    {
        $money = Money::fromDecimal(12.34, 'ARS');

        $this->assertSame(1234, $money->minorUnits);
        $this->assertSame('ARS', $money->currency);
        $this->assertSame(12.34, $money->toDecimal());
    }

    public function test_it_handles_zero_decimal_currencies(): void
    {
        $money = Money::fromDecimal(1500, 'CLP');

        $this->assertSame(1500, $money->minorUnits);
        $this->assertSame(0, $money->decimals());
    }

    public function test_it_adds_and_subtracts_same_currency(): void
    {
        $a = Money::fromMinor(1000, 'ARS');
        $b = Money::fromMinor(250, 'ARS');

        $this->assertSame(1250, $a->plus($b)->minorUnits);
        $this->assertSame(750, $a->minus($b)->minorUnits);
    }

    public function test_it_rejects_operations_across_currencies(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromMinor(1000, 'ARS')->plus(Money::fromMinor(1000, 'USD'));
    }

    public function test_it_formats_without_intl(): void
    {
        $this->assertSame('$ 1.234,56', Money::fromDecimal(1234.56, 'ARS')->format());
        $this->assertSame('-$ 1.234,56', Money::fromDecimal(-1234.56, 'ARS')->format());
    }

    public function test_it_supports_peruvian_soles(): void
    {
        $money = Money::fromDecimal(1234.5, 'PEN');

        $this->assertSame(123450, $money->minorUnits);
        $this->assertSame('S/', $money->symbol());
        $this->assertSame('S/ 1.234,50', $money->format());
    }
}
