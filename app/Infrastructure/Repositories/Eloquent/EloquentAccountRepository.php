<?php

namespace App\Infrastructure\Repositories\Eloquent;

use App\Domain\Models\Account;
use App\Domain\ValueObjects\Money;
use App\Infrastructure\Repositories\Contracts\AccountRepository;
use Illuminate\Support\Collection;

class EloquentAccountRepository implements AccountRepository
{
    public function allActive(): Collection
    {
        return Account::query()
            ->where('is_archived', false)
            ->orderBy('name')
            ->get();
    }

    public function archived(): Collection
    {
        return Account::query()
            ->where('is_archived', true)
            ->orderBy('name')
            ->get();
    }

    public function totalsByCurrency(): Collection
    {
        return Account::query()
            ->where('is_archived', false)
            ->selectRaw('currency, SUM(current_balance) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->map(fn ($total, $currency) => Money::fromMinor((int) $total, $currency));
    }
}
