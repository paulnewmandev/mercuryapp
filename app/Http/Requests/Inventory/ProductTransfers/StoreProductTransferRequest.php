<?php

namespace App\Http\Requests\Inventory\ProductTransfers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreProductTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('reference')) {
            $this->merge([
                'reference' => trim((string) $this->input('reference')),
            ]);
        }

        if ($this->filled('notes')) {
            $this->merge([
                'notes' => trim((string) $this->input('notes')),
            ]);
        }

        $items = $this->input('items');

        if (is_array($items)) {
            $normalizedItems = collect($items)
                ->map(function ($item) {
                    return [
                        'product_id' => $item['product_id'] ?? null,
                        'quantity' => isset($item['quantity']) ? (int) $item['quantity'] : null,
                        'notes' => isset($item['notes']) ? trim((string) $item['notes']) : null,
                    ];
                })
                ->filter(fn ($item) => $item['product_id'] !== null);

            $this->merge([
                'items' => $normalizedItems->values()->all(),
            ]);
        }
    }

    public function rules(): array
    {
        $companyId = Auth::user()?->company_id;

        return [
            'movement_date' => ['required', 'date'],
            'origin_warehouse_id' => [
                'required',
                'uuid',
                Rule::exists('warehouses', 'id')->where(function ($query) use ($companyId) {
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                }),
                'different:destination_warehouse_id',
            ],
            'destination_warehouse_id' => [
                'required',
                'uuid',
                Rule::exists('warehouses', 'id')->where(function ($query) use ($companyId) {
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                }),
            ],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'uuid',
                Rule::exists('products', 'id')->where(function ($query) use ($companyId) {
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                }),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function passedValidation(): void
    {
        $items = collect($this->input('items', []));

        $duplicates = $items
            ->groupBy('product_id')
            ->filter(fn ($group) => $group->count() > 1)
            ->keys()
            ->all();

        if (! empty($duplicates)) {
            throw ValidationException::withMessages([
                'items' => gettext('No se pueden repetir productos dentro del movimiento.'),
            ]);
        }
    }
}

