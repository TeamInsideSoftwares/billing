<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('account_users', function (Blueprint $table) {
            // Drop foreign key constraints first
            $table->dropForeign(['roleid']);
            $table->dropForeign(['depid']);

            // Drop the specified columns
            $table->dropColumn(['role', 'department', 'email_verified_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_users', function (Blueprint $table) {
            // Re-add the columns
            $table->string('role', 50)->nullable()->after('password');
            $table->string('department', 100)->nullable()->after('email');
            $table->timestamp('email_verified_at')->nullable();

            // Re-add foreign key constraints
            $table->foreign('roleid')->references('roleid')->on('account_roles')->nullOnDelete();
            $table->foreign('depid')->references('depid')->on('account_departments')->nullOnDelete();
        });
    }
};
