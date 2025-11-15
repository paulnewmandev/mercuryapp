<?php

namespace App\Http\Requests\Workshop\Categories;

use App\Models\WorkshopCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateWorkshopCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('name');

        $this->merge([
            'name' => $name !== null ? trim($name) : null,
        ]);
    }

    public function rules(): array
    {
        $companyId = Auth::user()?->company_id;
        $category = $this->route('workshop_category');
        $categoryId = $category instanceof WorkshopCategory ? $category->getKey() : $category;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('workshop_categories', 'name')
                    ->ignore($categoryId)
                    ->where(function ($query) use ($companyId): void {
                        if ($companyId) {
                            $query->where('company_id', $companyId);
                        }
                    }),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['sometimes', 'required', Rule::in(['A', 'I', 'T'])],
        ];
    }
}


