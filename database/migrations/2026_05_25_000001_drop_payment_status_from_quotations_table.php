<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('quotations', 'payment_status')) {
            Schema::table('quotations', function (Blueprint $table) {
                $table->dropColumn('payment_status');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('quotations', 'payment_status')) {
            Schema::table('quotations', function (Blueprint $table) {
                $table->string('payment_status', 20)->default('unpaid')->after('status');
            });
        }
    }
};
