<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ItemPrice;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductLine;
use App\Models\ProductStock;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AppleProductSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->first();

        if (!$company) {
            $this->command?->warn('No se encontró una compañía para asociar los productos de Apple.');
            return;
        }

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
            $this->command?->warn("No se pudieron crear productos de Apple para la compañía {$company->name} por falta de bodegas o listas de precios activas.");
            return;
        }

        $defaultWarehouse = $warehouses->first();
        $productLine = ProductLine::query()
            ->where('company_id', $company->id)
            ->first();

        // Crear o obtener categoría "Apple"
        $appleCategory = ProductCategory::query()->firstOrNew([
            'company_id' => $company->id,
            'name' => 'Apple',
            'parent_id' => null,
        ]);

        if (!$appleCategory->exists) {
            $appleCategory->id = (string) Str::uuid();
            $appleCategory->product_line_id = $productLine?->id;
            $appleCategory->status = 'A';
            $appleCategory->save();
        }

        // Subcategorías de Apple
        $subcategories = [
            'iPhone' => ProductCategory::query()->firstOrNew([
                'company_id' => $company->id,
                'name' => 'iPhone',
                'parent_id' => $appleCategory->id,
            ]),
            'iPad' => ProductCategory::query()->firstOrNew([
                'company_id' => $company->id,
                'name' => 'iPad',
                'parent_id' => $appleCategory->id,
            ]),
            'MacBook' => ProductCategory::query()->firstOrNew([
                'company_id' => $company->id,
                'name' => 'MacBook',
                'parent_id' => $appleCategory->id,
            ]),
            'Apple Watch' => ProductCategory::query()->firstOrNew([
                'company_id' => $company->id,
                'name' => 'Apple Watch',
                'parent_id' => $appleCategory->id,
            ]),
            'AirPods' => ProductCategory::query()->firstOrNew([
                'company_id' => $company->id,
                'name' => 'AirPods',
                'parent_id' => $appleCategory->id,
            ]),
        ];

        foreach ($subcategories as $key => $subcategory) {
            if (!$subcategory->exists) {
                $subcategory->id = (string) Str::uuid();
                $subcategory->product_line_id = $productLine?->id;
                $subcategory->status = 'A';
                $subcategory->save();
            }
        }

        $posListId = $priceLists->firstWhere('name', 'Lista general')?->id ?? $priceLists->first()->id;
        $priceListsById = $priceLists->keyBy('id');

        // Productos de Apple
        $appleProducts = [
            // iPhone
            ['name' => 'iPhone 15 Pro Max 256GB', 'sku' => 'APPLE-IP15PM-256', 'category' => 'iPhone', 'price' => 1299.00, 'stock' => 15],
            ['name' => 'iPhone 15 Pro 128GB', 'sku' => 'APPLE-IP15P-128', 'category' => 'iPhone', 'price' => 999.00, 'stock' => 20],
            ['name' => 'iPhone 15 128GB', 'sku' => 'APPLE-IP15-128', 'category' => 'iPhone', 'price' => 799.00, 'stock' => 25],
            ['name' => 'iPhone 14 Pro 256GB', 'sku' => 'APPLE-IP14P-256', 'category' => 'iPhone', 'price' => 1099.00, 'stock' => 12],
            ['name' => 'iPhone 14 128GB', 'sku' => 'APPLE-IP14-128', 'category' => 'iPhone', 'price' => 699.00, 'stock' => 18],
            ['name' => 'iPhone 13 128GB', 'sku' => 'APPLE-IP13-128', 'category' => 'iPhone', 'price' => 599.00, 'stock' => 10],
            
            // iPad
            ['name' => 'iPad Pro 12.9" M2 256GB', 'sku' => 'APPLE-IPADP-129-256', 'category' => 'iPad', 'price' => 1099.00, 'stock' => 8],
            ['name' => 'iPad Pro 11" M2 128GB', 'sku' => 'APPLE-IPADP-11-128', 'category' => 'iPad', 'price' => 799.00, 'stock' => 12],
            ['name' => 'iPad Air M1 256GB', 'sku' => 'APPLE-IPADA-256', 'category' => 'iPad', 'price' => 649.00, 'stock' => 15],
            ['name' => 'iPad 10.2" 64GB', 'sku' => 'APPLE-IPAD-102-64', 'category' => 'iPad', 'price' => 329.00, 'stock' => 20],
            ['name' => 'iPad mini 256GB', 'sku' => 'APPLE-IPADM-256', 'category' => 'iPad', 'price' => 599.00, 'stock' => 10],
            
            // MacBook
            ['name' => 'MacBook Pro 16" M3 Pro 512GB', 'sku' => 'APPLE-MBP16-M3P-512', 'category' => 'MacBook', 'price' => 2499.00, 'stock' => 5],
            ['name' => 'MacBook Pro 14" M3 512GB', 'sku' => 'APPLE-MBP14-M3-512', 'category' => 'MacBook', 'price' => 1999.00, 'stock' => 8],
            ['name' => 'MacBook Air 15" M2 256GB', 'sku' => 'APPLE-MBA15-M2-256', 'category' => 'MacBook', 'price' => 1299.00, 'stock' => 10],
            ['name' => 'MacBook Air 13" M2 256GB', 'sku' => 'APPLE-MBA13-M2-256', 'category' => 'MacBook', 'price' => 1099.00, 'stock' => 12],
            ['name' => 'MacBook Pro 13" M2 256GB', 'sku' => 'APPLE-MBP13-M2-256', 'category' => 'MacBook', 'price' => 1299.00, 'stock' => 6],
            
            // Apple Watch
            ['name' => 'Apple Watch Ultra 2 49mm', 'sku' => 'APPLE-AWU2-49', 'category' => 'Apple Watch', 'price' => 799.00, 'stock' => 8],
            ['name' => 'Apple Watch Series 9 45mm GPS', 'sku' => 'APPLE-AW9-45-GPS', 'category' => 'Apple Watch', 'price' => 429.00, 'stock' => 15],
            ['name' => 'Apple Watch Series 9 41mm GPS', 'sku' => 'APPLE-AW9-41-GPS', 'category' => 'Apple Watch', 'price' => 399.00, 'stock' => 18],
            ['name' => 'Apple Watch SE 44mm GPS', 'sku' => 'APPLE-AWSE-44', 'category' => 'Apple Watch', 'price' => 249.00, 'stock' => 20],
            
            // AirPods
            ['name' => 'AirPods Pro 2 USB-C', 'sku' => 'APPLE-APP2-USB', 'category' => 'AirPods', 'price' => 249.00, 'stock' => 25],
            ['name' => 'AirPods Pro 2 Lightning', 'sku' => 'APPLE-APP2-LT', 'category' => 'AirPods', 'price' => 249.00, 'stock' => 22],
            ['name' => 'AirPods 3ra generación', 'sku' => 'APPLE-AP3-GEN', 'category' => 'AirPods', 'price' => 179.00, 'stock' => 30],
            ['name' => 'AirPods 2da generación', 'sku' => 'APPLE-AP2-GEN', 'category' => 'AirPods', 'price' => 129.00, 'stock' => 35],
            ['name' => 'AirPods Max', 'sku' => 'APPLE-APM-01', 'category' => 'AirPods', 'price' => 549.00, 'stock' => 5],
        ];

        $created = 0;
        $updated = 0;

        foreach ($appleProducts as $productData) {
            $subcategory = $subcategories[$productData['category']] ?? $appleCategory;

            $product = Product::query()->firstOrNew([
                'company_id' => $company->id,
                'sku' => $productData['sku'],
            ]);

            $isNew = !$product->exists;

            if (!$product->exists) {
                $product->id = (string) Str::uuid();
            }

            $product->company_id = $company->id;
            $product->product_line_id = $productLine?->id;
            $product->category_id = $appleCategory->id;
            $product->subcategory_id = $subcategory->id;
            $product->warehouse_id = $defaultWarehouse?->id;
            $product->sku = $productData['sku'];
            $product->barcode = 'BAR' . strtoupper(str_replace('-', '', $productData['sku']));
            $product->name = $productData['name'];
            $product->description = 'Producto oficial de Apple - ' . $productData['name'];
            $product->featured_image_path = null;
            $product->gallery_images = [];
            $product->show_in_pos = true;
            $product->show_in_b2b = true;
            $product->show_in_b2c = true;
            $product->price_list_pos_id = $posListId;
            $product->price_list_b2c_id = $posListId;
            $product->price_list_b2b_id = $posListId;
            $product->status = 'A';
            $product->save();

            // Stock
            $stock = $productData['stock'];
            $minimumStock = max(1, (int) floor($stock * 0.2));

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

            // Precios
            $price = $productData['price'];

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

            if ($isNew) {
                $created++;
            } else {
                $updated++;
            }
        }

        $this->command?->info("✅ Productos de Apple: {$created} creados, {$updated} actualizados para la compañía {$company->name}.");
    }
}

