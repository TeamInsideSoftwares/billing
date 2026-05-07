<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_emails', function (Blueprint $table) {
            $table->string('templateid', 6)->nullable()->after('channel');
            $table->index(['invoiceid', 'channel', 'templateid'], 'invoice_emails_template_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_emails', function (Blueprint $table) {
            $table->dropIndex('invoice_emails_template_lookup_idx');
            $table->dropColumn(['templateid']);
        });
    }
};
