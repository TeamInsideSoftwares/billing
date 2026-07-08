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
        Schema::table('payroll_components', function (Blueprint $table) {
            $table->string('accountid', 20)->change();
        });

        Schema::table('account_policies', function (Blueprint $table) {
            $table->string('accountid', 20)->change();
        });

        Schema::table('user_salaries', function (Blueprint $table) {
            $table->string('accountid', 20)->change();
        });

        Schema::table('user_policies', function (Blueprint $table) {
            $table->string('accountid', 20)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No safe down operation since changing string back to integer will fail with string data.
    }
};
