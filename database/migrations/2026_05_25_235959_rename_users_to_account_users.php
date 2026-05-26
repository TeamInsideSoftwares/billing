<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasTable('account_users')) {
            Schema::rename('users', 'account_users');
        }

        if (!Schema::hasTable('account_users')) {
            return;
        }

        if (Schema::hasColumn('account_users', 'id') && !Schema::hasColumn('account_users', 'userid')) {
            Schema::table('account_users', function (Blueprint $table): void {
                $table->renameColumn('id', 'userid');
            });
        }

        if (Schema::hasColumn('account_users', 'clientid')) {
            Schema::table('account_users', function (Blueprint $table): void {
                $table->dropColumn('clientid');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('account_users')) {
            return;
        }

        if (Schema::hasColumn('account_users', 'userid') && !Schema::hasColumn('account_users', 'id')) {
            Schema::table('account_users', function (Blueprint $table): void {
                $table->renameColumn('userid', 'id');
            });
        }

        if (!Schema::hasColumn('account_users', 'clientid')) {
            Schema::table('account_users', function (Blueprint $table): void {
                $table->string('clientid', 10)->nullable()->index()->after('accountid');
            });
        }

        if (!Schema::hasTable('users')) {
            Schema::rename('account_users', 'users');
        }
    }
};
