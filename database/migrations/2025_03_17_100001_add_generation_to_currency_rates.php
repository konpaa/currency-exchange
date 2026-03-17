<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('currency_rates', function (Blueprint $table) {
            $table->unsignedBigInteger('generation')->default(1)->after('rate');
        });

        Schema::table('currency_rates', function (Blueprint $table) {
            $table->dropUnique(['base_currency', 'currency_code']);
            $table->index(['base_currency', 'generation']);
        });
    }

    public function down(): void
    {
        Schema::table('currency_rates', function (Blueprint $table) {
            $table->dropIndex(['base_currency', 'generation']);
            $table->unique(['base_currency', 'currency_code']);
        });
        Schema::table('currency_rates', function (Blueprint $table) {
            $table->dropColumn('generation');
        });
    }
};
