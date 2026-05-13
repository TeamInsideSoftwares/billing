<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('client_documents') || Schema::hasColumn('client_documents', 'status')) {
            return;
        }

        Schema::table('client_documents', function (Blueprint $table) {
            $table->string('status', 20)->default('running')->after('type');
        });

        DB::table('client_documents')->whereNull('status')->update(['status' => 'running']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('client_documents') || !Schema::hasColumn('client_documents', 'status')) {
            return;
        }

        Schema::table('client_documents', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
