<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_inbox', function (Blueprint $table) {
            // PK = id del mensaje en el servidor: garantiza que un mensaje
            // no se aplique dos veces aunque el ACK se pierda.
            $table->string('id')->primary();
            $table->uuid('transaction_id')->nullable();
            $table->string('status'); // applied|failed
            $table->string('reason')->nullable();
            $table->string('raw_text');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_inbox');
    }
};
