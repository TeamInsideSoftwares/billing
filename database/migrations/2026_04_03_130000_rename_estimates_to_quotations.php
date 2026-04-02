<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rename estimates table to quotations
        Schema::rename('estimates', 'quotations');
        
        // Rename estimate_items table to quotation_items
        Schema::rename('estimate_items', 'quotation_items');
        
        // Rename columns in quotations table
        Schema::table('quotations', function (Blueprint $table) {
            $table->renameColumn('estimateid', 'quotationid');
            $table->renameColumn('estimate_number', 'quotation_number');
        });
        
        // Rename columns in quotation_items table
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->renameColumn('estimateitemid', 'quotationitemid');
            $table->renameColumn('estimateid', 'quotationid');
        });
    }

    public function down(): void
    {
        // Rename columns back in quotation_items table
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->renameColumn('quotationitemid', 'estimateitemid');
            $table->renameColumn('quotationid', 'estimateid');
        });
        
        // Rename columns back in quotations table
        Schema::table('quotations', function (Blueprint $table) {
            $table->renameColumn('quotationid', 'estimateid');
            $table->renameColumn('quotation_number', 'estimate_number');
        });
        
        // Rename quotation_items table back to estimate_items
        Schema::rename('quotation_items', 'estimate_items');
        
        // Rename quotations table back to estimates
        Schema::rename('quotations', 'estimates');
    }
};
