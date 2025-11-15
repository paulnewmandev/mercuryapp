<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\WorkshopBrand;
use App\Models\WorkshopEquipment;
use App\Models\WorkshopModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WorkshopEquipmentSeeder extends Seeder
{
    public function run(): void
    {
        Company::query()->each(function (Company $company): void {
            $brands = WorkshopBrand::query()
                ->where('company_id', $company->id)
                ->where('status', 'A')
                ->with('models')
                ->get();

            if ($brands->isEmpty()) {
                return;
            }

            $brand = $brands->first();
            $models = WorkshopModel::query()
                ->where('company_id', $company->id)
                ->where('brand_id', $brand->id)
                ->where('status', 'A')
                ->get();

            if ($models->isEmpty()) {
                return;
            }

            $createdCount = 0;

            for ($i = 0; $i < 10; $i++) {
                $model = $models->random();
                $identifier = 'EQ-' . str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT);

                $equipment = WorkshopEquipment::query()->firstOrNew([
                    'company_id' => $company->id,
                    'brand_id' => $brand->id,
                    'model_id' => $model->id,
                    'identifier' => $identifier,
                ]);
                
                if (!$equipment->exists) {
                    $equipment->id = (string) Str::uuid();
                }
                
                $equipment->note = rand(0, 1) ? 'Equipo en buen estado' : null;
                $equipment->status = 'A';
                $equipment->save();

                $createdCount++;
            }

            $this->command?->info(sprintf(
                'Taller · %s -> %d equipos creados',
                $company->name,
                $createdCount
            ));
        });
    }
}

