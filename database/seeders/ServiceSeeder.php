<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $structure = [
            'Reparación' => [
                ['Cambio de pantalla', 'Reemplazo de pantalla dañada con repuesto original.'],
                ['Cambio de batería', 'Sustitución de baterías agotadas con calibración completa.'],
                ['Diagnóstico eléctrico', 'Revisión avanzada de componentes electrónicos.'],
            ],
            'Programas' => [
                ['Instalación suite Office', 'Instalación y configuración de Microsoft 365.'],
                ['Implementación CRM', 'Configuración inicial y capacitación básica de CRM.'],
            ],
            'Sistemas Operativos' => [
                ['Reinstalación macOS', 'Formateo e instalación limpia de macOS última versión.'],
                ['Optimización macOS', 'Limpieza, actualizaciones y ajustes de rendimiento.'],
            ],
            'Diseño' => [
                ['Diseño de logo', 'Creación de logo profesional con tres propuestas.'],
                ['Branding express', 'Kit básico de identidad visual digital.'],
            ],
        ];

        $companies = Company::query()->get();

        foreach ($companies as $company) {
            $categories = ServiceCategory::query()
                ->where('company_id', $company->id)
                ->get()
                ->keyBy('name');

            foreach ($structure as $categoryName => $services) {
                $category = $categories->get($categoryName);
                if (! $category) {
                    continue;
                }

                foreach ($services as [$name, $description]) {
                    $service = Service::query()->firstOrNew([
                        'company_id' => $company->id,
                        'category_id' => $category->id,
                        'name' => $name,
                    ]);
                    
                    if (!$service->exists) {
                        $service->id = (string) \Illuminate\Support\Str::uuid();
                    }
                    
                    $service->description = $description;
                    $service->status = 'A';
                    $service->save();
                }
            }
        }
    }
}
