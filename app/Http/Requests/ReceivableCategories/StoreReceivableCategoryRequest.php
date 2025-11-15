<?php

namespace App\Http\Requests\ReceivableCategories;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreReceivableCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = Auth::user()?->company_id;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('receivable_categories', 'code')
                    ->where(static fn ($query) => $companyId ? $query->where('company_id', $companyId) : $query),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::in(['A', 'I', 'T'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => Str::upper((string) $this->input('code')),
            'name' => mb_strtoupper((string) $this->input('name')),
            'status' => $this->input('status', 'A'),
        ]);
    }

    public function messages(): array
    {
        return [
            'code.required' => gettext('El código es obligatorio.'),
            'code.unique' => gettext('El código ya está en uso.'),
            'code.max' => gettext('El código no puede exceder 50 caracteres.'),
            'name.required' => gettext('El nombre es obligatorio.'),
            'name.max' => gettext('El nombre no puede exceder 255 caracteres.'),
            'status.required' => gettext('El estado es obligatorio.'),
            'status.in' => gettext('El estado seleccionado no es válido.'),
        ];
    }
}

