<?php

namespace App\Http\Requests;

use App\Enums\StockLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filters shared by every report. Reports are administrator-only; the route
 * group checks the same gate, so access is refused even if one check is removed.
 */
class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view-reports');
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', Rule::when($this->filled('from'), 'after_or_equal:from')],
            'category' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'supplier' => ['nullable', 'integer', Rule::exists('suppliers', 'id')],
            'product' => ['nullable', 'integer', Rule::exists('products', 'id')],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'level' => ['nullable', Rule::enum(StockLevel::class)],
            'format' => ['nullable', Rule::in(['csv'])],
        ];
    }

    public function wantsCsv(): bool
    {
        return $this->validated('format') === 'csv';
    }

    public function filters(): array
    {
        return $this->safe()->except('format');
    }
}
