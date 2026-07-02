<?php

namespace App\Data;

use App\Domain\Models\Debt;
use Spatie\LaravelData\Data;

class DebtData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $direction,
        public string $directionLabel,
        public MoneyData $original,
        public MoneyData $paid,
        public MoneyData $remaining,
        public float $progress,
        public string $status,
        public string $statusLabel,
        public ?string $dueDate,
        public bool $isOverdue,
    ) {}

    public static function fromModel(Debt $debt): self
    {
        return new self(
            id: $debt->id,
            name: $debt->name,
            direction: $debt->direction->value,
            directionLabel: $debt->direction->label(),
            original: MoneyData::fromMoney($debt->original_amount),
            paid: MoneyData::fromMoney($debt->paid_amount),
            remaining: MoneyData::fromMoney($debt->remaining()),
            progress: $debt->progressPercentage(),
            status: $debt->status->value,
            statusLabel: $debt->status->label(),
            dueDate: $debt->due_date?->toDateString(),
            isOverdue: $debt->isOverdue(),
        );
    }
}
