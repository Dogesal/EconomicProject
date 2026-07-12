<?php

namespace Database\Seeders;

use App\Domain\Enums\CategoryType;
use App\Domain\Models\Category;
use App\Domain\Models\Currency;
use Illuminate\Database\Seeder;

/**
 * Base catalog every fresh install needs: currencies and default categories.
 * Runs from a migration on-device (NativePHP only executes `migrate --force`
 * at boot, never `db:seed`), so it must be idempotent.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['PEN', 'Sol peruano', 'S/', 2],
            ['USD', 'Dólar estadounidense', '$', 2],
            ['EUR', 'Euro', '€', 2],
            ['ARS', 'Peso argentino', '$', 2],
            ['BRL', 'Real brasileño', 'R$', 2],
            ['CLP', 'Peso chileno', '$', 0],
            ['MXN', 'Peso mexicano', '$', 2],
        ])->each(fn ($c) => Currency::updateOrCreate(
            ['code' => $c[0]],
            ['name' => $c[1], 'symbol' => $c[2], 'decimals' => $c[3]],
        ));

        if (Category::query()->exists()) {
            return;
        }

        collect([
            ['Sueldo', CategoryType::Income, '💼', '#059669'],
            ['Otros ingresos', CategoryType::Income, '💰', '#0891b2'],
            ['Comida', CategoryType::Expense, '🍽️', '#d97706'],
            ['Transporte', CategoryType::Expense, '🚌', '#4f46e5'],
            ['Servicios', CategoryType::Expense, '💡', '#0891b2'],
            ['Salud', CategoryType::Expense, '🏥', '#dc2626'],
            ['Ocio', CategoryType::Expense, '🎮', '#9333ea'],
            ['Educación', CategoryType::Expense, '📚', '#475569'],
        ])->each(fn ($c) => Category::create([
            'name' => $c[0],
            'type' => $c[1],
            'icon' => $c[2],
            'color' => $c[3],
        ]));
    }
}
