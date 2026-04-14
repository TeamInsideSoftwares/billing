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
        Schema::create('service_addon_costings', function (Blueprint $table) {
            $table->string('addon_cid', 6)->primary();
            $table->string('accountid', 10);
            $table->string('addonid', 6);
            $table->string('currency_code', 3);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->string('sac_code', 20)->nullable();
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->string('tax_included', 3)->default('no');
            $table->timestamps();

            $table->unique(['addonid', 'currency_code']);
            $table->index(['accountid', 'addonid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_addon_costings');
    }
};
