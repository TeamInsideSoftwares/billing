<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_taxes', function (Blueprint $table) {
            $table->string('taxid', 6)->primary();
            $table->string('accountid', 10);
            $table->string('tax_name', 100)->nullable();
            $table->decimal('rate', 5, 2)->default(0);
            $table->string('type', 20)->default('GST');
            $table->string('description', 255)->nullable();
            $table->integer('sequence')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_taxes');
    }
};
