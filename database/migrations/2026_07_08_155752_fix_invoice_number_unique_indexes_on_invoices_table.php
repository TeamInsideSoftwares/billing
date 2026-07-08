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
        Schema::table('invoices', function (Blueprint $table) {
            $indexes = Schema::getIndexes('invoices');
            $indexNames = array_column($indexes, 'name');
            
            if (in_array('invoices_pi_number_unique', $indexNames)) {
                $table->dropUnique('invoices_pi_number_unique');
            } elseif (in_array('pi_number', $indexNames)) {
                $table->dropUnique('pi_number');
            }

            if (in_array('invoices_ti_number_unique', $indexNames)) {
                $table->dropUnique('invoices_ti_number_unique');
            } elseif (in_array('ti_number', $indexNames)) {
                $table->dropUnique('ti_number');
            }
            
            $table->unique(['accountid', 'pi_number'], 'invoices_accountid_pi_number_unique');
            $table->unique(['accountid', 'ti_number'], 'invoices_accountid_ti_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_accountid_pi_number_unique');
            $table->dropUnique('invoices_accountid_ti_number_unique');
            
            $table->unique('pi_number');
            $table->unique('ti_number');
        });
    }
};
