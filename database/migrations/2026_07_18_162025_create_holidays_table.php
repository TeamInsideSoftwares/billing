<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->string('holidayid', 10)->primary();
            $table->string('accountid', 10)->nullable();
            $table->string('title');
            $table->date('holiday_date');
            $table->string('type', 20)->default('custom'); // 'weekend', 'custom'
            $table->timestamps();
            
            $table->index('accountid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
