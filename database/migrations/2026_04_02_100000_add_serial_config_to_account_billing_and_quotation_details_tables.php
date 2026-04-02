<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_billing_details', function (Blueprint $table) {
            $table->string('prefix', 50)->nullable()->after('serial_number');
            $table->string('suffix', 50)->nullable()->after('prefix');
            $table->boolean('use_auto_generate')->default(false)->after('suffix');
            $table->unsignedInteger('auto_increment_start')->default(1)->after('use_auto_generate');
            $table->boolean('reset_on_fy')->default(false)->after('auto_increment_start');
        });

        Schema::table('account_quotation_details', function (Blueprint $table) {
            $table->string('prefix', 50)->nullable()->after('serial_number');
            $table->string('suffix', 50)->nullable()->after('prefix');
            $table->boolean('use_auto_generate')->default(false)->after('suffix');
            $table->unsignedInteger('auto_increment_start')->default(1)->after('use_auto_generate');
            $table->boolean('reset_on_fy')->default(false)->after('auto_increment_start');
        });
    }

    public function down(): void
    {
        Schema::table('account_billing_details', function (Blueprint $table) {
            $table->dropColumn(['prefix', 'suffix', 'use_auto_generate', 'auto_increment_start', 'reset_on_fy']);
        });

        Schema::table('account_quotation_details', function (Blueprint $table) {
            $table->dropColumn(['prefix', 'suffix', 'use_auto_generate', 'auto_increment_start', 'reset_on_fy']);
        });
    }
};

