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
        Schema::create('leave_policies', function (Blueprint $table) {
            $table->string('leave_policyid', 10)->primary();
            $table->string('accountid', 10);
            $table->string('policy_name', 255);
            $table->text('description')->nullable();
            $table->integer('carry_forward_limit')->default(0);
            $table->integer('min_days_per_application')->default(1);
            $table->integer('max_days_per_application')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_policies');
    }
};
