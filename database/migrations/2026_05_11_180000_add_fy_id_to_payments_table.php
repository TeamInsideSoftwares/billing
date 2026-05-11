<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payments')) {
            return;
        }

        if (!Schema::hasColumn('payments', 'fy_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('fy_id', 6)->nullable()->after('accountid');
            });
        }

        DB::statement("
            UPDATE payments p
            LEFT JOIN financial_year fy
                ON fy.accountid = p.accountid
               AND fy.`default` = 1
            SET p.fy_id = COALESCE(p.fy_id, fy.fy_id)
            WHERE p.fy_id IS NULL
        ");
    }

    public function down(): void
    {
        if (!Schema::hasTable('payments') || !Schema::hasColumn('payments', 'fy_id')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('fy_id');
        });
    }
};
