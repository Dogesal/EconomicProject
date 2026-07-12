<?php

use Database\Seeders\CatalogSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Instalaciones existentes ya corrieron el seed del catálogo, así que
     * el MXN nuevo del CatalogSeeder solo les llega re-ejecutándolo aquí
     * (es idempotente: updateOrCreate por código de moneda).
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
        DB::table('currencies')->where('code', 'MXN')->delete();
    }
};
