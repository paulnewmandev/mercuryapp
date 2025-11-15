<?php

namespace App\Http\Requests\Workshop\Advances;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateWorkshopOrderAdvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'order_id' => $this->input('order_id') ? trim((string) $this->input('order_id')) : null,
            'currency' => 'USD', // Siempre USD
            'amount' => $this->input('amount') !== null && $this->input('amount') !== '' ? (float) $this->input('amount') : null,
            'payment_date' => $this->input('payment_date') ? trim((string) $this->input('payment_date')) : null,
            'payment_method_id' => $this->input('payment_method_id') ? trim((string) $this->input('payment_method_id')) : null,
            'reference' => $this->input('reference') ? trim((string) $this->input('reference')) : null,
            'notes' => $this->input('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    public function rules(): array
    {
        $companyId = Auth::user()?->company_id;

        return [
            'order_id' => [
                'required',
                'uuid',
                Rule::exists('workshop_orders', 'id')->where(function ($query) use ($companyId): void {
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                }),
            ],
            'currency' => ['sometimes', 'string', 'max:5'], // Siempre USD, no se valida del request
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['required', 'date'],
            'payment_method_id' => [
                'nullable',
                'string',
                Rule::in(['EFECTIVO', 'TRANSFERENCIA', 'CHEQUE']),
            ],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::in(['A', 'I', 'T'])],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => gettext('Debes seleccionar una orden de trabajo.'),
            'order_id.exists' => gettext('La orden de trabajo seleccionada no existe.'),
            'currency.required' => gettext('Debes seleccionar una moneda.'),
            'amount.required' => gettext('Debes ingresar el monto del abono.'),
            'amount.numeric' => gettext('El monto debe ser un número válido.'),
            'amount.min' => gettext('El monto debe ser mayor o igual a cero.'),
            'payment_date.required' => gettext('Debes ingresar la fecha de pago.'),
            'payment_date.date' => gettext('La fecha de pago debe ser una fecha válida.'),
            'payment_method_id.in' => gettext('El método de pago seleccionado no es válido.'),
        ];
    }
}

