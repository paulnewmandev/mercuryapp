<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\WorkshopBrand;
use App\Models\WorkshopModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class WorkshopBrandModelSeeder extends Seeder
{
    public function run(): void
    {
        $productsPath = database_path('seeders/data/products.json');
        $products = [];

        if (File::exists($productsPath)) {
            $decoded = json_decode(File::get($productsPath), true);
            if (is_array($decoded)) {
                $products = $decoded;
            }
        } else {
            $this->command?->warn('No se encontró products.json; se crearán modelos base sin datos adicionales.');
        }

        $brandNames = [
            'APPLE' => [
                'keywords' => ['APPLE', 'IPHONE', 'IPAD', 'IMAC', 'MACBOOK', 'MAC MINI', 'WATCH', 'AIRPODS'],
            ],
            'OTRAS' => [
                'keywords' => [],
            ],
        ];

        Company::query()->each(function (Company $company) use ($brandNames, $products): void {
            $brands = collect($brandNames)->mapWithKeys(function ($config, $name) use ($company) {
                /** @var WorkshopBrand $brand */
                $brand = WorkshopBrand::query()->firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'name' => $name,
                    ],
                    [
                        'description' => $name === 'APPLE'
                            ? 'Equipos y accesorios originales Apple.'
                            : 'Equipos de otras marcas.',
                        'status' => 'A',
                    ]
                );

                return [$name => $brand];
            });

            $existingModels = WorkshopModel::query()
                ->where('company_id', $company->id)
                ->get()
                ->groupBy(fn (WorkshopModel $model) => $model->brand_id);

            $createdCount = 0;

            $modelsToCreate = collect($products)
                ->map(function (array $product) use ($brandNames) {
                    $name = Str::of(Arr::get($product, 'name', ''))
                        ->trim()
                        ->replaceMatches('/\s+/', ' ')
                        ->toString();

                    if ($name === '') {
                        return null;
                    }

                    $upperName = Str::upper($name);

                    $brandKey = 'OTRAS';
                    foreach ($brandNames as $candidate => $config) {
                        foreach ($config['keywords'] as $keyword) {
                            if ($keyword !== '' && Str::contains($upperName, $keyword)) {
                                $brandKey = $candidate;
                                break 2;
                            }
                        }
                    }

                    return [
                        'brand_key' => $brandKey,
                        'name' => $name,
                        'description' => Arr::get($product, 'description'),
                    ];
                })
                ->filter()
                ->unique(fn ($item) => Str::upper($item['brand_key'].'|'.$item['name']))
                ->values();

            $fallbackAppleModels = [
                'MacBook Pro 13"',
                'MacBook Pro 15"',
                'MacBook Air',
                'Mac Mini',
                'iMac 24"',
                'iPad Pro 12.9"',
                'iPad Pro 11"',
                'iPad Air',
                'iPad 10.2"',
                'iPhone 15 Pro Max',
                'iPhone 15',
                'iPhone 14',
                'Apple Watch Series 9',
                'AirPods Pro',
            ];

            $fallbackOtherModels = [
                'Cargador universal USB',
                'Protector de pantalla templado',
                'Soporte magnético',
                'Estuche impermeable',
                'Batería portátil 20.000 mAh',
                'Cargador inalámbrico 3 en 1',
            ];

            if ($modelsToCreate->isEmpty()) {
                $modelsToCreate = collect($fallbackAppleModels)->map(fn ($name) => [
                    'brand_key' => 'APPLE',
                    'name' => $name,
                    'description' => null,
                ])->merge(collect($fallbackOtherModels)->map(fn ($name) => [
                    'brand_key' => 'OTRAS',
                    'name' => $name,
                    'description' => null,
                ]));
            }

            $modelsToCreate->each(function (array $item) use ($company, $brands, $existingModels, &$createdCount): void {
                $brand = $brands->get($item['brand_key']);
                if (! $brand) {
                    return;
                }

                $normalizedName = Str::limit($item['name'], 255, '');

                $alreadyExists = $existingModels->get($brand->id)
                    ?->contains(fn (WorkshopModel $model) => Str::upper($model->name) === Str::upper($normalizedName));

                if ($alreadyExists) {
                    return;
                }

                WorkshopModel::query()->create([
                    'company_id' => $company->id,
                    'brand_id' => $brand->id,
                    'name' => $normalizedName,
                    'description' => $item['description'] ?? null,
                    'status' => 'A',
                ]);

                $createdCount++;
            });

            $this->command?->info(sprintf(
                'Taller · %s -> %d marcas / +%d modelos',
                $company->name,
                $brands->count(),
                $createdCount
            ));
        });
    }
}
