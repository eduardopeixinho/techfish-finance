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
            // Índices para consultas frequentes de faturas de cartão
            $table->index(['user_id', 'bill_identifier'], 'idx_transactions_user_bill');
            $table->index(['user_id', 'competency_date'], 'idx_transactions_user_competency');
            $table->index(['user_id', 'due_date'], 'idx_transactions_user_due_date');

            // Índice composto para dashboard (evolução mensal)
            $table->index(['user_id', 'type', 'transaction_date'], 'idx_transactions_user_type_date');
        });

        Schema::table('investment_transactions', function (Blueprint $table) {
            // Índice para consultas por investimento
            $table->index('investment_id', 'idx_inv_trans_investment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_user_bill');
            $table->dropIndex('idx_transactions_user_competency');
            $table->dropIndex('idx_transactions_user_due_date');
            $table->dropIndex('idx_transactions_user_type_date');
        });

        Schema::table('investment_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_inv_trans_investment');
        });
    }
};
