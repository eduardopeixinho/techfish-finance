<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('investments', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('investment_transactions', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('investment_dividends', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('investments', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('investment_transactions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('investment_dividends', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
