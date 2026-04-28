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
        // Seed GST reference data (tax slabs, state codes, HSN codes)
        $this->call(GstDataSeeder::class);

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
            User::firstOrCreate(
                ['email' => $testUser['email']],
                [
                    'name' => $testUser['name'],
                    'password' => Hash::make($testUser['password']),
                    'role' => $testUser['role'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }

        $this->command->info('Test login users are available: admin@gstplatform.com, demo@gstplatform.com, manager@gstplatform.com.');
    }
}
