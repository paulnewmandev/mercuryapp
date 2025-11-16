<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\DocumentSequence;
use App\Models\Invoice;
use App\Models\WorkshopOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixSequentialNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:sequential-numbers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige los números secuenciales de facturas, notas de venta y órdenes de taller';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Iniciando corrección de números secuenciales...');

        $companies = Company::query()->get();

        if ($companies->isEmpty()) {
            $this->warn('No se encontraron compañías.');
            return Command::FAILURE;
        }

        foreach ($companies as $company) {
            $this->info("\nProcesando compañía: {$company->name}");

            // 1. Corregir facturas
            $this->fixInvoices($company);

            // 2. Corregir notas de venta
            $this->fixSalesNotes($company);

            // 3. Corregir órdenes de taller
            $this->fixWorkshopOrders($company);
        }

        $this->info("\n✓ Proceso completado.");

        return Command::SUCCESS;
    }

    private function fixInvoices(Company $company): void
    {
        $this->info('  Corrigiendo facturas...');

        // Obtener todas las facturas ordenadas por fecha de creación
        $invoices = Invoice::query()
            ->where('company_id', $company->id)
            ->where('document_type', 'FACTURA')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($invoices->isEmpty()) {
            $this->warn('    No se encontraron facturas.');
            return;
        }

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
        }

        DB::beginTransaction();

        try {
            $updatedCount = 0;
            
            // Agrupar por branch_id para mantener secuenciales por sucursal
            $branchCounters = [];

            foreach ($invoices as $invoice) {
                $branchId = $invoice->branch_id;
                
                // Inicializar contador para esta sucursal si no existe
                if (!isset($branchCounters[$branchId])) {
                    $branchCounters[$branchId] = 0;
                }
                
                $branchCounters[$branchId]++;
                $sequenceNumber = $branchCounters[$branchId];

                // Formato correcto: 001-001-000000001
                $establishmentCode = $sequence->establishment_code ?? '001';
                $emissionPointCode = $sequence->emission_point_code ?? '001';
                $sequentialNumber = str_pad((string) $sequenceNumber, 9, '0', STR_PAD_LEFT);
                
                $newInvoiceNumber = sprintf('%s-%s-%s', $establishmentCode, $emissionPointCode, $sequentialNumber);

                // Verificar si el número ya existe en esta sucursal (el índice único es por branch_id + invoice_number, sin importar el tipo)
                $numberExists = Invoice::where('company_id', $company->id)
                    ->where('branch_id', $branchId)
                    ->where('invoice_number', $newInvoiceNumber)
                    ->where('id', '!=', $invoice->id)
                    ->exists();

                // Si el número ya existe, incrementar hasta encontrar uno disponible
                while ($numberExists) {
                    $branchCounters[$branchId]++;
                    $sequenceNumber = $branchCounters[$branchId];
                    $sequentialNumber = str_pad((string) $sequenceNumber, 9, '0', STR_PAD_LEFT);
                    $newInvoiceNumber = sprintf('%s-%s-%s', $establishmentCode, $emissionPointCode, $sequentialNumber);
                    
                    $numberExists = Invoice::where('company_id', $company->id)
                        ->where('branch_id', $branchId)
                        ->where('invoice_number', $newInvoiceNumber)
                        ->where('id', '!=', $invoice->id)
                        ->exists();
                }

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

            // Actualizar el secuencial al número más alto global
            $maxSequence = max($branchCounters);
            $sequence->current_sequence = $maxSequence;
            $sequence->save();

            DB::commit();

            $this->info("    ✓ Actualizadas {$updatedCount} facturas.");
            $this->info("    ✓ Secuencial actualizado a {$sequence->current_sequence}.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("    ✗ Error al actualizar facturas: {$e->getMessage()}");
        }
    }

    private function fixSalesNotes(Company $company): void
    {
        $this->info('  Corrigiendo notas de venta...');

        // Obtener todas las notas de venta ordenadas por fecha de creación
        $salesNotes = Invoice::query()
            ->where('company_id', $company->id)
            ->where('document_type', 'NOTA_DE_VENTA')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($salesNotes->isEmpty()) {
            $this->warn('    No se encontraron notas de venta.');
            return;
        }

        // Obtener o crear el secuencial
        $sequence = DocumentSequence::query()
            ->where('company_id', $company->id)
            ->where('document_type', 'NOTA_DE_VENTA')
            ->where('status', 'A')
            ->first();

        if (!$sequence) {
            $sequence = new DocumentSequence();
            $sequence->id = (string) \Illuminate\Support\Str::uuid();
            $sequence->company_id = $company->id;
            $sequence->name = 'Notas de Venta';
            $sequence->document_type = 'NOTA_DE_VENTA';
            $sequence->establishment_code = '001';
            $sequence->emission_point_code = '001';
            $sequence->current_sequence = 0;
            $sequence->status = 'A';
            $sequence->save();
        }

        DB::beginTransaction();

        try {
            $updatedCount = 0;
            
            // Agrupar por branch_id para mantener secuenciales por sucursal
            $branchCounters = [];
            
            // Primero, verificar números existentes por sucursal para evitar duplicados
            $existingNumbers = [];
            foreach ($salesNotes as $salesNote) {
                $branchId = $salesNote->branch_id;
                if (!isset($existingNumbers[$branchId])) {
                    $existingNumbers[$branchId] = [];
                }
                if ($salesNote->invoice_number) {
                    $existingNumbers[$branchId][] = $salesNote->invoice_number;
                }
            }

            foreach ($salesNotes as $salesNote) {
                $branchId = $salesNote->branch_id;
                
                // Inicializar contador para esta sucursal si no existe
                if (!isset($branchCounters[$branchId])) {
                    $branchCounters[$branchId] = 0;
                }
                
                $branchCounters[$branchId]++;
                $sequenceNumber = $branchCounters[$branchId];

                // Formato correcto: 001-001-000000001
                $establishmentCode = $sequence->establishment_code ?? '001';
                $emissionPointCode = $sequence->emission_point_code ?? '001';
                $sequentialNumber = str_pad((string) $sequenceNumber, 9, '0', STR_PAD_LEFT);
                
                $newInvoiceNumber = sprintf('%s-%s-%s', $establishmentCode, $emissionPointCode, $sequentialNumber);

                // Verificar si el número ya existe en esta sucursal (el índice único es por branch_id + invoice_number, sin importar el tipo)
                $numberExists = Invoice::where('company_id', $company->id)
                    ->where('branch_id', $branchId)
                    ->where('invoice_number', $newInvoiceNumber)
                    ->where('id', '!=', $salesNote->id)
                    ->exists();

                // Si el número ya existe, incrementar hasta encontrar uno disponible
                while ($numberExists) {
                    $branchCounters[$branchId]++;
                    $sequenceNumber = $branchCounters[$branchId];
                    $sequentialNumber = str_pad((string) $sequenceNumber, 9, '0', STR_PAD_LEFT);
                    $newInvoiceNumber = sprintf('%s-%s-%s', $establishmentCode, $emissionPointCode, $sequentialNumber);
                    
                    $numberExists = Invoice::where('company_id', $company->id)
                        ->where('branch_id', $branchId)
                        ->where('invoice_number', $newInvoiceNumber)
                        ->where('id', '!=', $salesNote->id)
                        ->exists();
                }

                // Solo actualizar si el número es diferente
                if ($salesNote->invoice_number !== $newInvoiceNumber) {
                    $oldNumber = $salesNote->invoice_number;
                    $salesNote->invoice_number = $newInvoiceNumber;
                    $salesNote->save();
                    $updatedCount++;

                    if ($this->output->isVerbose()) {
                        $this->line("    {$oldNumber} → {$newInvoiceNumber}");
                    }
                }
            }

            // Actualizar el secuencial al número más alto global
            $maxSequence = max($branchCounters);
            $sequence->current_sequence = $maxSequence;
            $sequence->save();

            DB::commit();

            $this->info("    ✓ Actualizadas {$updatedCount} notas de venta.");
            $this->info("    ✓ Secuencial actualizado a {$sequence->current_sequence}.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("    ✗ Error al actualizar notas de venta: {$e->getMessage()}");
        }
    }

    private function fixWorkshopOrders(Company $company): void
    {
        $this->info('  Corrigiendo órdenes de taller...');

        // Obtener todas las órdenes ordenadas por fecha de creación
        $orders = WorkshopOrder::query()
            ->where('company_id', $company->id)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($orders->isEmpty()) {
            $this->warn('    No se encontraron órdenes de taller.');
            return;
        }

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

                // Formato correcto: 001-XXX-YYY
                // Segundo grupo: incrementa cada 999 órdenes (001, 002, 003...)
                // Tercer grupo: va de 001 a 999, luego se resetea a 001
                $secondGroup = (int) floor(($sequenceNumber - 1) / 999) + 1;
                $thirdGroup = (($sequenceNumber - 1) % 999) + 1;
                
                $newOrderNumber = sprintf('001-%03d-%03d', $secondGroup, $thirdGroup);

                // Solo actualizar si el número es diferente o está vacío
                if (!$order->order_number || $order->order_number !== $newOrderNumber) {
                    $oldNumber = $order->order_number ?: 'SIN NÚMERO';
                    $order->order_number = $newOrderNumber;
                    $order->save();
                    $updatedCount++;

                    if ($this->output->isVerbose()) {
                        $this->line("    {$oldNumber} → {$newOrderNumber}");
                    }
                }
            }

            // Actualizar el secuencial al número más alto
            $sequence->current_sequence = $orders->count();
            $sequence->save();

            DB::commit();

            $this->info("    ✓ Actualizadas {$updatedCount} órdenes de taller.");
            $this->info("    ✓ Secuencial actualizado a {$sequence->current_sequence}.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("    ✗ Error al actualizar órdenes de taller: {$e->getMessage()}");
        }
    }
}

