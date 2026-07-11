<?php

namespace App\Application\WhatsApp;

use App\Domain\Models\Account;

/**
 * Snapshot de cuentas y saldos que la app sube al servidor del bot para
 * que pueda preguntar la cuenta destino y validar saldos por WhatsApp.
 */
class AccountsSnapshot
{
    /**
     * @return list<array{id: string, name: string, balance: float, currency: string}>
     */
    public static function build(): array
    {
        return Account::where('is_archived', false)
            ->get()
            ->map(fn (Account $account): array => [
                'id' => $account->id,
                'name' => $account->name,
                'balance' => $account->current_balance->toDecimal(),
                'currency' => $account->currency,
            ])
            ->values()
            ->all();
    }
}
