<?php

namespace App\Http\Requests\Workshop\Models;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreWorkshopModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('name');

        $this->merge([
            'name' => $name !== null ? trim($name) : null,
            'brand_id' => $this->input('brand_id'),
        ]);
    }

    public function rules(): array
    {
        $companyId = Auth::user()?->company_id;
        $brandId = $this->input('brand_id');

        return [
            'brand_id' => [
                'required',
                'uuid',
                Rule::exists('workshop_brands', 'id')->where(function ($query) use ($companyId): void {
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                }),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('workshop_models', 'name')->where(function ($query) use ($companyId, $brandId): void {
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                    if ($brandId) {
                        $query->where('brand_id', $brandId);
                    }
                }),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['sometimes', 'required', Rule::in(['A', 'I', 'T'])],
        ];
    }

    public function messages(): array
    {
        return [
            'brand_id.required' => gettext('Debes seleccionar una marca.'),
            'brand_id.uuid' => gettext('La marca seleccionada no es válida.'),
            'brand_id.exists' => gettext('La marca seleccionada no es válida.'),
            'name.required' => gettext('El nombre es obligatorio.'),
        ];
    }
}
