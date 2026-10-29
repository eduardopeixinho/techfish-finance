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
            $table->date('competency_date')->nullable()->after('transaction_date')->comment('Data da competência/fatura para cartões de crédito');
            $table->date('due_date')->nullable()->after('competency_date')->comment('Data de vencimento da fatura');
            $table->string('bill_identifier')->nullable()->after('due_date')->comment('Identificador da fatura (ex: 08/2025)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['competency_date', 'due_date', 'bill_identifier']);
        });
    }
};
