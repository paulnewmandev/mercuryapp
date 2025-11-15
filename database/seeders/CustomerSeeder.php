<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $individuals = [
            ['Juan', 'Pérez'],
            ['María', 'Gómez'],
            ['Carlos', 'Ramírez'],
            ['Ana', 'Torres'],
            ['Luis', 'Sánchez'],
            ['Fernanda', 'Vega'],
            ['Andrés', 'Carrillo'],
            ['Gabriela', 'Ríos'],
            ['Jorge', 'Benítez'],
            ['Natalia', 'Mora'],
        ];

        $businesses = [
            'Tecnología Andina S.A.',
            'Innova Digital Cía. Ltda.',
            'Soluciones Integrales del Pacífico',
            'Servicios Industriales Sierra',
            'Consultores del Futuro S.A.',
            'Ecuaprint Gráficos',
            'Distribuidora Eléctrica Andina',
            'Logística Express Latam',
            'Agroexportadora del Litoral',
            'Salud Total Integral',
        ];

        $companies = Company::query()->get();

        foreach ($companies as $companyIndex => $company) {
            Customer::query()->where('company_id', $company->id)->delete();

            $categories = CustomerCategory::query()
                ->where('company_id', $company->id)
                ->where('status', 'A')
                ->pluck('id')
                ->all();

            if (empty($categories)) {
                continue;
            }

            $records = collect();

            foreach ($individuals as $index => [$firstName, $lastName]) {
                $documentNumber = $this->cedulaFor($companyIndex, $index);
                $emailSlug = Str::slug($firstName . '.' . $lastName, '.');
                $sex = Arr::random(['MASCULINO', 'FEMENINO']);
                $birthDate = Carbon::now()->subYears(rand(20, 55))->subDays(rand(1, 365));

                $records->push([
                    'category_id' => Arr::random($categories),
                    'customer_type' => 'individual',
                    'first_name' => mb_strtoupper($firstName),
                    'last_name' => mb_strtoupper($lastName),
                    'business_name' => null,
                    'sex' => $sex,
                    'birth_date' => $birthDate->toDateString(),
                    'document_type' => 'CEDULA',
                    'document_number' => $documentNumber,
                    'email' => Str::lower($emailSlug . '.' . $documentNumber . '@ejemplo.com'),
                    'phone_number' => $this->phoneFor('09', $companyIndex, $index),
                    'address' => 'AV. REPÚBLICA Y AMAZONAS, QUITO',
                ]);
            }

            foreach ($businesses as $index => $businessName) {
                $documentNumber = $this->rucFor($companyIndex, $index);
                $emailSlug = Str::slug($businessName, '.');

                $records->push([
                    'category_id' => Arr::random($categories),
                    'customer_type' => 'business',
                    'first_name' => null,
                    'last_name' => null,
                    'business_name' => mb_strtoupper($businessName),
                    'sex' => null,
                    'birth_date' => null,
                    'document_type' => 'RUC',
                    'document_number' => $documentNumber,
                    'email' => Str::lower($emailSlug . '.' . $documentNumber . '@empresa.ec'),
                    'phone_number' => $this->phoneFor('02', $companyIndex, $index),
                    'address' => 'PARQUE INDUSTRIAL NORTE, GUAYAQUIL',
                ]);
            }

            $records->shuffle()->take(20)->each(function (array $data) use ($company): void {
                $customer = Customer::query()->firstOrNew([
                    'company_id' => $company->id,
                    'document_number' => $data['document_number'],
                ]);
                
                if (!$customer->exists) {
                    $customer->id = (string) Str::uuid();
                }
                
                $customer->fill(array_merge($data, [
                    'company_id' => $company->id,
                    'status' => 'A',
                ]));
                $customer->save();
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
