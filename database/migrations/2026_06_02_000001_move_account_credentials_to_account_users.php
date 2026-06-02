<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('account_credentials') || !DB::getSchemaBuilder()->hasTable('account_users')) {
            return;
        }

        $accountNames = DB::table('accounts')->pluck('name', 'accountid');

        DB::table('account_credentials')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($accountNames): void {
                foreach ($rows as $row) {
                    $email = strtolower(trim((string) ($row->email ?? '')));
                    $accountid = (string) ($row->accountid ?? '');

                    if ($email === '' || $accountid === '') {
                        continue;
                    }

                    $payload = [
                        'accountid' => $accountid,
                        'name' => (string) ($accountNames[$accountid] ?? 'Account'),
                        'email' => $email,
                        'profile_image' => null,
                        'department' => null,
                        'phone' => null,
                        'designation' => null,
                        'notes' => null,
                        'password' => (string) $row->password,
                        'role' => 'admin',
                        'permissions' => null,
                        'is_active' => true,
                        'remember_token' => $row->remember_token,
                        'email_verified_at' => null,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                    ];

                    $existing = DB::table('account_users')->where('email', $email)->first();
                    if ($existing) {
                        DB::table('account_users')
                            ->where('userid', $existing->userid)
                            ->update($payload);
                        continue;
                    }

                    $payload['userid'] = $this->generateUserId();
                    DB::table('account_users')->insert($payload);
                }
            });
    }

    public function down(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('account_users') || !DB::getSchemaBuilder()->hasTable('account_credentials')) {
            return;
        }

        $emails = DB::table('account_credentials')->pluck('email')->filter()->map(fn ($email) => strtolower((string) $email))->values()->all();

        if ($emails === []) {
            return;
        }

        DB::table('account_users')
            ->whereIn('email', $emails)
            ->delete();
    }

    private function generateUserId(): string
    {
        do {
            $userid = strtoupper(Str::random(6));
        } while (DB::table('account_users')->where('userid', $userid)->exists());

        return $userid;
    }
};
