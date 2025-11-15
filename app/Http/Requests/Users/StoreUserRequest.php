<?php

namespace App\Http\Requests\Users;

use App\Support\Tenant\CurrentCompany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => $this->normalizeName($this->input('first_name')),
            'last_name' => $this->normalizeName($this->input('last_name')),
            'email' => $this->normalizeEmail($this->input('email')),
            'document_number' => $this->normalizeDocument($this->input('document_number')),
            'phone_number' => $this->normalizePhone($this->input('phone_number')),
            'status' => $this->input('status', 'A'),
        ]);
    }

    public function rules(): array
    {
        $companyId = app(CurrentCompany::class)->id();

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role_id' => [
                'required',
                Rule::exists('roles', 'id')->when($companyId, fn ($query) => $query->where('company_id', $companyId)),
            ],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers()->symbols()],
            'password_confirmation' => ['required', 'same:password'],
            'document_number' => ['nullable', 'string', 'max:50'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['A', 'I', 'T'])],
        ];
    }

    private function normalizeName(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return mb_strtoupper(preg_replace('/\s+/', ' ', trim($value)));
    }

    private function normalizeEmail(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return mb_strtolower(trim($value));
    }

    private function normalizeDocument(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return preg_replace('/\s+/', '', strtoupper($value));
    }

    private function normalizePhone(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return preg_replace('/\D+/', '', $value);
    }
}

