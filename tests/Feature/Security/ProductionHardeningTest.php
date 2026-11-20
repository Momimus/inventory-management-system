<?php

namespace Tests\Feature\Security;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Http\Middleware\TrustHosts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Tests\TestCase;

class ProductionHardeningTest extends TestCase
{
    public function test_errors_do_not_reveal_details_when_debug_is_off(): void
    {
        config(['app.debug' => false]);
        Route::middleware('web')->get('/_test/explode', fn () => throw new RuntimeException('DB password is hunter2'));

        $response = $this->get('/_test/explode');

        $response->assertStatus(500)
            ->assertSee('Something went wrong')
            ->assertDontSee('hunter2')
            ->assertDontSee('RuntimeException')
            ->assertDontSee('vendor/laravel');
    }

    public function test_json_errors_do_not_reveal_details_when_debug_is_off(): void
    {
        config(['app.debug' => false]);
        Route::middleware('web')->get('/_test/explode-json', fn () => throw new RuntimeException('secret path /var/www'));

        $this->getJson('/_test/explode-json')
            ->assertStatus(500)
            ->assertExactJson(['message' => 'Server Error']);
    }

    public function test_custom_error_pages_are_used(): void
    {
        $this->get('/no-such-page')->assertNotFound()->assertSee('Page not found');
    }

    public function test_hsts_is_sent_over_https_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->get('https://localhost/login')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_hsts_is_not_sent_in_local_development(): void
    {
        $this->get('https://localhost/login')->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_trusted_hosts_come_from_configuration(): void
    {
        config(['app.trusted_hosts' => ['inventory.example.com', 'stock.example.org']]);

        $patterns = app(TrustHosts::class)->hosts();

        $this->assertContains('^inventory\.example\.com$', $patterns);
        $this->assertContains('^stock\.example\.org$', $patterns);
    }

    public function test_only_exact_trusted_host_names_are_accepted(): void
    {
        config(['app.trusted_hosts' => ['inventory.example.com']]);

        Request::setTrustedHosts(app(TrustHosts::class)->hosts());

        try {
            $this->assertSame('inventory.example.com', Request::create('https://inventory.example.com/')->getHost());

            foreach (['inventory.example.com.evil.net', 'inventoryXexample.com', 'evil.net'] as $host) {
                try {
                    Request::create("https://{$host}/")->getHost();
                    $this->fail("{$host} should not be trusted.");
                } catch (SuspiciousOperationException) {
                    // expected
                }
            }
        } finally {
            Request::setTrustedHosts([]);
        }
    }

    public function test_cross_origin_requests_get_no_cors_permission(): void
    {
        $this->withHeaders(['Origin' => 'https://evil.example'])
            ->get('/login')
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_unfillable_attributes_are_not_silently_discarded_in_development(): void
    {
        $this->expectException(MassAssignmentException::class);

        new Product(['name' => 'Test', 'quantity' => 999]);
    }

    public function test_every_application_route_requires_authentication(): void
    {
        $public = ['login', 'forgot-password', 'reset-password', 'reset-password/{token}', 'up'];

        foreach (Route::getRoutes() as $route) {
            if (in_array($route->uri(), $public, true) || str_starts_with($route->uri(), '_test/')) {
                continue;
            }

            $this->assertContains('auth', $route->gatherMiddleware(), "Route {$route->uri()} must require authentication.");
        }
    }

    public function test_private_storage_is_not_served_over_http(): void
    {
        $this->assertFalse(Route::has('storage.local'));
    }

    public function test_hidden_user_attributes_are_never_serialised(): void
    {
        $user = User::factory()->make();

        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertArrayNotHasKey('remember_token', $user->toArray());
    }
}
