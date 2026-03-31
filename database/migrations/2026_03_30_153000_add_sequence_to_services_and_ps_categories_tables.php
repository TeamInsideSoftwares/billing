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
        Schema::table('services', function (Blueprint $table) {
            $table->unsignedInteger('sequence')->default(0)->after('name');
        });

        Schema::table('ps_categories', function (Blueprint $table) {
            $table->unsignedInteger('sequence')->default(0)->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('sequence');
        });

        Schema::table('ps_categories', function (Blueprint $table) {
            $table->dropColumn('sequence');
        });
    }
};
