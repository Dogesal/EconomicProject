<?php

namespace App\Data;

use App\Domain\Models\RecurringTransaction;
use Spatie\LaravelData\Data;

class RecurringTransactionData extends Data
{
    public function __construct(
        public string $id,
        public string $accountId,
        public ?string $accountName,
        public string $type,
        public string $typeLabel,
        public MoneyData $amount,
        public ?string $description,
        public string $frequency,
        public string $frequencyLabel,
        public int $interval,
        public string $nextRunOn,
        public ?string $endOn,
    ) {}

    public static function fromModel(RecurringTransaction $recurring): self
    {
        return new self(
            id: $recurring->id,
            accountId: $recurring->account_id,
            accountName: $recurring->account?->name,
            type: $recurring->type->value,
            typeLabel: $recurring->type->label(),
            amount: MoneyData::fromMoney($recurring->amount),
            description: $recurring->description,
            frequency: $recurring->frequency->value,
            frequencyLabel: $recurring->frequency->label(),
            interval: $recurring->interval,
            nextRunOn: $recurring->next_run_on->toDateString(),
            endOn: $recurring->end_on?->toDateString(),
        );
    }
}
