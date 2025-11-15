<?php

namespace App\Http\Requests\Inventory\Products;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $companyId = auth()->user()?->company_id;
        $product = $this->route('product');
        $productId = $product instanceof Product ? $product->getKey() : $product;
        $priceListTable = 'price_lists';
        $productTable = (new Product())->getTable();

        return [
            'product_line_id' => [
                'nullable',
                'uuid',
                Rule::exists('product_lines', 'id')->where(function ($query) use ($companyId) {
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                }),
            ],
            'category_id' => [
                'nullable',
                'uuid',
                Rule::exists('product_categories', 'id')->where(function ($query) use ($companyId) {
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                }),
            ],
            'subcategory_id' => [
                'nullable',
                'uuid',
                Rule::exists('product_categories', 'id')->where(function ($query) use ($companyId) {
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                }),
            ],
            'warehouse_id' => [
                'required',
                'uuid',
                Rule::exists('warehouses', 'id')->where(function ($query) use ($companyId) {
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                }),
            ],
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique($productTable, 'sku')
                    ->ignore($productId)
                    ->where(function ($query) use ($companyId) {
                        if ($companyId) {
                            $query->where('company_id', $companyId);
                        }
                    }),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique($productTable, 'barcode')
                    ->ignore($productId)
                    ->where(function ($query) use ($companyId) {
                        if ($companyId) {
                            $query->where('company_id', $companyId);
                        }
                    }),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'show_in_pos' => ['sometimes', 'boolean'],
            'show_in_b2b' => ['sometimes', 'boolean'],
            'show_in_b2c' => ['sometimes', 'boolean'],
            'featured_image' => ['nullable', 'image', 'max:2048'],
            'gallery_images' => ['nullable', 'array', 'max:5'],
            'gallery_images.*' => ['image', 'max:2048'],
            'retain_gallery' => ['nullable', 'array', 'max:5'],
            'retain_gallery.*' => ['string', 'max:512'],
            'removed_gallery' => ['nullable', 'array', 'max:5'],
            'removed_gallery.*' => ['string', 'max:512'],
            'remove_featured_image' => ['sometimes', 'boolean'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'minimum_stock' => ['nullable', 'integer', 'min:0'],
            'prices' => ['required', 'array', 'min:1'],
            'prices.*.price_list_id' => [
                'required',
                'uuid',
                Rule::exists($priceListTable, 'id')->where(function ($query) use ($companyId) {
                    if ($companyId) {
                        $query->where('company_id', $companyId)->orWhereNull('company_id');
                    }
                }),
            ],
            'prices.*.value' => ['required', 'numeric', 'min:0'],
            'price_list_pos_id' => [
                'required',
                'uuid',
                Rule::exists($priceListTable, 'id')->where(function ($query) use ($companyId) {
                    if ($companyId) {
                        $query->where('company_id', $companyId)->orWhereNull('company_id');
                    }
                }),
            ],
            'price_list_b2c_id' => [
                'required',
                'uuid',
                Rule::exists($priceListTable, 'id')->where(function ($query) use ($companyId) {
                    if ($companyId) {
                        $query->where('company_id', $companyId)->orWhereNull('company_id');
                    }
                }),
            ],
            'price_list_b2b_id' => [
                'required',
                'uuid',
                Rule::exists($priceListTable, 'id')->where(function ($query) use ($companyId) {
                    if ($companyId) {
                        $query->where('company_id', $companyId)->orWhereNull('company_id');
                    }
                }),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'show_in_pos' => filter_var($this->input('show_in_pos', true), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
            'show_in_b2b' => filter_var($this->input('show_in_b2b', true), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
            'show_in_b2c' => filter_var($this->input('show_in_b2c', true), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
            'stock_quantity' => (int) $this->input('stock_quantity', 0),
            'minimum_stock' => (int) $this->input('minimum_stock', 0),
            'remove_featured_image' => filter_var($this->input('remove_featured_image', false), FILTER_VALIDATE_BOOLEAN),
        ]);

        $prices = collect($this->input('prices', []))
            ->map(function ($price) {
                return [
                    'price_list_id' => $price['price_list_id'] ?? null,
                    'value' => isset($price['value']) ? (float) $price['value'] : null,
                ];
            })
            ->filter(fn ($price) => $price['price_list_id'] !== null)
            ->values();

        $retainGallery = collect($this->input('retain_gallery', []))
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->take(5)
            ->values();

        $removedGallery = collect($this->input('removed_gallery', []))
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->take(5)
            ->values();

        $this->merge([
            'prices' => $prices->toArray(),
            'retain_gallery' => $retainGallery->toArray(),
            'removed_gallery' => $removedGallery->toArray(),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $prices = collect($this->input('prices', []))
                ->pluck('price_list_id')
                ->filter();

            foreach (['price_list_pos_id', 'price_list_b2c_id', 'price_list_b2b_id'] as $field) {
                $value = $this->input($field);
                if ($value && ! $prices->contains($value)) {
                    $validator->errors()->add($field, gettext('Debes asignar un valor a la lista de precios seleccionada.'));
                }
            }
        });
    }
}

