<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('item_costings') || ! Schema::hasColumn('item_costings', 'taxid')) {
            return;
        }

        Schema::table('item_costings', function (Blueprint $table) {
            $table->dropColumn('taxid');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('item_costings') || Schema::hasColumn('item_costings', 'taxid')) {
            return;
        }

        Schema::table('item_costings', function (Blueprint $table) {
            $table->string('taxid', 20)->nullable()->after('tax_rate');
        });
    }
};
