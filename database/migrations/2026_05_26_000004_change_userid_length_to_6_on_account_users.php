<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('account_users') || !Schema::hasColumn('account_users', 'userid')) {
            return;
        }

        DB::statement('ALTER TABLE `account_users` MODIFY `userid` VARCHAR(6) NOT NULL');
    }

    public function down(): void
    {
        if (!Schema::hasTable('account_users') || !Schema::hasColumn('account_users', 'userid')) {
            return;
        }

        DB::statement('ALTER TABLE `account_users` MODIFY `userid` VARCHAR(10) NOT NULL');
    }
};

