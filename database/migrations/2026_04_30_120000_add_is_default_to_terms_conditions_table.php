<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('terms_conditions', function (Blueprint $table) {
            if (!Schema::hasColumn('terms_conditions', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('is_active');
                $table->index(['accountid', 'type', 'is_default'], 'terms_account_type_default_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('terms_conditions', function (Blueprint $table) {
            if (Schema::hasColumn('terms_conditions', 'is_default')) {
                $table->dropIndex('terms_account_type_default_idx');
                $table->dropColumn('is_default');
            }
        });
    }
};

