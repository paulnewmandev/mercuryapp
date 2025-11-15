<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\WorkshopCategory;
use App\Models\WorkshopState;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WorkshopStateSeeder extends Seeder
{
    public function run(): void
    {
        $statesByCategory = [
            'Reparación' => [
                ['name' => 'Recibido', 'description' => 'Equipo recibido y registrado.'],
                ['name' => 'En diagnóstico', 'description' => 'En proceso de diagnóstico técnico.'],
                ['name' => 'Esperando repuesto', 'description' => 'Esperando llegada de repuesto.'],
                ['name' => 'En reparación', 'description' => 'Trabajo de reparación en curso.'],
                ['name' => 'Listo para entrega', 'description' => 'Reparación completada, listo para retiro.'],
                ['name' => 'Entregado', 'description' => 'Equipo entregado al cliente.'],
            ],
            'Mantenimiento' => [
                ['name' => 'Programado', 'description' => 'Mantenimiento programado.'],
                ['name' => 'En proceso', 'description' => 'Mantenimiento en ejecución.'],
                ['name' => 'Completado', 'description' => 'Mantenimiento finalizado.'],
            ],
            'Instalación' => [
                ['name' => 'Pendiente', 'description' => 'Instalación pendiente de realizar.'],
                ['name' => 'En proceso', 'description' => 'Instalación en curso.'],
                ['name' => 'Completado', 'description' => 'Instalación finalizada.'],
            ],
            'Diagnóstico' => [
                ['name' => 'Iniciado', 'description' => 'Diagnóstico iniciado.'],
                ['name' => 'En análisis', 'description' => 'Análisis técnico en proceso.'],
                ['name' => 'Finalizado', 'description' => 'Diagnóstico completado.'],
            ],
            'Actualización' => [
                ['name' => 'Pendiente', 'description' => 'Actualización pendiente.'],
                ['name' => 'En proceso', 'description' => 'Actualización en curso.'],
                ['name' => 'Completado', 'description' => 'Actualización finalizada.'],
            ],
        ];

        $companies = Company::query()->get();

        foreach ($companies as $company) {
            $categories = WorkshopCategory::query()
                ->where('company_id', $company->id)
                ->get()
                ->keyBy('name');

            foreach ($statesByCategory as $categoryName => $states) {
                $category = $categories->get($categoryName);
                
                if (!$category) {
                    continue;
                }

                foreach ($states as $stateData) {
                    $state = WorkshopState::query()->firstOrNew([
                        'company_id' => $company->id,
                        'category_id' => $category->id,
                        'name' => $stateData['name'],
                    ]);
                    
                    if (!$state->exists) {
                        $state->id = (string) Str::uuid();
                    }
                    
                    $state->description = $stateData['description'];
                    $state->status = 'A';
                    $state->save();
                }
            }
        }

        $this->command?->info('Estados de taller generados correctamente.');
    }
}

