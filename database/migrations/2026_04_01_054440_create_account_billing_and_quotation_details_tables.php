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
        Schema::create('account_billing_details', function (Blueprint $table) {
            $table->string('account_bdid', 6)->primary();
            $table->string('accountid', 10);
            $table->string('serial_number', 20);
            $table->string('billing_name', 150);
            $table->text('address');
            $table->string('gstin', 50)->nullable();
            $table->string('tin', 50)->nullable();
            $table->text('terms_conditions')->nullable();
            $table->timestamps();

            $table->foreign('accountid')->references('accountid')->on('accounts')->onDelete('cascade');
            $table->index('accountid');
        });

        Schema::create('account_quotation_details', function (Blueprint $table) {
            $table->string('account_qdid', 6)->primary();
            $table->string('accountid', 10);
            $table->string('serial_number', 20);
            $table->string('quotation_name', 150);
            $table->text('address');
            $table->string('gstin', 50)->nullable();
            $table->string('tin', 50)->nullable();
            $table->text('terms_conditions')->nullable();
            $table->timestamps();

            $table->foreign('accountid')->references('accountid')->on('accounts')->onDelete('cascade');
            $table->index('accountid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_quotation_details');
        Schema::dropIfExists('account_billing_details');
    }
};
