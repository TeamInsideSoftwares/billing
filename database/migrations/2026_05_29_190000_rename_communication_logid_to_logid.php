<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('communication_logs')) {
            return;
        }

        if (Schema::hasColumn('communication_logs', 'communication_logid') && ! Schema::hasColumn('communication_logs', 'logid')) {
            Schema::table('communication_logs', function (Blueprint $table): void {
                $table->renameColumn('communication_logid', 'logid');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('communication_logs')) {
            return;
        }

        if (Schema::hasColumn('communication_logs', 'logid') && ! Schema::hasColumn('communication_logs', 'communication_logid')) {
            Schema::table('communication_logs', function (Blueprint $table): void {
                $table->renameColumn('logid', 'communication_logid');
            });
        }
    }
};
