<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed the default user.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'nahid@gmail.com'],
            [
                'name' => 'nahid',
                'password' => Hash::make('12345678'),
            ]
        );
    }
}
