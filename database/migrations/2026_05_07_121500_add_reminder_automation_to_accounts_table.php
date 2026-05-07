<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('reminder_automation_enabled')->default(false)->after('fixed_tax_type');
            $table->unsignedSmallInteger('reminder_days_before')->default(30)->after('reminder_automation_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['reminder_automation_enabled', 'reminder_days_before']);
        });
    }
};
