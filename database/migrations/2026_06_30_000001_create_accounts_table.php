<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('type');
            $table->char('currency', 3);
            $table->bigInteger('initial_balance')->default(0);
            $table->bigInteger('current_balance')->default(0);
            $table->string('color')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_archived');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
