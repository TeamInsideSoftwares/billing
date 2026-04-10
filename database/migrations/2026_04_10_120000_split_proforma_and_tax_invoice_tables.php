<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rename old tables if they exist
        if (Schema::hasTable('invoices')) {
            $this->dropForeignIfExists('invoices', 'converted_from_invoiceid');
            Schema::rename('invoices', 'tax_invoices');
        }

        if (Schema::hasTable('invoice_items')) {
            Schema::rename('invoice_items', 'ti_items');
        }

        // Create proforma_invoices table
        if (!Schema::hasTable('proforma_invoices')) {
            Schema::create('proforma_invoices', function (Blueprint $table) {
                $table->string('proformaid', 6)->primary();
                $table->string('accountid', 10);
                $table->string('fy_id', 20)->nullable();
                $table->string('clientid', 6);
                $table->string('orderid', 6)->nullable();
                $table->string('invoice_number', 30)->unique();
                $table->string('invoice_title', 255)->nullable();
                $table->string('invoice_for', 30)->nullable();
                $table->string('status', 20)->default('draft');
                $table->date('issue_date');
                $table->date('due_date');
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('tax_total', 12, 2)->default(0);
                $table->decimal('discount_total', 12, 2)->default(0);
                $table->decimal('grand_total', 12, 2)->default(0);
                $table->decimal('amount_paid', 12, 2)->default(0);
                $table->decimal('balance_due', 12, 2)->default(0);
                $table->string('currency_code', 10)->default('INR');
                $table->text('notes')->nullable();
                $table->text('terms')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->string('created_by', 10)->nullable();
                $table->timestamps();

                $table->foreign('accountid')->references('accountid')->on('accounts')->onDelete('cascade');
                $table->foreign('clientid')->references('clientid')->on('clients')->onDelete('restrict');
                $table->foreign('orderid')->references('orderid')->on('orders')->onDelete('set null');
                $table->index(['accountid', 'status']);
            });
        }

        // Create tax_invoices table with alphanumeric ID
        if (!Schema::hasTable('tax_invoices')) {
            Schema::create('tax_invoices', function (Blueprint $table) {
                $table->string('invoiceid', 6)->primary(); // Alphanumeric primary key
                $table->string('accountid', 10);
                $table->string('fy_id', 20)->nullable();
                $table->string('clientid', 6);
                $table->string('orderid', 6)->nullable();
                $table->string('proformaid', 6)->nullable();
                $table->string('invoice_number', 30)->unique();
                $table->string('invoice_title', 255)->nullable();
                $table->string('invoice_for', 30)->nullable();
                $table->string('status', 20)->default('draft');
                $table->date('issue_date');
                $table->date('due_date');
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('tax_total', 12, 2)->default(0);
                $table->decimal('discount_total', 12, 2)->default(0);
                $table->decimal('grand_total', 12, 2)->default(0);
                $table->decimal('amount_paid', 12, 2)->default(0);
                $table->decimal('balance_due', 12, 2)->default(0);
                $table->string('currency_code', 10)->default('INR');
                $table->text('notes')->nullable();
                $table->text('terms')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->string('created_by', 10)->nullable();
                $table->timestamps();

                $table->foreign('accountid')->references('accountid')->on('accounts')->onDelete('cascade');
                $table->foreign('clientid')->references('clientid')->on('clients')->onDelete('restrict');
                $table->foreign('orderid')->references('orderid')->on('orders')->onDelete('set null');
                $table->foreign('proformaid')->references('proformaid')->on('proforma_invoices')->onDelete('set null');
                $table->index(['accountid', 'status']);
                $table->index('proformaid');
            });
        }

        // Create pi_items table (proforma invoice items)
        if (!Schema::hasTable('pi_items')) {
            Schema::create('pi_items', function (Blueprint $table) {
                $table->string('proformaitemid', 6)->primary();
                $table->string('proformaid', 6);
                $table->string('itemid', 6)->nullable();
                $table->string('item_name', 150);
                $table->text('item_description')->nullable();
                $table->decimal('quantity', 10, 2)->default(1);
                $table->decimal('unit_price', 12, 2)->default(0);
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->string('taxid', 20)->nullable();
                $table->integer('duration')->nullable();
                $table->string('frequency', 20)->nullable();
                $table->integer('no_of_users')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->decimal('line_total', 12, 2)->default(0);
                $table->unsignedInteger('sort_order')->default(1);
                $table->timestamps();

                $table->foreign('proformaid')->references('proformaid')->on('proforma_invoices')->onDelete('cascade');
                $table->foreign('itemid')->references('itemid')->on('items')->onDelete('set null');
            });
        }

        // Create ti_items table (tax invoice items)
        if (!Schema::hasTable('ti_items')) {
            Schema::create('ti_items', function (Blueprint $table) {
                $table->string('invoiceitemid', 6)->primary();
                $table->string('invoiceid', 6); // Alphanumeric foreign key
                $table->string('itemid', 6)->nullable();
                $table->string('item_name', 150);
                $table->text('item_description')->nullable();
                $table->decimal('quantity', 10, 2)->default(1);
                $table->decimal('unit_price', 12, 2)->default(0);
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->string('taxid', 20)->nullable();
                $table->integer('duration')->nullable();
                $table->string('frequency', 20)->nullable();
                $table->integer('no_of_users')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->decimal('line_total', 12, 2)->default(0);
                $table->unsignedInteger('sort_order')->default(1);
                $table->timestamps();

                $table->foreign('invoiceid')->references('invoiceid')->on('tax_invoices')->onDelete('cascade');
                $table->foreign('itemid')->references('itemid')->on('items')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tax_invoices') && Schema::hasTable('proforma_invoices')) {
            if (!Schema::hasColumn('tax_invoices', 'invoice_type')) {
                Schema::table('tax_invoices', function (Blueprint $table) {
                    $table->string('invoice_type', 30)->default('tax')->after('invoice_title');
                });
            }

            if (!Schema::hasColumn('tax_invoices', 'converted_from_invoiceid')) {
                Schema::table('tax_invoices', function (Blueprint $table) {
                    $table->string('converted_from_invoiceid', 6)->nullable()->after('orderid');
                });
            }

            DB::statement("
                UPDATE tax_invoices
                SET converted_from_invoiceid = proformaid
                WHERE proformaid IS NOT NULL
            ");

            DB::statement("
                INSERT INTO tax_invoices (
                    invoiceid, accountid, fy_id, clientid, orderid, converted_from_invoiceid,
                    invoice_number, invoice_title, invoice_type, invoice_for, status,
                    issue_date, due_date, subtotal, tax_total, discount_total, grand_total,
                    amount_paid, balance_due, currency_code, notes, terms, sent_at, paid_at,
                    created_by, created_at, updated_at
                )
                SELECT
                    proformaid, accountid, fy_id, clientid, orderid, NULL,
                    invoice_number, invoice_title, 'proforma', invoice_for, status,
                    issue_date, due_date, subtotal, tax_total, discount_total, grand_total,
                    amount_paid, balance_due, currency_code, notes, terms, sent_at, paid_at,
                    created_by, created_at, updated_at
                FROM proforma_invoices
            ");
        }

        if (Schema::hasTable('ti_items') && Schema::hasTable('pi_items')) {
            DB::statement("
                INSERT INTO ti_items (
                    invoiceitemid, invoiceid, itemid, item_name, item_description,
                    quantity, unit_price, tax_rate, taxid, duration, frequency,
                    no_of_users, start_date, end_date, line_total, sort_order,
                    created_at, updated_at
                )
                SELECT
                    proformaitemid, proformaid, itemid, item_name, item_description,
                    quantity, unit_price, tax_rate, taxid, duration, frequency,
                    no_of_users, start_date, end_date, line_total, sort_order,
                    created_at, updated_at
                FROM pi_items
            ");
        }

        Schema::dropIfExists('pi_items');
        Schema::dropIfExists('proforma_invoices');

        if (Schema::hasTable('tax_invoices') && Schema::hasColumn('tax_invoices', 'proformaid')) {
            Schema::table('tax_invoices', function (Blueprint $table) {
                $table->dropColumn('proformaid');
            });
        }

        if (Schema::hasTable('ti_items')) {
            Schema::rename('ti_items', 'invoice_items');
        }

        if (Schema::hasTable('tax_invoices')) {
            Schema::rename('tax_invoices', 'invoices');
        }
    }

    private function dropForeignIfExists(string $table, string $column): void
    {
        $database = DB::getDatabaseName();

        $constraint = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');

        if ($constraint) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        }
    }
};
