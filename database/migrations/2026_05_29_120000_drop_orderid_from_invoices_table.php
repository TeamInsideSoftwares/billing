<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'orderid')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('orderid');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('invoices') && !Schema::hasColumn('invoices', 'orderid')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->string('orderid', 6)->nullable()->after('clientid');
            });
        }
    }
};
