<?php

namespace Tests\Feature;

use App\Domain\Models\ExchangeRate;
use App\Domain\ValueObjects\Money;
use App\Support\MoneyConverter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoneyConverterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_converts_using_a_direct_rate(): void
    {
        ExchangeRate::create([
            'base_currency' => 'USD',
            'quote_currency' => 'ARS',
            'rate' => 1000,
            'effective_on' => now()->toDateString(),
        ]);

        $result = app(MoneyConverter::class)->convert(Money::fromDecimal(10, 'USD'), 'ARS');

        $this->assertSame(1000000, $result->minorUnits); // 10 USD * 1000 = 10.000 ARS
        $this->assertSame('ARS', $result->currency);
    }

    public function test_it_converts_using_the_inverse_rate(): void
    {
        ExchangeRate::create([
            'base_currency' => 'USD',
            'quote_currency' => 'ARS',
            'rate' => 1000,
            'effective_on' => now()->toDateString(),
        ]);

        $result = app(MoneyConverter::class)->convert(Money::fromDecimal(5000, 'ARS'), 'USD');

        $this->assertSame(500, $result->minorUnits); // 5000 ARS / 1000 = 5 USD
    }

    public function test_same_currency_is_a_no_op(): void
    {
        $money = Money::fromDecimal(100, 'ARS');

        $this->assertTrue(app(MoneyConverter::class)->convert($money, 'ARS')->equals($money));
    }

    public function test_try_convert_returns_null_without_a_rate(): void
    {
        $this->assertNull(app(MoneyConverter::class)->tryConvert(Money::fromDecimal(100, 'ARS'), 'JPY'));
    }
}
