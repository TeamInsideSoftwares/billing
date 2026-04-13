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
        Schema::table('pi_items', function (Blueprint $table) {
            $table->string('renewed_from_proformaitemid', 10)->nullable()->after('renewed_to_proformaid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pi_items', function (Blueprint $table) {
            $table->dropColumn('renewed_from_proformaitemid');
        });
    }
};
