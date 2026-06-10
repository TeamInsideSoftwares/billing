<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('client_contacts')) {
            Schema::create('client_contacts', function (Blueprint $table) {
                $table->string('contactid', 6)->primary();
                $table->string('accountid', 10);
                $table->string('clientid', 10);
                $table->string('name', 150);
                $table->string('phone', 100)->nullable();
                $table->string('email', 255)->nullable();
                $table->string('designation', 100)->nullable();
                $table->boolean('is_primary')->default(false);
                $table->timestamps();

                $table->index(['accountid', 'clientid']);
                $table->index(['clientid', 'is_primary']);
            });
        }

        // Migrate existing contact_name details from clients to client_contacts
        if (Schema::hasTable('clients') && Schema::hasColumn('clients', 'contact_name')) {
            $clients = DB::table('clients')
                ->select('clientid', 'accountid', 'contact_name', 'phone', 'primary_email', 'email')
                ->get();

            foreach ($clients as $client) {
                $contactName = trim((string) $client->contact_name);
                if ($contactName === '') {
                    if (empty($client->primary_email) && empty($client->phone)) {
                        continue;
                    }
                    $contactName = 'Primary Contact';
                }

                // Truncate to avoid errors
                $contactName = substr($contactName, 0, 150);
                $phone = $client->phone ? substr(trim((string) $client->phone), 0, 100) : null;
                $emailVal = $client->primary_email ?: ($client->email ? explode(',', $client->email)[0] : null);
                $email = $emailVal ? substr(trim((string) $emailVal), 0, 255) : null;

                // Generate a unique 6-char alphanumeric ID
                $attempts = 0;
                do {
                    $contactId = strtoupper(Str::random(6));
                    $attempts++;
                } while (DB::table('client_contacts')->where('contactid', $contactId)->exists() && $attempts < 20);

                DB::table('client_contacts')->insert([
                    'contactid' => $contactId,
                    'accountid' => $client->accountid,
                    'clientid' => $client->clientid,
                    'name' => $contactName,
                    'phone' => $phone,
                    'email' => $email,
                    'designation' => 'Primary Contact',
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Drop contact_name column from clients table
            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn('contact_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('clients') && ! Schema::hasColumn('clients', 'contact_name')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('contact_name', 150)->nullable()->after('business_name');
            });

            // Restore contact_name from primary contact if possible
            if (Schema::hasTable('client_contacts')) {
                $contacts = DB::table('client_contacts')
                    ->where('is_primary', true)
                    ->get();

                foreach ($contacts as $contact) {
                    DB::table('clients')
                        ->where('clientid', $contact->clientid)
                        ->update(['contact_name' => $contact->name]);
                }
            }
        }

        Schema::dropIfExists('client_contacts');
    }
};
