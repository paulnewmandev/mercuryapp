<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [
            'Bodega principal' => 'Av. Tecnológica 101 y Río Amazonas, Quito',
            'Bodega satélite' => 'Parque Industrial Norte, Km 5 Vía Daule, Guayaquil',
        ];

        $companies = Company::query()->get();

        foreach ($companies as $company) {
            foreach ($warehouses as $name => $address) {
                $warehouse = Warehouse::query()->firstOrNew([
                    'company_id' => $company->id,
                    'name' => $name,
                ]);
                
                if (!$warehouse->exists) {
                    $warehouse->id = (string) \Illuminate\Support\Str::uuid();
                }
                
                $warehouse->address = $address;
                $warehouse->status = 'A';
                $warehouse->save();
            }
        }
    }
}
