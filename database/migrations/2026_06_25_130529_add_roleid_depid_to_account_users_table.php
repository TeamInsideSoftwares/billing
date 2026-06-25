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
            $table->string('roleid', 10)->nullable()->after('role');
            $table->string('depid', 10)->nullable()->after('department');

            $table->foreign('roleid')->references('roleid')->on('account_roles')->nullOnDelete();
            $table->foreign('depid')->references('depid')->on('account_departments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_users', function (Blueprint $table) {
            $table->dropForeign(['roleid']);
            $table->dropForeign(['depid']);
            $table->dropColumn(['roleid', 'depid']);
        });
    }
};
