<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('accounts')) {
            return;
        }

        Schema::table('accounts', function (Blueprint $table): void {
            $dropColumns = [];

            if (Schema::hasColumn('accounts', 'reminder_automation_enabled')) {
                $dropColumns[] = 'reminder_automation_enabled';
            }

            if (Schema::hasColumn('accounts', 'reminder_days_before')) {
                $dropColumns[] = 'reminder_days_before';
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('accounts')) {
            return;
        }

        Schema::table('accounts', function (Blueprint $table): void {
            if (!Schema::hasColumn('accounts', 'reminder_automation_enabled')) {
                $table->boolean('reminder_automation_enabled')->default(false)->after('fixed_tax_type');
            }

            if (!Schema::hasColumn('accounts', 'reminder_days_before')) {
                $table->unsignedSmallInteger('reminder_days_before')->default(30)->after('reminder_automation_enabled');
            }
        });
    }
};
