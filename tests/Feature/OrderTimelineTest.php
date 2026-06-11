<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\OrderTimeline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTimelineTest extends TestCase
{
    use RefreshDatabase;

    protected Account $account;

    protected User $user;

    protected Client $client;

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
            'accountid' => $this->account->accountid,
            'business_name' => 'Test Client',
            'primary_email' => 'client@example.com',
            'currency' => 'INR',
            'state' => 'Maharashtra',
            'type' => 'regular',
        ]);
    }

    public function test_order_creation_logs_timeline_event(): void
    {
        $order = Order::create([
            'accountid' => $this->account->accountid,
            'clientid' => $this->client->clientid,
            'order_number' => 'ORD-9999',
            'status' => 'draft',
            'item_name' => 'Test Item',
            'item_description' => 'Original Description',
            'quantity' => 1,
            'no_of_users' => 5,
        ]);

        $timeline = OrderTimeline::where('orderid', $order->orderid)->get();
        $this->assertCount(1, $timeline);
        $this->assertEquals('order_created', $timeline->first()->action_type);
        $this->assertEquals($this->client->clientid, $timeline->first()->clientid);
        $this->assertStringContainsString('ORD-9999', $timeline->first()->description);
    }

    public function test_order_field_updates_log_timeline_events(): void
    {
        $order = Order::create([
            'accountid' => $this->account->accountid,
            'clientid' => $this->client->clientid,
            'order_number' => 'ORD-8888',
            'status' => 'draft',
            'item_name' => 'Test Item',
            'item_description' => 'Original Description',
            'quantity' => 1,
            'no_of_users' => 5,
        ]);

        $order->update([
            'end_date' => '2026-12-31',
            'delivery_date' => '2026-06-15',
            'item_description' => 'New Description',
            'no_of_users' => 10,
            'quantity' => 2,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('order_timeline', [
            'orderid' => $order->orderid,
            'clientid' => $this->client->clientid,
            'action_type' => 'expiry_date_changed',
            'field_name' => 'end_date',
            'new_value' => '2026-12-31',
        ]);

        $this->assertDatabaseHas('order_timeline', [
            'orderid' => $order->orderid,
            'clientid' => $this->client->clientid,
            'action_type' => 'delivery_date_changed',
            'field_name' => 'delivery_date',
            'new_value' => '2026-06-15',
        ]);

        $this->assertDatabaseHas('order_timeline', [
            'orderid' => $order->orderid,
            'clientid' => $this->client->clientid,
            'action_type' => 'description_changed',
            'field_name' => 'item_description',
            'old_value' => 'Original Description',
            'new_value' => 'New Description',
        ]);

        $this->assertDatabaseHas('order_timeline', [
            'orderid' => $order->orderid,
            'clientid' => $this->client->clientid,
            'action_type' => 'users_changed',
            'field_name' => 'no_of_users',
            'old_value' => '5',
            'new_value' => '10',
        ]);

        $this->assertDatabaseHas('order_timeline', [
            'orderid' => $order->orderid,
            'clientid' => $this->client->clientid,
            'action_type' => 'quantity_changed',
            'field_name' => 'quantity',
            'old_value' => '1',
            'new_value' => '2',
        ]);

        $this->assertDatabaseHas('order_timeline', [
            'orderid' => $order->orderid,
            'clientid' => $this->client->clientid,
            'action_type' => 'status_changed',
            'field_name' => 'status',
            'old_value' => 'draft',
            'new_value' => 'active',
        ]);
    }

    public function test_invoice_item_link_update_and_delete_log_timeline_events(): void
    {
        $order = Order::create([
            'accountid' => $this->account->accountid,
            'clientid' => $this->client->clientid,
            'order_number' => 'ORD-7777',
            'status' => 'draft',
            'item_name' => 'Test Item',
        ]);

        $invoice = Invoice::create([
            'accountid' => $this->account->accountid,
            'clientid' => $this->client->clientid,
            'pi_number' => 'PI-001',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'draft',
        ]);

        $item = InvoiceItem::create([
            'invoiceid' => $invoice->invoiceid,
            'orderid' => $order->orderid,
            'accountid' => $this->account->accountid,
            'clientid' => $this->client->clientid,
            'item_name' => 'Test Item Billed',
            'quantity' => 1,
            'unit_price' => 100.00,
            'discount_amount' => 10.00,
            'amount' => 90.00,
        ]);

        $this->assertDatabaseHas('order_timeline', [
            'orderid' => $order->orderid,
            'clientid' => $this->client->clientid,
            'action_type' => 'invoice_item_billed',
        ]);

        $item->update([
            'quantity' => 2,
            'amount' => 180.00,
        ]);

        $this->assertDatabaseHas('order_timeline', [
            'orderid' => $order->orderid,
            'clientid' => $this->client->clientid,
            'action_type' => 'invoice_item_updated',
        ]);

        $item->delete();

        $this->assertDatabaseHas('order_timeline', [
            'orderid' => $order->orderid,
            'clientid' => $this->client->clientid,
            'action_type' => 'invoice_item_deleted',
        ]);
    }

    public function test_order_timeline_ajax_endpoint(): void
    {
        $order = Order::create([
            'accountid' => $this->account->accountid,
            'clientid' => $this->client->clientid,
            'order_number' => 'ORD-1234',
            'status' => 'draft',
            'item_name' => 'Test Item',
        ]);

        $this->get(route('orders.timeline', $order))->assertStatus(302);

        $response = $this->actingAs($this->user)->getJson(route('orders.timeline', $order));
        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'action_type' => 'order_created',
            'clientid' => $this->client->clientid,
            'orderid' => $order->orderid,
        ]);
    }
}
