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
        Schema::create('user_salaries', function (Blueprint $table) {
            $table->string('salaryid', 6)->primary();
            $table->unsignedBigInteger('accountid')->index();
            $table->string('userid', 6)->index();
            $table->decimal('amount', 10, 2);
            $table->date('effective_date');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_salaries');
    }
};
