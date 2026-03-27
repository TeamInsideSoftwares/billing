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
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('billing_type');
            $table->decimal('cost_price', 12, 2)->default(0)->after('description');
            $table->decimal('selling_price', 12, 2)->default(0)->after('cost_price');
            $table->string('sac_code', 20)->nullable()->after('selling_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('billing_type', 20)->default('one_time')->after('description');
            $table->dropColumn(['cost_price', 'selling_price', 'sac_code']);
        });
    }
};
