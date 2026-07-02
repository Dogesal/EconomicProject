<?php

namespace App\Data;

use App\Domain\Models\Account;
use Spatie\LaravelData\Data;

/**
 * Lightweight account shape for embedding in transaction lists (matches the
 * partial column selection used by the transaction repository).
 */
class AccountSummaryData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $currency,
        public ?string $color,
    ) {}

    public static function fromModel(Account $account): self
    {
        return new self(
            id: $account->id,
            name: $account->name,
            currency: $account->currency,
            color: $account->color,
        );
    }
}
