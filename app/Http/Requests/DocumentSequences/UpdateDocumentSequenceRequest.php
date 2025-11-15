<?php

namespace App\Http\Requests\DocumentSequences;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentSequenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $user = auth()->user();
        $companyId = $user?->company_id;
        
        // Si el usuario no tiene compañía (super admin), obtener la primera compañía disponible
        if (! $companyId) {
            $company = \App\Models\Company::query()->first();
            $companyId = $company?->id;
        }
        
        $documentTypes = array_keys(config('document_sequences.types', []));
        $routeSequence = $this->route('documentSequence') ?? $this->route('document_sequence') ?? $this->route('sequence') ?? $this->route('id');
        $sequenceId = is_object($routeSequence) ? ($routeSequence->id ?? null) : $routeSequence;

        return [
            'document_type' => [
                'required',
                'string',
                Rule::in($documentTypes),
                Rule::unique('document_sequences', 'document_type')
                    ->where(fn ($query) => $query->when($companyId, fn ($q) => $q->where('company_id', $companyId)))
                    ->ignore((string) $sequenceId),
            ],
            'establishment_code' => [
                'required',
                'string',
                'min:1',
                'max:5',
                'regex:/^[A-Za-z0-9]+$/',
            ],
            'emission_point_code' => [
                'required',
                'string',
                'min:1',
                'max:5',
                'regex:/^[A-Za-z0-9]+$/',
            ],
            'current_sequence' => [
                'required',
                'integer',
                'min:1',
            ],
            'status' => [
                'nullable',
                'string',
                Rule::in(['A', 'I', 'T']),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'document_type' => gettext('tipo de documento'),
            'establishment_code' => gettext('establecimiento'),
            'emission_point_code' => gettext('punto de emisión'),
            'current_sequence' => gettext('secuencial'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'establishment_code' => strtoupper((string) $this->input('establishment_code')),
            'emission_point_code' => strtoupper((string) $this->input('emission_point_code')),
        ]);
    }
}

