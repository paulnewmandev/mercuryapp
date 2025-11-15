<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CustomerCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CustomerCategorySeeder extends Seeder
{
    public function run(): void
    {
        $defaultCategories = [
            [
                'name' => 'Cliente normal',
                'description' => 'Clientes particulares sin acuerdos especiales.',
            ],
            [
                'name' => 'Cliente PYME',
                'description' => 'Pequeñas o medianas empresas con atención preferente.',
            ],
            [
                'name' => 'Cliente corporativo',
                'description' => 'Grandes cuentas con contratos activos.',
            ],
            [
                'name' => 'Cliente premium',
                'description' => 'Clientes con beneficios exclusivos y SLA prioritario.',
            ],
        ];

        Company::query()->select(['id'])->chunk(100, function ($companies) use ($defaultCategories): void {
            foreach ($companies as $company) {
                foreach ($defaultCategories as $category) {
                    $name = Arr::get($category, 'name');

                    $cat = CustomerCategory::withoutGlobalScopes()->firstOrNew([
                        'company_id' => $company->id,
                        'slug' => Str::slug($name),
                    ]);
                    
                    if (!$cat->exists) {
                        $cat->id = (string) Str::uuid();
                    }
                    
                    $cat->name = $name;
                    $cat->description = Arr::get($category, 'description');
                    $cat->status = 'A';
                    $cat->save();
                }
            }
        });
    }
}

