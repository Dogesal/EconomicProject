<?php

namespace App\Data;

use App\Domain\Models\Account;
use Spatie\LaravelData\Data;

class AccountData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $type,
        public string $typeLabel,
        public string $currency,
        public MoneyData $currentBalance,
        public MoneyData $initialBalance,
        public ?string $color,
        public bool $isArchived,
    ) {}

    public static function fromModel(Account $account): self
    {
        return new self(
            id: $account->id,
            name: $account->name,
            type: $account->type->value,
            typeLabel: $account->type->label(),
            currency: $account->currency,
            currentBalance: MoneyData::fromMoney($account->current_balance),
            initialBalance: MoneyData::fromMoney($account->initial_balance),
            color: $account->color,
            isArchived: $account->is_archived,
        );
    }
}
