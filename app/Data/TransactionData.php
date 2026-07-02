<?php

namespace App\Data;

use App\Domain\Models\Transaction;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class TransactionData extends Data
{
    public function __construct(
        public string $id,
        public string $accountId,
        public ?string $categoryId,
        public string $type,
        public string $typeLabel,
        public MoneyData $amount,
        public bool $isInflow,
        public ?string $description,
        public string $occurredOn,
        public ?string $transferGroupId,
        public AccountSummaryData|Optional $account,
        public CategorySummaryData|null|Optional $category,
    ) {}

    public static function fromModel(Transaction $transaction): self
    {
        return new self(
            id: $transaction->id,
            accountId: $transaction->account_id,
            categoryId: $transaction->category_id,
            type: $transaction->type->value,
            typeLabel: $transaction->type->label(),
            amount: MoneyData::fromMoney($transaction->amount),
            isInflow: $transaction->is_inflow,
            description: $transaction->description,
            occurredOn: $transaction->occurred_on->toDateString(),
            transferGroupId: $transaction->transfer_group_id,
            account: $transaction->relationLoaded('account') && $transaction->account
                ? AccountSummaryData::fromModel($transaction->account)
                : Optional::create(),
            category: $transaction->relationLoaded('category')
                ? ($transaction->category ? CategorySummaryData::fromModel($transaction->category) : null)
                : Optional::create(),
        );
    }
}
