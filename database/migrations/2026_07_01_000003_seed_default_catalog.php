<?php

use Database\Seeders\CatalogSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * On-device installs (NativePHP) only run migrations, never seeders, so
     * the base catalog (currencies + default categories) is seeded here.
     * Skipped in tests: they build their own fixtures and assert counts.
     */
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        (new CatalogSeeder)->run();
    }

    public function down(): void
    {
        // Catalog data is left in place; nothing to reverse safely.
    }
};
