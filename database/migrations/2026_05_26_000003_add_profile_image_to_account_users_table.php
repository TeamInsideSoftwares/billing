<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('account_users')) {
            return;
        }

        Schema::table('account_users', function (Blueprint $table): void {
            if (!Schema::hasColumn('account_users', 'profile_image')) {
                $table->string('profile_image', 255)->nullable()->after('email');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('account_users')) {
            return;
        }

        Schema::table('account_users', function (Blueprint $table): void {
            if (Schema::hasColumn('account_users', 'profile_image')) {
                $table->dropColumn('profile_image');
            }
        });
    }
};
