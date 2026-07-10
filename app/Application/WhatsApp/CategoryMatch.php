<?php

namespace App\Application\WhatsApp;

use App\Domain\Models\Category;

/**
 * Resultado del matching: la categoría encontrada (o null) y el texto
 * sobrante que pasa a la descripción de la transacción.
 */
final class CategoryMatch
{
    public function __construct(
        public readonly ?Category $category,
        public readonly ?string $description,
    ) {}
}
