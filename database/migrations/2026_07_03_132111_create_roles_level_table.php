<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles_level', function (Blueprint $table) {
            $table->uuid('levelid')->primary();
            $table->string('level_name');
            $table->integer('level_value');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // Seed the table with default values
        $levels = [];
        for ($i = 1; $i <= 6; $i++) {
            $levels[] = [
                'levelid' => (string) \Illuminate\Support\Str::uuid(),
                'level_name' => "Level {$i}" . ($i === 6 ? ' (Top)' : ''),
                'level_value' => $i,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('roles_level')->insert($levels);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles_level');
    }
};
