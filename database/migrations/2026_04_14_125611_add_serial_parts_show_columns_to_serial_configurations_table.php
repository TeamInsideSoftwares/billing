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
        Schema::table('serial_configurations', function (Blueprint $table) {
            $table->tinyInteger('prefix_show')->default(1)->after('document_type');
            $table->tinyInteger('number_show')->default(1)->after('prefix_show');
            $table->tinyInteger('suffix_show')->default(1)->after('number_show');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('serial_configurations', function (Blueprint $table) {
            $table->dropColumn(['prefix_show', 'number_show', 'suffix_show']);
        });
    }
};
