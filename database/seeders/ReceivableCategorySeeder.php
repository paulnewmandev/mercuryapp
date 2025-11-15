<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ReceivableCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ReceivableCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'code' => 'COBRO',
                'name' => 'Cobros generales',
                'description' => 'Cobros habituales provenientes de ventas o servicios.',
            ],
            [
                'code' => 'SERV',
                'name' => 'Servicios facturados',
                'description' => 'Servicios pendientes de cobro emitidos a clientes.',
            ],
            [
                'code' => 'GARANT',
                'name' => 'Garantías por cobrar',
                'description' => 'Valores pendientes asociados a garantías y soporte.',
            ],
            [
                'code' => 'OTRO',
                'name' => 'Otros por cobrar',
                'description' => 'Montos no clasificados dentro de las categorías principales.',
            ],
        ];

        $companies = Company::query()->get();

        foreach ($companies as $company) {
            foreach ($categories as $category) {
                $cat = ReceivableCategory::query()->firstOrNew([
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

