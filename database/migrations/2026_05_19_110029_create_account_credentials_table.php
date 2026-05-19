<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_credentials', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('accountid', 10)->unique();
            $table->string('email', 150)->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();

            $table->index('email');
            $table->index('accountid');
        });

        // Backfill existing account login credentials.
        DB::table('accounts')
            ->select('accountid', 'email', 'password', 'remember_token', 'created_at', 'updated_at')
            ->orderBy('accountid')
            ->chunk(200, function ($rows) {
                $payload = [];
                foreach ($rows as $row) {
                    if (empty($row->accountid) || empty($row->email) || empty($row->password)) {
                        continue;
                    }

                    $payload[] = [
                        'accountid' => (string) $row->accountid,
                        'email' => (string) $row->email,
                        'password' => (string) $row->password,
                        'remember_token' => $row->remember_token,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ];
                }

                if (!empty($payload)) {
                    DB::table('account_credentials')->upsert(
                        $payload,
                        ['accountid'],
                        ['email', 'password', 'remember_token', 'updated_at']
                    );
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_credentials');
    }
};
