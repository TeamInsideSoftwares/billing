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
        Schema::create('order_timeline', function (Blueprint $table) {
            $table->string('timelineid', 10)->primary();
            $table->string('accountid', 10);
            $table->string('orderid', 6);
            $table->string('action_type', 50);
            $table->string('field_name', 50)->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('description');
            $table->string('created_by', 12)->nullable();
            $table->timestamps();

            $table->index(['accountid', 'orderid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_timeline');
    }
};
