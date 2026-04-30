<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_emails', function (Blueprint $table) {
            $table->string('invoice_emailid', 6)->primary();
            $table->string('accountid', 10);
            $table->string('invoiceid', 6);
            $table->string('clientid', 6)->nullable();
            $table->string('from_email', 255)->nullable();
            $table->string('to_email', 255);
            $table->string('subject', 255)->nullable();
            $table->longText('body')->nullable();
            $table->string('attachment_type', 20)->nullable(); // pi | ti | dsc | none
            $table->string('attachment_path', 500)->nullable();
            $table->string('custom_attachment_path', 500)->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('created_by', 10)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['invoiceid', 'created_at']);
            $table->index(['accountid', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_emails');
    }
};
