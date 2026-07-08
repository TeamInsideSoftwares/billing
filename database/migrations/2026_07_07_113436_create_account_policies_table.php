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
        Schema::create('account_policies', function (Blueprint $table) {
            $table->string('policyid', 6)->primary();
            $table->unsignedBigInteger('accountid')->index();
            $table->string('componentid', 6)->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('rules')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_policies');
    }
};
