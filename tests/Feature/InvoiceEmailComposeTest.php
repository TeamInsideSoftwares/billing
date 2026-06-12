<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceEmailComposeTest extends TestCase
{
    use RefreshDatabase;

    protected Account $account;

    protected User $user;

    protected Client $client;

    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->account = Account::create([
            'accountid' => 'ACC0123456',
            'name' => 'Test Account',
            'slug' => 'test-account',
            'status' => 'active',
            'currency_code' => 'INR',
            'email' => 'admin@testaccount.com',
            'password' => bcrypt('password'),
        ]);

        $this->user = User::create([
            'userid' => 'USR001',
            'accountid' => $this->account->accountid,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->client = Client::create([
            'clientid' => 'CLI001',
            'accountid' => $this->account->accountid,
            'business_name' => 'Test Client',
            'primary_email' => 'client@example.com',
            'currency' => 'INR',
            'state' => 'Maharashtra',
            'type' => 'regular',
        ]);

        $this->invoice = Invoice::create([
            'invoiceid' => 'INV001',
            'accountid' => $this->account->accountid,
            'clientid' => $this->client->clientid,
            'invoice_number' => 'INV-2026-001',
            'pi_number' => 'PI-2026-001',
            'ti_number' => 'TI-2026-001',
            'status' => 'draft',
            'grand_total' => 1000,
            'currency' => 'INR',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
        ]);
    }

    public function test_email_compose_renders_successfully(): void
    {
        $response = $this->actingAs($this->user)->get(route('invoices.email-compose', $this->invoice->invoiceid));

        $response->assertStatus(200);
        $response->assertViewIs('invoices.email-compose');
        $response->assertSee('Email');
        $response->assertSee('WhatsApp');
        $response->assertSee('SMS');
    }

    public function test_email_compose_store_saves_draft_via_ajax(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('invoices.email-compose.store', $this->invoice->invoiceid), [
            'channel' => 'email',
            'action' => 'save',
            'attachment_type' => 'pi',
            'to_email' => 'recipient@example.com',
            'subject' => 'Draft Email Subject',
            'body' => 'Draft Email Body',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Message draft saved successfully.',
        ]);

        $this->assertDatabaseHas('communication_logs', [
            'invoiceid' => $this->invoice->invoiceid,
            'channel' => 'email',
            'status' => 'draft',
            'subject' => 'Draft Email Subject',
            'body' => 'Draft Email Body',
        ]);
    }
}
