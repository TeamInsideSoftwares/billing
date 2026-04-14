<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terms_conditions', function (Blueprint $table) {
            $table->string('tc_id', 6)->primary();
            $table->string('accountid', 10);
            $table->enum('type', ['billing', 'quotation']);
            $table->string('title', 200);
            $table->text('content');
            $table->boolean('is_active')->default(true);
            $table->integer('sequence')->default(0);
            $table->timestamps();

            $table->index(['accountid', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terms_conditions');
    }
};
