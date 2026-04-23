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

        // Create default admin user if none exists
        if (User::where('role', 'admin')->count() === 0) {
            User::create([
                'name' => 'Admin',
                'email' => 'admin@gstplatform.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $this->command->info('✅ Default admin user created (admin@gstplatform.com / admin123)');
        }
    }
}
