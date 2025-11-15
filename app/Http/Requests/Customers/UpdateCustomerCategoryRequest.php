<?php

namespace App\Http\Requests\Customers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateCustomerCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('name');

        $this->merge([
            'name' => $name !== null ? trim($name) : null,
            'slug' => $name ? Str::slug($name) : null,
        ]);
    }

    public function rules(): array
    {
        $companyId = Auth::user()->company_id;
        $categoryId = $this->route('category')?->id ?? $this->route('customer_category');

        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => [
                'required',
                'string',
                'max:160',
                Rule::unique('customer_categories')->where(function ($query) use ($companyId): void {
                    $query->where('company_id', $companyId);
                })->ignore($categoryId),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(['A', 'I', 'T'])],
        ];
    }
}

