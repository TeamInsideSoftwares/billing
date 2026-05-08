<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_items', 'status')) {
                $table->string('status', 20)->default('active')->after('end_date');
                $table->index(['status', 'end_date'], 'invoice_items_status_end_date_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_items', 'status')) {
                $table->dropIndex('invoice_items_status_end_date_idx');
                $table->dropColumn('status');
            }
        });
    }
};
