<?php

namespace App\Http\Requests\Branches;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form request para validar la creación de nuevas sucursales.
 */
class StoreBranchRequest extends FormRequest
{
    /**
     * Determina si el usuario puede ejecutar la petición.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $user = $this->user();
        // Permitir si el usuario tiene company_id o es super admin (sin company_id)
        return $user !== null;
    }

    /**
     * Reglas de validación.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('branches', 'code')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'website' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'status' => ['required', Rule::in(['A', 'I', 'T'])],
        ];
    }
}

