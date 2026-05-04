<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_templates', function (Blueprint $table) {
            $table->string('templateid', 6)->primary();
            $table->string('accountid', 10);
            $table->enum('template_type', ['pi', 'ti', 'digital_signed']);
            $table->enum('channel', ['email', 'whatsapp', 'sms']);
            $table->string('name', 120);
            $table->string('subject')->nullable();
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['accountid', 'channel', 'template_type']);
            $table->unique(['accountid', 'channel', 'template_type'], 'account_templates_unique_per_context');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_templates');
    }
};
