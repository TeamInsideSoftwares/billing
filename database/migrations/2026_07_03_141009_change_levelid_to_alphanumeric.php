<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop foreign key from account_roles
        Schema::table('account_roles', function (Blueprint $table) {
            $table->dropForeign(['levelid']);
        });

        // Set levelid in account_roles to null to avoid constraints when changing type
        DB::table('account_roles')->update(['levelid' => null]);

        // Change column type in account_roles
        Schema::table('account_roles', function (Blueprint $table) {
            $table->string('levelid', 6)->nullable()->change();
        });

        // Delete old uuid seed data
        DB::table('roles_level')->truncate();

        // Change column type in roles_level
        Schema::table('roles_level', function (Blueprint $table) {
            $table->string('levelid', 6)->change();
        });

        // Re-seed roles_level with alphanumeric ID
        $levels = [];
        for ($i = 1; $i <= 6; $i++) {
            $levels[] = [
                'levelid' => Str::upper(Str::random(6)),
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
        // Re-adding foreign key in down
        Schema::table('account_roles', function (Blueprint $table) {
            $table->uuid('levelid')->nullable()->change();
            $table->foreign('levelid')->references('levelid')->on('roles_level')->onDelete('set null');
        });

        Schema::table('roles_level', function (Blueprint $table) {
            $table->uuid('levelid')->change();
        });
    }
};
