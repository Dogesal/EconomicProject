<?php

namespace App\Support;

use App\Domain\Models\Account;
use App\Domain\Models\Setting;

/**
 * Estado local de la vinculación con el bot de WhatsApp: credenciales del
 * dispositivo contra el servidor de sync y la cuenta destino por defecto.
 * La feature es opcional: sin vincular, la app sigue 100% offline.
 */
class WhatsAppLink
{
    public const DEVICE_ID_KEY = 'whatsapp_device_id';

    public const API_TOKEN_KEY = 'whatsapp_api_token';

    public const LINKED_KEY = 'whatsapp_linked';

    public const DEFAULT_ACCOUNT_KEY = 'whatsapp_default_account_id';

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

    public function defaultAccount(): ?Account
    {
        $accountId = Setting::get(self::DEFAULT_ACCOUNT_KEY);

        return $accountId !== null ? Account::find($accountId) : null;
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

    public function storeDefaultAccount(string $accountId): void
    {
        Setting::put(self::DEFAULT_ACCOUNT_KEY, $accountId);
    }

    public function clear(): void
    {
        Setting::put(self::LINKED_KEY, null);
        Setting::put(self::DEFAULT_ACCOUNT_KEY, null);
    }
}
