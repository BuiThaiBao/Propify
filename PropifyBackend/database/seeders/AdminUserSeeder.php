<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'full_name' => 'System Administrator',
                'password'  => Hash::make('Admin123'),
                'role'      => UserRole::Admin,
                'status'    => UserStatus::Active,
                'phone'     => '0999999999', // Mock phone number in case it's required
            ]
        );
    }
}
