<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PasswordRestrictionTest extends TestCase
{
    public function test_admin_demo_and_manager_accounts_cannot_change_password_via_api(): void
    {
        $accounts = [
            User::factory()->create(['email' => 'admin@gstplatform.com', 'role' => 'admin']),
            User::factory()->create(['email' => 'demo@gstplatform.com', 'role' => 'business_user', 'name' => 'Demo User']),
            User::factory()->create(['email' => 'manager@gstplatform.com', 'role' => 'business_user', 'name' => 'Demo Manager']),
        ];

        foreach ($accounts as $user) {
            Sanctum::actingAs($user);

            $this->putJson('/api/auth/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])->assertForbidden();
        }
    }
}

