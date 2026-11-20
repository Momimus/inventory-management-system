<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/profile')->assertOk()->assertSee($user->email);
    }

    public function test_user_can_update_their_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/profile', ['name' => 'Updated Name', 'email' => 'updated@example.com'])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $user->refresh();
        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('updated@example.com', $user->email);
        $this->assertDatabaseHas('activity_logs', ['action' => 'profile.updated', 'user_id' => $user->id]);
    }

    public function test_profile_update_cannot_change_role_or_status(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)->put('/profile', [
            'name' => $staff->name,
            'email' => $staff->email,
            'role' => 'admin',
            'is_active' => false,
        ]);

        $staff->refresh();
        $this->assertSame(UserRole::Staff, $staff->role);
        $this->assertTrue($staff->is_active);
    }

    public function test_email_must_be_unique(): void
    {
        $other = User::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/profile', ['name' => $user->name, 'email' => $other->email])
            ->assertSessionHasErrors('email');
    }

    public function test_password_can_be_changed_with_the_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'password',
            'password' => 'NewSecurePass123',
            'password_confirmation' => 'NewSecurePass123',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('NewSecurePass123', $user->fresh()->password));
        $this->assertDatabaseHas('activity_logs', ['action' => 'profile.password_changed', 'user_id' => $user->id]);
    }

    public function test_password_change_requires_the_correct_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'wrong',
            'password' => 'NewSecurePass123',
            'password_confirmation' => 'NewSecurePass123',
        ])->assertSessionHasErrorsIn('updatePassword', 'current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_password_change_logs_never_contain_the_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'password',
            'password' => 'NewSecurePass123',
            'password_confirmation' => 'NewSecurePass123',
        ]);

        $this->assertStringNotContainsString(
            'NewSecurePass123',
            (string) $user->activityLogs()->pluck('properties')->toJson(),
        );
    }
}
