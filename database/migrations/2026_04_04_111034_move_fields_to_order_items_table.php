<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['frequency', 'no_of_users']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('frequency')->nullable()->after('tax_rate');
            $table->unsignedInteger('no_of_users')->nullable()->after('frequency');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('frequency')->nullable()->after('duration');
            $table->unsignedInteger('no_of_users')->nullable()->after('frequency');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['frequency', 'no_of_users']);
        });
    }
};
