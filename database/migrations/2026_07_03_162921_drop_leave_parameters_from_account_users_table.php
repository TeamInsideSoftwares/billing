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
            $table->dropColumn(['paid_leaves_pm', 'carry_forward', 'probation_months']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_users', function (Blueprint $table) {
            $table->integer('paid_leaves_pm')->default(0)->after('att_policyid');
            $table->boolean('carry_forward')->default(false)->after('paid_leaves_pm');
            $table->integer('probation_months')->default(0)->after('carry_forward');
        });
    }
};
