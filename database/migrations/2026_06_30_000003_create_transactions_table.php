<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('type');
            $table->bigInteger('amount'); // positive magnitude in minor units
            $table->char('currency', 3);
            $table->boolean('is_inflow'); // drives balance effect: true => +amount, false => -amount
            $table->string('description')->nullable();
            $table->date('occurred_on');
            $table->uuid('transfer_group_id')->nullable();
            $table->uuid('recurring_transaction_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'occurred_on']);
            $table->index('transfer_group_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
