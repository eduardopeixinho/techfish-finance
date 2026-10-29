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
        Schema::create('investment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // FII, Ação, BDR, Tesouro, CDB, etc.
            $table->string('code')->unique(); // fii, stock, bdr, treasury, cdb
            $table->text('description')->nullable();
            $table->boolean('has_dividends')->default(true); // Se paga proventos
            $table->string('color', 7)->default('#3B82F6'); // Cor para gráficos
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_types');
    }
};
