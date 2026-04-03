<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_year', function (Blueprint $table) {
            $table->string('prefix_separator', 10)->nullable()->after('financial_year');
            $table->string('number_separator', 10)->nullable()->after('prefix_separator');
        });
    }

    public function down(): void
    {
        Schema::table('financial_year', function (Blueprint $table) {
            $table->dropColumn(['prefix_separator', 'number_separator']);
        });
    }
};
