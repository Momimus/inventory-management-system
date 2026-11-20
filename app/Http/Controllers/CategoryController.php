<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Http\Requests\DeleteCategoryRequest;
use App\Models\Category;
use App\Services\ActivityLogger;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private ActivityLogger $activity) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Category::class);

        $search = $request->validate(['search' => ['nullable', 'string', 'max:100']])['search'] ?? null;

        $categories = Category::query()
            ->search($search)
            ->withCount('products')
            ->withSum('products', 'quantity')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('categories.index', ['categories' => $categories, 'search' => $search]);
    }

    public function create(): View
    {
        $this->authorize('create', Category::class);

        return view('categories.create', ['category' => new Category]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = Category::create($request->validated());

        $this->activity->log('category.created', "Created category {$category->name}", $category);

        return redirect()->route('categories.index')->with('success', "Category {$category->name} created.");
    }

    public function show(Category $category): View
    {
        $this->authorize('view', $category);

        $products = $category->products()->orderBy('name')->paginate(15);

        $otherCategories = Category::whereKeyNot($category->id)->orderBy('name')->pluck('name', 'id');

        return view('categories.show', [
            'category' => $category,
            'products' => $products,
            'otherCategories' => $otherCategories,
        ]);
    }

    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        return view('categories.edit', ['category' => $category]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        if ($category->wasChanged()) {
            $this->activity->log('category.updated', "Updated category {$category->name}", $category, $this->activity->changesOf($category));
        }

        return redirect()->route('categories.show', $category)->with('success', 'Category updated.');
    }

    public function destroy(DeleteCategoryRequest $request, Category $category): RedirectResponse
    {
        $targetId = $request->validated('reassign_to');

        try {
            $moved = DB::transaction(function () use ($category, $targetId) {
                $moved = $targetId ? $category->products()->update(['category_id' => $targetId]) : 0;
                $category->delete();

                return $moved;
            });
        } catch (QueryException) {
            // The products foreign key refuses the delete if a product was added in the meantime.
            return back()->with('error', 'The category could not be deleted because products still belong to it.');
        }

        $this->activity->log('category.deleted', "Deleted category {$category->name}", $category, [
            'products_moved' => $moved,
            'moved_to_category_id' => $targetId,
        ]);

        return redirect()->route('categories.index')->with('success', "Category {$category->name} deleted.");
    }
}
