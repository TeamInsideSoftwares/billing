<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'clients',
            'client_billing_details',
            'services',
            'items',
            'invoices',
            'tax_invoices',
            'invoice_items',
            'ti_items',
            'payments',
            'subscriptions',
            'estimates',
            'quotations',
            'estimate_items',
            'quotation_items',
            'settings',
            'ps_categories',
            'financial_year',
            'groups',
            'service_costings',
            'item_costings',
            'service_addons',
            'service_addon_costings',
            'terms_conditions',
            'account_billing_details',
            'account_quotation_details',
            'account_taxes',
            'orders',
            'order_items',
            'proforma_invoices',
            'pi_items',
            'serial_configurations',
        ];

        foreach ($tables as $table) {
            $this->dropAllForeignKeys($table);
        }
    }

    public function down(): void
    {
        // Foreign keys are intentionally not recreated.
    }

    private function dropAllForeignKeys(string $table): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $database = DB::getDatabaseName();

        $constraints = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->pluck('CONSTRAINT_NAME');

        foreach ($constraints as $constraint) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        }
    }
};
