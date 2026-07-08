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
        Schema::table('orders', function (Blueprint $table) {
            $indexes = Schema::getIndexes('orders');
            $indexNames = array_column($indexes, 'name');
            if (in_array('orders_new_order_number_unique', $indexNames)) {
                $table->dropUnique('orders_new_order_number_unique');
            } elseif (in_array('order_number', $indexNames)) {
                $table->dropUnique('order_number');
            }
            $table->unique(['accountid', 'order_number'], 'orders_accountid_order_number_unique');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $indexes = Schema::getIndexes('quotations');
            $indexNames = array_column($indexes, 'name');
            if (in_array('quotations_quo_number_unique', $indexNames)) {
                $table->dropUnique('quotations_quo_number_unique');
            } elseif (in_array('quo_number', $indexNames)) {
                $table->dropUnique('quo_number');
            }
            $table->unique(['accountid', 'quo_number'], 'quotations_accountid_quo_number_unique');
        });

        Schema::table('payments', function (Blueprint $table) {
            $indexes = Schema::getIndexes('payments');
            $indexNames = array_column($indexes, 'name');
            if (in_array('payments_receipt_number_unique', $indexNames)) {
                $table->dropUnique('payments_receipt_number_unique');
            } elseif (in_array('receipt_number', $indexNames)) {
                $table->dropUnique('receipt_number');
            }
            $table->unique(['accountid', 'receipt_number'], 'payments_accountid_receipt_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('orders_accountid_order_number_unique');
            $table->unique('order_number', 'orders_new_order_number_unique');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropUnique('quotations_accountid_quo_number_unique');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('payments_accountid_receipt_number_unique');
        });
    }
};
