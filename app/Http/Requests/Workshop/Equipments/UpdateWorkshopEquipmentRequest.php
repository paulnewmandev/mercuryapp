<?php

namespace App\Http\Requests\Workshop\Equipments;

use App\Models\WorkshopEquipment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateWorkshopEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        $identifier = $this->input('identifier');

        $this->merge([
            'identifier' => $identifier !== null ? trim($identifier) : null,
            'brand_id' => $this->input('brand_id'),
            'model_id' => $this->input('model_id'),
        ]);
    }

    public function rules(): array
    {
        $companyId = Auth::user()?->company_id;
        $equipment = $this->route('workshop_equipment');
        $equipmentId = $equipment instanceof WorkshopEquipment ? $equipment->getKey() : $equipment;
        $brandId = $this->input('brand_id');
        $modelId = $this->input('model_id');

        return [
            'brand_id' => [
                'required',
                'uuid',
                Rule::exists('workshop_brands', 'id')->where(function ($query) use ($companyId): void {
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                }),
            ],
            'model_id' => [
                'required',
                'uuid',
                Rule::exists('workshop_models', 'id')->where(function ($query) use ($companyId, $brandId): void {
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                    if ($brandId) {
                        $query->where('brand_id', $brandId);
                    }
                }),
            ],
            'identifier' => [
                'required',
                'string',
                'max:255',
                Rule::unique('workshop_equipments', 'identifier')
                    ->ignore($equipmentId)
                    ->where(function ($query) use ($companyId): void {
                        if ($companyId) {
                            $query->where('company_id', $companyId);
                        }
                    }),
            ],
            'note' => ['nullable', 'string', 'max:2000'],
            'status' => ['sometimes', 'required', Rule::in(['A', 'I', 'T'])],
        ];
    }
}
