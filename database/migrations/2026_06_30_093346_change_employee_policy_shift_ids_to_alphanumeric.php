<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE team_employees MODIFY employeeid VARCHAR(6) NOT NULL');
        DB::statement('ALTER TABLE team_employees MODIFY att_policyid VARCHAR(6) NULL');
        DB::statement('ALTER TABLE team_employees MODIFY shiftid VARCHAR(6) NULL');

        DB::statement('ALTER TABLE attendance_policies MODIFY att_policyid VARCHAR(6) NOT NULL');
        DB::statement('ALTER TABLE shifts MODIFY shiftid VARCHAR(6) NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE team_employees MODIFY employeeid BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        DB::statement('ALTER TABLE team_employees MODIFY att_policyid BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE team_employees MODIFY shiftid BIGINT UNSIGNED NULL');

        DB::statement('ALTER TABLE attendance_policies MODIFY att_policyid BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        DB::statement('ALTER TABLE shifts MODIFY shiftid BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
    }
};
