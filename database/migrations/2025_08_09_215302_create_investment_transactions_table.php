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
        Schema::create('investment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('investment_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['buy', 'sell']); // Compra ou Venda
            $table->integer('quantity'); // Quantidade
            $table->decimal('price', 15, 4); // Preço por unidade
            $table->decimal('total_amount', 15, 2); // Valor total da operação
            $table->decimal('fees', 15, 2)->default(0); // Taxas (corretagem, etc.)
            $table->decimal('net_amount', 15, 2); // Valor líquido
            $table->date('transaction_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'investment_id'], 'inv_trans_user_investment_idx');
            $table->index(['transaction_date', 'type'], 'inv_trans_date_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_transactions');
    }
};
