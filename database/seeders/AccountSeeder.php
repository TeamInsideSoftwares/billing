<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;
use App\Models\User;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $account = Account::create([
            'accountid'      => 'T499MU1L1l',
            'name'           => 'Test',
            'slug'           => 'test',
            'email'          => 'team@insidesoftwares.com',
            'status'         => 'active',
            'currency_code'  => 'INR',
            'timezone'       => 'Asia/Kolkata',
            // Add any other fields if necessary
        ]);

        User::updateOrCreate(
            ['email' => 'team@insidesoftwares.com'],
            [
                'accountid' => $account->accountid,
                'name' => $account->name,
                'email' => 'team@insidesoftwares.com',
                'password' => '123456',
                'role' => 'admin',
                'is_active' => true,
            ]
        );
    }
}
