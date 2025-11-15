<?php

namespace App\Http\Requests\Customers;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    private const PORTAL_PASSWORD_REGEX = '/^(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $customerType = Str::lower((string) $this->input('customer_type', 'individual'));
        $documentType = Str::upper((string) $this->input('document_type', 'RUC'));
        $documentNumber = strtoupper(trim((string) $this->input('document_number', '')));
        $categoryId = $this->input('category_id');
        $firstName = $this->input('first_name');
        $lastName = $this->input('last_name');
        $businessName = $this->input('business_name');
        $sex = $this->input('sex');
        $birthDate = $this->input('birth_date');
        $email = $this->input('email');
        $phone = $this->input('phone_number');
        $portalPassword = trim((string) $this->input('portal_password', ''));
        $b2bAccess = filter_var($this->input('b2b_access'), FILTER_VALIDATE_BOOLEAN);
        $b2cAccess = filter_var($this->input('b2c_access'), FILTER_VALIDATE_BOOLEAN);

        $this->merge([
            'category_id' => $categoryId !== null && $categoryId !== '' ? trim((string) $categoryId) : null,
            'customer_type' => in_array($customerType, ['individual', 'business'], true) ? $customerType : 'individual',
            'document_type' => $documentType,
            'document_number' => $documentNumber,
            'first_name' => $firstName !== null && $firstName !== '' ? mb_strtoupper($firstName) : null,
            'last_name' => $lastName !== null && $lastName !== '' ? mb_strtoupper($lastName) : null,
            'business_name' => $businessName !== null && $businessName !== '' ? mb_strtoupper($businessName) : null,
            'sex' => $sex !== null && $sex !== '' ? Str::upper(trim($sex)) : null,
            'birth_date' => $birthDate !== null && $birthDate !== '' ? trim($birthDate) : null,
            'email' => $email !== null && $email !== '' ? Str::lower($email) : null,
            'phone_number' => $phone !== null && $phone !== '' ? preg_replace('/\D+/', '', $phone) : null,
            'portal_password' => $portalPassword !== '' ? $portalPassword : null,
            'b2b_access' => $b2bAccess ? 1 : 0,
            'b2c_access' => $b2cAccess ? 1 : 0,
        ]);
    }

    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'category_id' => [
                'required',
                'uuid',
                Rule::exists('customer_categories', 'id')->where(function ($query) use ($companyId): void {
                    $query->where('company_id', $companyId)
                        ->whereNot('status', 'T');
                }),
            ],
            'customer_type' => ['required', Rule::in(['individual', 'business'])],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'sex' => ['nullable', 'string', Rule::in(['MASCULINO', 'FEMENINO', 'OTRO'])],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'document_type' => ['required', Rule::in(['RUC', 'CEDULA', 'PASAPORTE'])],
            'document_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('customers', 'document_number')->where('company_id', $companyId),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->where('company_id', $companyId),
            ],
            'phone_number' => ['nullable', 'regex:/^\d+$/', 'max:50'],
            'address' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::in(['A', 'I', 'T'])],
            'portal_password' => ['nullable', 'string', 'min:8', 'max:100', 'regex:'.self::PORTAL_PASSWORD_REGEX],
            'b2b_access' => ['required', 'boolean'],
            'b2c_access' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $requiresPortal = (bool) $this->boolean('b2b_access') || (bool) $this->boolean('b2c_access');

            if ($requiresPortal && ! $this->input('email')) {
                $validator->errors()->add('email', __('Debes registrar un correo electrónico para acceso al portal.'));
            }

            if ($requiresPortal && ! $this->input('portal_password')) {
                $validator->errors()->add('portal_password', __('Debes definir una clave para el portal.'));
            }
        });
    }
}
