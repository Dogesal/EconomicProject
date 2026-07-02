<?php

namespace App\Http\Controllers;

use App\Application\Transactions\DeleteTransaction;
use App\Application\Transactions\RecordTransaction;
use App\Application\Transactions\UpdateTransaction;
use App\Data\AccountData;
use App\Data\CategoryData;
use App\Data\TransactionData;
use App\Domain\Enums\TransactionType;
use App\Domain\Models\Account;
use App\Domain\Models\Category;
use App\Domain\Models\Transaction;
use App\Domain\ValueObjects\Money;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Infrastructure\Repositories\Contracts\AccountRepository;
use App\Infrastructure\Repositories\Contracts\TransactionRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(TransactionRepository $transactions, AccountRepository $accounts): Response
    {
        $filters = request()->only(['account_id', 'category_id', 'type', 'from', 'to', 'search']);

        return Inertia::render('Transactions/Index', [
            'transactions' => TransactionData::collect($transactions->paginateForIndex($filters)),
            'filters' => $filters,
            'accounts' => AccountData::collect($accounts->allActive()),
            'categories' => CategoryData::collect(Category::orderBy('name')->get()),
        ]);
    }

    public function store(StoreTransactionRequest $request, RecordTransaction $record): RedirectResponse
    {
        $account = Account::findOrFail($request->string('account_id'));
        $category = $request->filled('category_id')
            ? Category::find($request->string('category_id'))
            : null;

        $record->handle(
            account: $account,
            type: $request->enum('type', TransactionType::class),
            amount: Money::fromDecimal($request->input('amount'), $account->currency),
            category: $category,
            description: $request->input('description'),
            occurredOn: $request->date('occurred_on'),
        );

        return back()->with('success', 'Movimiento registrado.');
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction, UpdateTransaction $update): RedirectResponse
    {
        $category = $request->filled('category_id')
            ? Category::find($request->string('category_id'))
            : null;

        $update->handle(
            transaction: $transaction,
            amount: Money::fromDecimal($request->input('amount'), $transaction->currency),
            category: $category,
            description: $request->input('description'),
            occurredOn: $request->date('occurred_on'),
        );

        return back()->with('success', 'Movimiento actualizado.');
    }

    public function destroy(Transaction $transaction, DeleteTransaction $delete): RedirectResponse
    {
        $delete->handle($transaction);

        return back()->with('success', 'Movimiento eliminado.');
    }
}
