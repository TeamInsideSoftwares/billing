<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_users', function (Blueprint $table): void {
            if (!Schema::hasColumn('account_users', 'phone')) {
                $table->string('phone', 30)->nullable()->after('department');
            }
            if (!Schema::hasColumn('account_users', 'designation')) {
                $table->string('designation', 100)->nullable()->after('phone');
            }
            if (!Schema::hasColumn('account_users', 'notes')) {
                $table->text('notes')->nullable()->after('designation');
            }
            if (!Schema::hasColumn('account_users', 'permissions')) {
                $table->json('permissions')->nullable()->after('role');
            }
        });
    }

    public function down(): void
    {
        Schema::table('account_users', function (Blueprint $table): void {
            $drop = [];
            foreach (['phone', 'designation', 'notes', 'permissions'] as $column) {
                if (Schema::hasColumn('account_users', $column)) {
                    $drop[] = $column;
                }
            }
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
