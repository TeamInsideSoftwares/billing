<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_logs', function (Blueprint $table) {
            $table->string('logid', 6)->primary();
            $table->string('accountid', 12);
            $table->string('invoiceid', 12)->nullable()->index();
            $table->string('quotationid', 12)->nullable()->index();
            $table->string('clientid', 10)->nullable()->index();
            $table->string('from_email', 255)->nullable();
            $table->string('to_email', 500)->nullable();
            $table->string('cc_email', 500)->nullable();
            $table->string('phone_number', 50)->nullable();
            $table->string('subject', 255)->nullable();
            $table->longText('body')->nullable();
            $table->string('attachment_type', 50)->nullable();
            $table->text('attachment_path')->nullable();
            $table->text('custom_attachment_path')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->string('channel', 20)->default('email')->index();
            $table->string('created_by', 12)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['accountid', 'channel']);
            $table->index(['invoiceid', 'channel', 'attachment_type']);
            $table->index(['quotationid', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_logs');
    }
};
