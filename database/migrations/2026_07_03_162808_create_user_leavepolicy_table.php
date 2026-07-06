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
        Schema::connection('team')->create('user_leavepolicy', function (Blueprint $table) {
            $table->string('policyid', 6)->primary();
            $table->string('accountid', 6);
            $table->string('userid', 6);
            $table->string('typeid', 6);
            $table->decimal('leave_per_month', 8, 2)->default(0);
            $table->boolean('carry_forward')->default(false);
            $table->integer('probation_months')->default(0);
            $table->timestamps();

            $table->index(['accountid', 'userid']);
            $table->index('typeid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('team')->dropIfExists('user_leavepolicy');
    }
};
