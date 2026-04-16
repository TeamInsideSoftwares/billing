<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pi_items')) {
            Schema::table('pi_items', function (Blueprint $table) {
                if (!Schema::hasColumn('pi_items', 'discount_percent')) {
                    $table->decimal('discount_percent', 5, 2)->default(0)->after('tax_rate');
                }

                if (!Schema::hasColumn('pi_items', 'discount_amount')) {
                    $table->decimal('discount_amount', 12, 2)->default(0)->after('discount_percent');
                }
            });
        }

        if (Schema::hasTable('ti_items')) {
            Schema::table('ti_items', function (Blueprint $table) {
                if (!Schema::hasColumn('ti_items', 'discount_percent')) {
                    $table->decimal('discount_percent', 5, 2)->default(0)->after('tax_rate');
                }

                if (!Schema::hasColumn('ti_items', 'discount_amount')) {
                    $table->decimal('discount_amount', 12, 2)->default(0)->after('discount_percent');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pi_items')) {
            Schema::table('pi_items', function (Blueprint $table) {
                $dropColumns = [];

                if (Schema::hasColumn('pi_items', 'discount_percent')) {
                    $dropColumns[] = 'discount_percent';
                }

                if (Schema::hasColumn('pi_items', 'discount_amount')) {
                    $dropColumns[] = 'discount_amount';
                }

                if (!empty($dropColumns)) {
                    $table->dropColumn($dropColumns);
                }
            });
        }

        if (Schema::hasTable('ti_items')) {
            Schema::table('ti_items', function (Blueprint $table) {
                $dropColumns = [];

                if (Schema::hasColumn('ti_items', 'discount_percent')) {
                    $dropColumns[] = 'discount_percent';
                }

                if (Schema::hasColumn('ti_items', 'discount_amount')) {
                    $dropColumns[] = 'discount_amount';
                }

                if (!empty($dropColumns)) {
                    $table->dropColumn($dropColumns);
                }
            });
        }
    }
};
