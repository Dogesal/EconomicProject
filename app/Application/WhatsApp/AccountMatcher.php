<?php

namespace App\Application\WhatsApp;

use App\Domain\Models\Account;

/**
 * Matchea el texto libre de cuenta de un mensaje de WhatsApp ("cuenta bcp")
 * contra las cuentas locales, ignorando mayúsculas y acentos. Si no hay
 * match exacto, acepta una coincidencia parcial solo cuando es única
 * (p.ej. "bcp" matchea "BCP Soles" si no hay otra cuenta con "bcp").
 */
class AccountMatcher
{
    public function match(?string $text): ?Account
    {
        $text = trim((string) $text);

        if ($text === '') {
            return null;
        }

        $accounts = Account::where('is_archived', false)->get();
        $normalized = $this->normalize($text);

        foreach ($accounts as $account) {
            if ($this->normalize($account->name) === $normalized) {
                return $account;
            }
        }

        $partial = $accounts->filter(
            fn (Account $account): bool => str_contains($this->normalize($account->name), $normalized)
        );

        return $partial->count() === 1 ? $partial->first() : null;
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
}
