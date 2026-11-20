<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

class HttpSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_sent(): void
    {
        // The policy is skipped while `npm run dev` is running, so pretend it is not.
        Vite::useHotFile(storage_path('framework/testing/no-vite-hot-file'));

        $response = $this->get('/login');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString("frame-ancestors 'none'", $response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("script-src 'self'", $response->headers->get('Content-Security-Policy'));
    }

    public function test_session_cookie_is_http_only(): void
    {
        $cookie = collect($this->get('/login')->headers->getCookies())
            ->firstWhere(fn ($cookie) => $cookie->getName() === config('session.cookie'));

        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
    }

    public function test_post_without_csrf_token_is_rejected(): void
    {
        $this->enableCsrfProtection();
        $user = User::factory()->create();

        $this->withoutExceptionHandling();
        $this->expectException(TokenMismatchException::class);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
    }

    public function test_post_with_valid_csrf_token_is_accepted(): void
    {
        $this->enableCsrfProtection();
        $user = User::factory()->create();

        $this->withSession(['_token' => 'known-token'])
            ->post('/login', ['_token' => 'known-token', 'email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    /**
     * Laravel skips CSRF checks while running tests. This swaps in the real
     * middleware behaviour so the protection itself can be verified.
     */
    private function enableCsrfProtection(): void
    {
        $this->app->bind(ValidateCsrfToken::class, fn ($app) => new class($app, $app['encrypter']) extends ValidateCsrfToken
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });
    }
}
