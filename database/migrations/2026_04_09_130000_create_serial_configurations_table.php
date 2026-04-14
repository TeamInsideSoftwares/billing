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
        Schema::create('serial_configurations', function (Blueprint $table) {
            $table->string('serial_configid', 6)->primary();
            $table->string('accountid', 10)->index();
            $table->string('document_type')->index(); // invoice, quotation
            $table->string('fy_id', 6)->nullable()->index();
            
            // Prefix configuration
            $table->string('prefix_type')->default('manual text');
            $table->string('prefix_value')->nullable();
            $table->string('prefix_length')->nullable();
            $table->string('prefix_separator')->default('none');
            
            // Number configuration
            $table->string('number_type')->default('auto increment');
            $table->string('number_value')->nullable();
            $table->string('number_length')->nullable();
            $table->string('number_separator')->default('none');
            
            // Suffix configuration
            $table->string('suffix_type')->default('manual text');
            $table->string('suffix_value')->nullable();
            $table->string('suffix_length')->nullable();
            
            // Settings
            $table->boolean('reset_on_fy')->default(false);
            
            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serial_configurations');
    }
};
