<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestLoginUserSeeder extends Seeder
{
    /**
     * Seed fixed accounts used by the login page test buttons.
     */
    public function run(): void
    {
        $testUsers = [
            [
                'name' => 'Admin',
                'email' => 'admin@gstplatform.com',
                'password' => 'admin123',
                'role' => 'admin',
            ],
            [
                'name' => 'Demo Business User',
                'email' => 'demo@gstplatform.com',
                'password' => 'demo123',
                'role' => 'business_user',
            ],
            [
                'name' => 'Demo Manager',
                'email' => 'manager@gstplatform.com',
                'password' => 'manager123',
                'role' => 'business_user',
            ],
        ];

        foreach ($testUsers as $testUser) {
            User::updateOrCreate(
                ['email' => $testUser['email']],
                [
                    'name' => $testUser['name'],
                    'password' => Hash::make($testUser['password']),
                    'role' => $testUser['role'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );
        }

        $this->command->info('Test login users injected: admin@gstplatform.com, demo@gstplatform.com, manager@gstplatform.com.');
    }
}
