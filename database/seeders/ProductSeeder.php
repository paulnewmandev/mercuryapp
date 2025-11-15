<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ItemPrice;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStock;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $productsPath = database_path('seeders/data/products.json');
        if (! File::exists($productsPath)) {
            $this->command?->warn('No se encontró el archivo products.json. Se omite la carga de productos.');

            return;
        }

        $productsData = json_decode(File::get($productsPath), true) ?? [];
        if ($productsData === [] || ! is_array($productsData)) {
            $this->command?->warn('No se pudo interpretar el archivo products.json. Se omite la carga de productos.');

            return;
        }

        $products = collect($productsData)
            ->filter(fn (array $row) => filled(Arr::get($row, 'sku')))
            ->values();

        Company::query()->each(function (Company $company) use ($products): void {
            $warehouses = Warehouse::query()
                ->where('company_id', $company->id)
                ->get();

            $priceLists = PriceList::query()
                ->where(function ($query) use ($company): void {
                    $query->whereNull('company_id')
                        ->orWhere('company_id', $company->id);
                })
                ->where('status', 'A')
                ->get();

            if ($warehouses->isEmpty() || $priceLists->isEmpty()) {
                $this->command?->warn("No se pudieron crear productos para la compañía {$company->name} por falta de bodegas o listas de precios activas.");

                return;
            }

            $parentCategories = ProductCategory::query()
                ->where('company_id', $company->id)
                ->whereNull('parent_id')
                ->get()
                ->keyBy(fn (ProductCategory $category) => Str::lower($category->name));

            $subCategories = ProductCategory::query()
                ->where('company_id', $company->id)
                ->whereNotNull('parent_id')
                ->get();

            $subcategoryIndex = [];
            foreach ($subCategories as $subcategory) {
                $parent = $parentCategories->firstWhere('id', $subcategory->parent_id);
                if (! $parent) {
                    continue;
                }

                $key = Str::lower($parent->name).'||'.Str::lower($subcategory->name);
                $subcategoryIndex[$key] = $subcategory;
            }

            $existingProductIds = Product::query()
                ->where('company_id', $company->id)
                ->pluck('id');

            if ($existingProductIds->isNotEmpty()) {
                // Eliminar items de transferencias primero
                \App\Models\ProductTransferItem::query()
                    ->whereIn('product_id', $existingProductIds)
                    ->delete();

                // Eliminar stock
                \App\Models\ProductStock::query()
                    ->whereIn('product_id', $existingProductIds)
                    ->delete();

                // Eliminar precios
                ItemPrice::query()
                    ->where('item_type', 'product')
                    ->whereIn('item_id', $existingProductIds)
                    ->delete();

                // Finalmente eliminar productos
                Product::query()
                    ->whereIn('id', $existingProductIds)
                    ->delete();
            }

            $posListId = $priceLists->firstWhere('name', 'Lista general')?->id ?? $priceLists->first()->id;
            $b2cListId = $priceLists->firstWhere('name', 'Lista online')?->id ?? $priceLists->first()->id;
            $b2bListId = $priceLists->firstWhere('name', 'Lista distribuidores')?->id ?? $priceLists->first()->id;

            $priceListsById = $priceLists->keyBy('id');
            $defaultWarehouse = $warehouses->first();

            $usedSkus = [];
            $usedBarcodes = [];

            foreach ($products as $item) {
                $categoryName = Str::of(Arr::get($item, 'category', 'Sin categoría'))->trim()->value();
                $categoryKey = Str::lower($categoryName);
                $category = $parentCategories->get($categoryKey);

                if (! $category) {
                    $category = new ProductCategory();
                    $category->id = (string) Str::uuid();
                    $category->company_id = $company->id;
                    $category->product_line_id = $parentCategories->first()?->product_line_id;
                    $category->parent_id = null;
                    $category->name = $categoryName;
                    $category->status = 'A';
                    $category->save();

                    $parentCategories[$categoryKey] = $category;
                }

                $subcategoryName = Str::of(Arr::get($item, 'subcategory'))->trim()->value();
                $subcategory = null;

                if ($subcategoryName) {
                    $subcategoryKey = $categoryKey.'||'.Str::lower($subcategoryName);
                    $subcategory = $subcategoryIndex[$subcategoryKey] ?? null;

                    if (! $subcategory) {
                        $subcategory = new ProductCategory();
                        $subcategory->id = (string) Str::uuid();
                        $subcategory->company_id = $company->id;
                        $subcategory->product_line_id = $category->product_line_id;
                        $subcategory->parent_id = $category->id;
                        $subcategory->name = $subcategoryName;
                        $subcategory->status = 'A';
                        $subcategory->save();

                        $subcategoryIndex[$subcategoryKey] = $subcategory;
                    }
                }

                $rawSku = Str::of(Arr::get($item, 'sku'))->replaceMatches('/[^\\w\\-]/u', '-')->replace(' ', '-')->upper()->value();
                $skuBase = trim(preg_replace('/-+/', '-', $rawSku), '-');
                if ($skuBase === '') {
                    $skuBase = 'SKU-'.Str::upper(Str::substr(md5(Arr::get($item, 'name', uniqid('PRODUCT', true))), 0, 8));
                }

                $sku = $skuBase;
                $skuSuffix = 1;
                while (isset($usedSkus[$sku])) {
                    $sku = sprintf('%s-%02d', $skuBase, $skuSuffix++);
                }
                $usedSkus[$sku] = true;

                $rawBarcode = Str::of(Arr::get($item, 'barcode', $skuBase))->replaceMatches('/[^A-Za-z0-9]/', '')->upper()->value();
                $barcodeBase = $rawBarcode !== '' ? $rawBarcode : $sku;
                $barcode = $barcodeBase;
                $barcodeSuffix = 1;
                while (isset($usedBarcodes[$barcode])) {
                    $barcode = $barcodeBase.str_pad((string) $barcodeSuffix++, 2, '0', STR_PAD_LEFT);
                }
                $usedBarcodes[$barcode] = true;

                $name = Str::of(Arr::get($item, 'name', $sku))->trim()->value();

                $product = new Product();
                $product->id = (string) Str::uuid();
                $product->company_id = $company->id;
                $product->product_line_id = $subcategory?->product_line_id ?? $category?->product_line_id;
                $product->category_id = $category?->id;
                $product->subcategory_id = $subcategory?->id;
                $product->warehouse_id = $defaultWarehouse?->id;
                $product->sku = $sku;
                $product->barcode = $barcode;
                $product->name = $name;
                $product->description = null;
                $product->featured_image_path = null;
                $product->gallery_images = [];
                $product->show_in_pos = true;
                $product->show_in_b2b = true;
                $product->show_in_b2c = true;
                $product->price_list_pos_id = $posListId;
                $product->price_list_b2c_id = $b2cListId;
                $product->price_list_b2b_id = $b2bListId;
                $product->status = 'A';
                $product->save();

                $stock = max(0, (int) Arr::get($item, 'stock', 0));
                $minimumStock = $stock > 0 ? max(1, min($stock, (int) floor($stock * 0.1))) : 0;

                if ($product->warehouse_id) {
                    ProductStock::query()->updateOrInsert(
                        [
                            'product_id' => $product->id,
                            'warehouse_id' => $product->warehouse_id,
                        ],
                        [
                            'quantity' => $stock,
                            'minimum_stock' => $minimumStock,
                            'updated_at' => now(),
                        ]
                    );
                }

                $price = max(
                    0.01,
                    round((float) Arr::get(
                        $item,
                        'price_sale',
                        Arr::get($item, 'price_without_tax', Arr::get($item, 'price_cost', 0))
                    ), 2)
                );

                foreach ($priceListsById as $priceList) {
                    $itemPrice = ItemPrice::query()->firstOrNew([
                        'item_type' => 'product',
                        'item_id' => $product->id,
                        'price_list_id' => $priceList->id,
                    ]);
                    
                    if (!$itemPrice->exists) {
                        $itemPrice->id = (string) Str::uuid();
                    }
                    
                    $itemPrice->value = $price;
                    $itemPrice->status = 'A';
                    $itemPrice->save();
                }
            }

            $this->command?->info("Productos importados desde Excel para la compañía {$company->name}.");
        });
    }
}
