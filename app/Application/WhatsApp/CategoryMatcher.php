<?php

namespace App\Application\WhatsApp;

use App\Domain\Enums\CategoryType;
use App\Domain\Models\Category;

/**
 * Matchea el texto libre de categoría de un mensaje de WhatsApp contra las
 * categorías locales del usuario, ignorando mayúsculas y acentos. Si solo
 * matchea la primera palabra, el resto se conserva como descripción; si no
 * matchea nada, todo el texto pasa a la descripción.
 */
class CategoryMatcher
{
    public function match(?string $text, CategoryType $type): CategoryMatch
    {
        $text = trim((string) $text);

        if ($text === '') {
            return new CategoryMatch(null, null);
        }

        $categories = Category::where('type', $type)->get();
        $normalized = $this->normalize($text);

        foreach ($categories as $category) {
            if ($this->normalize($category->name) === $normalized) {
                return new CategoryMatch($category, null);
            }
        }

        $tokens = explode(' ', $normalized);
        $firstToken = $tokens[0];

        foreach ($categories as $category) {
            if ($this->normalize($category->name) === $firstToken) {
                $rest = trim(implode(' ', array_slice(explode(' ', $text), 1)));

                return new CategoryMatch($category, $rest === '' ? null : $rest);
            }
        }

        return new CategoryMatch(null, $text);
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
