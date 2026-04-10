<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_billing_details', function (Blueprint $table) {
            $table->string('billing_name', 150)->nullable()->default(null)->change();
        });

        Schema::table('account_quotation_details', function (Blueprint $table) {
            $table->string('quotation_name', 150)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('account_billing_details', function (Blueprint $table) {
            $table->string('billing_name', 150)->nullable(false)->change();
        });

        Schema::table('account_quotation_details', function (Blueprint $table) {
            $table->string('quotation_name', 150)->nullable(false)->change();
        });
    }
};
