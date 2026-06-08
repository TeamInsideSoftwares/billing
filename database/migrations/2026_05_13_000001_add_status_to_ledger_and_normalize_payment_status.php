<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ledger', 'status')) {
            Schema::table('ledger', function (Blueprint $table) {
                $table->string('status', 20)->default('active')->after('description');
                $table->index(['type', 'status']);
            });
        }

        if (Schema::hasColumn('payments', 'status')) {
            DB::table('payments')
                ->whereIn('status', ['completed', 'success', 'paid'])
                ->update(['status' => 'active']);

            DB::table('payments')
                ->whereNull('status')
                ->orWhere('status', '')
                ->update(['status' => 'active']);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ledger', 'status')) {
            Schema::table('ledger', function (Blueprint $table) {
                $table->dropIndex(['type', 'status']);
                $table->dropColumn('status');
            });
        }
    }
};
