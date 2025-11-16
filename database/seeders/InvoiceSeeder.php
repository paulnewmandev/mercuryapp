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

            // Crear 10 facturas
            $this->createDocuments($company, $branches, $customers, $products, $services, $users, 'FACTURA', 10);

            // Crear 10 notas de venta (continuarán después de las facturas por sucursal)
            $this->createDocuments($company, $branches, $customers, $products, $services, $users, 'NOTA_DE_VENTA', 10);

            // Crear 10 cotizaciones
            $this->createDocuments($company, $branches, $customers, $products, $services, $users, 'COTIZACIONES', 10);
            
            // Actualizar secuenciales al final
            $this->updateSequences($company);
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
        int $count
    ): void {
        // Obtener o crear el secuencial para este tipo de documento
        $sequence = \App\Models\DocumentSequence::query()
            ->where('company_id', $company->id)
            ->where('document_type', $documentType)
            ->where('status', 'A')
            ->first();

        if (!$sequence) {
            $sequence = new \App\Models\DocumentSequence();
            $sequence->id = (string) Str::uuid();
            $sequence->company_id = $company->id;
            $sequence->name = match($documentType) {
                'FACTURA' => 'Facturas',
                'NOTA_DE_VENTA' => 'Notas de Venta',
                'COTIZACIONES' => 'Cotizaciones',
                default => 'Documentos',
            };
            $sequence->document_type = $documentType;
            $sequence->establishment_code = '001';
            $sequence->emission_point_code = '001';
            $sequence->current_sequence = 0;
            $sequence->status = 'A';
            $sequence->save();
        }

        // Distribuir documentos entre todas las sucursales de manera uniforme
        $branchCount = $branches->count();
        $documentsPerBranch = (int) ceil($count / $branchCount);
        
        // Contadores por sucursal para este tipo de documento
        $branchCounters = [];
        
        // Inicializar contadores para todas las sucursales
        foreach ($branches as $branch) {
            if ($documentType === 'NOTA_DE_VENTA') {
                // Para notas de venta, continuar después de las facturas en esta sucursal
                $facturasEnBranch = Invoice::where('company_id', $company->id)
                    ->where('branch_id', $branch->id)
                    ->where('document_type', 'FACTURA')
                    ->count();
                $branchCounters[$branch->id] = $facturasEnBranch;
            } elseif ($documentType === 'COTIZACIONES') {
                // Para cotizaciones, continuar después de facturas y notas de venta
                $facturasEnBranch = Invoice::where('company_id', $company->id)
                    ->where('branch_id', $branch->id)
                    ->where('document_type', 'FACTURA')
                    ->count();
                $notasEnBranch = Invoice::where('company_id', $company->id)
                    ->where('branch_id', $branch->id)
                    ->where('document_type', 'NOTA_DE_VENTA')
                    ->count();
                $branchCounters[$branch->id] = $facturasEnBranch + $notasEnBranch;
            } else {
                $branchCounters[$branch->id] = 0;
            }
        }
        
        $documentIndex = 0;
        foreach ($branches as $branchIndex => $branch) {
            // Calcular cuántos documentos crear para esta sucursal
            $documentsForThisBranch = min($documentsPerBranch, $count - $documentIndex);
            
            for ($i = 0; $i < $documentsForThisBranch; $i++) {
                $customer = $customers->random();
                $salesperson = $users->isNotEmpty() ? $users->random() : null;

                // Incrementar contador para esta sucursal
                $branchCounters[$branch->id]++;
                $sequenceNumber = $branchCounters[$branch->id];
                
                // Generar número de documento en formato correcto: 001-001-000000001
                $documentNumber = $this->generateDocumentNumber($sequence, $sequenceNumber);

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

    private function generateDocumentNumber(\App\Models\DocumentSequence $sequence, int $sequenceNumber): string
    {
        // Formato correcto: 001-001-000000001
        // Primer grupo (001): establishment_code - número del establecimiento
        // Segundo grupo (001): emission_point_code - número del facturero
        // Tercer grupo (000000001): secuencial de 9 dígitos - número del documento
        $establishmentCode = $sequence->establishment_code ?? '001';
        $emissionPointCode = $sequence->emission_point_code ?? '001';
        $sequentialNumber = str_pad((string) $sequenceNumber, 9, '0', STR_PAD_LEFT);
        
        return sprintf('%s-%s-%s', $establishmentCode, $emissionPointCode, $sequentialNumber);
    }

    private function updateSequences(Company $company): void
    {
        // Actualizar secuenciales basándose en los números más altos usados por sucursal
        $documentTypes = ['FACTURA', 'NOTA_DE_VENTA', 'COTIZACIONES'];
        
        foreach ($documentTypes as $documentType) {
            $sequence = \App\Models\DocumentSequence::query()
                ->where('company_id', $company->id)
                ->where('document_type', $documentType)
                ->where('status', 'A')
                ->first();
            
            if (!$sequence) {
                continue;
            }
            
            // Obtener el número más alto usado por sucursal
            $maxSequence = 0;
            $branches = $company->branches()->where('status', 'A')->get();
            
            foreach ($branches as $branch) {
                $invoices = Invoice::where('company_id', $company->id)
                    ->where('branch_id', $branch->id)
                    ->where('document_type', $documentType)
                    ->get();
                
                foreach ($invoices as $invoice) {
                    // Extraer el número secuencial del invoice_number (último grupo)
                    if (preg_match('/-\d{9}$/', $invoice->invoice_number, $matches)) {
                        $seqNum = (int) substr($matches[0], 1); // Remover el guion inicial
                        $maxSequence = max($maxSequence, $seqNum);
                    }
                }
            }
            
            // Actualizar el secuencial al número más alto
            if ($maxSequence > 0) {
                $sequence->current_sequence = $maxSequence;
                $sequence->save();
            }
        }
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
