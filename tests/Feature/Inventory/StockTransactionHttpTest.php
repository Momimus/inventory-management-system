<?php

namespace Tests\Feature\Inventory;

use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransactionHttpTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $staff;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->staff = User::factory()->create();
        $this->product = Product::factory()->create(['quantity' => 20]);
    }

    public function test_stock_forms_render(): void
    {
        $this->actingAs($this->staff)->get('/stock/in/create')->assertOk()->assertSee($this->product->name);
        $this->actingAs($this->staff)->get("/stock/out/create?product={$this->product->id}")->assertOk();
        $this->actingAs($this->admin)->get('/stock/adjustment/create')->assertOk()->assertSee('Reason for adjustment');
    }

    public function test_staff_can_record_stock_in_with_a_supplier(): void
    {
        $supplier = Supplier::factory()->create();

        $this->actingAs($this->staff)->post('/stock/in', [
            'product_id' => $this->product->id,
            'quantity' => 30,
            'supplier_id' => $supplier->id,
            'reference' => ' INV-2024-01 ',
        ])->assertRedirect("/products/{$this->product->id}")->assertSessionHas('success');

        $this->assertSame(50, $this->product->fresh()->quantity);
        $transaction = StockTransaction::sole();
        $this->assertSame('INV-2024-01', $transaction->reference);
        $this->assertSame($this->staff->id, $transaction->user_id);
    }

    public function test_staff_can_record_stock_out(): void
    {
        $this->actingAs($this->staff)->post('/stock/out', ['product_id' => $this->product->id, 'quantity' => 5])
            ->assertSessionHasNoErrors();

        $this->assertSame(15, $this->product->fresh()->quantity);
    }

    public function test_stock_out_beyond_available_quantity_shows_an_error(): void
    {
        $this->actingAs($this->staff)
            ->from('/stock/out/create')
            ->post('/stock/out', ['product_id' => $this->product->id, 'quantity' => 21])
            ->assertRedirect('/stock/out/create')
            ->assertSessionHasErrors(['quantity' => 'Only 20 in stock; cannot remove 21.']);

        $this->assertSame(20, $this->product->fresh()->quantity);
        $this->assertSame(0, StockTransaction::count());
    }

    public function test_invalid_quantities_are_rejected(): void
    {
        foreach ([0, -5, 1.5, 'ten', '', 1000001] as $invalid) {
            $this->actingAs($this->staff)
                ->post('/stock/in', ['product_id' => $this->product->id, 'quantity' => $invalid])
                ->assertSessionHasErrors('quantity');
        }

        $this->assertSame(20, $this->product->fresh()->quantity);
    }

    public function test_inactive_or_missing_products_are_rejected(): void
    {
        $inactive = Product::factory()->inactive()->create();

        $this->actingAs($this->staff)->post('/stock/in', ['product_id' => $inactive->id, 'quantity' => 1])
            ->assertSessionHasErrors('product_id');
        $this->actingAs($this->staff)->post('/stock/in', ['product_id' => 99999, 'quantity' => 1])
            ->assertSessionHasErrors('product_id');
    }

    public function test_the_recording_user_cannot_be_spoofed(): void
    {
        $this->actingAs($this->staff)->post('/stock/in', [
            'product_id' => $this->product->id,
            'quantity' => 1,
            'user_id' => $this->admin->id,
            'balance_after' => 9999,
        ]);

        $transaction = StockTransaction::sole();
        $this->assertSame($this->staff->id, $transaction->user_id);
        $this->assertSame(21, $transaction->balance_after);
    }

    public function test_staff_cannot_make_adjustments(): void
    {
        $this->actingAs($this->staff)->get('/stock/adjustment/create')->assertForbidden();
        $this->actingAs($this->staff)->post('/stock/adjustment', [
            'product_id' => $this->product->id,
            'counted_quantity' => 0,
            'note' => 'Wiping out stock',
        ])->assertForbidden();

        $this->assertSame(20, $this->product->fresh()->quantity);
    }

    public function test_admin_adjustment_requires_a_reason(): void
    {
        $this->actingAs($this->admin)
            ->post('/stock/adjustment', ['product_id' => $this->product->id, 'counted_quantity' => 18])
            ->assertSessionHasErrors('note');

        $this->actingAs($this->admin)
            ->post('/stock/adjustment', ['product_id' => $this->product->id, 'counted_quantity' => 18, 'note' => 'Stocktake'])
            ->assertSessionHasNoErrors();

        $this->assertSame(18, $this->product->fresh()->quantity);
        $this->assertSame(-2, StockTransaction::sole()->quantity);
    }

    public function test_unknown_transaction_types_return_404(): void
    {
        $this->actingAs($this->admin)->get('/stock/steal/create')->assertNotFound();
        $this->actingAs($this->admin)->post('/stock/steal', ['product_id' => $this->product->id])->assertNotFound();
    }

    public function test_there_is_no_way_to_edit_or_delete_a_transaction_over_http(): void
    {
        $transaction = app(StockService::class)->stockIn($this->product, 5, $this->admin);

        $this->actingAs($this->admin)->put("/stock/transactions/{$transaction->id}", ['quantity' => 1])->assertMethodNotAllowed();
        $this->actingAs($this->admin)->patch("/stock/transactions/{$transaction->id}", ['quantity' => 1])->assertMethodNotAllowed();
        $this->actingAs($this->admin)->delete("/stock/transactions/{$transaction->id}")->assertMethodNotAllowed();
        $this->actingAs($this->admin)->get("/stock/transactions/{$transaction->id}/edit")->assertNotFound();

        $this->assertSame(5, $transaction->fresh()->quantity);
    }

    public function test_history_can_be_filtered_by_type_product_and_date(): void
    {
        $service = app(StockService::class);
        $other = Product::factory()->create(['quantity' => 10, 'name' => 'Other Product']);

        $this->travelTo(now()->subDays(10));
        $old = $service->stockIn($this->product, 1, $this->staff, reference: 'OLD-REF');
        $this->travelBack();
        $in = $service->stockIn($this->product, 2, $this->staff, reference: 'NEW-IN');
        $out = $service->stockOut($other, 3, $this->staff, reference: 'NEW-OUT');

        $this->actingAs($this->staff)->get('/stock?type=out')
            ->assertOk()->assertSee('NEW-OUT')->assertDontSee('NEW-IN');

        $this->actingAs($this->staff)->get("/stock?product={$this->product->id}")
            ->assertSee('NEW-IN')->assertSee('OLD-REF')->assertDontSee('NEW-OUT');

        $this->actingAs($this->staff)->get('/stock?from='.now()->subDays(2)->toDateString())
            ->assertSee('NEW-IN')->assertDontSee('OLD-REF');

        $this->actingAs($this->staff)->get('/stock?to='.now()->subDays(5)->toDateString())
            ->assertSee('OLD-REF')->assertDontSee('NEW-IN');

        $this->actingAs($this->staff)->get('/stock?search=NEW-')
            ->assertSee('NEW-IN')->assertSee('NEW-OUT')->assertDontSee('OLD-REF');

        $this->actingAs($this->staff)->get("/stock/transactions/{$out->id}")->assertOk()->assertSee('NEW-OUT');
        $this->assertNotNull($old->id);
        $this->assertNotNull($in->id);
    }

    public function test_end_date_before_start_date_is_rejected(): void
    {
        $this->actingAs($this->staff)->get('/stock?from=2026-05-10&to=2026-05-01')->assertSessionHasErrors('to');
    }

    public function test_product_page_shows_recent_movements(): void
    {
        app(StockService::class)->stockIn($this->product, 5, $this->staff, reference: 'SHOWN-REF');

        $this->actingAs($this->staff)->get("/products/{$this->product->id}")->assertOk()->assertSee('SHOWN-REF');
    }
}
