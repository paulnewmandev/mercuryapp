<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\WorkshopAccessory;
use App\Models\WorkshopCategory;
use App\Models\WorkshopEquipment;
use App\Models\WorkshopOrder;
use App\Models\WorkshopOrderAdvance;
use App\Models\WorkshopState;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class WorkshopOrderSeeder extends Seeder
{
    public function run(): void
    {
        Company::query()->each(function (Company $company): void {
            $users = User::query()
                ->where('company_id', $company->id)
                ->where('status', 'A')
                ->get();

            if ($users->isEmpty()) {
                return;
            }

            $currentUser = $users->first();

            $customers = Customer::query()
                ->where('company_id', $company->id)
                ->where('status', 'A')
                ->get();

            if ($customers->isEmpty()) {
                return;
            }

            $categories = WorkshopCategory::query()
                ->where('company_id', $company->id)
                ->where('status', 'A')
                ->get();

            if ($categories->isEmpty()) {
                return;
            }

            $states = WorkshopState::query()
                ->whereHas('category', fn ($q) => $q->where('company_id', $company->id))
                ->where('status', 'A')
                ->get();

            $equipments = WorkshopEquipment::query()
                ->where('company_id', $company->id)
                ->where('status', 'A')
                ->get();

            if ($equipments->isEmpty()) {
                return;
            }

            $branches = Branch::query()
                ->where('company_id', $company->id)
                ->where('status', 'A')
                ->get();

            if ($branches->isEmpty()) {
                return;
            }

            $accessories = WorkshopAccessory::query()
                ->where('company_id', $company->id)
                ->where('status', 'A')
                ->get();

            $paymentMethods = PaymentMethod::query()
                ->where('status', 'A')
                ->get();

            $priorities = ['Normal', 'Alta', 'Urgente'];

            $createdCount = 0;
            $advanceCount = 0;

            // Asegurar que el secuencial de órdenes de trabajo existe y está configurado correctamente
            $sequence = \App\Models\DocumentSequence::query()
                ->where('company_id', $company->id)
                ->where('document_type', 'ORDEN_DE_TRABAJO')
                ->where('status', 'A')
                ->first();

            if (!$sequence) {
                // Crear el secuencial si no existe
                $sequence = new \App\Models\DocumentSequence();
                $sequence->id = (string) Str::uuid();
                $sequence->company_id = $company->id;
                $sequence->name = 'Órdenes de Trabajo';
                $sequence->document_type = 'ORDEN_DE_TRABAJO';
                $sequence->establishment_code = '001';
                $sequence->emission_point_code = '001';
                $sequence->current_sequence = 0;
                $sequence->status = 'A';
                $sequence->save();
            }

            for ($i = 0; $i < 10; $i++) {
                $category = $categories->random();
                $state = $states->where('category_id', $category->id)->isNotEmpty()
                    ? $states->where('category_id', $category->id)->random()
                    : null;
                $customer = $customers->random();
                $equipment = $equipments->random();
                $branch = $branches->random();

                $order = new WorkshopOrder();
                $order->id = (string) Str::uuid();
                $order->company_id = $company->id;
                $order->branch_id = $branch->id;
                $order->category_id = $category->id;
                $order->state_id = $state?->id;
                $order->customer_id = $customer->id;
                $order->equipment_id = $equipment->id;
                $order->responsible_user_id = $currentUser->id;
                $order->priority = $priorities[array_rand($priorities)];
                $order->work_summary = 'Reparación de equipo - Orden #' . ($i + 1);
                $order->work_description = 'Descripción detallada del trabajo a realizar para la orden #' . ($i + 1);
                $order->general_condition = 'El equipo presenta buen estado general';
                $order->diagnosis = (bool) rand(0, 1);
                $order->warranty = (bool) rand(0, 1);
                $order->equipment_password = rand(0, 1) ? 'password123' : null;
                $order->promised_at = now()->addDays(rand(1, 30));
                $order->budget_currency = 'USD';
                $order->budget_amount = rand(50, 500) + (rand(0, 99) / 100);
                $order->advance_currency = 'USD';
                $order->advance_amount = rand(0, 200) + (rand(0, 99) / 100);
                $order->status = 'A';
                
                // El order_number se generará automáticamente en el evento creating
                // con el formato correcto: 001-XXX-YYY
                $order->save();
                
                // Refrescar el modelo para obtener el order_number generado automáticamente
                $order->refresh();

                if ($accessories->isNotEmpty() && rand(0, 1)) {
                    $selectedAccessories = $accessories->random(rand(1, min(3, $accessories->count())));
                    $order->accessories()->attach($selectedAccessories->pluck('id')->toArray());
                }

                // Crear un abono para cada orden
                $advanceAmount = min(
                    rand(10, (int) ($order->budget_amount ?? 500)) + (rand(0, 99) / 100),
                    $order->budget_amount ?? 500
                );
                $paymentDate = Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59));

                $advance = new WorkshopOrderAdvance();
                $advance->id = (string) Str::uuid();
                $advance->company_id = $company->id;
                $advance->order_id = $order->id;
                $advance->currency = 'USD';
                $advance->amount = $advanceAmount;
                $advance->payment_date = $paymentDate;
                $advance->payment_method_id = $paymentMethods->isNotEmpty() && rand(0, 1) ? $paymentMethods->random()->id : null;
                $advance->reference = rand(0, 1) ? 'REF-' . strtoupper(substr(md5(uniqid()), 0, 8)) : null;
                $advance->notes = rand(0, 1) ? 'Abono para la orden ' . ($order->order_number ?? 'N/A') : null;
                $advance->status = 'A';
                $advance->save();

                $advanceCount++;
                $createdCount++;
            }

            $this->command?->info(sprintf(
                'Taller · %s -> %d órdenes creadas con %d abonos',
                $company->name,
                $createdCount,
                $advanceCount
            ));
        });
    }
}

