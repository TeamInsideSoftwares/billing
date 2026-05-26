<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_users', function (Blueprint $table): void {
            if (!Schema::hasColumn('account_users', 'department')) {
                $table->string('department', 100)->nullable()->after('email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('account_users', function (Blueprint $table): void {
            if (Schema::hasColumn('account_users', 'department')) {
                $table->dropColumn('department');
            }
        });
    }
};
