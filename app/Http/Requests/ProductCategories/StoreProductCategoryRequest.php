<?php

namespace App\Http\Requests\ProductCategories;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'product_line_id' => ['nullable', 'uuid', Rule::exists('product_lines', 'id'), 'required_without:parent_id'],
            'parent_id' => ['nullable', 'uuid', Rule::exists('product_categories', 'id')],
            'status' => ['sometimes', 'required', Rule::in(['A', 'I', 'T'])],
        ];
    }
}
