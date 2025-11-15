<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Reparación',
            'Programas',
            'Sistemas Operativos',
            'Diseño',
        ];

        $companies = Company::query()->get();

        foreach ($companies as $company) {
            foreach ($categories as $name) {
                $serviceCategory = ServiceCategory::query()->firstOrNew([
                    'company_id' => $company->id,
                    'name' => $name,
                    'parent_id' => null,
                ]);
                
                if (!$serviceCategory->exists) {
                    $serviceCategory->id = (string) \Illuminate\Support\Str::uuid();
                }
                
                $serviceCategory->status = 'A';
                $serviceCategory->save();
            }
        }
    }
}
