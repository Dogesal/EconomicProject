<?php

namespace App\Listeners;

use App\Infrastructure\Http\WebhookServerClient;
use App\Support\WhatsAppLink;
use Illuminate\Support\Facades\Log;
use Native\Mobile\Events\PushNotification\TokenGenerated;
use Throwable;

/**
 * Cuando el dispositivo genera su token FCM, se sube al servidor de sync
 * para que pueda despertar la app con un push al llegar un WhatsApp.
 */
class StorePushToken
{
    public function __construct(
        private readonly WhatsAppLink $link,
        private readonly WebhookServerClient $client,
    ) {}

    public function handle(TokenGenerated $event): void
    {
        if (! $this->link->isConfigured() || $this->link->apiToken() === null) {
            return;
        }

        try {
            $this->client->updateFcmToken($event->token);
        } catch (Throwable $e) {
            // Los tokens FCM rotan: si falla ahora, el próximo enroll lo reintenta.
            Log::info('FCM token upload failed: '.$e->getMessage());
        }
    }
}
