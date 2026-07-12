<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Antes "eliminar" una cuenta la borraba en suave (deleted_at), lo que
     * ocultaba la cuenta y dejaba sus movimientos sin nombre. El nuevo flujo
     * archiva en vez de borrar. Recupera esas cuentas: las desmarca como
     * borradas y las deja archivadas, así reaparecen como registro de solo
     * lectura y sus movimientos vuelven a mostrar la cuenta.
     */
    public function up(): void
    {
        DB::table('accounts')
            ->whereNotNull('deleted_at')
            ->update(['deleted_at' => null, 'is_archived' => true]);
    }

    public function down(): void
    {
        // No se puede distinguir cuáles estaban borradas; no se revierte.
    }
};
