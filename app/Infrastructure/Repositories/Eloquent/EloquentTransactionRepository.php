<?php

namespace App\Infrastructure\Repositories\Eloquent;

use App\Domain\Models\Transaction;
use App\Infrastructure\Repositories\Contracts\TransactionRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentTransactionRepository implements TransactionRepository
{
    public function paginateForIndex(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return Transaction::query()
            ->with(['account:id,name,currency,color', 'category:id,name,icon,color'])
            ->when($filters['account_id'] ?? null, fn ($q, $id) => $q->where('account_id', $id))
            ->when($filters['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('occurred_on', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('occurred_on', '<=', $to))
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where('description', 'like', "%{$search}%"))
            ->orderByDesc('occurred_on')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function recent(int $limit = 8): Collection
    {
        return Transaction::query()
            ->with(['account:id,name,currency,color', 'category:id,name,icon,color'])
            ->orderByDesc('occurred_on')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
