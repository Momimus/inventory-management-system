<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $staff;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->staff = User::factory()->create();
        $this->category = Category::factory()->create();
    }

    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Blue Ballpoint Pen',
            'sku' => 'pen-blue-01',
            'category_id' => $this->category->id,
            'supplier_id' => null,
            'description' => 'Pack of 10',
            'purchase_price' => '4.50',
            'selling_price' => '7.99',
            'reorder_level' => '20',
            'is_active' => '1',
            ...$overrides,
        ];
    }

    public function test_staff_can_create_a_product(): void
    {
        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($this->staff)->post('/products', $this->payload(['supplier_id' => $supplier->id]));

        $product = Product::where('sku', 'PEN-BLUE-01')->sole();
        $response->assertRedirect("/products/{$product->id}");
        $this->assertSame('7.99', $product->selling_price);
        $this->assertSame('4.50', $product->purchase_price);
        $this->assertSame($supplier->id, $product->supplier_id);
        $this->assertSame(0, $product->quantity);
        $this->assertDatabaseHas('activity_logs', ['action' => 'product.created', 'subject_id' => $product->id]);
    }

    public function test_quantity_cannot_be_set_through_the_product_form(): void
    {
        $this->actingAs($this->staff)->post('/products', $this->payload(['quantity' => 500]));
        $this->assertSame(0, Product::where('sku', 'PEN-BLUE-01')->sole()->quantity);

        $product = Product::factory()->create(['quantity' => 12]);
        $this->actingAs($this->staff)->put("/products/{$product->id}", $this->payload([
            'sku' => $product->sku,
            'quantity' => 9999,
        ]))->assertSessionHasNoErrors();

        $this->assertSame(12, $product->fresh()->quantity);
    }

    public function test_product_can_be_updated(): void
    {
        $product = Product::factory()->create(['sku' => 'OLD-SKU']);

        $this->actingAs($this->staff)
            ->put("/products/{$product->id}", $this->payload(['sku' => 'new-sku', 'name' => 'Renamed product']))
            ->assertRedirect("/products/{$product->id}");

        $product->refresh();
        $this->assertSame('NEW-SKU', $product->sku);
        $this->assertSame('Renamed product', $product->name);

        $log = ActivityLog::where('action', 'product.updated')->sole();
        $this->assertSame('OLD-SKU', $log->properties['old']['sku']);
        $this->assertSame('NEW-SKU', $log->properties['new']['sku']);
    }

    public function test_duplicate_sku_is_rejected_regardless_of_case(): void
    {
        Product::factory()->create(['sku' => 'PEN-BLUE-01']);

        $this->actingAs($this->staff)->post('/products', $this->payload(['sku' => 'pen-blue-01']))
            ->assertSessionHasErrors('sku');

        $this->assertSame(1, Product::count());
    }

    public function test_product_keeps_its_own_sku_when_updated(): void
    {
        $product = Product::factory()->create(['sku' => 'KEEP-ME']);

        $this->actingAs($this->staff)
            ->put("/products/{$product->id}", $this->payload(['sku' => 'KEEP-ME']))
            ->assertSessionHasNoErrors();
    }

    public function test_invalid_product_input_is_rejected(): void
    {
        $this->actingAs($this->staff)->post('/products', $this->payload([
            'name' => '',
            'sku' => 'bad sku!',
            'category_id' => 999,
            'purchase_price' => '-1',
            'selling_price' => '12.345',
            'reorder_level' => '2.5',
        ]))->assertSessionHasErrors(['name', 'sku', 'category_id', 'purchase_price', 'selling_price', 'reorder_level']);

        $this->assertSame(0, Product::count());
    }

    public function test_inactive_supplier_cannot_be_chosen_for_a_product(): void
    {
        $inactive = Supplier::factory()->inactive()->create();

        $this->actingAs($this->staff)->post('/products', $this->payload(['supplier_id' => $inactive->id]))
            ->assertSessionHasErrors('supplier_id');
    }

    public function test_existing_link_to_a_supplier_that_became_inactive_can_be_kept(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->for($supplier)->create();
        $supplier->update(['is_active' => false]);

        $this->actingAs($this->staff)
            ->put("/products/{$product->id}", $this->payload(['sku' => $product->sku, 'supplier_id' => $supplier->id]))
            ->assertSessionHasNoErrors();
    }

    public function test_products_can_be_searched_and_filtered(): void
    {
        $other = Category::factory()->create();
        Product::factory()->for($this->category)->create(['name' => 'Stapler', 'sku' => 'STP-1']);
        Product::factory()->for($other)->lowStock()->create(['name' => 'Glue Stick', 'sku' => 'GLU-1']);
        Product::factory()->for($other)->outOfStock()->create(['name' => 'Tape Roll', 'sku' => 'TAP-1']);

        $this->actingAs($this->staff)->get('/products?search=stp')
            ->assertSee('Stapler')->assertDontSee('Glue Stick');

        $this->actingAs($this->staff)->get("/products?category={$other->id}")
            ->assertSee('Glue Stick')->assertSee('Tape Roll')->assertDontSee('Stapler');

        $this->actingAs($this->staff)->get('/products?stock=low')
            ->assertSee('Glue Stick')->assertDontSee('Tape Roll')->assertDontSee('Stapler');

        $this->actingAs($this->staff)->get('/products?stock=out')
            ->assertSee('Tape Roll')->assertDontSee('Glue Stick');
    }

    public function test_sorting_only_accepts_whitelisted_columns(): void
    {
        Product::factory()->create(['name' => 'Alpha', 'quantity' => 50]);
        Product::factory()->create(['name' => 'Beta', 'quantity' => 5]);

        $this->actingAs($this->staff)->get('/products?sort=quantity&direction=asc')
            ->assertOk()
            ->assertSeeInOrder(['Beta', 'Alpha']);

        $this->actingAs($this->staff)->get('/products?sort=password')->assertSessionHasErrors('sort');
        $this->actingAs($this->staff)->get('/products?sort=name;DROP TABLE products')->assertSessionHasErrors('sort');
        $this->actingAs($this->staff)->get('/products?direction=sideways')->assertSessionHasErrors('direction');

        $this->assertSame(2, Product::count());
    }

    public function test_search_input_is_treated_as_data_not_sql(): void
    {
        Product::factory()->create(['name' => 'Safe Product']);

        $this->actingAs($this->staff)->get('/products?search='.urlencode("' OR 1=1 --"))
            ->assertOk()
            ->assertDontSee('Safe Product');
    }

    public function test_admin_can_delete_a_product_without_history(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin)->delete("/products/{$product->id}")->assertRedirect('/products');

        $this->assertModelMissing($product);
        $this->assertDatabaseHas('activity_logs', ['action' => 'product.deleted']);
    }

    public function test_staff_cannot_delete_products(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->staff)->delete("/products/{$product->id}")->assertForbidden();

        $this->assertModelExists($product);
    }

    public function test_delete_button_is_hidden_from_staff(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->staff)->get("/products/{$product->id}")->assertOk()->assertDontSee('Delete product');
        $this->actingAs($this->admin)->get("/products/{$product->id}")->assertOk()->assertSee('Delete product');
    }

    public function test_product_with_stock_history_cannot_be_deleted(): void
    {
        $product = Product::factory()->create(['quantity' => 5]);
        StockTransaction::forceCreate([
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 5,
            'balance_after' => 5,
        ]);

        $this->actingAs($this->admin)->delete("/products/{$product->id}")->assertSessionHas('error');

        $this->assertModelExists($product);
    }

    public function test_product_text_is_escaped(): void
    {
        $product = Product::factory()->create([
            'name' => '<b onmouseover=alert(1)>Bold</b>',
            'description' => '<script>steal()</script>',
        ]);

        $this->actingAs($this->staff)->get("/products/{$product->id}")
            ->assertOk()
            ->assertDontSee('<script>steal()</script>', escape: false)
            ->assertDontSee('<b onmouseover=alert(1)>', escape: false);
    }

    public function test_product_pages_load_without_lazy_loading(): void
    {
        // preventLazyLoading() is on in tests, so a missing eager load would throw here.
        Product::factory()->count(3)->for(Supplier::factory())->create();

        $this->actingAs($this->staff)->get('/products')->assertOk();
    }
}
