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
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('investment_type_id')->constrained()->onDelete('cascade');
            $table->string('ticker'); // BBAS3, HGLG11, BOVA11, etc.
            $table->string('name'); // Nome do investimento
            $table->text('description')->nullable();
            $table->decimal('current_price', 15, 4)->nullable(); // Preço atual
            $table->decimal('total_invested', 15, 2)->default(0); // Total investido
            $table->integer('current_quantity')->default(0); // Quantidade atual
            $table->decimal('average_price', 15, 4)->default(0); // Preço médio
            $table->decimal('current_value', 15, 2)->default(0); // Valor atual da posição
            $table->decimal('profit_loss', 15, 2)->default(0); // Lucro/Prejuízo
            $table->decimal('profit_loss_percentage', 8, 4)->default(0); // % Lucro/Prejuízo
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'ticker']);
            $table->unique(['user_id', 'ticker']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};
