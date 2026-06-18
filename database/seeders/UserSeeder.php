<?php

namespace Database\Seeders;

use App\Domain\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (User::query()->exists()) {
            return;
        }

        User::query()->create([
            'name' => 'مدیر سیستم',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::SuperAdmin,
            'hr_access' => true,
            'email_verified_at' => now(),
        ]);
    }
}
