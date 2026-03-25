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
        // Consolidated 'accounts' (The Agency / Workspace)
        Schema::create('accounts', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->string('status', 20)->default('active');
            $table->string('plan_name', 50)->default('starter');
            
            // Merged 'Company' fields into Account
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
            $table->string('id', 10)->primary();
            $table->string('account_id', 10); // Pointer to Agency Workspace
            $table->string('client_code', 30)->nullable()->unique();
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

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->index(['account_id', 'status']);
        });

        Schema::create('services', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('account_id', 10);
            $table->string('service_code', 30)->nullable()->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('billing_type', 20)->default('one_time');
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->index(['account_id', 'billing_type']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('account_id', 10);
            $table->string('client_id', 10);
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

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['account_id', 'status']);
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('invoice_id', 10);
            $table->string('service_id', 10)->nullable();
            $table->string('item_name', 150);
            $table->text('item_description')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
            $table->foreign('service_id')->references('id')->on('services')->nullOnDelete();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('account_id', 10);
            $table->string('client_id', 10);
            $table->string('invoice_id', 10)->nullable();
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

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();
            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
            $table->foreign('received_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['account_id', 'status']);
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('account_id', 10);
            $table->string('client_id', 10);
            $table->string('service_id', 10);
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

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();
            $table->foreign('service_id')->references('id')->on('services')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['account_id', 'status']);
        });

        Schema::create('estimates', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('account_id', 10);
            $table->string('client_id', 10);
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
            $table->string('converted_invoice_id', 10)->nullable();
            $table->string('created_by', 10)->nullable();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();
            $table->foreign('converted_invoice_id')->references('id')->on('invoices')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['account_id', 'status']);
        });

        Schema::create('estimate_items', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('estimate_id', 10);
            $table->string('service_id', 10)->nullable();
            $table->string('item_name', 150);
            $table->text('item_description')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->foreign('estimate_id')->references('id')->on('estimates')->cascadeOnDelete();
            $table->foreign('service_id')->references('id')->on('services')->nullOnDelete();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('account_id', 10);
            $table->string('setting_key', 100);
            $table->text('setting_value')->nullable();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->unique(['account_id', 'setting_key']);
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
