<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payments')) {
            if (Schema::hasColumn('payments', 'tds') && ! Schema::hasColumn('payments', 'type')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->string('type', 20)->nullable()->after('received_amount');
                });

                DB::table('payments')
                    ->where('tds', true)
                    ->update(['type' => 'tds']);

                DB::table('payments')
                    ->whereNull('type')
                    ->update(['type' => 'payment']);

                Schema::table('payments', function (Blueprint $table) {
                    $table->dropColumn('tds');
                });
            }
        }

        if (Schema::hasTable('ledger')) {
            Schema::table('ledger', function (Blueprint $table) {
                if (! Schema::hasColumn('ledger', 'mode')) {
                    $table->string('mode', 50)->nullable()->after('type');
                }
                if (! Schema::hasColumn('ledger', 'reference_number')) {
                    $table->string('reference_number', 100)->nullable()->after('mode');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payments')) {
            if (! Schema::hasColumn('payments', 'tds') && Schema::hasColumn('payments', 'type')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->boolean('tds')->default(false)->after('received_amount');
                });

                DB::table('payments')
                    ->where('type', 'tds')
                    ->update(['tds' => true]);

                DB::table('payments')
                    ->whereNull('tds')
                    ->update(['tds' => false]);

                Schema::table('payments', function (Blueprint $table) {
                    $table->dropColumn('type');
                });
            }
        }

        if (Schema::hasTable('ledger')) {
            Schema::table('ledger', function (Blueprint $table) {
                $dropColumns = [];
                if (Schema::hasColumn('ledger', 'mode')) {
                    $dropColumns[] = 'mode';
                }
                if (Schema::hasColumn('ledger', 'reference_number')) {
                    $dropColumns[] = 'reference_number';
                }
                if ($dropColumns !== []) {
                    $table->dropColumn($dropColumns);
                }
            });
        }
    }
};
