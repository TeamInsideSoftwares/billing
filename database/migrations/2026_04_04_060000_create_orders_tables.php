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
        Schema::create('orders', function (Blueprint $table) {
            $table->string('orderid', 6)->primary();
            $table->string('accountid', 10);
            $table->string('clientid', 6);
            $table->string('order_number', 30)->unique();
            $table->string('status', 20)->default('draft');
            $table->date('order_date');
            $table->date('delivery_date')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->string('currency_code', 3)->default('INR');
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->string('created_by', 10)->nullable();
            $table->timestamps();

            $table->index(['accountid', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->string('orderitemid', 6)->primary();
            $table->string('orderid', 6);
            $table->string('itemid', 6)->nullable();
            $table->string('item_name', 150);
            $table->text('item_description')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
