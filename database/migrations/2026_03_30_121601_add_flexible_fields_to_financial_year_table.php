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
        Schema::table('financial_year', function (Blueprint $table) {
            $table->unsignedInteger('duration_months')->default(12);
            $table->date('start_date');
            $table->date('end_date');
            $table->index(['start_date', 'duration_months']);
        });
    }

    /**
     * Reverse the migrations.
     */
public function down(): void
    {
        Schema::table('financial_year', function (Blueprint $table) {
            $table->dropColumn(['duration_months', 'start_date', 'end_date']);
        });
    }
};
