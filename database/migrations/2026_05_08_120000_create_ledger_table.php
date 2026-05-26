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
            $table->string('clientid', 10);
            $table->date('date');
            $table->string('invoiceid_paymentid', 20);
            $table->decimal('amount', 12, 2)->default(0);
            $table->enum('type', ['dr', 'cr']);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['accountid', 'date']);
            $table->index(['clientid', 'date']);
            $table->unique(['invoiceid_paymentid', 'type'], 'ledger_invoice_payment_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger');
    }
};
