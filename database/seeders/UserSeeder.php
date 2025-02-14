<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Test credentials for quick API login
        User::create([
            'first_name' => 'Test',
            'last_name'  => 'User',
            'email'      => 'test@demo.com',
            'password'   => Hash::make('password'), // password: "password"
        ]);
    }
}
