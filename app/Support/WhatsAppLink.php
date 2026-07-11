<?php

namespace App\Support;

use App\Domain\Models\Setting;

/**
 * Estado local de la vinculación con el bot de WhatsApp: credenciales del
 * dispositivo contra el servidor de sync. La cuenta destino se elige en la
 * conversación de WhatsApp (el bot pregunta), no aquí. La feature es
 * opcional: sin vincular, la app sigue 100% offline.
 */
class WhatsAppLink
{
    public const DEVICE_ID_KEY = 'whatsapp_device_id';

    public const API_TOKEN_KEY = 'whatsapp_api_token';

    public const LINKED_KEY = 'whatsapp_linked';

    public const BOT_PHONE_KEY = 'whatsapp_bot_phone';

    public function isConfigured(): bool
    {
        return (bool) config('services.whatsapp_sync.url');
    }

    public function isLinked(): bool
    {
        return Setting::get(self::LINKED_KEY) === '1' && $this->apiToken() !== null;
    }

    public function deviceId(): ?string
    {
        return Setting::get(self::DEVICE_ID_KEY);
    }

    public function apiToken(): ?string
    {
        return Setting::get(self::API_TOKEN_KEY);
    }

    public function botPhone(): ?string
    {
        return Setting::get(self::BOT_PHONE_KEY);
    }

    public function storeDevice(string $deviceId, string $apiToken): void
    {
        Setting::put(self::DEVICE_ID_KEY, $deviceId);
        Setting::put(self::API_TOKEN_KEY, $apiToken);
    }

    public function markLinked(bool $linked): void
    {
        Setting::put(self::LINKED_KEY, $linked ? '1' : '0');
    }

    public function storeBotPhone(?string $phone): void
    {
        Setting::put(self::BOT_PHONE_KEY, $phone);
    }

    public function clear(): void
    {
        Setting::put(self::LINKED_KEY, null);
        // Limpieza de la clave heredada (la cuenta destino ya se elige en WhatsApp).
        Setting::put('whatsapp_default_account_id', null);
    }
}
