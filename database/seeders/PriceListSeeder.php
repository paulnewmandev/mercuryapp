<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\PriceList;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PriceListSeeder extends Seeder
{
    public function run(): void
    {
        $lists = [
            ['Lista general', 'Tarifario base aplicado a todos los clientes.'],
            ['Lista corporativa', 'Precios especiales para cuentas corporativas.'],
            ['Lista distribuidores', 'Descuentos para distribuidores autorizados.'],
            ['Lista online', 'Precios exclusivos para ventas en línea.'],
            ['Lista fidelización', 'Beneficios para clientes frecuentes.'],
        ];

        $companies = Company::query()->get();

        foreach ($companies as $company) {
            foreach ($lists as [$name, $description]) {
                $priceList = PriceList::query()->firstOrNew([
                    'company_id' => $company->id,
                    'name' => $name,
                ]);
                
                if (!$priceList->exists) {
                    $priceList->id = (string) Str::uuid();
                }
                
                $priceList->description = $description;
                $priceList->status = 'A';
                $priceList->save();
            }
        }
    }
}
