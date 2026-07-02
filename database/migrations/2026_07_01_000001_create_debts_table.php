<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('direction');
            $table->bigInteger('original_amount');
            $table->bigInteger('paid_amount')->default(0);
            $table->char('currency', 3);
            $table->date('due_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('direction');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
