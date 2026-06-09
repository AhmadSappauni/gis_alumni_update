<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::where('email', 'test@example.com')->delete();

        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@pilkom.test')],
            [
                'name' => 'Admin WebGIS',
                'role' => 'admin',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'admin12345')),
            ]
        );

        User::updateOrCreate(
            ['email' => env('USER_EMAIL', 'user@pilkom.test')],
            [
                'name' => 'User WebGIS',
                'role' => 'user',
                'password' => Hash::make(env('USER_PASSWORD', 'user12345')),
            ]
        );
    }
}
