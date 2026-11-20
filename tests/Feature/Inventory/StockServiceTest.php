<?php

namespace Tests\Feature\Inventory;

use App\Enums\StockTransactionType;
use App\Exceptions\StockOperationException;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $stock;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stock = app(StockService::class);
        $this->user = User::factory()->create();
    }

    public function test_stock_in_increases_quantity_and_records_the_movement(): void
    {
        $product = Product::factory()->create(['quantity' => 10]);
        $supplier = Supplier::factory()->create();

        $transaction = $this->stock->stockIn($product, 25, $this->user, $supplier, 'DN-1001', 'Weekly delivery');

        $this->assertSame(35, $product->fresh()->quantity);
        $this->assertSame(35, $product->quantity, 'The caller\'s model is kept in sync.');
        $this->assertSame(StockTransactionType::StockIn, $transaction->type);
        $this->assertSame(25, $transaction->quantity);
        $this->assertSame(35, $transaction->balance_after);
        $this->assertSame($this->user->id, $transaction->user_id);
        $this->assertSame($supplier->id, $transaction->supplier_id);
        $this->assertSame('DN-1001', $transaction->reference);
        $this->assertDatabaseHas('activity_logs', ['action' => 'stock.in', 'subject_id' => $transaction->id]);
    }

    public function test_stock_out_decreases_quantity(): void
    {
        $product = Product::factory()->create(['quantity' => 10]);

        $transaction = $this->stock->stockOut($product, 4, $this->user, 'SO-55');

        $this->assertSame(6, $product->fresh()->quantity);
        $this->assertSame(-4, $transaction->quantity);
        $this->assertSame(6, $transaction->balance_after);
    }

    public function test_stock_out_can_take_the_last_unit(): void
    {
        $product = Product::factory()->create(['quantity' => 3]);

        $this->stock->stockOut($product, 3, $this->user);

        $this->assertSame(0, $product->fresh()->quantity);
    }

    public function test_stock_out_cannot_make_stock_negative(): void
    {
        $product = Product::factory()->create(['quantity' => 5]);

        try {
            $this->stock->stockOut($product, 6, $this->user);
            $this->fail('Expected the stock-out to be refused.');
        } catch (StockOperationException $exception) {
            $this->assertSame('Only 5 in stock; cannot remove 6.', $exception->getMessage());
        }

        $this->assertSame(5, $product->fresh()->quantity);
        $this->assertSame(0, StockTransaction::count());
        $this->assertSame(0, ActivityLog::where('action', 'like', 'stock.%')->count());
    }

    public function test_adjustment_sets_the_counted_quantity_and_records_the_difference(): void
    {
        $product = Product::factory()->create(['quantity' => 40]);

        $down = $this->stock->adjust($product, 37, $this->user, 'Cycle count: 3 damaged');
        $up = $this->stock->adjust($product, 39, $this->user, 'Found 2 in back room');

        $this->assertSame(-3, $down->quantity);
        $this->assertSame(37, $down->balance_after);
        $this->assertSame(2, $up->quantity);
        $this->assertSame(39, $product->fresh()->quantity);
        $this->assertSame('Cycle count: 3 damaged', $down->note);
    }

    public function test_adjustment_to_the_same_quantity_is_refused(): void
    {
        $product = Product::factory()->create(['quantity' => 8]);

        $this->expectException(StockOperationException::class);

        $this->stock->adjust($product, 8, $this->user, 'No change');
    }

    public function test_inactive_products_cannot_move_stock(): void
    {
        $product = Product::factory()->inactive()->create(['quantity' => 8]);

        $this->expectException(StockOperationException::class);

        $this->stock->stockIn($product, 1, $this->user);
    }

    public function test_quantity_update_is_rolled_back_when_recording_the_transaction_fails(): void
    {
        $product = Product::factory()->create(['quantity' => 10]);

        StockTransaction::creating(function () {
            throw new RuntimeException('Simulated database failure');
        });

        try {
            $this->stock->stockIn($product, 5, $this->user);
            $this->fail('Expected the simulated failure to be thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated database failure', $exception->getMessage());
        }

        $this->assertSame(10, $product->fresh()->quantity);
        $this->assertSame(0, StockTransaction::count());
        $this->assertSame(0, ActivityLog::count());
    }

    public function test_quantities_are_read_from_the_database_not_from_a_stale_model(): void
    {
        $product = Product::factory()->create(['quantity' => 10]);
        $staleCopy = Product::find($product->id);

        // Another request sells 8 units after this copy was loaded.
        $this->stock->stockOut($product, 8, $this->user);
        $this->assertSame(10, $staleCopy->quantity);

        try {
            $this->stock->stockOut($staleCopy, 5, $this->user);
            $this->fail('A stale copy must not allow selling stock that is gone.');
        } catch (StockOperationException) {
            // expected: only 2 are left
        }

        $transaction = $this->stock->stockIn($staleCopy, 3, $this->user);

        $this->assertSame(5, $transaction->balance_after);
        $this->assertSame(5, $product->fresh()->quantity);
    }

    public function test_balances_form_an_unbroken_chain(): void
    {
        $product = Product::factory()->create(['quantity' => 0]);

        $this->stock->stockIn($product, 50, $this->user);
        $this->stock->stockOut($product, 12, $this->user);
        $this->stock->adjust($product, 35, $this->user, 'Count');
        $this->stock->stockOut($product, 35, $this->user);
        $this->stock->stockIn($product, 7, $this->user);

        $balance = 0;
        foreach ($product->stockTransactions()->orderBy('id')->get() as $transaction) {
            $balance += $transaction->quantity;
            $this->assertSame($balance, $transaction->balance_after);
        }

        $this->assertSame($balance, $product->fresh()->quantity);
        $this->assertSame(7, $balance);
    }

    public function test_product_edits_do_not_overwrite_stock_changed_in_the_meantime(): void
    {
        $product = Product::factory()->create(['quantity' => 10]);
        $formCopy = Product::find($product->id);

        $this->stock->stockOut($product, 4, $this->user);

        // Saving the older copy only writes the attributes that changed on it.
        $formCopy->update(['name' => 'Renamed while stock moved']);

        $this->assertSame(6, $product->fresh()->quantity);
    }

    public function test_transactions_cannot_be_modified_or_deleted(): void
    {
        $product = Product::factory()->create(['quantity' => 10]);
        $transaction = $this->stock->stockIn($product, 5, $this->user);

        try {
            $transaction->update(['quantity' => 500]);
            $this->fail('Updating a transaction must be refused.');
        } catch (LogicException) {
        }

        try {
            $transaction->delete();
            $this->fail('Deleting a transaction must be refused.');
        } catch (LogicException) {
        }

        $this->assertSame(5, $transaction->fresh()->quantity);
    }

    public function test_activity_log_entries_cannot_be_modified(): void
    {
        $product = Product::factory()->create(['quantity' => 10]);
        $this->stock->stockIn($product, 5, $this->user);

        $this->expectException(LogicException::class);

        ActivityLog::first()->update(['description' => 'Tampered']);
    }
}
