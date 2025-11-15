<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Income;
use App\Models\IncomeType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class IncomeSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::query()->get();

        foreach ($companies as $company) {
            $types = IncomeType::query()
                ->where('company_id', $company->id)
                ->where('status', 'A')
                ->get();

            if ($types->isEmpty()) {
                continue;
            }

            Income::query()
                ->where('company_id', $company->id)
                ->delete();

            $concepts = collect([
                ['concept' => 'Servicio de reparación', 'description' => 'Cobro por reparación integral de dispositivo.'],
                ['concept' => 'Venta de accesorios', 'description' => 'Venta de accesorios originales y compatibles.'],
                ['concept' => 'Consultoría técnica', 'description' => 'Sesión de consultoría para optimización de equipos.'],
                ['concept' => 'Mantenimiento preventivo', 'description' => 'Plan de mantenimiento preventivo para equipos críticos.'],
                ['concept' => 'Capacitación express', 'description' => 'Taller express de uso avanzado de dispositivos.'],
                ['concept' => 'Soporte remoto', 'description' => 'Soporte técnico remoto para incidencias urgentes.'],
                ['concept' => 'Configuración inicial', 'description' => 'Servicio de configuración inicial de equipos nuevos.'],
                ['concept' => 'Venta de licencias', 'description' => 'Venta de licencias oficiales de software.'],
                ['concept' => 'Reacondicionamiento', 'description' => 'Reacondicionamiento y calibración de dispositivos.'],
                ['concept' => 'Diagnóstico avanzado', 'description' => 'Diagnóstico de hardware y software de alta complejidad.'],
            ]);

            $dates = collect(range(0, 120))->map(fn ($days) => Carbon::now()->subDays($days));

            $records = collect(range(1, 20))->map(function (int $index) use ($company, $types, $concepts, $dates): array {
                $type = $types->random();
                $concept = $concepts->random();
                $movementDate = $dates->random();
                $amount = Arr::random([89.9, 120.45, 150.25, 180.5, 210.3, 240.0, 275.6, 310.75, 360.4, 415.2, 490.0]);

                return [
                    'id' => (string) Str::uuid(),
                    'company_id' => $company->id,
                    'income_type_id' => $type->id,
                    'movement_date' => $movementDate->toDateString(),
                    'concept' => $concept['concept'],
                    'description' => $concept['description'],
                    'reference' => sprintf('INC-%s-%03d', Arr::random(['SRV', 'VEN', 'SUP', 'CFG']), $index),
                    'amount_cents' => (int) round($amount * 100),
                    'currency_code' => 'USD',
                    'status' => Arr::random(['A', 'A', 'I']),
                    'created_at' => $movementDate->copy()->subDays(rand(1, 5)),
                    'updated_at' => $movementDate,
                ];
            });

            Income::query()->insert($records->all());
        }
    }
}


