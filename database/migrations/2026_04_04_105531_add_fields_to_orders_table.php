<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_title')->nullable()->after('order_number');
            $table->string('duration')->nullable()->after('delivery_date');
            $table->string('frequency')->nullable()->after('duration');
            $table->unsignedInteger('no_of_users')->nullable()->after('frequency');
            $table->string('sales_person_id', 10)->nullable()->after('created_by');
            $table->dropColumn(['tax_total', 'currency_code']);
            
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['order_title', 'duration', 'frequency', 'no_of_users', 'sales_person_id']);
            $table->decimal('tax_total', 12, 2)->default(0)->after('subtotal');
            $table->string('currency_code', 3)->default('INR')->after('grand_total');
        });
    }
};
