<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('quotations', 'invoice_title') && ! Schema::hasColumn('quotations', 'quo_title')) {
            Schema::table('quotations', function ($table) {
                $table->renameColumn('invoice_title', 'quo_title');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('quotations', 'quo_title') && ! Schema::hasColumn('quotations', 'invoice_title')) {
            Schema::table('quotations', function ($table) {
                $table->renameColumn('quo_title', 'invoice_title');
            });
        }
    }
};
