<?php

namespace App\Infrastructure\Repositories\Contracts;

use App\Domain\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TransactionRepository
{
    /**
     * Paginate transactions for the index screen, eager-loading account and category.
     *
     * @param  array{account_id?: string, category_id?: string, type?: string, from?: string, to?: string, search?: string}  $filters
     * @return LengthAwarePaginator<int, Transaction>
     */
    public function paginateForIndex(array $filters, int $perPage = 25): LengthAwarePaginator;

    /**
     * Most recent transactions across all accounts (for the dashboard).
     *
     * @return Collection<int, Transaction>
     */
    public function recent(int $limit = 8): Collection;
}
