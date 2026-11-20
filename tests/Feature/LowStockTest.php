<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LowStockTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->staff = User::factory()->create();
    }

    public function test_products_at_or_below_reorder_level_are_listed_most_urgent_first(): void
    {
        Product::factory()->create(['name' => 'Healthy Item', 'quantity' => 50, 'reorder_level' => 10]);
        Product::factory()->create(['name' => 'Exactly At Level', 'quantity' => 10, 'reorder_level' => 10]);
        Product::factory()->create(['name' => 'Nearly Gone', 'quantity' => 2, 'reorder_level' => 10]);
        Product::factory()->create(['name' => 'Completely Empty', 'quantity' => 0, 'reorder_level' => 10]);
        Product::factory()->inactive()->create(['name' => 'Discontinued Item', 'quantity' => 0, 'reorder_level' => 10]);

        $this->actingAs($this->staff)->get('/low-stock')
            ->assertOk()
            ->assertSeeInOrder(['Completely Empty', 'Nearly Gone', 'Exactly At Level'])
            ->assertDontSee('Healthy Item')
            ->assertDontSee('Discontinued Item');
    }

    public function test_alerts_can_be_filtered_by_level_category_and_search(): void
    {
        $tools = Category::factory()->create();
        Product::factory()->for($tools)->create(['name' => 'Low Hammer', 'quantity' => 2, 'reorder_level' => 5]);
        Product::factory()->create(['name' => 'Empty Glue', 'quantity' => 0, 'reorder_level' => 5]);

        $this->actingAs($this->staff)->get('/low-stock?level=out')->assertSee('Empty Glue')->assertDontSee('Low Hammer');
        $this->actingAs($this->staff)->get('/low-stock?level=low')->assertSee('Low Hammer')->assertDontSee('Empty Glue');
        $this->actingAs($this->staff)->get("/low-stock?category={$tools->id}")->assertSee('Low Hammer')->assertDontSee('Empty Glue');
        $this->actingAs($this->staff)->get('/low-stock?search=glue')->assertSee('Empty Glue')->assertDontSee('Low Hammer');
        $this->actingAs($this->staff)->get('/low-stock?level=in')->assertSessionHasErrors('level');
    }

    public function test_alert_disappears_once_stock_is_replenished(): void
    {
        $product = Product::factory()->create(['name' => 'Refill Me', 'quantity' => 1, 'reorder_level' => 5]);

        $this->actingAs($this->staff)->get('/low-stock')->assertSee('Refill Me');

        app(StockService::class)->stockIn($product, 20, $this->staff);

        $this->actingAs($this->staff)->get('/low-stock')->assertDontSee('Refill Me');
    }

    public function test_sidebar_shows_the_number_of_alerts(): void
    {
        Product::factory()->count(2)->lowStock()->create();
        Product::factory()->outOfStock()->create();

        $this->actingAs($this->staff)->get('/products')->assertSee('3 products need attention');
    }

    public function test_guests_cannot_see_low_stock(): void
    {
        $this->get('/low-stock')->assertRedirect('/login');
    }
}
