<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->string('accountid', 10)->primary();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->string('status', 20)->default('active');
            
            $table->string('legal_name', 150)->nullable();
            $table->string('email', 150)->unique();
            $table->string('password');
            $table->string('phone', 30)->nullable();
            $table->string('tax_number', 50)->nullable();
            $table->string('website', 150)->nullable();
            $table->string('currency_code', 3)->default('INR');
            $table->string('timezone', 64)->default('Asia/Kolkata');
            $table->string('address_line_1', 150)->nullable();
            $table->string('address_line_2', 150)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->string('clientid', 6)->primary();
            $table->string('accountid', 10);
            $table->string('business_name', 150);
            $table->string('contact_name', 150)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('billing_email', 150)->nullable();
            $table->string('tax_number', 50)->nullable();
            $table->string('status', 20)->default('active');
            $table->string('address_line_1', 150)->nullable();
            $table->string('address_line_2', 150)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('accountid')->references('accountid')->on('accounts')->onDelete('cascade');
            $table->index(['accountid', 'status']);
        });

        Schema::create('services', function (Blueprint $table) {
            $table->string('serviceid', 6)->primary();
            $table->string('accountid', 10);
            $table->string('service_code', 30)->nullable()->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('billing_type', 20)->default('one_time');
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('accountid')->references('accountid')->on('accounts')->onDelete('cascade');
            $table->index(['accountid', 'billing_type']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->string('invoiceid', 6)->primary();
            $table->string('accountid', 10);
            $table->string('clientid', 6);
            $table->string('invoice_number', 30)->unique();
            $table->string('status', 20)->default('draft');
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('balance_due', 12, 2)->default(0);
            $table->string('currency_code', 3)->default('INR');
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('created_by', 10)->nullable();
            $table->timestamps();

            $table->foreign('accountid')->references('accountid')->on('accounts')->onDelete('cascade');
            $table->foreign('clientid')->references('clientid')->on('clients')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['accountid', 'status']);
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->string('invoiceitemid', 6)->primary();
            $table->string('invoiceid', 6);
            $table->string('serviceid', 6)->nullable();
            $table->string('item_name', 150);
            $table->text('item_description')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->foreign('invoiceid')->references('invoiceid')->on('invoices')->onDelete('cascade');
            $table->foreign('serviceid')->references('serviceid')->on('services')->onDelete('set null');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->string('paymentid', 6)->primary();
            $table->string('accountid', 10);
            $table->string('clientid', 6);
            $table->string('invoiceid', 6)->nullable();
            $table->string('payment_number', 30)->unique();
            $table->date('payment_date');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 30);
            $table->string('reference_number', 100)->nullable();
            $table->string('gateway_name', 50)->nullable();
            $table->string('gateway_transaction_id', 100)->nullable();
            $table->string('status', 20)->default('completed');
            $table->text('notes')->nullable();
            $table->string('received_by', 10)->nullable();
            $table->timestamps();

            $table->foreign('accountid')->references('accountid')->on('accounts')->onDelete('cascade');
            $table->foreign('clientid')->references('clientid')->on('clients')->onDelete('restrict');
            $table->foreign('invoiceid')->references('invoiceid')->on('invoices')->onDelete('set null');
            $table->foreign('received_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['accountid', 'status']);
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->string('subscriptionid', 6)->primary();
            $table->string('accountid', 10);
            $table->string('clientid', 6);
            $table->string('serviceid', 6);
            $table->date('start_date');
            $table->date('next_billing_date');
            $table->date('end_date')->nullable();
            $table->string('billing_cycle', 20);
            $table->decimal('price', 12, 2);
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('status', 20)->default('active');
            $table->boolean('auto_generate_invoice')->default(true);
            $table->string('created_by', 10)->nullable();
            $table->timestamps();

            $table->foreign('accountid')->references('accountid')->on('accounts')->onDelete('cascade');
            $table->foreign('clientid')->references('clientid')->on('clients')->onDelete('restrict');
            $table->foreign('serviceid')->references('serviceid')->on('services')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['accountid', 'status']);
        });

        Schema::create('estimates', function (Blueprint $table) {
            $table->string('estimateid', 6)->primary();
            $table->string('accountid', 10);
            $table->string('clientid', 6);
            $table->string('estimate_number', 30)->unique();
            $table->string('status', 20)->default('draft');
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->string('invoiceid', 6)->nullable();
            $table->string('created_by', 10)->nullable();
            $table->timestamps();

            $table->foreign('accountid')->references('accountid')->on('accounts')->onDelete('cascade');
            $table->foreign('clientid')->references('clientid')->on('clients')->onDelete('restrict');
            $table->foreign('invoiceid')->references('invoiceid')->on('invoices')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['accountid', 'status']);
        });

        Schema::create('estimate_items', function (Blueprint $table) {
            $table->string('estimateitemid', 6)->primary();
            $table->string('estimateid', 6);
            $table->string('serviceid', 6)->nullable();
            $table->string('item_name', 150);
            $table->text('item_description')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->foreign('estimateid')->references('estimateid')->on('estimates')->onDelete('cascade');
            $table->foreign('serviceid')->references('serviceid')->on('services')->onDelete('set null');
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('settingid', 6)->primary();
            $table->string('accountid', 10);
            $table->string('setting_key', 100);
            $table->text('setting_value')->nullable();
            $table->timestamps();

            $table->foreign('accountid')->references('accountid')->on('accounts')->onDelete('cascade');
            $table->unique(['accountid', 'setting_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('estimate_items');
        Schema::dropIfExists('estimates');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('services');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('accounts');
    }
};

