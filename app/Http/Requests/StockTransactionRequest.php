<?php

namespace App\Http\Requests;

use App\Enums\StockTransactionType;
use App\Models\StockTransaction;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The person recording the movement is always the signed-in user; any user_id
 * sent with the form is ignored because it is not in these rules.
 */
class StockTransactionRequest extends FormRequest
{
    public const MAX_QUANTITY = 1000000;

    public function authorize(): bool
    {
        return $this->user()->can('create', [StockTransaction::class, $this->type()]);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reference' => $this->filled('reference') ? trim($this->input('reference')) : null,
        ]);
    }

    public function rules(): array
    {
        $activeProduct = Rule::exists('products', 'id')->where(fn (Builder $query) => $query->where('is_active', true));

        return match ($this->type()) {
            StockTransactionType::StockIn => [
                'product_id' => ['required', 'integer', $activeProduct],
                'quantity' => ['required', 'integer', 'min:1', 'max:'.self::MAX_QUANTITY],
                'supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')->where('is_active', true)],
                'reference' => ['nullable', 'string', 'max:100'],
                'note' => ['nullable', 'string', 'max:500'],
            ],
            StockTransactionType::StockOut => [
                'product_id' => ['required', 'integer', $activeProduct],
                'quantity' => ['required', 'integer', 'min:1', 'max:'.self::MAX_QUANTITY],
                'reference' => ['nullable', 'string', 'max:100'],
                'note' => ['nullable', 'string', 'max:500'],
            ],
            StockTransactionType::Adjustment => [
                'product_id' => ['required', 'integer', $activeProduct],
                'counted_quantity' => ['required', 'integer', 'min:0', 'max:'.self::MAX_QUANTITY],
                'reference' => ['nullable', 'string', 'max:100'],
                'note' => ['required', 'string', 'min:3', 'max:500'],
            ],
        };
    }

    public function messages(): array
    {
        return [
            'product_id.exists' => 'Choose an active product.',
            'supplier_id.exists' => 'Choose an active supplier.',
            'note.required' => 'Explain why the stock is being adjusted.',
        ];
    }

    public function attributes(): array
    {
        return ['product_id' => 'product', 'supplier_id' => 'supplier', 'counted_quantity' => 'counted quantity'];
    }

    public function type(): StockTransactionType
    {
        return $this->route('type');
    }
}
