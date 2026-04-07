<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->integer('duration')->nullable()->after('tax_rate');
            $table->string('frequency', 20)->nullable()->after('duration');
            $table->integer('no_of_users')->nullable()->after('frequency');
            $table->date('start_date')->nullable()->after('no_of_users');
            $table->date('end_date')->nullable()->after('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['duration', 'frequency', 'no_of_users', 'start_date', 'end_date']);
        });
    }
};
