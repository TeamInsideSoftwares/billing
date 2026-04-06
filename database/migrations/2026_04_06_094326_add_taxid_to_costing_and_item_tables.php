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
        // Item costings (services use 'items' table)
        if (Schema::hasTable('item_costings')) {
            Schema::table('item_costings', function (Blueprint $table) {
                $table->string('taxid', 20)->nullable()->after('tax_rate');
            });
        }

        // Order items
        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->string('taxid', 20)->nullable()->after('tax_rate');
            });
        }

        // Invoice items
        if (Schema::hasTable('invoice_items')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->string('taxid', 20)->nullable()->after('tax_rate');
            });
        }

        // Items (services)
        if (Schema::hasTable('items')) {
            Schema::table('items', function (Blueprint $table) {
                $table->string('taxid', 20)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('item_costings')) {
            Schema::table('item_costings', function (Blueprint $table) {
                $table->dropColumn('taxid');
            });
        }

        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn('taxid');
            });
        }

        if (Schema::hasTable('invoice_items')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->dropColumn('taxid');
            });
        }

        if (Schema::hasTable('items')) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropColumn('taxid');
            });
        }
    }
};
