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
            $table->integer('prefix_length')->default(0)->after('prefix_value');
            $table->integer('suffix_length')->default(0)->after('suffix_value');
        });

        Schema::table('account_quotation_details', function (Blueprint $table) {
            $table->integer('prefix_length')->default(0)->after('prefix_value');
            $table->integer('suffix_length')->default(0)->after('suffix_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_billing_details', function (Blueprint $table) {
            $table->dropColumn(['prefix_length', 'suffix_length']);
        });

        Schema::table('account_quotation_details', function (Blueprint $table) {
            $table->dropColumn(['prefix_length', 'suffix_length']);
        });
    }
};
