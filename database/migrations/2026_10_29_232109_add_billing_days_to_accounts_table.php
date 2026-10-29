<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cartão de crédito: dia em que a fatura fecha e dia em que vence (1–31).
     * Definem em qual fatura cada compra cai (CreditCardBillService::billFor).
     * Nulos em contas que não são cartão.
     */
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->unsignedTinyInteger('closing_day')->nullable()->after('type');
            $table->unsignedTinyInteger('due_day')->nullable()->after('closing_day');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['closing_day', 'due_day']);
        });
    }
};
