<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('clients')) {
            return;
        }

        if (!Schema::hasColumn('clients', 'type')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('type', 20)->default('regular')->after('email');
            });
        }

        DB::table('clients')
            ->whereNull('type')
            ->orWhere('type', '')
            ->update(['type' => 'regular']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('clients') || !Schema::hasColumn('clients', 'type')) {
            return;
        }

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
