<?php

namespace Tests\Feature\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_shown_to_guests(): void
    {
        $this->get('/login')->assertOk()->assertSee('Sign in');
    }

    public function test_guests_are_redirected_from_the_dashboard(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_user_can_log_in_with_valid_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'auth.login', 'user_id' => $user->id]);
    }

    public function test_login_regenerates_the_session_id(): void
    {
        $user = User::factory()->create();

        $this->get('/login');
        $sessionIdBeforeLogin = session()->getId();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertNotSame($sessionIdBeforeLogin, session()->getId());
    }

    public function test_user_cannot_log_in_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_failed_login_is_logged_without_the_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-attempt']);

        $log = ActivityLog::where('action', 'auth.failed')->sole();
        $this->assertSame($user->email, $log->properties['email']);
        $this->assertStringNotContainsString('secret-attempt', json_encode($log->properties));
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => __('auth.failed')]);

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < LoginRequest::MAX_ATTEMPTS; $attempt++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
        }

        // Even the correct password is refused while the limit is active.
        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many login attempts', session('errors')->first('email'));
        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', ['action' => 'auth.lockout']);
    }

    public function test_logout_ends_the_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/');
        $sessionId = session()->getId();

        $this->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
        $this->assertNotSame($sessionId, session()->getId());
        $this->get('/')->assertRedirect('/login');
        $this->assertDatabaseHas('activity_logs', ['action' => 'auth.logout', 'user_id' => $user->id]);
    }

    public function test_logout_requires_post(): void
    {
        $this->actingAs(User::factory()->create())->get('/logout')->assertMethodNotAllowed();
    }

    public function test_deactivated_user_is_signed_out_on_next_request(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/')->assertOk();

        $user->forceFill(['is_active' => false])->save();

        $this->get('/')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_signed_in_users_are_redirected_away_from_login(): void
    {
        $this->actingAs(User::factory()->create())->get('/login')->assertRedirect(route('dashboard'));
    }
}
