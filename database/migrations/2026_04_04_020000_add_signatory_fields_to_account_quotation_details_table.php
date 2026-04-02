<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_quotation_details', function (Blueprint $table) {
            $table->string('authorize_signatory', 255)->nullable()->after('tin');
            $table->string('signature_upload', 500)->nullable()->after('authorize_signatory');
            $table->string('billing_from_email', 255)->nullable()->after('signature_upload');
        });
    }

    public function down(): void
    {
        Schema::table('account_quotation_details', function (Blueprint $table) {
            $table->dropColumn(['authorize_signatory', 'signature_upload', 'billing_from_email']);
        });
    }
};

