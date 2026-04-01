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
        Schema::create('service_addons', function (Blueprint $table) {
            $table->string('addonid', 6)->primary();
            $table->string('accountid', 10);
            $table->string('serviceid', 6);
            $table->string('addon_code', 30)->nullable()->unique();
            $table->string('name', 150);
            $table->unsignedInteger('sequence')->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('accountid')->references('accountid')->on('accounts')->onDelete('cascade');
            $table->foreign('serviceid')->references('serviceid')->on('services')->onDelete('cascade');
            $table->index(['accountid', 'serviceid']);
            $table->index(['serviceid', 'sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_addons');
    }
};
