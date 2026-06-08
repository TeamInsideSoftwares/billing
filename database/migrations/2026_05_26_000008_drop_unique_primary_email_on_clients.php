<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('clients')) {
            return;
        }

        if ($this->hasIndex('clients', 'clients_primary_email_unique')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropUnique('clients_primary_email_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('clients')) {
            return;
        }

        if (! $this->hasIndex('clients', 'clients_primary_email_unique')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->unique('primary_email', 'clients_primary_email_unique');
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }
};
