<?php

namespace App\Domain\Enums;

use Carbon\CarbonInterface;

/**
 * Strategy for advancing a recurring transaction's next run date. Each case
 * knows how to step forward by a given interval.
 */
enum RecurrenceFrequency: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Diaria',
            self::Weekly => 'Semanal',
            self::Monthly => 'Mensual',
            self::Yearly => 'Anual',
        };
    }

    /**
     * Advance a date forward by `interval` units of this frequency.
     */
    public function advance(CarbonInterface $from, int $interval = 1): CarbonInterface
    {
        return match ($this) {
            self::Daily => $from->copy()->addDays($interval),
            self::Weekly => $from->copy()->addWeeks($interval),
            self::Monthly => $from->copy()->addMonthsNoOverflow($interval),
            self::Yearly => $from->copy()->addYearsNoOverflow($interval),
        };
    }
}
