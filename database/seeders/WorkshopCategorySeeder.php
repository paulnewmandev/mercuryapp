<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\WorkshopCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WorkshopCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Reparación',
                'description' => 'Trabajos de reparación de equipos y dispositivos.',
            ],
            [
                'name' => 'Mantenimiento',
                'description' => 'Servicios de mantenimiento preventivo y correctivo.',
            ],
            [
                'name' => 'Instalación',
                'description' => 'Instalación de software, hardware y configuraciones.',
            ],
            [
                'name' => 'Diagnóstico',
                'description' => 'Servicios de diagnóstico y análisis técnico.',
            ],
            [
                'name' => 'Actualización',
                'description' => 'Actualizaciones de software y sistemas operativos.',
            ],
        ];

        $companies = Company::query()->get();

        foreach ($companies as $company) {
            foreach ($categories as $categoryData) {
                $category = WorkshopCategory::query()->firstOrNew([
                    'company_id' => $company->id,
                    'name' => $categoryData['name'],
                ]);
                
                if (!$category->exists) {
                    $category->id = (string) Str::uuid();
                }
                
                $category->description = $categoryData['description'];
                $category->status = 'A';
                $category->save();
            }
        }

        $this->command?->info('Categorías de taller generadas correctamente.');
    }
}

