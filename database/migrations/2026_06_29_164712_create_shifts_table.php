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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id('shiftid');
            $table->string('accountid', 36)->index();
            $table->string('shift_name');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('break_duration')->default(0)->comment('Break duration in minutes');
            $table->time('break_start_time')->nullable();
            $table->time('break_end_time')->nullable();
            $table->integer('break_grace_period')->default(0)->comment('Break grace period in minutes');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
