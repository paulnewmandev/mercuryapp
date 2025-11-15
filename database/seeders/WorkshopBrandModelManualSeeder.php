<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\WorkshopBrand;
use App\Models\WorkshopModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WorkshopBrandModelManualSeeder extends Seeder
{
    public function run(): void
    {
        $appleModels = [
            'MacBook Pro 13"',
            'MacBook Pro 14"',
            'MacBook Pro 15"',
            'MacBook Pro 16"',
            'MacBook Air 13"',
            'MacBook Air 15"',
            'Mac mini',
            'Mac Studio',
            'iMac 24"',
            'iMac 27"',
            'Mac Pro',
            'iPad Pro 12.9"',
            'iPad Pro 11"',
            'iPad Air',
            'iPad mini',
            'iPad 10.2"',
            'iPhone 15 Pro Max',
            'iPhone 15 Pro',
            'iPhone 15',
            'iPhone 14 Pro Max',
            'iPhone 14 Pro',
            'iPhone 14',
            'iPhone 13 Pro Max',
            'iPhone 13 Pro',
            'iPhone 13',
            'iPhone SE (3rd gen)',
            'Apple Watch Ultra 2',
            'Apple Watch Series 9',
            'Apple Watch SE',
            'AirPods Pro (2nd gen)',
            'AirPods Max',
            'AirPods (3rd gen)',
            'Apple TV 4K',
        ];

        Company::query()->each(function (Company $company) use ($appleModels): void {
            WorkshopModel::query()->where('company_id', $company->id)->delete();

            WorkshopBrand::query()
                ->where('company_id', $company->id)
                ->where('name', '!=', 'APPLE')
                ->delete();

            $appleBrand = WorkshopBrand::query()->firstOrNew([
                'company_id' => $company->id,
                'name' => 'APPLE',
            ]);
            
            if (!$appleBrand->exists) {
                $appleBrand->id = (string) Str::uuid();
            }
            
            $appleBrand->description = 'Equipos y accesorios Apple.';
            $appleBrand->status = 'A';
            $appleBrand->save();

            $this->seedModels($company->id, $appleBrand->id, $appleModels);

            $this->command?->info(sprintf(
                'Taller · %s -> Marca APPLE con %d modelos',
                $company->name,
                count($appleModels)
            ));
        });
    }

    private function seedModels(string $companyId, string $brandId, array $models): void
    {
        foreach ($models as $modelName) {
            $normalized = Str::limit(trim($modelName), 255, '');
            if ($normalized === '') {
                continue;
            }

            $model = WorkshopModel::query()->firstOrNew([
                'company_id' => $companyId,
                'brand_id' => $brandId,
                'name' => $normalized,
            ]);
            
            if (!$model->exists) {
                $model->id = (string) Str::uuid();
            }
            
            $model->description = null;
            $model->status = 'A';
            $model->save();
        }
    }
}
