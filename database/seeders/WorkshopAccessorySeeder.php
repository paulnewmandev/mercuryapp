<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\WorkshopAccessory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WorkshopAccessorySeeder extends Seeder
{
    public function run(): void
    {
        $accessories = [
            'Cable',
            'Cargador',
            'Forro',
        ];

        Company::query()->each(function (Company $company) use ($accessories): void {
            foreach ($accessories as $name) {
                $accessory = WorkshopAccessory::query()->firstOrNew([
                    'company_id' => $company->id,
                    'name' => $name,
                ]);
                
                if (!$accessory->exists) {
                    $accessory->id = (string) Str::uuid();
                }
                
                $accessory->status = 'A';
                $accessory->save();
            }

            $this->command?->info(sprintf(
                'Taller · %s -> %d accesorios creados',
                $company->name,
                count($accessories)
            ));
        });
    }
}

