<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InventoryStatistics;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->staff = User::factory()->create();
    }

    public function test_dashboard_figures_come_from_the_database(): void
    {
        $tools = Category::factory()->create(['name' => 'Tools']);
        $paper = Category::factory()->create(['name' => 'Paper']);
        Supplier::factory()->create();
        Supplier::factory()->inactive()->create();

        Product::factory()->for($tools)->create(['quantity' => 10, 'reorder_level' => 2, 'purchase_price' => 5, 'selling_price' => 8]);
        Product::factory()->for($tools)->create(['quantity' => 3, 'reorder_level' => 5, 'purchase_price' => 2, 'selling_price' => 3]);
        Product::factory()->for($paper)->create(['quantity' => 0, 'reorder_level' => 5, 'purchase_price' => 1, 'selling_price' => 2]);
        Product::factory()->for($paper)->inactive()->create(['quantity' => 0, 'reorder_level' => 5, 'purchase_price' => 1, 'selling_price' => 2]);

        $response = $this->actingAs($this->staff)->get('/')->assertOk();

        $summary = $response->viewData('summary');
        $this->assertSame(4, $summary['products']);
        $this->assertSame(3, $summary['active_products']);
        $this->assertSame(2, $summary['categories']);
        $this->assertSame(1, $summary['active_suppliers']);
        $this->assertSame(13, $summary['units']);
        $this->assertEqualsWithDelta(56.0, $summary['stock_value_cost'], 0.001);   // 10*5 + 3*2
        $this->assertEqualsWithDelta(89.0, $summary['stock_value_retail'], 0.001); // 10*8 + 3*3
        $this->assertSame(1, $summary['low_stock'], 'Low stock excludes out-of-stock and inactive products.');
        $this->assertSame(1, $summary['out_of_stock'], 'Inactive products do not raise alerts.');
        $this->assertSame('Tools', $summary['by_category'][0]['name']);
        $this->assertSame(13, $summary['by_category'][0]['units']);

        $this->assertCount(2, $response->viewData('alerts'));
    }

    public function test_daily_movements_add_up_units_in_and_out(): void
    {
        $product = Product::factory()->create(['quantity' => 0]);
        $stock = app(StockService::class);

        $this->travelTo(now()->subDays(2));
        $stock->stockIn($product, 40, $this->staff);
        $this->travelBack();
        $stock->stockIn($product, 5, $this->staff);
        $stock->stockOut($product, 12, $this->staff);
        $stock->adjust($product, 30, $this->staff, 'Count'); // adjustments are not counted as in/out

        $movements = collect(app(InventoryStatistics::class)->summary()['movements'])->keyBy('date');

        $this->assertCount(InventoryStatistics::MOVEMENT_DAYS, $movements);
        $this->assertSame(['date' => now()->subDays(2)->toDateString(), 'in' => 40, 'out' => 0], $movements[now()->subDays(2)->toDateString()]);
        $this->assertSame(['date' => today()->toDateString(), 'in' => 5, 'out' => 12], $movements[today()->toDateString()]);
    }

    public function test_cached_figures_are_refreshed_after_a_stock_movement(): void
    {
        $product = Product::factory()->create(['quantity' => 10, 'reorder_level' => 2]);

        $this->actingAs($this->staff)->get('/')->assertViewHas('summary', fn ($summary) => $summary['units'] === 10);
        $this->assertTrue(Cache::has(InventoryStatistics::CACHE_KEY));

        app(StockService::class)->stockOut($product, 10, $this->staff);

        $this->assertFalse(Cache::has(InventoryStatistics::CACHE_KEY));
        $this->actingAs($this->staff)->get('/')->assertViewHas('summary', fn ($summary) => $summary['units'] === 0
            && $summary['out_of_stock'] === 1);
    }

    public function test_cache_is_cleared_when_catalogue_data_changes(): void
    {
        app(InventoryStatistics::class)->summary();
        $this->assertTrue(Cache::has(InventoryStatistics::CACHE_KEY));

        Category::factory()->create();

        $this->assertFalse(Cache::has(InventoryStatistics::CACHE_KEY));
    }

    public function test_recent_activity_is_shown_to_admins_only(): void
    {
        $admin = User::factory()->admin()->create();
        app(StockService::class)->stockIn(Product::factory()->create(), 5, $admin);

        $this->actingAs($admin)->get('/')->assertSee('Recent activity')->assertViewHas('recentActivity', fn ($items) => $items->isNotEmpty());
        $this->actingAs($this->staff)->get('/')->assertDontSee('Recent activity')->assertViewHas('recentActivity', fn ($items) => $items->isEmpty());
    }

    public function test_dashboard_works_with_an_empty_database(): void
    {
        $this->actingAs($this->staff)->get('/')->assertOk()->assertSee('All products are above their reorder level');
    }
}
