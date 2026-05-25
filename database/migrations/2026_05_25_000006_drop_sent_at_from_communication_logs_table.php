<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('communication_logs') && Schema::hasColumn('communication_logs', 'sent_at')) {
            Schema::table('communication_logs', function (Blueprint $table) {
                $table->dropColumn('sent_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('communication_logs') && !Schema::hasColumn('communication_logs', 'sent_at')) {
            Schema::table('communication_logs', function (Blueprint $table) {
                $table->timestamp('sent_at')->nullable()->after('created_by');
            });
        }
    }
};
