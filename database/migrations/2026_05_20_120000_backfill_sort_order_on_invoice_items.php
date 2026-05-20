<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('invoice_items')) {
            return;
        }

        if (!Schema::hasColumn('invoice_items', 'sequence')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->unsignedInteger('sequence')->default(1);
            });
        }

        $primaryKey = Schema::hasColumn('invoice_items', 'invoice_itemid') ? 'invoice_itemid' : 'invoiceitemid';
        if (!Schema::hasColumn('invoice_items', $primaryKey)) {
            return;
        }

        $invoiceIds = DB::table('invoice_items')
            ->select('invoiceid')
            ->distinct()
            ->pluck('invoiceid');

        foreach ($invoiceIds as $invoiceId) {
            $itemsQuery = DB::table('invoice_items')
                ->where('invoiceid', $invoiceId);

            if (Schema::hasColumn('invoice_items', 'sort_order')) {
                $itemsQuery->orderBy('sort_order');
            }

            $itemIds = $itemsQuery
                ->orderBy('created_at')
                ->orderBy($primaryKey)
                ->pluck($primaryKey)
                ->values();

            foreach ($itemIds as $index => $itemId) {
                DB::table('invoice_items')
                    ->where($primaryKey, $itemId)
                    ->update(['sequence' => $index + 1]);
            }
        }

        if (Schema::hasColumn('invoice_items', 'sort_order')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->dropColumn('sort_order');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('invoice_items')) {
            return;
        }

        if (Schema::hasColumn('invoice_items', 'sequence')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->dropColumn('sequence');
            });
        }

        if (!Schema::hasColumn('invoice_items', 'sort_order')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')->default(1);
            });
        }
    }
};
