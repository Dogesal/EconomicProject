<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('type');
            $table->bigInteger('amount');
            $table->char('currency', 3);
            $table->string('description')->nullable();
            $table->string('frequency');
            $table->unsignedSmallInteger('interval')->default(1);
            $table->date('next_run_on');
            $table->date('end_on')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('next_run_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_transactions');
    }
};
