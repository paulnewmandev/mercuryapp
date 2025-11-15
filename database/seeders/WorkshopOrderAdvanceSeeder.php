<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\PaymentMethod;
use App\Models\WorkshopOrder;
use App\Models\WorkshopOrderAdvance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class WorkshopOrderAdvanceSeeder extends Seeder
{
    public function run(): void
    {
        Company::query()->each(function (Company $company): void {
            $orders = WorkshopOrder::query()
                ->where('company_id', $company->id)
                ->where('status', 'A')
                ->get();

            if ($orders->isEmpty()) {
                return;
            }

            $paymentMethods = PaymentMethod::query()
                ->where('status', 'A')
                ->get();

            $createdCount = 0;

            // Crear 2-3 abonos por cada orden
            foreach ($orders->take(10) as $order) {
                $advanceCount = rand(1, 3);
                
                for ($i = 0; $i < $advanceCount; $i++) {
                    $amount = rand(10, 500) + (rand(0, 99) / 100);
                    $paymentDate = now()->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59));
                    
                    $advance = new WorkshopOrderAdvance();
                    $advance->id = (string) Str::uuid();
                    $advance->company_id = $company->id;
                    $advance->order_id = $order->id;
                    $advance->currency = 'USD';
                    $advance->amount = $amount;
                    $advance->payment_date = $paymentDate;
                    $advance->payment_method_id = $paymentMethods->isNotEmpty() && rand(0, 1) ? $paymentMethods->random()->id : null;
                    $advance->reference = rand(0, 1) ? 'REF-' . strtoupper(substr(md5(uniqid()), 0, 8)) : null;
                    $advance->notes = rand(0, 1) ? 'Notas del abono #' . ($i + 1) . ' para la orden ' . ($order->order_number ?? 'N/A') : null;
                    $advance->status = 'A';
                    $advance->save();
                    
                    $createdCount++;
                }
            }

            $this->command?->info(sprintf(
                'Taller · %s -> %d abonos creados',
                $company->name,
                $createdCount
            ));
        });
    }
}