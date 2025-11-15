<?php

namespace App\Http\Requests\Workshop\Accessories;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreWorkshopAccessoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->input('name') ? trim((string) $this->input('name')) : null,
        ]);
    }

    public function rules(): array
    {
        $companyId = Auth::user()?->company_id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('workshop_accessories', 'name')->where(function ($query) use ($companyId): void {
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => gettext('Debes ingresar el nombre del accesorio.'),
            'name.unique' => gettext('Ya existe un accesorio con ese nombre.'),
        ];
    }
}
