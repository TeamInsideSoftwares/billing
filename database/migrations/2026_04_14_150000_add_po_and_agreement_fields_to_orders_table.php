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
        Schema::table('orders', function (Blueprint $table) {
            // Purchase Order fields
            $table->string('po_number', 50)->nullable()->after('order_number');
            $table->date('po_date')->nullable()->after('po_number');
            $table->string('po_file', 255)->nullable()->after('po_date');
            
            // Agreement fields
            $table->string('agreement_ref', 50)->nullable()->after('po_file');
            $table->date('agreement_date')->nullable()->after('agreement_ref');
            $table->string('agreement_file', 255)->nullable()->after('agreement_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'po_number',
                'po_date',
                'po_file',
                'agreement_ref',
                'agreement_date',
                'agreement_file',
            ]);
        });
    }
};
