<?php

namespace App\Http\Requests\Workshop\States;

use App\Models\WorkshopState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateWorkshopStateRequest extends FormRequest
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
            'category_id' => $this->input('category_id'),
        ]);
    }

    public function rules(): array
    {
        $companyId = Auth::user()?->company_id;
        $state = $this->route('workshop_state');
        $stateId = $state instanceof WorkshopState ? $state->getKey() : $state;
        $categoryId = $this->input('category_id');

        return [
            'category_id' => [
                'required',
                'uuid',
                Rule::exists('workshop_categories', 'id')->where(function ($query) use ($companyId): void {
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                }),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('workshop_states', 'name')
                    ->ignore($stateId)
                    ->where(function ($query) use ($companyId, $categoryId): void {
                        if ($companyId) {
                            $query->where('company_id', $companyId);
                        }
                        if ($categoryId) {
                            $query->where('category_id', $categoryId);
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
            'category_id.required' => gettext('Debes seleccionar una categoría.'),
            'category_id.uuid' => gettext('La categoría seleccionada no es válida.'),
            'category_id.exists' => gettext('La categoría seleccionada no es válida.'),
            'name.required' => gettext('El nombre es obligatorio.'),
        ];
    }
}


