<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('converted_from_invoiceid', 6)->nullable()->after('orderid');
            $table->foreign('converted_from_invoiceid')
                ->references('invoiceid')
                ->on('invoices')
                ->nullOnDelete();
            $table->index('converted_from_invoiceid');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['converted_from_invoiceid']);
            $table->dropIndex(['converted_from_invoiceid']);
            $table->dropColumn('converted_from_invoiceid');
        });
    }
};
