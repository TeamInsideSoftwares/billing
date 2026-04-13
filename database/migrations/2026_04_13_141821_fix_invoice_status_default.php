<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update existing 'draft' status to 'unpaid'
        DB::table('proforma_invoices')->where('status', 'draft')->update(['status' => 'unpaid']);
        DB::table('tax_invoices')->where('status', 'draft')->update(['status' => 'unpaid']);
        
        // Change default status
        Schema::table('proforma_invoices', function ($table) {
            $table->string('status', 20)->default('unpaid')->change();
        });
        
        Schema::table('tax_invoices', function ($table) {
            $table->string('status', 20)->default('unpaid')->change();
        });
    }

    public function down(): void
    {
        Schema::table('proforma_invoices', function ($table) {
            $table->string('status', 20)->default('draft')->change();
        });
        
        Schema::table('tax_invoices', function ($table) {
            $table->string('status', 20)->default('draft')->change();
        });
    }
};
