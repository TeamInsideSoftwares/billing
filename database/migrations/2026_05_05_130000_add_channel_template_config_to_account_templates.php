<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_templates', function (Blueprint $table) {
            $table->string('template_id', 120)->nullable()->after('name');
            $table->string('meta_template_id', 160)->nullable()->after('template_id');
            $table->string('sender_id', 120)->nullable()->after('meta_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('account_templates', function (Blueprint $table) {
            $table->dropColumn(['template_id', 'meta_template_id', 'sender_id']);
        });
    }
};
