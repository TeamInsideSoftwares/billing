<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Account;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Account::create([
            'accountid'      => 'ACC0000001',
            'name'           => 'Test',
            'slug'           => 'test',
            'email'          => 'team@insidesoftwares.com',
            'password'       => Hash::make('123456'),
            'status'         => 'active',
            'currency_code'  => 'INR',
            'timezone'       => 'Asia/Kolkata',
            // Add any other fields if necessary
        ]);
    }
}