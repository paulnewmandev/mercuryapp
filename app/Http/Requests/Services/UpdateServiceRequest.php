<?php

namespace App\Http\Requests\Services;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'required', 'uuid', Rule::exists('service_categories', 'id')],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:5'],
            'status' => ['sometimes', 'required', Rule::in(['A', 'I', 'T'])],
        ];
    }

    public function prepareForValidation(): void
    {
        if ($this->has('currency')) {
            $this->merge([
                'currency' => $this->currency ?? 'USD',
            ]);
        }
        if ($this->has('price')) {
            $this->merge([
                'price' => $this->price ?? 0,
            ]);
        }
    }
}
