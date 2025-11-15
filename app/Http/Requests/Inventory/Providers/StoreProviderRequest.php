<?php

namespace App\Http\Requests\Inventory\Providers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = Auth::user()?->company_id;

        return [
            'provider_type' => ['required', 'in:individual,business'],
            'identification_type' => ['required', 'in:RUC,CEDULA,PASAPORTE'],
            'identification_number' => [
                'required',
                'string',
                'max:30',
                Rule::unique('providers', 'identification_number')
                    ->where(static fn ($query) => $companyId ? $query->where('company_id', $companyId) : $query),
            ],
            'first_name' => ['required_if:provider_type,individual', 'nullable', 'string', 'max:120'],
            'last_name' => ['required_if:provider_type,individual', 'nullable', 'string', 'max:120'],
            'business_name' => ['required_if:provider_type,business', 'nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:150', 'email'],
            'phone_number' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+\-\s()]+$/'],
            'status' => ['nullable', 'in:A,I,T'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $providerType = Str::of((string) $this->input('provider_type'))->lower()->trim()->value();
        $identificationType = Str::upper((string) $this->input('identification_type'));
        $identificationNumber = Str::upper(Str::replace(' ', '', (string) $this->input('identification_number')));
        $firstName = $this->input('first_name');
        $lastName = $this->input('last_name');
        $businessName = $this->input('business_name');
        $email = Str::lower((string) $this->input('email'));
        $phone = preg_replace('/[^0-9+\-()\s]/', '', (string) $this->input('phone_number'));

        $this->merge([
            'provider_type' => $providerType ?: null,
            'identification_type' => $identificationType ?: null,
            'identification_number' => $identificationNumber ?: null,
            'first_name' => $firstName ? mb_strtoupper(trim($firstName)) : null,
            'last_name' => $lastName ? mb_strtoupper(trim($lastName)) : null,
            'business_name' => $businessName ? mb_strtoupper(trim($businessName)) : null,
            'email' => $email ?: null,
            'phone_number' => $phone ?: null,
            'status' => $this->input('status', 'A'),
        ]);
    }
}

