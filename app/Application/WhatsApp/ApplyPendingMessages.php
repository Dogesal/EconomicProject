<?php

namespace App\Application\WhatsApp;

use App\Application\Debts\CreateDebt;
use App\Application\Transactions\RecordTransaction;
use App\Domain\Enums\CategoryType;
use App\Domain\Enums\DebtDirection;
use App\Domain\Enums\TransactionType;
use App\Domain\Models\Account;
use App\Domain\Models\WhatsAppInboxEntry;
use App\Domain\ValueObjects\Money;
use App\Infrastructure\Http\WebhookServerClient;
use App\Support\WhatsAppLink;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Descarga los movimientos enviados por WhatsApp y los registra localmente.
 * El pull es la fuente de verdad (el push solo despierta a la app). Cada
 * mensaje se confirma al servidor como applied o failed.
 *
 * La cuenta destino llega resuelta por el bot como account_id (con
 * account_text como fallback para mensajes de un servidor viejo). Al final
 * de cada sync se sube el snapshot de cuentas y saldos para que el bot
 * pregunte y valide con datos frescos.
 *
 * Soporta tres tipos: income/expense (transacción en la cuenta) y debt
 * (crea una deuda "Debo" sin tocar saldos).
 */
class ApplyPendingMessages
{
    public function __construct(
        private readonly WhatsAppLink $link,
        private readonly WebhookServerClient $client,
        private readonly CategoryMatcher $matcher,
        private readonly AccountMatcher $accounts,
        private readonly RecordTransaction $recordTransaction,
        private readonly CreateDebt $createDebt,
    ) {}

    public function handle(): ApplyResult
    {
        if (! $this->link->isConfigured() || ! $this->link->isLinked()) {
            return ApplyResult::empty();
        }

        try {
            $pending = $this->client->pullPending();
        } catch (Throwable $e) {
            // Sin red o servidor caído: la app sigue offline sin ruido.
            Log::info('WhatsApp pull skipped: '.$e->getMessage());

            return ApplyResult::empty();
        }

        if ($pending === []) {
            $this->pushAccountsSnapshot();

            return ApplyResult::empty();
        }

        $acks = [];
        $applied = 0;
        $failed = 0;

        foreach ($pending as $message) {
            $seen = WhatsAppInboxEntry::find($message['id']);

            if ($seen !== null) {
                // Ya procesado en un pull anterior cuyo ACK se perdió.
                $acks[] = ['id' => $seen->id, 'status' => $seen->status, 'reason' => $seen->reason];

                continue;
            }

            $account = $this->resolveAccount($message);

            if ($account === null) {
                $accountText = $message['account_text'] ?? null;
                $reason = $accountText !== null
                    ? "No encontré la cuenta «{$accountText}» en la app."
                    : 'Elige la cuenta al registrar por WhatsApp.';
                $acks[] = $this->recordFailure($message, $reason);
                $failed++;

                continue;
            }

            if ($message['type'] === 'debt') {
                if ($this->applyDebt($message, $account, $acks)) {
                    $applied++;
                } else {
                    $failed++;
                }

                continue;
            }

            $type = $message['type'] === 'income' ? TransactionType::Income : TransactionType::Expense;
            $amount = Money::fromDecimal($message['amount'], $account->currency);

            if ($type === TransactionType::Expense && $amount->minorUnits > $account->current_balance->minorUnits) {
                $reason = 'Saldo insuficiente en la cuenta ('.$account->current_balance->format().').';
                $acks[] = $this->recordFailure($message, $reason);
                $failed++;

                continue;
            }

            $match = $this->matcher->match(
                $message['category_text'] ?? null,
                $type === TransactionType::Income ? CategoryType::Income : CategoryType::Expense,
            );

            try {
                $transaction = $this->recordTransaction->handle(
                    $account,
                    $type,
                    $amount,
                    $match->category,
                    $message['description'] ?? $match->description,
                    Carbon::parse($message['occurred_on']),
                );
            } catch (Throwable $e) {
                Log::warning("WhatsApp message {$message['id']} failed: {$e->getMessage()}");
                $acks[] = $this->recordFailure($message, 'No se pudo registrar el movimiento.');
                $failed++;

                continue;
            }

            WhatsAppInboxEntry::create([
                'id' => $message['id'],
                'transaction_id' => $transaction->id,
                'status' => WhatsAppInboxEntry::STATUS_APPLIED,
                'raw_text' => $message['raw_text'],
            ]);
            $acks[] = ['id' => $message['id'], 'status' => WhatsAppInboxEntry::STATUS_APPLIED];
            $applied++;

            // El observer recalcula el saldo: refrescar para validar el
            // siguiente gasto contra el saldo real.
            $account->refresh();
        }

        if ($acks !== []) {
            try {
                $this->client->ack($acks);
            } catch (Throwable $e) {
                // El inbox local evita re-aplicar; se re-ackeará en el próximo pull.
                Log::info('WhatsApp ack failed: '.$e->getMessage());
            }
        }

        $this->pushAccountsSnapshot();

        return new ApplyResult($applied, $failed);
    }

