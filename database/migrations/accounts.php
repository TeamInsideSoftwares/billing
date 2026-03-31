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

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('fy_startdate');
        });
    }
};
