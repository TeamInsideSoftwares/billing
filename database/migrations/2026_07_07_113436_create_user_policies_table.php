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
        Schema::create('user_policies', function (Blueprint $table) {
            $table->string('user_policyid', 6)->primary();
            $table->unsignedBigInteger('accountid')->index();
            $table->string('userid', 6)->index();
            $table->string('policyid', 6)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_policies');
    }
};
