<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->string('type')->comment('income, expense, transfer');
            $table->date('transaction_date');
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('financial_categories')->onDelete('set null');
            $table->foreignId('transfer_account_id')->nullable()->constrained('accounts')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->string('payment_method')->nullable()->comment('cash, credit_card, debit_card, bank_transfer, pix');
            $table->string('status')->default('completed')->comment('completed, pending, cancelled');
            $table->json('tags')->nullable();
            $table->string('receipt_path')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->index(['user_id', 'transaction_date']);
            $table->index(['account_id', 'type']);
            $table->index(['category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
