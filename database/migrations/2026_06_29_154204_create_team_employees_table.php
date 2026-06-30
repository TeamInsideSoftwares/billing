<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_employees', function (Blueprint $table) {
            $table->id('employeeid');
            $table->string('accountid', 10);
            $table->string('depid', 10)->nullable();
            $table->string('name', 255);
            $table->string('email', 255);
            $table->string('phone', 30)->nullable();
            $table->string('designation', 150)->nullable();
            $table->date('joining_date')->nullable();
            $table->decimal('salary', 15, 2)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_employees');
    }
};
