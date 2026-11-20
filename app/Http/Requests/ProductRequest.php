<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for creating and updating products. There is deliberately no
 * quantity field: stock levels only change through stock transactions.
 */
class ProductRequest extends FormRequest
{
    private const MAX_PRICE = 99999999.99;

    public function authorize(): bool
    {
        $product = $this->product();

        return $product
            ? $this->user()->can('update', $product)
            : $this->user()->can('create', Product::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sku' => strtoupper(trim((string) $this->input('sku'))),
            'name' => trim((string) $this->input('name')),
            'is_active' => $this->boolean('is_active'),
            'remove_image' => $this->boolean('remove_image'),
        ]);
    }

    public function rules(): array
    {
        $images = config('inventory.product_images');
        $currentSupplierId = $this->product()?->supplier_id;

        return [
            'name' => ['required', 'string', 'max:150'],
            'sku' => [
                'required',
                'string',
                'max:64',
                'regex:/^[A-Z0-9][A-Z0-9._-]*$/',
                Rule::unique('products', 'sku')->ignore($this->product()),
            ],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            // New links must be to an active supplier; an existing link to a now inactive one may be kept.
            'supplier_id' => [
                'nullable',
                'integer',
                Rule::exists('suppliers', 'id')->where(fn (Builder $query) => $query
                    ->where('is_active', true)
                    ->when($currentSupplierId, fn (Builder $query) => $query->orWhere('id', $currentSupplierId))),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'purchase_price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:'.self::MAX_PRICE],
            'selling_price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:'.self::MAX_PRICE],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['boolean'],
            'image' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:'.$images['max_upload_kb'],
                Rule::dimensions()->minWidth(50)->minHeight(50)
                    ->maxWidth($images['max_input_dimension'])->maxHeight($images['max_input_dimension']),
            ],
            'remove_image' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.regex' => 'Use letters, numbers, dots, dashes and underscores only, starting with a letter or number.',
            'supplier_id.exists' => 'Choose an active supplier.',
        ];
    }

    /**
     * The fields that may be written straight to the product.
     */
    public function productAttributes(): array
    {
        return $this->safe()->except(['image', 'remove_image']);
    }

    private function product(): ?Product
    {
        $product = $this->route('product');

        return $product instanceof Product ? $product : null;
    }
}
