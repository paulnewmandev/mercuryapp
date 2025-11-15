<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\DocumentSequence;
use App\Models\WorkshopOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateWorkshopOrderNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workshop:update-order-numbers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza todos los números de orden de trabajo al nuevo formato 001-XXX-YYY';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Iniciando actualización de números de orden de trabajo...');

        $companies = Company::query()->get();

        if ($companies->isEmpty()) {
            $this->warn('No se encontraron compañías.');
            return Command::FAILURE;
        }

        $totalUpdated = 0;

        foreach ($companies as $company) {
            $this->info("Procesando compañía: {$company->name}");

            // Obtener todas las órdenes de la compañía ordenadas por fecha de creación
            $orders = WorkshopOrder::query()
                ->where('company_id', $company->id)
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            if ($orders->isEmpty()) {
                $this->warn("  No se encontraron órdenes para esta compañía.");
                continue;
            }

            $this->info("  Encontradas {$orders->count()} órdenes.");

            // Obtener o crear el secuencial
            $sequence = DocumentSequence::query()
                ->where('company_id', $company->id)
                ->where('document_type', 'ORDEN_DE_TRABAJO')
                ->where('status', 'A')
                ->first();

            if (!$sequence) {
                $sequence = new DocumentSequence();
                $sequence->id = (string) \Illuminate\Support\Str::uuid();
                $sequence->company_id = $company->id;
                $sequence->name = 'Órdenes de Trabajo';
                $sequence->document_type = 'ORDEN_DE_TRABAJO';
                $sequence->establishment_code = '001';
                $sequence->emission_point_code = '001';
                $sequence->current_sequence = 0;
                $sequence->status = 'A';
                $sequence->save();
            }

            DB::beginTransaction();

            try {
                $updatedCount = 0;

                foreach ($orders as $index => $order) {
                    $sequenceNumber = $index + 1;

                    // Calcular el nuevo formato: 001-XXX-YYY
                    $secondGroup = (int) floor(($sequenceNumber - 1) / 999) + 1;
                    $thirdGroup = (($sequenceNumber - 1) % 999) + 1;
                    $newOrderNumber = sprintf('001-%03d-%03d', $secondGroup, $thirdGroup);

                    // Solo actualizar si el número es diferente
                    if ($order->order_number !== $newOrderNumber) {
                        $order->order_number = $newOrderNumber;
                        $order->save();
                        $updatedCount++;
                    }
                }

                // Actualizar el secuencial al número más alto
                $sequence->current_sequence = $orders->count();
                $sequence->save();

                DB::commit();

                $this->info("  ✓ Actualizadas {$updatedCount} órdenes.");
                $this->info("  ✓ Secuencial actualizado a {$sequence->current_sequence}.");
                $totalUpdated += $updatedCount;

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("  ✗ Error al actualizar órdenes: {$e->getMessage()}");
                return Command::FAILURE;
            }
        }

        $this->info("\n✓ Proceso completado. Total de órdenes actualizadas: {$totalUpdated}");

        return Command::SUCCESS;
    }
}
