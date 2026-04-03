<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_billing_details', function (Blueprint $table) {
            $table->string('prefix_separator', 10)->nullable()->after('prefix_length');
            $table->string('number_separator', 10)->nullable()->after('number_length');
        });

        Schema::table('account_quotation_details', function (Blueprint $table) {
            $table->string('prefix_separator', 10)->nullable()->after('prefix_length');
            $table->string('number_separator', 10)->nullable()->after('number_length');
        });
    }

    public function down(): void
    {
        Schema::table('account_billing_details', function (Blueprint $table) {
            $table->dropColumn(['prefix_separator', 'number_separator']);
        });

        Schema::table('account_quotation_details', function (Blueprint $table) {
            $table->dropColumn(['prefix_separator', 'number_separator']);
        });
    }
};
