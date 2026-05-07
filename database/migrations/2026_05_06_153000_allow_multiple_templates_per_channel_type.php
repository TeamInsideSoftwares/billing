<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_templates', function (Blueprint $table) {
            $table->dropUnique('account_templates_unique_per_context');
            $table->unique(
                ['accountid', 'channel', 'template_type', 'template_id'],
                'account_templates_unique_per_template'
            );
        });
    }

    public function down(): void
    {
        Schema::table('account_templates', function (Blueprint $table) {
            $table->dropUnique('account_templates_unique_per_template');
            $table->unique(
                ['accountid', 'channel', 'template_type'],
                'account_templates_unique_per_context'
            );
        });
    }
};
