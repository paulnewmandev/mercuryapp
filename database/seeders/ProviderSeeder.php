<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Provider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProviderSeeder extends Seeder
{
    public function run(): void
    {
        $individuals = [
            ['Carlos', 'Almeida'],
            ['Fernanda', 'Ortiz'],
            ['Javier', 'López'],
            ['Patricia', 'Núñez'],
            ['Diego', 'Bravo'],
            ['Daniela', 'Paredes'],
            ['Ricardo', 'Viteri'],
            ['Marisol', 'Quintero'],
            ['Andrés', 'Salazar'],
            ['Lucía', 'Cedeño'],
        ];

        $businesses = [
            'Tecnorepuestos Andinos',
            'Importadora del Pacífico',
            'Soluciones Industriales Sierra',
            'Distribuidora Electrotech',
            'Servicios Logísticos Express',
            'Agroinsumos Latam',
            'Comercializadora Nova',
            'Grupo Empresarial Altus',
            'Red Médica Integral',
            'Consultores Innovatec',
        ];

        $companies = Company::query()->get();

        foreach ($companies as $companyIndex => $company) {
            Provider::query()->where('company_id', $company->id)->delete();

            $records = collect();

            foreach ($individuals as $index => [$firstName, $lastName]) {
                $cedula = $this->cedulaFor($companyIndex, $index);
                $emailSlug = Str::slug($firstName . '.' . $lastName, '.');

                $records->push([
                    'provider_type' => 'individual',
                    'identification_type' => 'CEDULA',
                    'identification_number' => $cedula,
                    'first_name' => mb_strtoupper($firstName),
                    'last_name' => mb_strtoupper($lastName),
                    'business_name' => null,
                    'email' => Str::lower("{$emailSlug}.{$cedula}@proveedores.ec"),
                    'phone_number' => $this->phoneFor('09', $companyIndex, $index),
                ]);
            }

            foreach ($businesses as $index => $businessName) {
                $ruc = $this->rucFor($companyIndex, $index);
                $emailSlug = Str::slug($businessName, '.');

                $records->push([
                    'provider_type' => 'business',
                    'identification_type' => 'RUC',
                    'identification_number' => $ruc,
                    'first_name' => null,
                    'last_name' => null,
                    'business_name' => mb_strtoupper($businessName),
                    'email' => Str::lower("{$emailSlug}.{$ruc}@directorio.ec"),
                    'phone_number' => $this->phoneFor('02', $companyIndex, $index),
                ]);
            }

            $records->shuffle()->take(20)->each(function (array $data) use ($company): void {
                $provider = Provider::query()->firstOrNew([
                    'company_id' => $company->id,
                    'identification_number' => $data['identification_number'],
                ]);
                
                if (!$provider->exists) {
                    $provider->id = (string) \Illuminate\Support\Str::uuid();
                }
                
                $provider->fill(array_merge($data, [
                    'company_id' => $company->id,
                    'status' => 'A',
                ]));
                $provider->save();
            });
        }
    }

    private function cedulaFor(int $companyIndex, int $index): string
    {
        $base = 100000000 + ($companyIndex * 10000) + $index;

        return str_pad((string) $base, 10, '0', STR_PAD_LEFT);
    }

    private function rucFor(int $companyIndex, int $index): string
    {
        $base = 1000000000 + ($companyIndex * 100000) + $index;

        return str_pad((string) $base, 13, '0', STR_PAD_LEFT);
    }

    private function phoneFor(string $prefix, int $companyIndex, int $index): string
    {
        $number = 1000000 + ($companyIndex * 100000) + $index;

        return $prefix . str_pad((string) $number, 8, '0', STR_PAD_LEFT);
    }
}

