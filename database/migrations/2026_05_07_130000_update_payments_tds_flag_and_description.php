<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'tds')) {
                $table->boolean('tds')->default(false)->after('received_amount');
            }
            if (!Schema::hasColumn('payments', 'description')) {
                $table->text('description')->nullable()->after('reference_number');
            }
        });

        if (Schema::hasColumn('payments', 'tds_amount')) {
            DB::table('payments')
                ->where('tds_amount', '>', 0)
                ->update(['tds' => true]);
        }

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'tds_amount')) {
                $table->dropColumn('tds_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'tds_amount')) {
                $table->decimal('tds_amount', 12, 2)->default(0)->after('received_amount');
            }
        });

        if (Schema::hasColumn('payments', 'tds')) {
            DB::table('payments')
                ->where('tds', true)
                ->update(['tds_amount' => DB::raw('received_amount')]);
        }

        Schema::table('payments', function (Blueprint $table) {
            $dropColumns = [];
            if (Schema::hasColumn('payments', 'tds')) {
                $dropColumns[] = 'tds';
            }
            if (Schema::hasColumn('payments', 'description')) {
                $dropColumns[] = 'description';
            }
            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
