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
        // Fix account_billing_details
        Schema::table('account_billing_details', function (Blueprint $table) {
            // Drop the confusing use_auto_generate column
            $table->dropColumn('use_auto_generate');
        });

        Schema::table('account_billing_details', function (Blueprint $table) {
            // Add clean structure
            $table->enum('serial_mode', ['auto_generate', 'auto_increment'])->default('auto_generate')->after('suffix');
            $table->tinyInteger('alphanumeric_length')->default(4)->unsigned()->nullable()->after('serial_mode')->comment('For auto_generate: number of characters (4 or 6)');
            // Modify existing columns to be nullable and add comments
            $table->unsignedInteger('auto_increment_start')->default(1)->nullable()->comment('For auto_increment: starting number like 1001, 101, etc.')->change();
            $table->boolean('reset_on_fy')->default(false)->comment('For auto_increment: reset counter on financial year')->change();
        });

        // Fix account_quotation_details
        Schema::table('account_quotation_details', function (Blueprint $table) {
            // Drop the confusing use_auto_generate column
            $table->dropColumn('use_auto_generate');
        });

        Schema::table('account_quotation_details', function (Blueprint $table) {
            // Add clean structure
            $table->enum('serial_mode', ['auto_generate', 'auto_increment'])->default('auto_generate')->after('suffix');
            $table->tinyInteger('alphanumeric_length')->default(4)->unsigned()->nullable()->after('serial_mode')->comment('For auto_generate: number of characters (4 or 6)');
            // Modify existing columns to be nullable and add comments
            $table->unsignedInteger('auto_increment_start')->default(1)->nullable()->comment('For auto_increment: starting number like 1001, 101, etc.')->change();
            $table->boolean('reset_on_fy')->default(false)->comment('For auto_increment: reset counter on financial year')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_billing_details', function (Blueprint $table) {
            $table->dropColumn(['serial_mode', 'alphanumeric_length', 'auto_increment_start', 'reset_on_fy']);
        });

        Schema::table('account_quotation_details', function (Blueprint $table) {
            $table->dropColumn(['serial_mode', 'alphanumeric_length', 'auto_increment_start', 'reset_on_fy']);
        });
    }
};
