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
            $table->string('shiftid', 6)->nullable()->after('userid');
            $table->string('att_policyid', 6)->nullable()->after('shiftid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_users', function (Blueprint $table) {
            $table->dropColumn(['shiftid', 'att_policyid']);
        });
    }
};
