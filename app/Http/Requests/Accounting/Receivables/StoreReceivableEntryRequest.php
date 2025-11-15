<?php

namespace App\Http\Requests\Accounting\Receivables;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreReceivableEntryRequest extends FormRequest
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
        $companyId = Auth::user()?->company_id;

        return [
            'receivable_category_id' => [
                'required',
                'uuid',
                Rule::exists('receivable_categories', 'id')->where(fn ($query) => $companyId ? $query->where('company_id', $companyId) : $query),
            ],
            'movement_date' => ['required', 'date'],
            'concept' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'reference' => ['nullable', 'string', 'max:100'],
            'is_collected' => ['nullable', 'boolean'],
        ];
    }

    protected function passedValidation(): void
    {
        $amount = (float) $this->input('amount');
        $amountCents = (int) round($amount * 100);

        if ($amountCents <= 0) {
            throw ValidationException::withMessages([
                'amount' => gettext('El monto debe ser mayor a cero.'),
            ]);
        }

        $currency = strtoupper((string) $this->input('currency_code', 'USD'));
        $isCollected = (bool) $this->input('is_collected', false);

        $this->merge([
            'amount_cents' => $amountCents,
            'currency_code' => $currency,
            'is_collected' => $isCollected,
            'collected_at' => $isCollected ? now() : null,
        ]);
    }
}

