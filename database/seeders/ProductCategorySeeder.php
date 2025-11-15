<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ProductCategory;
use App\Models\ProductLine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categoriesPath = database_path('seeders/data/product_categories.json');
        if (! File::exists($categoriesPath)) {
            $this->command?->warn('No se encontró el archivo product_categories.json. Se omite la carga de categorías.');

            return;
        }

        $catalogue = json_decode(File::get($categoriesPath), true) ?? [];
        if ($catalogue === [] || ! is_array($catalogue)) {
            $this->command?->warn('No se pudo interpretar el archivo product_categories.json. Se omite la carga de categorías.');

            return;
        }

        $companies = Company::query()->get();

        foreach ($companies as $company) {
            $lines = ProductLine::query()
                ->where('company_id', $company->id)
                ->get()
                ->keyBy(fn (ProductLine $line) => Str::lower($line->name));

            foreach (array_keys($catalogue) as $categoryName) {
                $lineKey = Str::lower($categoryName);

                if (! $lines->has($lineKey)) {
                    $newLine = new ProductLine();
                    $newLine->id = (string) Str::uuid();
                    $newLine->company_id = $company->id;
                    $newLine->name = $categoryName;
                    $newLine->description = sprintf('Línea importada desde catálogo Excel (%s).', Str::title($categoryName));
                    $newLine->status = 'A';
                    $newLine->save();
                    $lines[$lineKey] = $newLine;
                }
            }

            ProductCategory::query()
                ->where('company_id', $company->id)
                ->delete();

            foreach ($catalogue as $categoryName => $subcategories) {
                $line = $lines[Str::lower($categoryName)] ?? null;

                /** @var ProductCategory $category */
                    $category = new ProductCategory();
                    $category->id = (string) Str::uuid();
                    $category->company_id = $company->id;
                    $category->product_line_id = $line?->id;
                    $category->parent_id = null;
                    $category->name = $categoryName;
                    $category->status = 'A';
                    $category->save();

                    foreach ($subcategories as $subName) {
                        $subCategory = new ProductCategory();
                        $subCategory->id = (string) Str::uuid();
                        $subCategory->company_id = $company->id;
                        $subCategory->product_line_id = $line?->id;
                        $subCategory->parent_id = $category->id;
                        $subCategory->name = $subName;
                        $subCategory->status = 'A';
                        $subCategory->save();
                }
            }
        }
    }
}
