<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ledger')) {
            return;
        }

        if (Schema::hasColumn('ledger', 'reference_number') && ! Schema::hasColumn('ledger', 'invoiceid_paymentid')) {
            Schema::table('ledger', function (Blueprint $table) {
                $table->renameColumn('reference_number', 'invoiceid_paymentid');
            });
        }

        try {
            DB::statement('ALTER TABLE `ledger` DROP INDEX `ledger_reference_type_unique`');
        } catch (Throwable $e) {
            // Index may already be missing or renamed.
        }

        // Allow both legacy and new enum values during data migration.
        DB::statement("ALTER TABLE `ledger` MODIFY `type` ENUM('payment','tds','invoice','dr','cr') NOT NULL");

        DB::table('ledger')
            ->where('type', 'invoice')
            ->update(['type' => 'dr']);

        DB::table('ledger')
            ->whereIn('type', ['payment', 'tds'])
            ->update(['type' => 'cr']);

        DB::statement("ALTER TABLE `ledger` MODIFY `type` ENUM('dr','cr') NOT NULL");

        try {
            DB::statement('ALTER TABLE `ledger` ADD UNIQUE `ledger_invoice_payment_type_unique` (`invoiceid_paymentid`, `type`)');
        } catch (Throwable $e) {
            // Unique index may already exist.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('ledger')) {
            return;
        }

        try {
            DB::statement('ALTER TABLE `ledger` DROP INDEX `ledger_invoice_payment_type_unique`');
        } catch (Throwable $e) {
            // Index may already be missing.
        }

        // Allow both new and legacy enum values during rollback data migration.
        DB::statement("ALTER TABLE `ledger` MODIFY `type` ENUM('payment','tds','invoice','dr','cr') NOT NULL");

        DB::table('ledger')
            ->where('type', 'dr')
            ->update(['type' => 'invoice']);

        DB::table('ledger')
            ->where('type', 'cr')
            ->update(['type' => 'payment']);

        if (Schema::hasColumn('ledger', 'invoiceid_paymentid') && ! Schema::hasColumn('ledger', 'reference_number')) {
            Schema::table('ledger', function (Blueprint $table) {
                $table->renameColumn('invoiceid_paymentid', 'reference_number');
            });
        }

        try {
            DB::statement('ALTER TABLE `ledger` ADD UNIQUE `ledger_reference_type_unique` (`reference_number`, `type`)');
        } catch (Throwable $e) {
            // Unique index may already exist.
        }
    }
};
