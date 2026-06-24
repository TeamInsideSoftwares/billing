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
        Schema::create('client_categories', function (Blueprint $table) {
            $table->string('categoryid', 6)->primary();
            $table->string('accountid', 10)->nullable();
            $table->string('name');
            $table->timestamps();

            $table->foreign('accountid')->references('accountid')->on('accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_categories');
    }
};
