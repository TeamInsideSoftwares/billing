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

        if (!Schema::hasColumn('clients', 'primary_email')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('primary_email', 150)->nullable()->after('contact_name');
            });
        }

        DB::statement('ALTER TABLE `clients` MODIFY `email` VARCHAR(500) NULL');

        // Backfill from the first email found in the legacy comma-separated `email` column.
        $clients = DB::table('clients')
            ->select('clientid', 'email', 'primary_email')
            ->orderBy('clientid')
            ->get();

        $seenPrimaryEmails = [];

        foreach ($clients as $row) {
            $existingPrimary = strtolower(trim((string) ($row->primary_email ?? '')));
            if ($existingPrimary !== '') {
                $seenPrimaryEmails[$existingPrimary] = true;
                continue;
            }

            $firstEmail = collect(explode(',', (string) ($row->email ?? '')))
                ->map(fn ($email) => strtolower(trim($email)))
                ->first(fn ($email) => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL));

            if (!$firstEmail) {
                continue;
            }

            if (isset($seenPrimaryEmails[$firstEmail])) {
                continue;
            }

            DB::table('clients')
                ->where('clientid', $row->clientid)
                ->update(['primary_email' => $firstEmail]);

            $seenPrimaryEmails[$firstEmail] = true;
        }

        if (!$this->hasIndex('clients', 'clients_primary_email_unique')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->unique('primary_email', 'clients_primary_email_unique');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('clients')) {
            return;
        }

        if (Schema::hasColumn('clients', 'primary_email')) {
            if ($this->hasIndex('clients', 'clients_primary_email_unique')) {
                Schema::table('clients', function (Blueprint $table) {
                    $table->dropUnique('clients_primary_email_unique');
                });
            }

            DB::statement('ALTER TABLE `clients` MODIFY `email` VARCHAR(150) NULL');

            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn('primary_email');
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
