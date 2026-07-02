<?php

namespace App\Infrastructure\Repositories\Contracts;

use App\Domain\Models\Account;
use App\Domain\ValueObjects\Money;
use Illuminate\Support\Collection;

interface AccountRepository
{
    /**
     * @return Collection<int, Account>
     */
    public function allActive(): Collection;

    /**
     * Total balance grouped by currency (multi-currency safe).
     *
     * @return Collection<string, Money>
     */
    public function totalsByCurrency(): Collection;
}
