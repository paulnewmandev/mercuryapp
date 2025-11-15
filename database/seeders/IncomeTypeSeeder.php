<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\IncomeType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class IncomeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'SERV', 'name' => 'Servicios', 'description' => 'Ingresos generados por la prestación de servicios.'],
            ['code' => 'REPA', 'name' => 'Reparaciones', 'description' => 'Ingresos provenientes de trabajos de reparación.'],
            ['code' => 'VENT', 'name' => 'Ventas', 'description' => 'Ventas de productos y mercaderías.'],
            ['code' => 'CONS', 'name' => 'Consultorías', 'description' => 'Servicios de consultoría y asesoría especializada.'],
        ];

        $companies = Company::query()->get();

        foreach ($companies as $company) {
            foreach ($types as $type) {
                $code = Str::upper($type['code']);
                $incomeType = IncomeType::query()->firstOrNew([
                    'company_id' => $company->id,
                    'code' => $code,
                ]);
                
                if (!$incomeType->exists) {
                    $incomeType->id = (string) Str::uuid();
                }
                
                $incomeType->name = $type['name'];
                $incomeType->description = $type['description'];
                $incomeType->status = 'A';
                $incomeType->save();
            }
        }
    }
}
