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
            $table->string('prefix_type')->default('manual text')->after('suffix');
            $table->string('prefix_value')->nullable()->after('prefix_type');
            $table->string('number_type')->default('auto increment')->after('serial_mode');
            $table->string('number_value')->nullable()->after('number_type');
            $table->integer('number_length')->default(4)->after('number_value');
            $table->string('suffix_type')->default('manual text')->after('number_length');
            $table->string('suffix_value')->nullable()->after('suffix_type');
        });

        Schema::table('account_quotation_details', function (Blueprint $table) {
            $table->string('prefix_type')->default('manual text')->after('suffix');
            $table->string('prefix_value')->nullable()->after('prefix_type');
            $table->string('number_type')->default('auto increment')->after('serial_mode');
            $table->string('number_value')->nullable()->after('number_type');
            $table->integer('number_length')->default(4)->after('number_value');
            $table->string('suffix_type')->default('manual text')->after('number_length');
            $table->string('suffix_value')->nullable()->after('suffix_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_billing_details', function (Blueprint $table) {
            $table->dropColumn([
                'prefix_type', 'prefix_value',
                'number_type', 'number_value', 'number_length',
                'suffix_type', 'suffix_value'
            ]);
        });

        Schema::table('account_quotation_details', function (Blueprint $table) {
            $table->dropColumn([
                'prefix_type', 'prefix_value',
                'number_type', 'number_value', 'number_length',
                'suffix_type', 'suffix_value'
            ]);
        });
    }
};
