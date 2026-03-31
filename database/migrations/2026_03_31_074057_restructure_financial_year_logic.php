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
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('fy_startdate', 10)->nullable()->after('timezone'); // format: 'MM-DD'
        });

        Schema::table('financial_year', function (Blueprint $table) {
            $table->dropColumn(['duration_months', 'start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_year', function (Blueprint $table) {
            $table->unsignedInteger('duration_months')->default(12);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('fy_startdate');
        });
    }
};
