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
        Schema::table('groups', function (Blueprint $table) {
            $table->renameColumn('address_line_1', 'registered_address');
            $table->dropColumn('address_line_2');

            $table->string('business_address', 150)->nullable();
            $table->string('business_city', 100)->nullable();
            $table->string('business_state', 100)->nullable();
            $table->string('business_postal_code', 20)->nullable();
            $table->string('business_country', 100)->default('India');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->renameColumn('registered_address', 'address_line_1');
            $table->string('address_line_2', 150)->nullable();

            $table->dropColumn([
                'business_address',
                'business_city',
                'business_state',
                'business_postal_code',
                'business_country',
            ]);
        });
    }
};
