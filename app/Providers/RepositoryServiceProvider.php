<?php

namespace App\Providers;

use App\Infrastructure\Repositories\Contracts\AccountRepository;
use App\Infrastructure\Repositories\Contracts\TransactionRepository;
use App\Infrastructure\Repositories\Eloquent\EloquentAccountRepository;
use App\Infrastructure\Repositories\Eloquent\EloquentTransactionRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Bind repository contracts to their Eloquent implementations. Swapping to a
     * sync-aware implementation later only requires changing these bindings.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        AccountRepository::class => EloquentAccountRepository::class,
        TransactionRepository::class => EloquentTransactionRepository::class,
    ];
}
