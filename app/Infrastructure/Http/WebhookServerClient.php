<?php

namespace App\Infrastructure\Http;

use App\Support\WhatsAppLink;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Cliente HTTP contra el servidor economic-webhook. Timeouts cortos a
 * propósito: estas llamadas corren al abrir la app y no deben degradar
 * el arranque cuando no hay conexión.
 */
class WebhookServerClient
{
    public function __construct(private readonly WhatsAppLink $link) {}

    /**
     * @return array{device_id: string, api_token: string}
     */
    public function register(string $name): array
    {
        return $this->request()
            ->post('/api/devices', ['name' => $name, 'platform' => 'android'])
            ->throw()
            ->json();
    }

    /**
     * @return array{code: string, expires_at: string, bot_phone: ?string}
     */
    public function requestLinkCode(): array
    {
        return $this->authenticated()->post('/api/link-codes')->throw()->json();
    }

    /**
     * @return array{linked: bool, phone_masked: ?string, linked_at: ?string}
     */
    public function deviceStatus(): array
    {
        return $this->authenticated()->get('/api/devices/me')->throw()->json();
    }

    public function updateFcmToken(string $fcmToken): void
    {
        $this->authenticated()->put('/api/devices/me/fcm-token', ['fcm_token' => $fcmToken])->throw();
    }

    /**
     * Sube el snapshot de cuentas para que el bot pregunte la cuenta
     * destino y valide saldos al momento del mensaje.
     *
     * @param  list<array{id: string, name: string, balance: float, currency: string}>  $accounts
     */
    public function syncAccounts(array $accounts): void
    {
        $this->authenticated()->put('/api/devices/me/accounts', ['accounts' => $accounts])->throw();
    }

    /**
     * @return list<array{id: string, type: string, amount: string, category_text: ?string, description: ?string, occurred_on: string, raw_text: string, received_at: ?string}>
     */
    public function pullPending(): array
    {
        return $this->authenticated()->get('/api/messages/pending')->throw()->json('data', []);
    }

    /**
     * @param  list<array{id: string, status: string, reason?: ?string}>  $results
     */
    public function ack(array $results): void
    {
        $this->authenticated()->post('/api/messages/ack', ['results' => $results])->throw();
    }

    public function unlink(): void
    {
        $this->authenticated()->delete('/api/devices/me/link')->throw();
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl((string) config('services.whatsapp_sync.url'))
            ->acceptJson()
            ->connectTimeout(2)
            ->timeout(3);
    }

    private function authenticated(): PendingRequest
    {
        return $this->request()->withToken((string) $this->link->apiToken());
    }
}
