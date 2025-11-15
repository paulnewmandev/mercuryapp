<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ExpenseType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExpenseTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'ALQU', 'name' => 'Alquiler', 'description' => 'Pagos periódicos por alquileres de oficinas, locales u otros inmuebles.'],
            ['code' => 'GPER', 'name' => 'Gastos personales', 'description' => 'Egresos relacionados a gastos administrativos, viáticos y otros personales.'],
            ['code' => 'COMP', 'name' => 'Compras casa', 'description' => 'Compras de insumos y artículos para oficinas o instalaciones.'],
            ['code' => 'SUEL', 'name' => 'Sueldos', 'description' => 'Pago de nómina y remuneraciones al personal.'],
            ['code' => 'SERV', 'name' => 'Servicios básicos', 'description' => 'Servicios de energía, agua, telecomunicaciones y similares.'],
        ];

        $companies = Company::query()->get();

        foreach ($companies as $company) {
            foreach ($types as $type) {
                $code = Str::upper($type['code']);
                $expenseType = ExpenseType::query()->firstOrNew([
                    'company_id' => $company->id,
                    'code' => $code,
                ]);
                
                if (!$expenseType->exists) {
                    $expenseType->id = (string) Str::uuid();
                }
                
                $expenseType->name = $type['name'];
                $expenseType->description = $type['description'];
                $expenseType->status = 'A';
                $expenseType->save();
            }
        }
    }
}
