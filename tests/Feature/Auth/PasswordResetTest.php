<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_link_is_sent_to_an_active_user(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('success');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_and_inactive_emails_get_the_same_response_and_no_link(): void
    {
        Notification::fake();
        $inactive = User::factory()->inactive()->create();

        $expected = 'If that address belongs to an active account, a reset link has been sent to it.';

        $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertSessionHas('success', $expected);
        $this->post('/forgot-password', ['email' => $inactive->email])->assertSessionHas('success', $expected);

        Notification::assertNothingSent();
    }

    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $this->get('/reset-password/'.$notification->token)->assertOk();

            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'BrandNewPass123',
                'password_confirmation' => 'BrandNewPass123',
            ])->assertRedirect('/login')->assertSessionHasNoErrors();

            return true;
        });

        $this->assertTrue(Hash::check('BrandNewPass123', $user->fresh()->password));
        $this->assertDatabaseHas('activity_logs', ['action' => 'auth.password_reset', 'user_id' => $user->id]);
    }

    public function test_reset_fails_with_an_invalid_token(): void
    {
        $user = User::factory()->create();

        $this->post('/reset-password', [
            'token' => 'not-a-real-token',
            'email' => $user->email,
            'password' => 'BrandNewPass123',
            'password_confirmation' => 'BrandNewPass123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_reset_rejects_weak_passwords(): void
    {
        $user = User::factory()->create();

        $this->post('/reset-password', [
            'token' => 'any',
            'email' => $user->email,
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');
    }
}
