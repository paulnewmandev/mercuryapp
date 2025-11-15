<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\DocumentSequence;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateInvoiceNumbersToNewFormat extends Command
{
    protected $signature = 'invoice:update-numbers-new-format';
    protected $description = 'Actualiza todos los números de factura al nuevo formato 001-001-000000001 (establecimiento-facturero-secuencial 9 dígitos)';

    public function handle(): int
    {
        $this->info('Iniciando actualización de números de factura al nuevo formato...');

        $companies = Company::query()->get();

        if ($companies->isEmpty()) {
            $this->warn('No se encontraron compañías.');
            return Command::FAILURE;
        }

        $totalUpdated = 0;

        foreach ($companies as $company) {
            $this->info("Procesando compañía: {$company->name}");

            $invoices = Invoice::query()
                ->where('company_id', $company->id)
                ->where('document_type', 'FACTURA')
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            if ($invoices->isEmpty()) {
                $this->warn("  No se encontraron facturas para esta compañía.");
                continue;
            }

            $this->info("  Encontradas {$invoices->count()} facturas.");

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

                    $establishmentCode = $sequence->establishment_code ?? '001';
                    $emissionPointCode = $sequence->emission_point_code ?? '001';
                    $sequentialNumber = str_pad((string) $sequenceNumber, 9, '0', STR_PAD_LEFT);
                    $newInvoiceNumber = sprintf('%s-%s-%s', $establishmentCode, $emissionPointCode, $sequentialNumber);

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

