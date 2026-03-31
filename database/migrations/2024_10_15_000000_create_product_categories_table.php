<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ps_categories', function (Blueprint $table) {
            $table->string('ps_catid', 6)->primary();
            $table->string('accountid', 10);
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->foreign('accountid')->references('accountid')->on('accounts')->onDelete('cascade');
            $table->index(['accountid', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ps_categories');
    }
};

