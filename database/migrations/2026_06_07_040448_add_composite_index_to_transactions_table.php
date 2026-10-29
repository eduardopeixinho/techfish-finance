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
        Schema::table('transactions', function (Blueprint $table) {
            // Índice composto para relatórios por categoria
            $table->index(['user_id', 'category_id', 'type', 'transaction_date'], 'idx_transactions_report_category');

            // Índice composto para relatórios por conta
            $table->index(['user_id', 'account_id', 'type', 'transaction_date'], 'idx_transactions_report_account');

            // Índice composto para faturas de cartão
            $table->index(['user_id', 'account_id', 'bill_identifier'], 'idx_transactions_bill');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_report_category');
            $table->dropIndex('idx_transactions_report_account');
            $table->dropIndex('idx_transactions_bill');
        });
    }
};
