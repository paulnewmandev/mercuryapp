<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ReceivableCategory;
use App\Models\ReceivableEntry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ReceivableEntrySeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::query()->get();

        foreach ($companies as $company) {
            $categories = ReceivableCategory::query()
                ->where('company_id', $company->id)
                ->get();

            if ($categories->isEmpty()) {
                continue;
            }

            ReceivableEntry::query()->where('company_id', $company->id)->delete();

            $concepts = [
                'Venta diferida de servicio técnico',
                'Plan de mantenimiento anual',
                'Actualización de software corporativo',
                'Reparación de equipos facturada',
                'Instalación de accesorios premium',
                'Paquete de soporte empresarial',
                'Asesoría remota integral',
                'Venta a crédito de repuestos',
            ];

            for ($i = 0; $i < 20; $i++) {
                $category = $categories->random();
                $amountCents = random_int(15_000, 250_000);
                $movementDate = Carbon::now()->subDays(random_int(0, 90));
                $isCollected = (bool) random_int(0, 1);
                $collectedAt = $isCollected ? $movementDate->copy()->addDays(random_int(1, 15)) : null;

                ReceivableEntry::query()->create([
                    'id' => (string) Str::uuid(),
                    'company_id' => $company->id,
                    'receivable_category_id' => $category->id,
                    'movement_date' => $movementDate->toDateString(),
                    'concept' => $concepts[array_rand($concepts)],
                    'description' => 'Generado automáticamente para la demostración de MercuryApp.',
                    'amount_cents' => $amountCents,
                    'currency_code' => 'USD',
                    'reference' => 'RC-' . strtoupper(Str::random(6)),
                    'is_collected' => $isCollected,
                    'collected_at' => $collectedAt,
                ]);
            }
        }
    }
}

