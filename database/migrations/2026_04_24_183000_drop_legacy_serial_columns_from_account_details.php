<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $billingColumns = [
            'serial_number',
            'prefix_type',
            'prefix_value',
            'prefix_length',
            'prefix_separator',
            'number_type',
            'number_value',
            'number_length',
            'number_separator',
            'suffix_type',
            'suffix_value',
            'suffix_length',
            'reset_on_fy',
            'terms_conditions',
        ];

        $quotationColumns = [
            'serial_number',
            'prefix_type',
            'prefix_value',
            'prefix_length',
            'prefix_separator',
            'number_type',
            'number_value',
            'number_length',
            'number_separator',
            'suffix_type',
            'suffix_value',
            'suffix_length',
            'reset_on_fy',
            'terms_conditions',
        ];

        $billingToDrop = array_values(array_filter(
            $billingColumns,
            fn (string $column) => Schema::hasColumn('account_billing_details', $column)
        ));

        if (!empty($billingToDrop)) {
            Schema::table('account_billing_details', function (Blueprint $table) use ($billingToDrop) {
                $table->dropColumn($billingToDrop);
            });
        }

        $quotationToDrop = array_values(array_filter(
            $quotationColumns,
            fn (string $column) => Schema::hasColumn('account_quotation_details', $column)
        ));

        if (!empty($quotationToDrop)) {
            Schema::table('account_quotation_details', function (Blueprint $table) use ($quotationToDrop) {
                $table->dropColumn($quotationToDrop);
            });
        }
    }

    public function down(): void
    {
        Schema::table('account_billing_details', function (Blueprint $table) {
            if (!Schema::hasColumn('account_billing_details', 'serial_number')) {
                $table->string('serial_number', 20)->nullable();
            }
            if (!Schema::hasColumn('account_billing_details', 'prefix_type')) {
                $table->string('prefix_type')->default('manual text');
            }
            if (!Schema::hasColumn('account_billing_details', 'prefix_value')) {
                $table->string('prefix_value')->nullable();
            }
            if (!Schema::hasColumn('account_billing_details', 'prefix_length')) {
                $table->integer('prefix_length')->default(0);
            }
            if (!Schema::hasColumn('account_billing_details', 'prefix_separator')) {
                $table->string('prefix_separator', 10)->nullable();
            }
            if (!Schema::hasColumn('account_billing_details', 'number_type')) {
                $table->string('number_type')->default('auto increment');
            }
            if (!Schema::hasColumn('account_billing_details', 'number_value')) {
                $table->string('number_value')->nullable();
            }
            if (!Schema::hasColumn('account_billing_details', 'number_length')) {
                $table->integer('number_length')->default(4);
            }
            if (!Schema::hasColumn('account_billing_details', 'number_separator')) {
                $table->string('number_separator', 10)->nullable();
            }
            if (!Schema::hasColumn('account_billing_details', 'suffix_type')) {
                $table->string('suffix_type')->default('manual text');
            }
            if (!Schema::hasColumn('account_billing_details', 'suffix_value')) {
                $table->string('suffix_value')->nullable();
            }
            if (!Schema::hasColumn('account_billing_details', 'suffix_length')) {
                $table->integer('suffix_length')->default(0);
            }
            if (!Schema::hasColumn('account_billing_details', 'reset_on_fy')) {
                $table->boolean('reset_on_fy')->default(false);
            }
            if (!Schema::hasColumn('account_billing_details', 'terms_conditions')) {
                $table->text('terms_conditions')->nullable();
            }
        });

        Schema::table('account_quotation_details', function (Blueprint $table) {
            if (!Schema::hasColumn('account_quotation_details', 'serial_number')) {
                $table->string('serial_number', 20)->nullable();
            }
            if (!Schema::hasColumn('account_quotation_details', 'prefix_type')) {
                $table->string('prefix_type')->default('manual text');
            }
            if (!Schema::hasColumn('account_quotation_details', 'prefix_value')) {
                $table->string('prefix_value')->nullable();
            }
            if (!Schema::hasColumn('account_quotation_details', 'prefix_length')) {
                $table->integer('prefix_length')->default(0);
            }
            if (!Schema::hasColumn('account_quotation_details', 'prefix_separator')) {
                $table->string('prefix_separator', 10)->nullable();
            }
            if (!Schema::hasColumn('account_quotation_details', 'number_type')) {
                $table->string('number_type')->default('auto increment');
            }
            if (!Schema::hasColumn('account_quotation_details', 'number_value')) {
                $table->string('number_value')->nullable();
            }
            if (!Schema::hasColumn('account_quotation_details', 'number_length')) {
                $table->integer('number_length')->default(4);
            }
            if (!Schema::hasColumn('account_quotation_details', 'number_separator')) {
                $table->string('number_separator', 10)->nullable();
            }
            if (!Schema::hasColumn('account_quotation_details', 'suffix_type')) {
                $table->string('suffix_type')->default('manual text');
            }
            if (!Schema::hasColumn('account_quotation_details', 'suffix_value')) {
                $table->string('suffix_value')->nullable();
            }
            if (!Schema::hasColumn('account_quotation_details', 'suffix_length')) {
                $table->integer('suffix_length')->default(0);
            }
            if (!Schema::hasColumn('account_quotation_details', 'reset_on_fy')) {
                $table->boolean('reset_on_fy')->default(false);
            }
            if (!Schema::hasColumn('account_quotation_details', 'terms_conditions')) {
                $table->text('terms_conditions')->nullable();
            }
        });
    }
};

