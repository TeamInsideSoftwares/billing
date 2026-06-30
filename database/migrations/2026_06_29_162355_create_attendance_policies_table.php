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
        Schema::create('attendance_policies', function (Blueprint $table) {
            $table->id('att_policyid');
            $table->string('accountid', 10);
            $table->string('policy_name', 255);
            $table->text('description')->nullable();
            $table->integer('late_arrival_grace')->default(0);
            $table->integer('early_departure_grace')->default(0);
            $table->decimal('overtime_rate', 10, 2)->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            // No foreign keys
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_policies');
    }
};
