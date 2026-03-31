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
        Schema::table('clients', function (Blueprint $table) {
            $table->string('logo_path', 255)->nullable()->after('groupid');
            $table->string('whatsapp_number', 30)->nullable()->after('phone');
            $table->string('group_name', 150)->nullable()->after('business_name');
            $table->string('currency', 3)->default('INR')->after('status');
        });

        Schema::create('client_billing_details', function (Blueprint $table) {
            $table->id();
            $table->string('clientid', 6);
            $table->string('gstin', 20)->nullable();
            $table->string('billing_email', 150)->nullable();
            $table->string('address_line_1', 150)->nullable();
            $table->string('address_line_2', 150)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->default('India');
            $table->timestamps();

            $table->foreign('clientid')->references('clientid')->on('clients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_billing_details');

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['logo_path', 'whatsapp_number', 'group_name', 'currency']);
        });
    }
};
