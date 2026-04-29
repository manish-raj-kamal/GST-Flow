<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminAccessRestrictionTest extends TestCase
{
    public function test_test_admin_only_receives_test_users_in_listing(): void
    {
        $testAdmin = User::factory()->create([
            'email' => 'admin@gstplatform.com',
            'role' => 'admin',
            'name' => 'Admin',
        ]);
        User::factory()->create(['email' => 'demo@gstplatform.com', 'name' => 'Demo User']);
        User::factory()->create(['email' => 'manager@gstplatform.com', 'name' => 'Manager']);
        User::factory()->create(['email' => 'realuser@example.com', 'name' => 'Real User']);

        Sanctum::actingAs($testAdmin);

        $response = $this->getJson('/api/admin/users');

        $response->assertOk();
        $emails = collect($response->json('data'))->pluck('email')->values()->all();

        $this->assertSame(
            ['admin@gstplatform.com', 'demo@gstplatform.com', 'manager@gstplatform.com'],
            $emails
        );
    }

    public function test_test_admin_cannot_change_non_test_users(): void
    {
        $testAdmin = User::factory()->create([
            'email' => 'admin@gstplatform.com',
            'role' => 'admin',
            'name' => 'Admin',
        ]);
        $realUser = User::factory()->create(['email' => 'realuser@example.com']);

        Sanctum::actingAs($testAdmin);

        $this->putJson('/api/admin/users/'.$realUser->id.'/toggle-status')
            ->assertForbidden();

        $this->putJson('/api/admin/users/'.$realUser->id.'/role', ['role' => 'admin'])
            ->assertForbidden();
    }
}

