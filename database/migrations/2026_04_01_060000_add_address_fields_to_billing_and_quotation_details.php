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
            $table->string('city', 100)->nullable()->after('address');
            $table->string('state', 100)->nullable()->after('city');
            $table->string('country', 100)->nullable()->after('state');
            $table->string('postal_code', 20)->nullable()->after('country');
        });

        Schema::table('account_quotation_details', function (Blueprint $table) {
            $table->string('city', 100)->nullable()->after('address');
            $table->string('state', 100)->nullable()->after('city');
            $table->string('country', 100)->nullable()->after('state');
            $table->string('postal_code', 20)->nullable()->after('country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_billing_details', function (Blueprint $table) {
            $table->dropColumn(['city', 'state', 'country', 'postal_code']);
        });

        Schema::table('account_quotation_details', function (Blueprint $table) {
            $table->dropColumn(['city', 'state', 'country', 'postal_code']);
        });
    }
};
