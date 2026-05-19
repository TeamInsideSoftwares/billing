<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Account;
use App\Models\AccountCredential;

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

        AccountCredential::updateOrCreate(
            ['accountid' => $account->accountid],
            [
                'email' => 'team@insidesoftwares.com',
                'password' => Hash::make('123456'),
            ]
        );
    }
}
