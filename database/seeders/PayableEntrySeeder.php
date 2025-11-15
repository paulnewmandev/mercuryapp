<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\PayableCategory;
use App\Models\PayableEntry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PayableEntrySeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::query()->get();

        foreach ($companies as $company) {
            $categories = PayableCategory::query()
                ->where('company_id', $company->id)
                ->get();

            if ($categories->isEmpty()) {
                continue;
            }

            PayableEntry::query()->where('company_id', $company->id)->delete();

            $concepts = [
                'Compra de repuestos Apple',
                'Arrendamiento de local comercial',
                'Pago de servicios básicos',
                'Honorarios técnicos externos',
                'Campaña de marketing digital',
                'Licencias de software',
                'Compra de empaques y accesorios',
                'Servicios de limpieza y mantenimiento',
            ];

            for ($i = 0; $i < 20; $i++) {
                $category = $categories->random();
                $amountCents = random_int(10_000, 200_000);
                $movementDate = Carbon::now()->subDays(random_int(0, 90));
                $isPaid = (bool) random_int(0, 1);
                $paidAt = $isPaid ? $movementDate->copy()->addDays(random_int(1, 20)) : null;

                PayableEntry::query()->create([
                    'id' => (string) Str::uuid(),
                    'company_id' => $company->id,
                    'payable_category_id' => $category->id,
                    'movement_date' => $movementDate->toDateString(),
                    'concept' => $concepts[array_rand($concepts)],
                    'description' => 'Generado automáticamente para la demostración de MercuryApp.',
                    'amount_cents' => $amountCents,
                    'currency_code' => 'USD',
                    'reference' => 'CP-' . strtoupper(Str::random(6)),
                    'is_paid' => $isPaid,
                    'paid_at' => $paidAt,
                ]);
            }
        }
    }
}

