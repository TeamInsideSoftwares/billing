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
        Schema::table('account_billing_details', function (Blueprint $table) {
            $table->integer('prefix_start')->default(1)->after('prefix_length');
            $table->integer('suffix_start')->default(1)->after('suffix_length');
        });

        Schema::table('account_quotation_details', function (Blueprint $table) {
            $table->integer('prefix_start')->default(1)->after('prefix_length');
            $table->integer('suffix_start')->default(1)->after('suffix_length');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_billing_details', function (Blueprint $table) {
            $table->dropColumn(['prefix_start', 'suffix_start']);
        });

        Schema::table('account_quotation_details', function (Blueprint $table) {
            $table->dropColumn(['prefix_start', 'suffix_start']);
        });
    }
};