    /**
     * Resuelve la cuenta destino: primero el account_id que el bot ya
     * resolvió; como fallback el texto libre (mensajes de un servidor
     * viejo). Las cuentas archivadas no reciben movimientos.
     *
     * @param  array<string, mixed>  $message
     */
    private function resolveAccount(array $message): ?Account
    {
        $accountId = $message['account_id'] ?? null;

        if ($accountId !== null) {
            $account = Account::find($accountId);

            if ($account !== null && ! $account->is_archived) {
                return $account;
            }
        }

        return $this->accounts->match($message['account_text'] ?? null);
    }

    /**
     * Sube cuentas y saldos frescos al servidor para que el bot pregunte
     * la cuenta destino y valide saldos. Best-effort: sin red no hay ruido.
     */
    private function pushAccountsSnapshot(): void
    {
        try {
            $this->client->syncAccounts(AccountsSnapshot::build());
        } catch (Throwable $e) {
            Log::info('WhatsApp accounts snapshot skipped: '.$e->getMessage());
        }
    }

    /**
     * Crea una deuda "Debo" a partir del mensaje. La cuenta solo aporta la
     * moneda: las deudas no afectan el saldo hasta que se paguen.
     *
     * @param  array<string, mixed>  $message
     * @param  list<array{id: string, status: string, reason?: ?string}>  $acks
     */
    private function applyDebt(array $message, Account $account, array &$acks): bool
    {
        $name = trim((string) ($message['category_text'] ?? ''));

        try {
            $this->createDebt->handle(
                $name !== '' ? Str::ucfirst($name) : 'Deuda por WhatsApp',
                DebtDirection::IOwe,
                Money::fromDecimal($message['amount'], $account->currency),
            );
        } catch (Throwable $e) {
            Log::warning("WhatsApp debt {$message['id']} failed: {$e->getMessage()}");
            $acks[] = $this->recordFailure($message, 'No se pudo registrar la deuda.');

            return false;
        }

        WhatsAppInboxEntry::create([
            'id' => $message['id'],
            'status' => WhatsAppInboxEntry::STATUS_APPLIED,
            'raw_text' => $message['raw_text'],
        ]);
        $acks[] = ['id' => $message['id'], 'status' => WhatsAppInboxEntry::STATUS_APPLIED];

        return true;
    }

    /**
     * @param  array{id: string, raw_text: string}  $message
     * @return array{id: string, status: string, reason: string}
     */
    private function recordFailure(array $message, string $reason): array
    {
        WhatsAppInboxEntry::create([
            'id' => $message['id'],
            'status' => WhatsAppInboxEntry::STATUS_FAILED,
            'reason' => $reason,
            'raw_text' => $message['raw_text'],
        ]);

        return ['id' => $message['id'], 'status' => WhatsAppInboxEntry::STATUS_FAILED, 'reason' => $reason];
    }
}
