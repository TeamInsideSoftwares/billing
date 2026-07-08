<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop att_policyid column from account_users
        Schema::table('account_users', function (Blueprint $table) {
            $table->dropColumn('att_policyid');
        });

        // Drop the old attendance_policies table entirely
        Schema::dropIfExists('attendance_policies');
    }

    public function down(): void
    {
        Schema::create('attendance_policies', function (Blueprint $table) {
            $table->string('att_policyid', 6)->primary();
            $table->string('accountid', 10);
            $table->string('policy_name');
            $table->text('description')->nullable();
            $table->integer('late_arrival_grace')->default(0);
            $table->integer('early_departure_grace')->default(0);
            $table->decimal('overtime_rate', 10, 2)->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::table('account_users', function (Blueprint $table) {
            $table->string('att_policyid', 6)->nullable()->after('shiftid');
        });
    }
};
