<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('invoice_items')) {
            return;
        }

        Schema::table('invoice_items', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_items', 'orderid')) {
                $table->string('orderid', 6)->nullable()->after('invoiceid');
                $table->index('orderid');
                $table->index(['clientid', 'orderid', 'end_date'], 'invoice_items_client_order_end_idx');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('invoice_items')) {
            return;
        }

        Schema::table('invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_items', 'orderid')) {
                $table->dropIndex('invoice_items_client_order_end_idx');
                $table->dropIndex(['orderid']);
                $table->dropColumn('orderid');
            }
        });
    }
};
