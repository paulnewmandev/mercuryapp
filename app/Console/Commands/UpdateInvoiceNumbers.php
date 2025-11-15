<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\DocumentSequence;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateInvoiceNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoice:update-numbers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza todos los números de factura al nuevo formato 001-XXX-YYY';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Iniciando actualización de números de factura...');

        $companies = Company::query()->get();

        if ($companies->isEmpty()) {
            $this->warn('No se encontraron compañías.');
            return Command::FAILURE;
        }

        $totalUpdated = 0;

        foreach ($companies as $company) {
            $this->info("Procesando compañía: {$company->name}");

            // Obtener todas las facturas de la compañía ordenadas por fecha de creación
            $invoices = Invoice::query()
                ->where('company_id', $company->id)
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            if ($invoices->isEmpty()) {
                $this->warn("  No se encontraron facturas para esta compañía.");
                continue;
            }

            $this->info("  Encontradas {$invoices->count()} facturas.");

            // Obtener o crear el secuencial
            $sequence = DocumentSequence::query()
                ->where('company_id', $company->id)
                ->where('document_type', 'FACTURA')
                ->where('status', 'A')
                ->first();

            if (!$sequence) {
                $sequence = new DocumentSequence();
                $sequence->id = (string) \Illuminate\Support\Str::uuid();
                $sequence->company_id = $company->id;
                $sequence->name = 'Facturas';
                $sequence->document_type = 'FACTURA';
                $sequence->establishment_code = '001';
                $sequence->emission_point_code = '001';
                $sequence->current_sequence = 0;
                $sequence->status = 'A';
                $sequence->save();
                $this->info("  ✓ Secuencial de facturas creado.");
            }

            DB::beginTransaction();

            try {
                $updatedCount = 0;

                foreach ($invoices as $index => $invoice) {
                    $sequenceNumber = $index + 1;

                    // Calcular el nuevo formato: 001-XXX-YYY
                    $establishmentCode = $sequence->establishment_code ?? '001';
                    $secondGroup = (int) floor(($sequenceNumber - 1) / 999) + 1;
                    $thirdGroup = (($sequenceNumber - 1) % 999) + 1;
                    $newInvoiceNumber = sprintf('%s-%03d-%03d', $establishmentCode, $secondGroup, $thirdGroup);

                    // Solo actualizar si el número es diferente
                    if ($invoice->invoice_number !== $newInvoiceNumber) {
                        $oldNumber = $invoice->invoice_number;
                        $invoice->invoice_number = $newInvoiceNumber;
                        $invoice->save();
                        $updatedCount++;
                        
                        if ($this->output->isVerbose()) {
                            $this->line("    {$oldNumber} → {$newInvoiceNumber}");
                        }
                    }
                }

                // Actualizar el secuencial al número más alto
                $sequence->current_sequence = $invoices->count();
                $sequence->save();

                DB::commit();

                $this->info("  ✓ Actualizadas {$updatedCount} facturas.");
                $this->info("  ✓ Secuencial actualizado a {$sequence->current_sequence}.");
                $totalUpdated += $updatedCount;

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("  ✗ Error al actualizar facturas: {$e->getMessage()}");
                $this->error("  Stack trace: {$e->getTraceAsString()}");
                return Command::FAILURE;
            }
        }

        $this->info("\n✓ Proceso completado. Total de facturas actualizadas: {$totalUpdated}");

        return Command::SUCCESS;
    }
}
