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
        Schema::create('investment_dividends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('investment_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['dividend', 'jscp', 'rent', 'bonus', 'split', 'grouping']);
            // dividend = dividendo, jscp = juros sobre capital próprio, rent = aluguel (FII)
            // bonus = bonificação, split = desdobramento, grouping = grupamento
            $table->decimal('amount', 15, 2); // Valor do provento
            $table->decimal('amount_per_share', 15, 6); // Valor por cota/ação
            $table->integer('quantity_owned'); // Quantidade que possuía na data
            $table->date('record_date'); // Data de registro
            $table->date('payment_date'); // Data de pagamento
            $table->decimal('tax_withheld', 15, 2)->default(0); // IR retido
            $table->decimal('net_amount', 15, 2); // Valor líquido recebido
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'investment_id'], 'inv_div_user_investment_idx');
            $table->index(['payment_date', 'type'], 'inv_div_date_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_dividends');
    }
};
