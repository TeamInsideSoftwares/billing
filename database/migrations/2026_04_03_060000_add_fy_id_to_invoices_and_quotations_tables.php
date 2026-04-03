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
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('fy_id', 6)->nullable()->after('accountid');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->string('fy_id', 6)->nullable()->after('accountid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('fy_id');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn('fy_id');
        });
    }
};
