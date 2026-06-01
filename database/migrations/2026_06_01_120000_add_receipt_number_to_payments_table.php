<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payments') || Schema::hasColumn('payments', 'receipt_number')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->string('receipt_number', 100)->nullable()->after('clientid');
            $table->index(['accountid', 'receipt_number'], 'payments_account_receipt_idx');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('payments') || !Schema::hasColumn('payments', 'receipt_number')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_account_receipt_idx');
            $table->dropColumn('receipt_number');
        });
    }
};

