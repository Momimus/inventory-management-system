<?php

namespace App\Http\Controllers;

use App\Enums\StockTransactionType;
use App\Exceptions\StockOperationException;
use App\Http\Requests\StockTransactionRequest;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Records stock movements and shows their history. There are no edit or
 * delete actions: transactions are permanent.
 */
class StockTransactionController extends Controller
{
    public function __construct(private StockService $stock) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', StockTransaction::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::enum(StockTransactionType::class)],
            'product' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', Rule::when($request->filled('from'), 'after_or_equal:from')],
        ]);

        $transactions = StockTransaction::query()
            ->with(['product', 'user', 'supplier'])
            ->search($filters['search'] ?? null)
            ->ofType(StockTransactionType::tryFrom($filters['type'] ?? ''))
            ->when($filters['product'] ?? null, fn ($query, $id) => $query->where('product_id', $id))
            ->betweenDates(
                isset($filters['from']) ? Carbon::parse($filters['from']) : null,
                isset($filters['to']) ? Carbon::parse($filters['to']) : null,
            )
            ->latest()
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $product = isset($filters['product']) ? Product::find($filters['product']) : null;

        return view('stock.index', ['transactions' => $transactions, 'filters' => $filters, 'product' => $product]);
    }

    public function create(Request $request, StockTransactionType $type): View
    {
        $this->authorize('create', [StockTransaction::class, $type]);

        return view('stock.create', [
            'type' => $type,
            'products' => Product::active()->orderBy('name')->get(['id', 'name', 'sku', 'quantity']),
            'suppliers' => Supplier::active()->orderBy('name')->pluck('name', 'id'),
            'selectedProductId' => $request->integer('product') ?: null,
        ]);
    }

    public function store(StockTransactionRequest $request, StockTransactionType $type): RedirectResponse
    {
        $data = $request->validated();
        $product = Product::findOrFail($data['product_id']);
        $user = $request->user();

        try {
            $transaction = match ($type) {
                StockTransactionType::StockIn => $this->stock->stockIn(
                    $product,
                    $data['quantity'],
                    $user,
                    isset($data['supplier_id']) ? Supplier::find($data['supplier_id']) : null,
                    $data['reference'],
                    $data['note'] ?? null,
                ),
                StockTransactionType::StockOut => $this->stock->stockOut(
                    $product,
                    $data['quantity'],
                    $user,
                    $data['reference'],
                    $data['note'] ?? null,
                ),
                StockTransactionType::Adjustment => $this->stock->adjust(
                    $product,
                    $data['counted_quantity'],
                    $user,
                    $data['note'],
                    $data['reference'],
                ),
            };
        } catch (StockOperationException $exception) {
            $field = $type === StockTransactionType::Adjustment ? 'counted_quantity' : 'quantity';

            return back()->withInput()->withErrors([$field => $exception->getMessage()]);
        }

        return redirect()
            ->route('products.show', $product)
            ->with('success', sprintf(
                '%s recorded: %+d %s. New quantity: %d.',
                $type->label(),
                $transaction->quantity,
                $product->sku,
                $transaction->balance_after,
            ));
    }

    public function show(StockTransaction $transaction): View
    {
        $this->authorize('view', $transaction);

        $transaction->load(['product', 'user', 'supplier']);

        return view('stock.show', ['transaction' => $transaction]);
    }
}
