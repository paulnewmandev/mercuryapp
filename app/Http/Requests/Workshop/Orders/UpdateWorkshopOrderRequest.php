<?php

namespace App\Http\Requests\Workshop\Orders;

use App\Models\WorkshopOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateWorkshopOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'note' => $this->input('note') ? trim((string) $this->input('note')) : null,
            'equipment_password' => $this->input('equipment_password') ? trim((string) $this->input('equipment_password')) : null,
            'priority' => $this->input('priority') ? trim((string) $this->input('priority')) : 'Normal',
        ]);
    }

    public function rules(): array
    {
        $companyId = Auth::user()?->company_id;
        /** @var WorkshopOrder|string|null $order */
        $order = $this->route('workshop_order');
        $orderId = $order instanceof WorkshopOrder ? $order->getKey() : $order;
        
        // Si solo se envía state_id (para drag and drop), hacer los demás campos opcionales
        $isStateOnlyUpdate = $this->has('state_id') && $this->input('state_id') && count($this->only(['state_id'])) === 1;

        return [
            'category_id' => [
                $isStateOnlyUpdate ? 'nullable' : 'required',
                'uuid',
                Rule::exists('workshop_categories', 'id')->where(function ($query) use ($companyId): void {
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                }),
            ],
            'state_id' => [
                'nullable',
                'uuid',
                Rule::exists('workshop_states', 'id')->where(function ($query) use ($companyId): void {
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                }),
            ],
            'customer_id' => [
                $isStateOnlyUpdate ? 'nullable' : 'required',
                'uuid',
                Rule::exists('customers', 'id')->where(function ($query) use ($companyId): void {
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                }),
            ],
            'equipment_id' => [
                $isStateOnlyUpdate ? 'nullable' : 'required',
                'uuid',
                Rule::exists('workshop_equipments', 'id')->where(function ($query) use ($companyId): void {
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                }),
            ],
            'responsible_user_id' => [
                $isStateOnlyUpdate ? 'nullable' : 'required',
                'uuid',
                Rule::exists('users', 'id')->where(function ($query) use ($companyId): void {
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                }),
            ],
            'priority' => [$isStateOnlyUpdate ? 'nullable' : 'required', 'string', 'max:50'],
            'note' => ['nullable', 'string'],
            'diagnosis' => [$isStateOnlyUpdate ? 'nullable' : 'required', 'boolean'],
            'warranty' => [$isStateOnlyUpdate ? 'nullable' : 'required', 'boolean'],
            'equipment_password' => ['nullable', 'string', 'max:255'],
            'promised_at' => ['nullable', 'date'],
            'budget_currency' => ['nullable', 'string', 'max:5'],
            'budget_amount' => ['nullable', 'numeric', 'min:0'],
            'advance_currency' => ['nullable', 'string', 'max:5'],
            'advance_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', 'required', Rule::in(['A', 'I', 'T'])],
            'accessories' => ['nullable', 'array'],
            'accessories.*' => ['uuid'],
        ];
    }
}
