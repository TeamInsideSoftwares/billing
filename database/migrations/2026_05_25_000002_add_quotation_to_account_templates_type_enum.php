<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE account_templates MODIFY template_type ENUM('pi', 'ti', 'quotation', 'renewal', 'expiry', 'reminder', 'payment_received') NOT NULL DEFAULT 'pi'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE account_templates MODIFY template_type ENUM('pi', 'ti', 'renewal', 'expiry', 'reminder', 'payment_received') NOT NULL DEFAULT 'pi'");
    }
};
