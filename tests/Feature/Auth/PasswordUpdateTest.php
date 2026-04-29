<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('updatePassword', 'current_password')
            ->assertRedirect('/profile');
    }

    public function test_admin_demo_and_manager_accounts_cannot_update_password(): void
    {
        $accounts = [
            User::factory()->create(['email' => 'admin@gstplatform.com', 'role' => 'admin']),
            User::factory()->create(['email' => 'demo@gstplatform.com', 'role' => 'business_user', 'name' => 'Demo Business User']),
            User::factory()->create(['email' => 'manager@gstplatform.com', 'role' => 'business_user', 'name' => 'Demo Manager']),
        ];

        foreach ($accounts as $user) {
            $response = $this
                ->actingAs($user)
                ->from('/profile')
                ->put('/password', [
                    'current_password' => 'password',
                    'password' => 'new-password',
                    'password_confirmation' => 'new-password',
                ]);

            $response
                ->assertSessionHasErrorsIn('updatePassword', 'password')
                ->assertRedirect('/profile');

            $this->assertTrue(Hash::check('password', $user->fresh()->password));
        }
    }
}
