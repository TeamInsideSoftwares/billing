<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('team_employees');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-creating the table is omitted for brevity as it is permanently removed
    }
};
