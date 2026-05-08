<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger', function (Blueprint $table) {
            $table->string('ledgerid', 6)->primary();
            $table->string('accountid', 10);
            $table->string('clientid', 6);
            $table->date('date');
            $table->string('reference_number', 20);
            $table->decimal('amount', 12, 2)->default(0);
            $table->enum('type', ['payment', 'tds', 'invoice']);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['accountid', 'date']);
            $table->index(['clientid', 'date']);
            $table->unique(['reference_number', 'type'], 'ledger_reference_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger');
    }
};
