<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_emails', function (Blueprint $table) {
            $table->string('cc_email', 255)->nullable()->after('to_email');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_emails', function (Blueprint $table) {
            $table->dropColumn('cc_email');
        });
    }
};
