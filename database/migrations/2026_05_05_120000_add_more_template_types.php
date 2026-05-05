<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Get current enum values
        $connection = DB::connection()->getDriverName();

        if ($connection === 'mysql') {
            // MySQL: Modify enum by recreating the column
            DB::statement("ALTER TABLE account_templates MODIFY template_type ENUM('pi', 'ti', 'renewal', 'expiry', 'reminder', 'payment_received') NOT NULL DEFAULT 'pi'");
        } else {
            // For other databases (SQLite, PostgreSQL), add new column approach
            Schema::table('account_templates', function (Blueprint $table) {
                $table->enum('template_type', ['pi', 'ti', 'renewal', 'expiry', 'reminder', 'payment_received'])->change();
            });
        }
    }

    public function down(): void
    {
        $connection = DB::connection()->getDriverName();

        if ($connection === 'mysql') {
            DB::statement("ALTER TABLE account_templates MODIFY template_type ENUM('pi', 'ti', 'renewal', 'expiry', 'reminder', 'payment_received') NOT NULL DEFAULT 'pi'");
        } else {
            Schema::table('account_templates', function (Blueprint $table) {
                $table->enum('template_type', ['pi', 'ti', 'renewal', 'expiry', 'reminder', 'payment_received'])->change();
            });
        }
    }
};
