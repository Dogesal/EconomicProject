<?php

namespace App\Data;

use App\Domain\Models\SavingsGoal;
use Spatie\LaravelData\Data;

class GoalData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public MoneyData $target,
        public MoneyData $current,
        public MoneyData $remaining,
        public float $progress,
        public string $status,
        public string $statusLabel,
        public ?string $targetDate,
    ) {}

    public static function fromModel(SavingsGoal $goal): self
    {
        $remaining = $goal->target_amount->minus(
            $goal->current_amount->minorUnits > $goal->target_amount->minorUnits
                ? $goal->target_amount
                : $goal->current_amount
        );

        return new self(
            id: $goal->id,
            name: $goal->name,
            target: MoneyData::fromMoney($goal->target_amount),
            current: MoneyData::fromMoney($goal->current_amount),
            remaining: MoneyData::fromMoney($remaining),
            progress: $goal->progressPercentage(),
            status: $goal->status->value,
            statusLabel: $goal->status->label(),
            targetDate: $goal->target_date?->toDateString(),
        );
    }
}
