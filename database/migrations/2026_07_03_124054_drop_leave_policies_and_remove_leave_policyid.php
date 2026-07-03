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
            if (Schema::hasColumn('account_users', 'leave_policyid')) {
                $table->dropColumn('leave_policyid');
            }
        });

        Schema::dropIfExists('leave_policies');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('leave_policies', function (Blueprint $table) {
            $table->string('leave_policyid', 10)->primary();
            $table->string('accountid', 36);
            $table->unsignedBigInteger('typeid')->nullable();
            $table->string('policy_name', 100);
            $table->boolean('is_paid')->default(false);
            $table->integer('min_days_per_application')->default(1);
            $table->integer('max_days_per_application')->nullable();
            $table->integer('carry_forward_limit')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::table('account_users', function (Blueprint $table) {
            if (!Schema::hasColumn('account_users', 'leave_policyid')) {
                $table->string('leave_policyid', 10)->nullable()->after('att_policyid');
            }
        });
    }
};
