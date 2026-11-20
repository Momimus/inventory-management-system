<?php

namespace App\Services;

use App\Enums\StockTransactionType;
use App\Exceptions\StockOperationException;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * The only place where product quantities change.
 *
 * Each operation runs inside a database transaction and locks the product row
 * with SELECT ... FOR UPDATE before reading its quantity. A second request for
 * the same product waits until the first has committed, so two simultaneous
 * stock-outs cannot both see the same starting quantity. The quantity update,
 * the transaction record and the activity log entry are saved together or not
 * at all.
 */
class StockService
{
    public function __construct(private ActivityLogger $activity) {}

    public function stockIn(
        Product $product,
        int $quantity,
        User $user,
        ?Supplier $supplier = null,
        ?string $reference = null,
        ?string $note = null,
    ): StockTransaction {
        return $this->apply(
            $product,
            StockTransactionType::StockIn,
            fn (int $current) => $current + $quantity,
            $user,
            ['supplier_id' => $supplier?->id, 'reference' => $reference, 'note' => $note],
        );
    }

    public function stockOut(
        Product $product,
        int $quantity,
        User $user,
        ?string $reference = null,
        ?string $note = null,
    ): StockTransaction {
        return $this->apply(
            $product,
            StockTransactionType::StockOut,
            function (int $current) use ($quantity) {
                if ($quantity > $current) {
                    throw StockOperationException::insufficientStock($current, $quantity);
                }

                return $current - $quantity;
            },
            $user,
            ['reference' => $reference, 'note' => $note],
        );
    }

    /**
     * Sets stock to a physically counted quantity and records the difference.
     */
    public function adjust(
        Product $product,
        int $countedQuantity,
        User $user,
        string $reason,
        ?string $reference = null,
    ): StockTransaction {
        return $this->apply(
            $product,
            StockTransactionType::Adjustment,
            function (int $current) use ($countedQuantity) {
                if ($countedQuantity === $current) {
                    throw StockOperationException::noChange();
                }

                return $countedQuantity;
            },
            $user,
            ['reference' => $reference, 'note' => $reason],
        );
    }

    /**
     * @param  Closure(int): int  $newQuantity  receives the locked current quantity
     */
    private function apply(
        Product $product,
        StockTransactionType $type,
        Closure $newQuantity,
        User $user,
        array $attributes,
    ): StockTransaction {
        $transaction = DB::transaction(function () use ($product, $type, $newQuantity, $user, $attributes) {
            $locked = Product::query()->whereKey($product->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->is_active) {
                throw StockOperationException::inactiveProduct();
            }

            $before = $locked->quantity;
            $after = $newQuantity($before);

            if ($after < 0) {
                throw StockOperationException::insufficientStock($before, $before - $after);
            }

            $locked->quantity = $after;
            $locked->save();

            $transaction = $locked->stockTransactions()->create([
                ...$attributes,
                'user_id' => $user->id,
                'type' => $type,
                'quantity' => $after - $before,
                'balance_after' => $after,
            ]);

            $this->activity->log(
                "stock.{$type->value}",
                sprintf('%s of %+d for %s (now %d)', $type->label(), $after - $before, $locked->sku, $after),
                $transaction,
                ['product_id' => $locked->id, 'before' => $before, 'after' => $after],
                $user,
            );

            return $transaction;
        }, attempts: 3);

        // Keep the caller's model in step with the database.
        $product->setAttribute('quantity', $transaction->balance_after)->syncOriginalAttribute('quantity');

        return $transaction;
    }
}
