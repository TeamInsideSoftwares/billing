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
            $table->string('renewed_to_proformaid', 10)->nullable()->after('end_date');
            $table->timestamp('renewed_at')->nullable()->after('renewed_to_proformaid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pi_items', function (Blueprint $table) {
            $table->dropColumn(['renewed_to_proformaid', 'renewed_at']);
        });
    }
};
