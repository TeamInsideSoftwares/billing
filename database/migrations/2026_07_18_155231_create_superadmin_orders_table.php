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
        Schema::connection('admin_mysql')->create('orders', function (Blueprint $table) {
            $table->string('orderid', 6)->primary();
            $table->string('accountid', 10);
            $table->string('clientid', 10);
            $table->string('order_number', 30);
            $table->string('status', 20)->nullable();
            $table->string('type', 20)->default('regular');
            $table->string('client_docid', 6)->nullable();
            $table->string('itemid', 6)->nullable();
            $table->string('item_name', 150);
            $table->text('item_description')->nullable();
            $table->integer('quantity')->unsigned()->default(1);
            $table->integer('no_of_users')->unsigned()->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('grace_period')->default(0);
            $table->date('delivery_date')->nullable();
            $table->timestamps();
            
            $table->index('accountid');
            $table->index('clientid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('admin_mysql')->dropIfExists('orders');
    }
};
