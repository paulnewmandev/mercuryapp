<?php

namespace App\Http\Requests\BankAccounts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_name' => ['sometimes', 'required', 'string', 'max:255'],
            'account_number' => ['sometimes', 'required', 'string', 'max:255'],
            'account_type' => ['sometimes', 'required', 'string', 'max:20'],
            'account_holder_name' => ['sometimes', 'required', 'string', 'max:255'],
            'alias' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'required', Rule::in(['A', 'I', 'T'])],
        ];
    }
}
