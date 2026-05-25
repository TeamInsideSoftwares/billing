<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('invoice_emails');
        Schema::dropIfExists('quotation_emails');
    }

    public function down(): void
    {
        // Legacy tables intentionally not recreated in rollback.
    }
};
