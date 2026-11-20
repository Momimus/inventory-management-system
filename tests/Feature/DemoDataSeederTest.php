<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_is_consistent_and_safe_to_run_twice(): void
    {
        $this->seed(DatabaseSeeder::class);
        $products = Product::count();
        $transactions = StockTransaction::count();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(25, $products);
        $this->assertSame($products, Product::count());
        $this->assertSame($transactions, StockTransaction::count());
        $this->assertSame(2, User::count());

        foreach (Product::all() as $product) {
            $this->assertSame((int) $product->stockTransactions()->sum('quantity'), $product->quantity, $product->sku);
        }

        $this->assertGreaterThan(0, Product::lowStock()->where('quantity', '>', 0)->count());
        $this->assertGreaterThan(0, Product::outOfStock()->count());
    }

    public function test_demo_accounts_can_sign_in(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->post('/login', ['email' => 'admin@example.com', 'password' => 'Password123'])->assertRedirect('/');
    }

    public function test_seeding_is_refused_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->expectException(RuntimeException::class);

        (new DatabaseSeeder)->run();
    }
}
