<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ClientContactTest extends TestCase
{
    use DatabaseTransactions;

    protected Account $account;

    protected User $user;

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
    }

    public function test_client_contact_relationship_and_accessor(): void
    {
        $client = Client::create([
            'accountid' => $this->account->accountid,
            'business_name' => 'Test Business',
            'primary_email' => 'business@example.com',
            'currency' => 'INR',
            'state' => 'Maharashtra',
            'type' => 'regular',
        ]);

        // Accessor should return null when no contacts exist
        $this->assertNull($client->contact_name);

        // Add a secondary contact
        $contact1 = ClientContact::create([
            'accountid' => $this->account->accountid,
            'clientid' => $client->clientid,
            'name' => 'Secondary Contact',
            'is_primary' => false,
        ]);

        // Accessor should fallback to the first contact if no primary is marked
        $this->assertEquals('Secondary Contact', $client->fresh()->contact_name);

        // Add a primary contact
        $contact2 = ClientContact::create([
            'accountid' => $this->account->accountid,
            'clientid' => $client->clientid,
            'name' => 'Primary Contact',
            'is_primary' => true,
        ]);

        // Accessor should now return the primary contact name
        $this->assertEquals('Primary Contact', $client->fresh()->contact_name);
    }

    public function test_clients_store_with_contacts(): void
    {
        $contactsData = [
            [
                'name' => 'John Doe',
                'designation' => 'Manager',
                'email' => 'john@example.com',
                'phone' => '1234567890',
                'is_primary' => true,
            ],
            [
                'name' => 'Jane Smith',
                'designation' => 'Billing',
                'email' => 'jane@example.com',
                'phone' => '9876543210',
                'is_primary' => false,
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('clients.store'), [
            'accountid' => $this->account->accountid,
            'business_name' => 'New Client',
            'primary_email' => 'client@example.com',
            'currency' => 'INR',
            'state' => 'Maharashtra',
            'billing_business_name' => 'New Client Billing',
            'billing_state' => 'Maharashtra',
            'contacts_json' => json_encode($contactsData),
        ]);

        $response->assertRedirect(route('clients.index'));

        $client = Client::where('business_name', 'New Client')->first();
        $this->assertNotNull($client);
        $this->assertCount(2, $client->contacts);
        $this->assertEquals('John Doe', $client->contact_name);
        $this->assertEquals('Manager', $client->primaryContact->designation);
    }

    public function test_clients_update_with_contacts(): void
    {
        $client = Client::create([
            'accountid' => $this->account->accountid,
            'business_name' => 'Edit Client',
            'primary_email' => 'edit@example.com',
            'currency' => 'INR',
            'state' => 'Maharashtra',
            'type' => 'regular',
        ]);

        $client->contacts()->create([
            'accountid' => $this->account->accountid,
            'name' => 'Old Contact',
            'is_primary' => true,
        ]);

        $contactsData = [
            [
                'name' => 'New Primary Contact',
                'designation' => 'Director',
                'email' => 'director@example.com',
                'phone' => '5555555',
                'is_primary' => true,
            ],
        ];

        $response = $this->actingAs($this->user)->put(route('clients.update', $client), [
            'business_name' => 'Updated Client Name',
            'primary_email' => 'edit@example.com',
            'currency' => 'INR',
            'state' => 'Maharashtra',
            'billing_business_name' => 'Updated Client Billing',
            'billing_state' => 'Maharashtra',
            'contacts_json' => json_encode($contactsData),
        ]);

        $response->assertRedirect(route('clients.index'));

        $client = $client->fresh();
        $this->assertEquals('Updated Client Name', $client->business_name);
        $this->assertCount(1, $client->contacts);
        $this->assertEquals('New Primary Contact', $client->contact_name);
    }
}
