<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pagamentos de fatura de cartão.
     *
     * - Com transaction_id: pagamento de verdade (transferência da conta de origem para o cartão).
     * - Sem transaction_id: fatura só marcada como paga (histórico que nunca foi lançado; não mexe em saldo).
     *
     * Valor em R$. A fatura é identificada por cartão (account_id) + bill_identifier ("MM-AAAA", mês do vencimento).
     */
    public function up(): void
    {
        Schema::create('bill_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('bill_identifier', 7);
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('paid_at');
            $table->timestamps();

            $table->index(['account_id', 'bill_identifier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_payments');
    }
};
