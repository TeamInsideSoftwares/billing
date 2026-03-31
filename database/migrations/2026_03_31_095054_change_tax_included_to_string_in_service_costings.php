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
        Schema::table('service_costings', function (Blueprint $table) {
            $table->string('tax_included', 3)->default('no')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_costings', function (Blueprint $table) {
            $table->boolean('tax_included')->default(false)->change();
        });
    }
};
