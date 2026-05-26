<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_emails', function (Blueprint $table) {
            $table->string('quotation_emailid', 6)->primary();
            $table->string('accountid', 10);
            $table->string('quotationid', 6);
            $table->string('clientid', 10)->nullable();
            $table->string('from_email', 255)->nullable();
            $table->string('to_email', 255);
            $table->string('cc_email', 255)->nullable();
            $table->string('phone_number', 50)->nullable();
            $table->string('subject', 255)->nullable();
            $table->longText('body')->nullable();
            $table->string('attachment_type', 20)->nullable();
            $table->string('attachment_path', 500)->nullable();
            $table->string('custom_attachment_path', 500)->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('channel', 20)->default('email');
            $table->string('created_by', 10)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['quotationid', 'created_at']);
            $table->index(['accountid', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_emails');
    }
};
