<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A category that still has products can only be deleted if those products are
 * moved to another category in the same request.
 */
class DeleteCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete', $this->route('category'));
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'reassign_to' => [
                Rule::requiredIf(fn () => $category->products()->exists()),
                'nullable',
                'integer',
                Rule::exists('categories', 'id'),
                Rule::notIn([$category->id]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reassign_to.required' => 'This category still has products. Choose a category to move them to.',
            'reassign_to.not_in' => 'Choose a different category to move the products to.',
        ];
    }
}
