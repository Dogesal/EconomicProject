<?php

namespace App\Application\WhatsApp;

use App\Application\Transactions\RecordTransaction;
use App\Domain\Enums\CategoryType;
use App\Domain\Enums\TransactionType;
use App\Domain\Models\WhatsAppInboxEntry;
use App\Domain\ValueObjects\Money;
use App\Infrastructure\Http\WebhookServerClient;
use App\Support\WhatsAppLink;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Descarga los movimientos enviados por WhatsApp y los registra localmente.
 * El pull es la fuente de verdad (el push solo despierta a la app). Cada
 * mensaje se confirma al servidor como applied o failed; los que no se
 * pueden procesar todavía (sin cuenta por defecto) quedan pendientes allá.
 */
class ApplyPendingMessages
{
    public function __construct(
        private readonly WhatsAppLink $link,
        private readonly WebhookServerClient $client,
        private readonly CategoryMatcher $matcher,
        private readonly RecordTransaction $recordTransaction,
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
            return ApplyResult::empty();
        }

        $account = $this->link->defaultAccount();
        $acks = [];
        $applied = 0;
        $failed = 0;
        $needsAccountSetup = false;

        foreach ($pending as $message) {
            $seen = WhatsAppInboxEntry::find($message['id']);

            if ($seen !== null) {
                // Ya procesado en un pull anterior cuyo ACK se perdió.
                $acks[] = ['id' => $seen->id, 'status' => $seen->status, 'reason' => $seen->reason];

                continue;
            }

            if ($account === null) {
                // Sin ACK: el mensaje espera en el servidor hasta que el
                // usuario configure la cuenta destino.
                $needsAccountSetup = true;

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

        return new ApplyResult($applied, $failed, $needsAccountSetup);
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
