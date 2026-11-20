<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function __construct(private ActivityLogger $activity) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Supplier::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $suppliers = Supplier::query()
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('is_active', $status === 'active'))
            ->withCount('products')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('suppliers.index', ['suppliers' => $suppliers, 'filters' => $filters]);
    }

    public function create(): View
    {
        $this->authorize('create', Supplier::class);

        return view('suppliers.create', ['supplier' => new Supplier(['is_active' => true])]);
    }

    public function store(SupplierRequest $request): RedirectResponse
    {
        $supplier = Supplier::create($request->validated());

        $this->activity->log('supplier.created', "Created supplier {$supplier->name}", $supplier);

        return redirect()->route('suppliers.show', $supplier)->with('success', "Supplier {$supplier->name} created.");
    }

    public function show(Supplier $supplier): View
    {
        $this->authorize('view', $supplier);

        return view('suppliers.show', [
            'supplier' => $supplier,
            'products' => $supplier->products()->with('category')->orderBy('name')->paginate(15),
            'canBeDeleted' => ! $supplier->isInUse(),
        ]);
    }

    public function edit(Supplier $supplier): View
    {
        $this->authorize('update', $supplier);

        return view('suppliers.edit', ['supplier' => $supplier]);
    }

    public function update(SupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        if ($supplier->wasChanged()) {
            $this->activity->log('supplier.updated', "Updated supplier {$supplier->name}", $supplier, $this->activity->changesOf($supplier));
        }

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->authorize('delete', $supplier);

        if ($supplier->isInUse()) {
            return back()->with('error', 'This supplier is linked to products or stock records. Deactivate it instead.');
        }

        $supplier->delete();

        $this->activity->log('supplier.deleted', "Deleted supplier {$supplier->name}", $supplier);

        return redirect()->route('suppliers.index')->with('success', "Supplier {$supplier->name} deleted.");
    }
}
