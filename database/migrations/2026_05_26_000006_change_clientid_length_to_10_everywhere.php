<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $database = DB::getDatabaseName();

        $columns = DB::table('information_schema.columns')
            ->select('table_name', 'is_nullable')
            ->where('table_schema', $database)
            ->where('column_name', 'clientid')
            ->get();

        foreach ($columns as $column) {
            $table = $column->table_name;
            $nullability = strtoupper((string) $column->is_nullable) === 'YES' ? 'NULL' : 'NOT NULL';

            DB::statement(sprintf(
                'ALTER TABLE `%s` MODIFY `clientid` VARCHAR(10) %s',
                str_replace('`', '``', $table),
                $nullability
            ));
        }
    }

    public function down(): void
    {
        // Intentionally irreversible: previous lengths varied across tables (6/10/12).
    }
};
