<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::query()->get();

        foreach ($companies as $company) {
            $types = ExpenseType::query()
                ->where('company_id', $company->id)
                ->where('status', 'A')
                ->get();

            if ($types->isEmpty()) {
                continue;
            }

            Expense::query()
                ->where('company_id', $company->id)
                ->delete();

            $entries = collect([
                ['concept' => 'Pago de arriendo', 'description' => 'Pago mensual de arriendo del local.'],
                ['concept' => 'Servicios básicos', 'description' => 'Consumo de luz, agua y telecomunicaciones.'],
                ['concept' => 'Compra de repuestos', 'description' => 'Adquisición de repuestos para reparaciones urgentes.'],
                ['concept' => 'Publicidad digital', 'description' => 'Campaña activa en redes sociales y SEM.'],
                ['concept' => 'Capacitación del equipo', 'description' => 'Curso de actualización técnica para el personal.'],
                ['concept' => 'Transporte y logística', 'description' => 'Servicios de courier y transporte de equipos.'],
                ['concept' => 'Viáticos', 'description' => 'Viáticos por desplazamiento del equipo técnico.'],
                ['concept' => 'Papelería y suministros', 'description' => 'Compra de insumos de oficina y papelería.'],
                ['concept' => 'Seguros', 'description' => 'Pago de pólizas de seguros para equipos y locales.'],
                ['concept' => 'Soporte externo', 'description' => 'Servicios tercerizados de soporte especializado.'],
            ]);

            $dates = collect(range(0, 120))->map(fn ($days) => Carbon::now()->subDays($days));

            $records = collect(range(1, 20))->map(function (int $index) use ($company, $types, $entries, $dates): array {
                $type = $types->random();
                $entry = $entries->random();
                $movementDate = $dates->random();
                $amount = Arr::random([75.2, 98.4, 120.45, 156.9, 180.3, 210.0, 245.6, 289.4, 325.0, 410.8, 520.15]);

                return [
                    'id' => (string) Str::uuid(),
                    'company_id' => $company->id,
                    'expense_type_id' => $type->id,
                    'movement_date' => $movementDate->toDateString(),
                    'concept' => $entry['concept'],
                    'description' => $entry['description'],
                    'reference' => sprintf('EXP-%s-%03d', Arr::random(['AR', 'SB', 'LOG', 'CAP', 'MKT', 'SOP']), $index),
                    'amount_cents' => (int) round($amount * 100),
                    'currency_code' => 'USD',
                    'status' => Arr::random(['A', 'A', 'I']),
                    'created_at' => $movementDate->copy()->subDays(rand(1, 5)),
                    'updated_at' => $movementDate,
                ];
            });

            Expense::query()->insert($records->all());
        }
    }
}


