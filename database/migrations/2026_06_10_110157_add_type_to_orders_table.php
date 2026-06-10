<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'type')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('type', 20)->default('regular')->after('status');
                $table->index(['accountid', 'type']);
            });

            // Backfill existing trial orders
            $trialClientIds = DB::table('clients')
                ->where('type', 'trial')
                ->pluck('clientid');

            if ($trialClientIds->isNotEmpty()) {
                DB::table('orders')
                    ->whereIn('clientid', $trialClientIds)
                    ->update(['type' => 'trial']);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'type')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex(['accountid', 'type']);
                $table->dropColumn('type');
            });
        }
    }
};
