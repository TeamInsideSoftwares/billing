<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('invoices') || ! Schema::hasColumn('invoices', 'ti_number')) {
            return;
        }

        DB::statement("ALTER TABLE `invoices` MODIFY `ti_number` VARCHAR(30) NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('invoices') || ! Schema::hasColumn('invoices', 'ti_number')) {
            return;
        }

        DB::statement("UPDATE `invoices` SET `ti_number` = '' WHERE `ti_number` IS NULL");
        DB::statement("ALTER TABLE `invoices` MODIFY `ti_number` VARCHAR(30) NOT NULL");
    }
};

