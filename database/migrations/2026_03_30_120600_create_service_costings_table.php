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
        Schema::create('service_costings', function (Blueprint $table) {
            $table->string('costingid', 8)->primary();
            $table->string('accountid', 10);
            $table->string('serviceid', 6);
            $table->string('currency_code', 3);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['serviceid', 'currency_code']);
            $table->index(['accountid', 'serviceid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_costings');
    }
};
