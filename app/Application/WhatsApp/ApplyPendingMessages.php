<?php

namespace App\Application\WhatsApp;

use App\Application\Debts\CreateDebt;
use App\Application\Debts\RecordDebtPayment;
use App\Application\Transactions\RecordTransaction;
use App\Application\Transactions\TransferBetweenAccounts;
use App\Domain\Enums\CategoryType;
use App\Domain\Enums\DebtDirection;
use App\Domain\Enums\DebtStatus;
use App\Domain\Enums\TransactionType;
use App\Domain\Models\Account;
use App\Domain\Models\Category;
use App\Domain\Models\Debt;
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
 * de cada sync se sube el snapshot (cuentas, categorías, deudas y resumen)
 * para que el bot pregunte, valide y responda consultas con datos frescos.
 *
 * Tipos soportados: income/expense (transacción en la cuenta), debt (crea
 * una deuda "Debo"), transfer (entre dos cuentas), debt_payment (pago de
 * una deuda existente) y create_category (categoría nueva).
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
        private readonly TransferBetweenAccounts $transferBetweenAccounts,
        private readonly RecordDebtPayment $recordDebtPayment,
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

            if ($message['type'] === 'create_category') {
                if ($this->applyCreateCategory($message, $acks)) {
                    $applied++;
                } else {
                    $failed++;
                }

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

            if ($message['type'] === 'transfer') {
                if ($this->applyTransfer($message, $account, $acks)) {
                    $applied++;
                } else {
                    $failed++;
                }

                $account->refresh();

                continue;
            }

            if ($message['type'] === 'debt_payment') {
                if ($this->applyDebtPayment($message, $account, $acks)) {
                    $applied++;
                } else {
                    $failed++;
                }

                $account->refresh();

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
     * Crea la categoría pedida por el bot. Si ya existe una con el mismo
     * nombre y tipo se considera aplicada: el estado deseado ya se cumple.
     *
     * @param  array<string, mixed>  $message
     * @param  list<array{id: string, status: string, reason?: ?string}>  $acks
     */
    private function applyCreateCategory(array $message, array &$acks): bool
    {
        $name = trim((string) ($message['category_text'] ?? ''));

        if ($name === '') {
            $acks[] = $this->recordFailure($message, 'La categoría no tiene nombre.');

            return false;
        }

        $type = CategoryType::tryFrom((string) ($message['meta']['category_type'] ?? '')) ?? CategoryType::Expense;

        $existing = Category::where('type', $type)
            ->get()
            ->first(fn (Category $category): bool => $this->normalize($category->name) === $this->normalize($name));

        try {
            if ($existing === null) {
                Category::create([
                    'name' => Str::ucfirst($name),
                    'type' => $type,
                ]);
            }
        } catch (Throwable $e) {
            Log::warning("WhatsApp category {$message['id']} failed: {$e->getMessage()}");
            $acks[] = $this->recordFailure($message, 'No se pudo crear la categoría.');

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
     * Aplica una transferencia entre la cuenta origen (resuelta como
     * account_id) y la destino que viaja en meta.to_account_id.
     *
     * @param  array<string, mixed>  $message
     * @param  list<array{id: string, status: string, reason?: ?string}>  $acks
     */
    private function applyTransfer(array $message, Account $from, array &$acks): bool
    {
        $toId = $message['meta']['to_account_id'] ?? null;
        $to = $toId !== null ? Account::find($toId) : null;

        if ($to === null || $to->is_archived) {
            $toName = $message['meta']['to_account_name'] ?? 'destino';
            $acks[] = $this->recordFailure($message, "No encontré la cuenta «{$toName}» en la app.");

            return false;
        }

        try {
            $result = $this->transferBetweenAccounts->handle(
                $from,
                $to,
                Money::fromDecimal($message['amount'], $from->currency),
                null,
                Carbon::parse($message['occurred_on']),
            );
        } catch (Throwable $e) {
            Log::warning("WhatsApp transfer {$message['id']} failed: {$e->getMessage()}");
            $acks[] = $this->recordFailure($message, 'No se pudo registrar la transferencia (revisa saldo y monedas).');

            return false;
        }

        WhatsAppInboxEntry::create([
            'id' => $message['id'],
            'transaction_id' => $result['out']->id,
            'status' => WhatsAppInboxEntry::STATUS_APPLIED,
            'raw_text' => $message['raw_text'],
        ]);
        $acks[] = ['id' => $message['id'], 'status' => WhatsAppInboxEntry::STATUS_APPLIED];

        return true;
    }

    /**
     * Registra un pago sobre la deuda que viaja en meta.debt_name como
     * movimiento real en la cuenta resuelta.
     *
     * @param  array<string, mixed>  $message
     * @param  list<array{id: string, status: string, reason?: ?string}>  $acks
     */
    private function applyDebtPayment(array $message, Account $account, array &$acks): bool
    {
        $debtName = trim((string) ($message['meta']['debt_name'] ?? ''));

        $debt = Debt::where('status', DebtStatus::Active)
            ->get()
            ->first(fn (Debt $candidate): bool => $this->normalize($candidate->name) === $this->normalize($debtName));

        if ($debt === null) {
            $acks[] = $this->recordFailure($message, "No encontré la deuda «{$debtName}» en la app.");

            return false;
        }

        try {
            $transaction = $this->recordDebtPayment->handle(
                $debt,
                $account,
                Money::fromDecimal($message['amount'], $account->currency),
                Carbon::parse($message['occurred_on']),
            );
        } catch (Throwable $e) {
            Log::warning("WhatsApp debt payment {$message['id']} failed: {$e->getMessage()}");
            $acks[] = $this->recordFailure($message, 'No se pudo registrar el pago (revisa saldo, moneda y lo que queda de la deuda).');

            return false;
        }

        WhatsAppInboxEntry::create([
            'id' => $message['id'],
            'transaction_id' => $transaction->id,
            'status' => WhatsAppInboxEntry::STATUS_APPLIED,
            'raw_text' => $message['raw_text'],
        ]);
        $acks[] = ['id' => $message['id'], 'status' => WhatsAppInboxEntry::STATUS_APPLIED];

        return true;
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));

        return strtr($value, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
            'ñ' => 'n',
        ]);
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
