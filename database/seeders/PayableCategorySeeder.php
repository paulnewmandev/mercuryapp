<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\PayableCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PayableCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'code' => 'PROV',
                'name' => 'Pago a proveedores',
                'description' => 'Obligaciones por compras de insumos y repuestos.',
            ],
            [
                'code' => 'SERV',
                'name' => 'Servicios contratados',
                'description' => 'Servicios externos pendientes de pago (software, soporte, etc.).',
            ],
            [
                'code' => 'NOMI',
                'name' => 'Nómina y honorarios',
                'description' => 'Sueldos, honorarios profesionales y comisiones por pagar.',
            ],
            [
                'code' => 'OTRO',
                'name' => 'Otros por pagar',
                'description' => 'Compromisos sin categoría específica.',
            ],
        ];

        $companies = Company::query()->get();

        foreach ($companies as $company) {
            foreach ($categories as $category) {
                $cat = PayableCategory::query()->firstOrNew([
                    'company_id' => $company->id,
                    'code' => Str::upper($category['code']),
                ]);
                
                if (!$cat->exists) {
                    $cat->id = (string) Str::uuid();
                }
                
                $cat->name = $category['name'];
                $cat->description = $category['description'];
                $cat->status = 'A';
                $cat->save();
            }
        }
    }
}

