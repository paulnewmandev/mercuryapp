<?php

namespace App\Http\Requests\Accounting\Expenses;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $amount = $this->input('amount');

        if ($amount !== null) {
            $normalized = preg_replace('/[^\d.,\-]/', '', (string) $amount);
            $normalized = str_replace(',', '.', $normalized ?? '');
            $this->merge([
                'amount' => $normalized,
            ]);
        }

        if ($this->filled('concept')) {
            $this->merge([
                'concept' => trim((string) $this->input('concept')),
            ]);
        }

        if ($this->filled('reference')) {
            $this->merge([
                'reference' => trim((string) $this->input('reference')),
            ]);
        }

        if ($this->filled('description')) {
            $this->merge([
                'description' => trim((string) $this->input('description')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'expense_type_id' => ['sometimes', 'uuid'],
            'movement_date' => ['sometimes', 'date'],
            'concept' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['sometimes', 'numeric', 'min:0.01', 'max:999999999.99'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'reference' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['A', 'I', 'T'])],
        ];
    }

    protected function passedValidation(): void
    {
        if ($this->filled('amount')) {
            $amount = (float) $this->input('amount');
            $amountCents = (int) round($amount * 100);

            if ($amountCents <= 0) {
                throw ValidationException::withMessages([
                    'amount' => gettext('El monto debe ser mayor a cero.'),
                ]);
            }

            $this->merge([
                'amount_cents' => $amountCents,
            ]);
        }

        if ($this->filled('currency_code')) {
            $this->merge([
                'currency_code' => strtoupper((string) $this->input('currency_code')),
            ]);
        }
    }
}


