<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::query()->where('status', 'A')->get();

        if ($companies->isEmpty()) {
            $this->command->warn('No hay compañías activas. Ejecuta primero CompanySeeder.');
            return;
        }

        foreach ($companies as $company) {
            $branches = $company->branches()->where('status', 'A')->get();
            if ($branches->isEmpty()) {
                continue;
            }

            $customers = Customer::query()
                ->where('company_id', $company->id)
                ->where('status', 'A')
                ->get();

            if ($customers->isEmpty()) {
                continue;
            }

            $products = Product::query()
                ->where('company_id', $company->id)
                ->where('status', 'A')
                ->get();

            $services = Service::query()
                ->where('company_id', $company->id)
                ->where('status', 'A')
                ->get();

            $users = User::query()
                ->where('company_id', $company->id)
                ->where('status', 'A')
                ->get();

            if ($products->isEmpty() && $services->isEmpty()) {
                continue;
            }

            // Eliminar documentos existentes del mismo tipo para esta compañía
            Invoice::query()
                ->where('company_id', $company->id)
                ->whereIn('document_type', ['FACTURA', 'NOTA_DE_VENTA', 'COTIZACIONES'])
                ->delete();

            // Crear 10 facturas (usar contador inicial 0)
            $this->createDocuments($company, $branches, $customers, $products, $services, $users, 'FACTURA', 10, 0);

            // Crear 10 notas de venta (usar contador inicial 1000 para evitar conflictos)
            $this->createDocuments($company, $branches, $customers, $products, $services, $users, 'NOTA_DE_VENTA', 10, 1000);

            // Crear 10 cotizaciones (usar contador inicial 2000 para evitar conflictos)
            $this->createDocuments($company, $branches, $customers, $products, $services, $users, 'COTIZACIONES', 10, 2000);
        }
    }

    private function createDocuments(
        Company $company,
        $branches,
        $customers,
        $products,
        $services,
        $users,
        string $documentType,
        int $count,
        int $startSequence = 0
    ): void {
        // Distribuir documentos entre todas las sucursales de manera uniforme
        $branchCount = $branches->count();
        $documentsPerBranch = (int) ceil($count / $branchCount);
        
        $documentIndex = 0;
        foreach ($branches as $branchIndex => $branch) {
            // Calcular cuántos documentos crear para esta sucursal
            $documentsForThisBranch = min($documentsPerBranch, $count - $documentIndex);
            
            for ($i = 0; $i < $documentsForThisBranch; $i++) {
                $customer = $customers->random();
                $salesperson = $users->isNotEmpty() ? $users->random() : null;

                // Usar el índice del documento + startSequence como número de secuencia único
                $sequenceNumber = $startSequence + $documentIndex + 1;
                
                // Generar número de documento usando el código de la sucursal y el número de secuencia
                $documentNumber = $this->generateDocumentNumber($company->id, $documentType, $branch->id, $sequenceNumber);

                if (!$documentNumber) {
                    $this->command->warn("No se pudo generar número de documento para {$documentType}");
                    $documentIndex++;
                    continue;
                }
                
                // Seleccionar items (productos y/o servicios)
                $items = [];
                $subtotal = 0;

                // Agregar 1-3 productos si hay disponibles
                if ($products->isNotEmpty()) {
                    $productCount = rand(1, min(3, $products->count()));
                    $selectedProducts = $products->random($productCount);
                    foreach ($selectedProducts as $product) {
                        $quantity = rand(1, 5);
                        $unitPrice = (float) ($product->price_list_pos_id 
                            ? $this->getProductPrice($product->id, $product->price_list_pos_id)
                            : rand(10, 500));
                        $itemSubtotal = $quantity * $unitPrice;
                        $subtotal += $itemSubtotal;

                        $items[] = [
                            'item_id' => $product->id,
                            'item_type' => 'product',
                            'quantity' => $quantity,
                            'unit_price' => $unitPrice,
                            'subtotal' => $itemSubtotal,
                        ];
                    }
                }

                // Agregar 0-2 servicios si hay disponibles
                if ($services->isNotEmpty() && rand(0, 1)) {
                    $serviceCount = rand(0, min(2, $services->count()));
                    if ($serviceCount > 0) {
                        $selectedServices = $services->random($serviceCount);
                        foreach ($selectedServices as $service) {
                            $quantity = rand(1, 3);
                            $unitPrice = (float) ($service->price ?? rand(20, 300));
                            $itemSubtotal = $quantity * $unitPrice;
                            $subtotal += $itemSubtotal;

                            $items[] = [
                                'item_id' => $service->id,
                                'item_type' => 'service',
                                'quantity' => $quantity,
                                'unit_price' => $unitPrice,
                                'subtotal' => $itemSubtotal,
                            ];
                        }
                    }
                }

                if (empty($items)) {
                    $documentIndex++;
                    continue;
                }

                $taxAmount = $subtotal * 0.15; // IVA 15%
                $totalAmount = $subtotal + $taxAmount;

                // Determinar workflow_status según el tipo de documento
                $workflowStatus = match ($documentType) {
                    'FACTURA' => rand(0, 1) ? 'paid' : 'pending',
                    'NOTA_DE_VENTA' => rand(0, 1) ? 'paid' : 'pending',
                    'COTIZACIONES' => rand(0, 1) ? 'approved' : 'draft',
                    default => 'draft',
                };

                $totalPaid = ($workflowStatus === 'paid') ? $totalAmount : (rand(0, 1) ? $totalAmount * 0.5 : 0);

                // Crear factura/nota/cotización
                $invoice = new Invoice();
                $invoice->id = (string) Str::uuid();
                $invoice->company_id = $company->id;
                $invoice->branch_id = $branch->id;
                $invoice->invoice_number = $documentNumber;
                $invoice->customer_id = $customer->id;
                $invoice->salesperson_id = $salesperson?->id;
                $invoice->document_type = $documentType;
                $invoice->source = 'manual';
                $invoice->source_id = null;
                $invoice->subtotal = $subtotal;
                $invoice->tax_amount = $taxAmount;
                $invoice->total_amount = $totalAmount;
                $invoice->total_paid = $totalPaid;
                $invoice->issue_date = now()->subDays(rand(0, 90));
                $invoice->due_date = $invoice->issue_date->copy()->addDays(rand(15, 60));
                $invoice->workflow_status = $workflowStatus;
                $invoice->notes = null;
                $invoice->status = 'A';
                $invoice->save();

                // Crear items (subtotal es una columna calculada, no se inserta)
                foreach ($items as $itemData) {
                    InvoiceItem::query()->insert([
                        'invoice_id' => $invoice->id,
                        'item_id' => $itemData['item_id'],
                        'item_type' => $itemData['item_type'],
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                
                $documentIndex++;
            }
        }
    }

    private function generateDocumentNumber(string $companyId, string $documentType, ?string $branchId = null, int $branchCounter = 0): ?string
    {
        // Obtener códigos de la sucursal si existe
        $establishmentCode = '001';
        $emissionPointCode = '001';
        
        if ($branchId) {
            $branch = \App\Models\Branch::find($branchId);
            if ($branch && $branch->code) {
                // Mapear códigos de sucursal a códigos numéricos
                // 'MAT-QIT' -> '001', 'ESP-RIO' -> '002'
                $branchCodeMap = [
                    'MAT-QIT' => '001',
                    'ESP-RIO' => '002',
                ];
                
                if (isset($branchCodeMap[$branch->code])) {
                    $establishmentCode = $branchCodeMap[$branch->code];
                    $emissionPointCode = $branchCodeMap[$branch->code];
                } else {
                    // Si no está en el mapa, usar un hash del código de sucursal
                    $branchHash = abs(crc32($branch->code)) % 999;
                    $establishmentCode = str_pad((string)($branchHash + 1), 3, '0', STR_PAD_LEFT);
                    $emissionPointCode = str_pad((string)($branchHash + 1), 3, '0', STR_PAD_LEFT);
                }
            }
        }
        
        // Usar el contador de la sucursal como número de secuencia
        // Esto asegura que cada sucursal tenga números únicos
        $sequenceNumber = $branchCounter;
        
        return sprintf(
            '%s-%s-%s-%06d',
            $establishmentCode,
            $emissionPointCode,
            date('Y'),
            $sequenceNumber
        );
    }

    private function getProductPrice(string $productId, string $priceListId): float
    {
        $itemPrice = DB::table('item_prices')
            ->where('price_list_id', $priceListId)
            ->where('item_id', $productId)
            ->where('item_type', 'product')
            ->first();

        return $itemPrice ? (float) $itemPrice->value : 0;
    }
}
