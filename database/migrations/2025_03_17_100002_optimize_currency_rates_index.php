<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('currency_rates', function (Blueprint $table) {
            $table->dropIndex(['base_currency', 'generation']);
            $table->index(['base_currency', 'generation', 'currency_code'], 'idx_rates_base_gen_code');
        });
    }

    public function down(): void
    {
        Schema::table('currency_rates', function (Blueprint $table) {
            $table->dropIndex('idx_rates_base_gen_code');
            $table->index(['base_currency', 'generation']);
        });
    }
};
