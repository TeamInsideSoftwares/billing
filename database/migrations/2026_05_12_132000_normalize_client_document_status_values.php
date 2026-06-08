<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_documents') || ! Schema::hasColumn('client_documents', 'status')) {
            return;
        }

        DB::table('client_documents')
            ->whereNull('status')
            ->orWhere('status', '')
            ->orWhere('status', 'running')
            ->update(['status' => 'active']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('client_documents') || ! Schema::hasColumn('client_documents', 'status')) {
            return;
        }

        DB::table('client_documents')
            ->where('status', 'active')
            ->update(['status' => 'running']);
    }
};
