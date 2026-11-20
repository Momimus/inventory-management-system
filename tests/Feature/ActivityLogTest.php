<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_important_actions_are_recorded_with_user_subject_and_time(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)->post('/products', [
            'name' => 'Logged Product', 'sku' => 'LOG-1', 'category_id' => $category->id,
            'purchase_price' => 1, 'selling_price' => 2, 'reorder_level' => 1, 'is_active' => 1,
        ]);
        $product = Product::where('sku', 'LOG-1')->sole();
        $this->actingAs($this->admin)->post('/stock/in', ['product_id' => $product->id, 'quantity' => 5]);
        $this->actingAs($this->admin)->put("/categories/{$category->id}", ['name' => 'Renamed Category']);

        $productLog = ActivityLog::where('action', 'product.created')->sole();
        $this->assertSame($this->admin->id, $productLog->user_id);
        $this->assertSame('product', $productLog->subject_type);
        $this->assertSame($product->id, $productLog->subject_id);
        $this->assertNotNull($productLog->created_at);
        $this->assertSame('127.0.0.1', $productLog->ip_address);

        $this->assertSame('stock_transaction', ActivityLog::where('action', 'stock.in')->sole()->subject_type);
        $this->assertSame('Renamed Category', ActivityLog::where('action', 'category.updated')->sole()->properties['new']['name']);
    }

    public function test_admin_can_view_and_filter_the_log(): void
    {
        $staff = User::factory()->create(['name' => 'Sam Staff']);
        $product = Product::factory()->create();
        app(StockService::class)->stockIn($product, 3, $staff);
        $this->actingAs($this->admin)->post('/categories', ['name' => 'Admin Category']);

        $this->actingAs($this->admin)->get('/activity')->assertOk()->assertSee('Created category Admin Category');

        $this->actingAs($this->admin)->get('/activity?area=stock')
            ->assertSee('Stock In of +3')->assertDontSee('Created category');

        $this->actingAs($this->admin)->get("/activity?user={$staff->id}")
            ->assertSee('Stock In of +3')->assertDontSee('Created category');

        $this->actingAs($this->admin)->get('/activity?search=Admin+Category')
            ->assertSee('Created category Admin Category')->assertDontSee('Stock In of');

        $this->actingAs($this->admin)->get('/activity?area=passwords')->assertSessionHasErrors('area');
    }

    public function test_entries_link_to_their_subject(): void
    {
        $product = Product::factory()->create();
        app(StockService::class)->stockIn($product, 3, $this->admin);

        $transaction = $product->stockTransactions()->sole();

        $this->actingAs($this->admin)->get('/activity')->assertSee(route('stock.show', $transaction));
    }

    public function test_staff_cannot_view_the_activity_log(): void
    {
        $this->actingAs(User::factory()->create())->get('/activity')->assertForbidden();
    }

    public function test_logged_values_are_escaped_when_displayed(): void
    {
        $this->actingAs($this->admin)->post('/categories', ['name' => '<script>alert(1)</script>']);

        $this->actingAs($this->admin)->get('/activity')
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', escape: false);
    }

    public function test_the_log_cannot_be_changed_through_the_application(): void
    {
        $this->actingAs($this->admin)->post('/categories', ['name' => 'Something']);
        $entry = ActivityLog::firstOrFail();

        $this->actingAs($this->admin)->delete('/activity')->assertMethodNotAllowed();
        $this->actingAs($this->admin)->delete("/activity/{$entry->id}")->assertNotFound();

        $this->expectException(\LogicException::class);
        $entry->delete();
    }
}
