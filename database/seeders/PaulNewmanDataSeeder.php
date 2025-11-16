<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerCategory;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkshopCategory;
use App\Models\WorkshopEquipment;
use App\Models\WorkshopOrder;
use App\Models\WorkshopOrderAdvance;
use App\Models\WorkshopOrderItem;
use App\Models\WorkshopOrderService;
use App\Models\WorkshopState;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaulNewmanDataSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->first();
        
        if (!$company) {
            $this->command->error('No hay compañías disponibles. Ejecuta primero CompanySeeder.');
            return;
        }

        $this->command->info('Creando datos para PAUL NEWMAN...');

        // 1. Crear o buscar el cliente PAUL NEWMAN
        $customer = Customer::query()->firstOrNew([
            'company_id' => $company->id,
            'document_number' => '1759474057',
        ]);

        if (!$customer->exists) {
            $customer->id = (string) Str::uuid();
            $category = CustomerCategory::query()
                ->where('company_id', $company->id)
                ->where('status', 'A')
                ->first();
            
            $customer->fill([
                'company_id' => $company->id,
                'category_id' => $category?->id,
                'customer_type' => 'INDIVIDUAL',
                'first_name' => 'PAUL',
                'last_name' => 'NEWMAN',
                'business_name' => null,
                'document_type' => 'CEDULA',
                'document_number' => '1759474057',
                'email' => 'paul.newman.dev@gmail.com',
                'phone_number' => '0999767814',
                'address' => 'AV. REPÚBLICA Y AMAZONAS, QUITO',
                'status' => 'A',
            ]);
            $customer->save();
            $this->command->info('✓ Cliente PAUL NEWMAN creado');
        } else {
            $this->command->info('✓ Cliente PAUL NEWMAN ya existe');
        }

        // Obtener datos necesarios
        $branch = Branch::query()
            ->where('company_id', $company->id)
            ->where('status', 'A')
            ->first();

        $user = User::query()
            ->where('company_id', $company->id)
            ->where('status', 'A')
            ->first();

        $categories = WorkshopCategory::query()
            ->where('company_id', $company->id)
            ->where('status', 'A')
            ->get();

        $equipments = WorkshopEquipment::query()
            ->where('company_id', $company->id)
            ->where('status', 'A')
            ->get();

        $products = Product::query()
            ->where('company_id', $company->id)
            ->where('status', 'A')
            ->get();

        $services = Service::query()
            ->where('company_id', $company->id)
            ->where('status', 'A')
            ->get();

        $paymentMethods = PaymentMethod::query()
            ->where('status', 'A')
            ->get();

        if ($categories->isEmpty() || $equipments->isEmpty() || !$branch || !$user) {
            $this->command->error('Faltan datos necesarios (categorías, equipos, sucursal o usuario).');
            return;
        }

        // 2. Crear órdenes de taller con diferentes combinaciones
        $this->command->info('Creando órdenes de taller...');
        $orderStates = WorkshopState::query()
            ->whereHas('category', fn($q) => $q->where('company_id', $company->id))
            ->where('status', 'A')
            ->get();

        $priorities = ['Normal', 'Alta', 'Urgente'];
        $orderCount = 0;

        // Obtener o crear secuencial de órdenes
        $orderSequence = \App\Models\DocumentSequence::query()
            ->where('company_id', $company->id)
            ->where('document_type', 'ORDEN_DE_TRABAJO')
            ->where('status', 'A')
            ->first();

        if (!$orderSequence) {
            $orderSequence = new \App\Models\DocumentSequence();
            $orderSequence->id = (string) Str::uuid();
            $orderSequence->company_id = $company->id;
            $orderSequence->name = 'Órdenes de Trabajo';
            $orderSequence->document_type = 'ORDEN_DE_TRABAJO';
            $orderSequence->establishment_code = '001';
            $orderSequence->emission_point_code = '001';
            $orderSequence->current_sequence = 0;
            $orderSequence->status = 'A';
            $orderSequence->save();
        }

        $currentSequence = $orderSequence->current_sequence;

        for ($i = 0; $i < 8; $i++) {
            $category = $categories->random();
            $state = $orderStates->where('category_id', $category->id)->isNotEmpty()
                ? $orderStates->where('category_id', $category->id)->random()
                : $orderStates->random();
            $equipment = $equipments->random();

            $currentSequence++;
            $secondGroup = (int) floor(($currentSequence - 1) / 999) + 1;
            $thirdGroup = (($currentSequence - 1) % 999) + 1;
            $orderNumber = sprintf('001-%03d-%03d', $secondGroup, $thirdGroup);

            $order = new WorkshopOrder();
            $order->id = (string) Str::uuid();
            $order->company_id = $company->id;
            $order->branch_id = $branch->id;
            $order->order_number = $orderNumber;
            $order->category_id = $category->id;
            $order->state_id = $state->id;
            $order->customer_id = $customer->id;
            $order->equipment_id = $equipment->id;
            $order->responsible_user_id = $user->id;
            $order->priority = $priorities[array_rand($priorities)];
            $order->work_summary = 'Orden de reparación #' . ($i + 1) . ' para ' . $customer->display_name;
            $order->work_description = 'Descripción detallada del trabajo a realizar para la orden #' . ($i + 1);
            $order->general_condition = 'El equipo presenta buen estado general';
            $order->diagnosis = (bool) rand(0, 1);
            $order->warranty = (bool) rand(0, 1);
            $order->equipment_password = rand(0, 1) ? 'password123' : null;
            $order->promised_at = Carbon::now()->addDays(rand(1, 30));
            $order->budget_currency = 'USD';
            $order->budget_amount = rand(50, 500) + (rand(0, 99) / 100);
            $order->advance_currency = 'USD';
            $order->advance_amount = rand(0, 200) + (rand(0, 99) / 100);
            $order->status = 'A';
            $order->save();

            // Agregar items a la orden
            if ($products->isNotEmpty() && rand(0, 1)) {
                $selectedProducts = $products->random(rand(1, min(3, $products->count())));
                foreach ($selectedProducts as $product) {
                    $item = new WorkshopOrderItem();
                    $item->id = (string) Str::uuid();
                    $item->company_id = $company->id;
                    $item->order_id = $order->id;
                    $item->product_id = $product->id;
                    $item->quantity = rand(1, 3);
                    $item->unit_price = (float) ($product->price ?? rand(20, 200));
                    $item->notes = 'Producto para orden ' . $orderNumber;
                    $item->status = 'A';
                    $item->save();
                }
            }

            // Agregar servicios a la orden
            if ($services->isNotEmpty() && rand(0, 1)) {
                $selectedServices = $services->random(rand(1, min(2, $services->count())));
                foreach ($selectedServices as $service) {
                    $orderService = new WorkshopOrderService();
                    $orderService->id = (string) Str::uuid();
                    $orderService->company_id = $company->id;
                    $orderService->order_id = $order->id;
                    $orderService->service_id = $service->id;
                    $orderService->quantity = rand(1, 2);
                    $orderService->unit_price = (float) ($service->price ?? rand(30, 150));
                    $orderService->notes = 'Servicio para orden ' . $orderNumber;
                    $orderService->status = 'A';
                    $orderService->save();
                }
            }

            // Crear abonos para algunas órdenes
            if (rand(0, 1)) {
                $advanceAmount = min(
                    rand(10, (int) ($order->budget_amount ?? 500)) + (rand(0, 99) / 100),
                    $order->budget_amount ?? 500
                );
                $paymentDate = Carbon::now()->subDays(rand(0, 30));

                $advance = new WorkshopOrderAdvance();
                $advance->id = (string) Str::uuid();
                $advance->company_id = $company->id;
                $advance->order_id = $order->id;
                $advance->currency = 'USD';
                $advance->amount = $advanceAmount;
                $advance->payment_date = $paymentDate;
                $advance->payment_method_id = $paymentMethods->isNotEmpty() ? $paymentMethods->random()->id : null;
                $advance->reference = 'REF-' . strtoupper(substr(md5(uniqid()), 0, 8));
                $advance->notes = 'Abono para orden ' . $orderNumber;
                $advance->status = 'A';
                $advance->save();
            }

            $orderCount++;
        }

        // Actualizar secuencial de órdenes
        $orderSequence->current_sequence = $currentSequence;
        $orderSequence->save();

        $this->command->info("✓ {$orderCount} órdenes de taller creadas");

        // 3. Crear facturas
        $this->command->info('Creando facturas...');
        
        // Obtener o crear secuencial de facturas
        $invoiceSequence = \App\Models\DocumentSequence::query()
            ->where('company_id', $company->id)
            ->where('document_type', 'FACTURA')
            ->where('status', 'A')
            ->first();

        if (!$invoiceSequence) {
            $invoiceSequence = new \App\Models\DocumentSequence();
            $invoiceSequence->id = (string) Str::uuid();
            $invoiceSequence->company_id = $company->id;
            $invoiceSequence->name = 'Facturas';
            $invoiceSequence->document_type = 'FACTURA';
            $invoiceSequence->establishment_code = '001';
            $invoiceSequence->emission_point_code = '001';
            $invoiceSequence->current_sequence = 0;
            $invoiceSequence->status = 'A';
            $invoiceSequence->save();
        }

        // Obtener el número de secuencia actual basado en facturas existentes
        $maxInvoice = Invoice::where('company_id', $company->id)
            ->where('branch_id', $branch->id)
            ->where('document_type', 'FACTURA')
            ->whereNotNull('invoice_number')
            ->orderBy('invoice_number', 'desc')
            ->first();

        $currentInvoiceSequence = 0;
        if ($maxInvoice && $maxInvoice->invoice_number) {
            // Extraer el número secuencial del invoice_number (formato: 001-001-000000001)
            if (preg_match('/001-001-(\d{9})/', $maxInvoice->invoice_number, $matches)) {
                $currentInvoiceSequence = (int) $matches[1];
            }
        }

        $invoiceCount = 0;

        for ($i = 0; $i < 6; $i++) {
            // Incrementar hasta encontrar un número disponible
            do {
                $currentInvoiceSequence++;
                $invoiceNumber = sprintf('001-001-%09d', $currentInvoiceSequence);
                $exists = Invoice::where('company_id', $company->id)
                    ->where('branch_id', $branch->id)
                    ->where('invoice_number', $invoiceNumber)
                    ->exists();
            } while ($exists);

            $subtotal = 0;
            $items = [];

            // Agregar productos
            if ($products->isNotEmpty()) {
                $selectedProducts = $products->random(rand(1, min(3, $products->count())));
                foreach ($selectedProducts as $product) {
                    $quantity = rand(1, 3);
                    $unitPrice = (float) ($product->price ?? rand(20, 200));
                    $itemSubtotal = $quantity * $unitPrice;
                    $subtotal += $itemSubtotal;

                    $items[] = [
                        'item_id' => $product->id,
                        'item_type' => 'product',
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                    ];
                }
            }

            // Agregar servicios
            if ($services->isNotEmpty() && rand(0, 1)) {
                $selectedServices = $services->random(rand(1, min(2, $services->count())));
                foreach ($selectedServices as $service) {
                    $quantity = rand(1, 2);
                    $unitPrice = (float) ($service->price ?? rand(30, 150));
                    $itemSubtotal = $quantity * $unitPrice;
                    $subtotal += $itemSubtotal;

                    $items[] = [
                        'item_id' => $service->id,
                        'item_type' => 'service',
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                    ];
                }
            }

            if (empty($items)) {
                continue;
            }

            $taxAmount = $subtotal * 0.12; // IVA 12%
            $totalAmount = $subtotal + $taxAmount;

            $invoice = new Invoice();
            $invoice->id = (string) Str::uuid();
            $invoice->company_id = $company->id;
            $invoice->branch_id = $branch->id;
            $invoice->invoice_number = $invoiceNumber;
            $invoice->document_type = 'FACTURA';
            $invoice->customer_id = $customer->id;
            $invoice->issue_date = Carbon::now()->subDays(rand(0, 60));
            $invoice->due_date = Carbon::parse($invoice->issue_date)->addDays(30);
            $invoice->subtotal = $subtotal;
            $invoice->tax_amount = $taxAmount;
            $invoice->total_amount = $totalAmount;
            $invoice->workflow_status = rand(0, 1) ? 'paid' : 'pending';
            $invoice->total_paid = ($invoice->workflow_status === 'paid') ? $totalAmount : (rand(0, 1) ? $totalAmount * 0.5 : 0);
            $invoice->source = 'manual';
            $invoice->source_id = null;
            $invoice->status = 'A';
            $invoice->salesperson_id = $user->id;
            $invoice->save();

            // Crear items de la factura
            foreach ($items as $itemData) {
                InvoiceItem::query()->insert([
                    'invoice_id' => $invoice->id,
                    'item_id' => $itemData['item_id'],
                    'item_type' => $itemData['item_type'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                ]);
            }

            // Crear pagos para algunas facturas
            if (rand(0, 1) && $paymentMethods->isNotEmpty()) {
                $paymentAmount = min(
                    rand(10, (int) $totalAmount) + (rand(0, 99) / 100),
                    $totalAmount
                );
                $paymentDate = Carbon::parse($invoice->issue_date)->addDays(rand(0, 15));

                DB::table('invoice_payments')->insert([
                    'id' => (string) Str::uuid(),
                    'invoice_id' => $invoice->id,
                    'payment_method_id' => $paymentMethods->random()->id,
                    'amount' => $paymentAmount,
                    'payment_date' => $paymentDate,
                    'reference' => 'PAY-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                    'notes' => 'Pago para factura ' . $invoiceNumber,
                    'created_at' => now(),
                ]);
            }

            $invoiceCount++;
        }

        // Actualizar secuencial de facturas
        $invoiceSequence->current_sequence = $currentInvoiceSequence;
        $invoiceSequence->save();

        $this->command->info("✓ {$invoiceCount} facturas creadas");

        // 4. Crear notas de venta
        $this->command->info('Creando notas de venta...');
        
        $salesNoteSequence = \App\Models\DocumentSequence::query()
            ->where('company_id', $company->id)
            ->where('document_type', 'NOTA_DE_VENTA')
            ->where('status', 'A')
            ->first();

        // Obtener el número de secuencia actual basado en notas de venta existentes
        $maxSalesNote = Invoice::where('company_id', $company->id)
            ->where('branch_id', $branch->id)
            ->where('document_type', 'NOTA_DE_VENTA')
            ->whereNotNull('invoice_number')
            ->orderBy('invoice_number', 'desc')
            ->first();

        $currentSalesNoteSequence = $currentInvoiceSequence;
        if ($maxSalesNote && $maxSalesNote->invoice_number) {
            if (preg_match('/001-001-(\d{9})/', $maxSalesNote->invoice_number, $matches)) {
                $currentSalesNoteSequence = (int) $matches[1];
            }
        }

        if (!$salesNoteSequence) {
            $salesNoteSequence = new \App\Models\DocumentSequence();
            $salesNoteSequence->id = (string) Str::uuid();
            $salesNoteSequence->company_id = $company->id;
            $salesNoteSequence->name = 'Notas de Venta';
            $salesNoteSequence->document_type = 'NOTA_DE_VENTA';
            $salesNoteSequence->establishment_code = '001';
            $salesNoteSequence->emission_point_code = '001';
            $salesNoteSequence->current_sequence = $currentSalesNoteSequence;
            $salesNoteSequence->status = 'A';
            $salesNoteSequence->save();
        }

        $salesNoteCount = 0;

        for ($i = 0; $i < 4; $i++) {
            // Incrementar hasta encontrar un número disponible
            do {
                $currentSalesNoteSequence++;
                $invoiceNumber = sprintf('001-001-%09d', $currentSalesNoteSequence);
                $exists = Invoice::where('company_id', $company->id)
                    ->where('branch_id', $branch->id)
                    ->where('invoice_number', $invoiceNumber)
                    ->exists();
            } while ($exists);

            $subtotal = 0;
            $items = [];

            if ($products->isNotEmpty()) {
                $selectedProducts = $products->random(rand(1, min(2, $products->count())));
                foreach ($selectedProducts as $product) {
                    $quantity = rand(1, 2);
                    $unitPrice = (float) ($product->price ?? rand(20, 200));
                    $itemSubtotal = $quantity * $unitPrice;
                    $subtotal += $itemSubtotal;

                    $items[] = [
                        'item_id' => $product->id,
                        'item_type' => 'product',
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                    ];
                }
            }

            if (empty($items)) {
                continue;
            }

            $taxAmount = $subtotal * 0.12;
            $totalAmount = $subtotal + $taxAmount;

            $invoice = new Invoice();
            $invoice->id = (string) Str::uuid();
            $invoice->company_id = $company->id;
            $invoice->branch_id = $branch->id;
            $invoice->invoice_number = $invoiceNumber;
            $invoice->document_type = 'NOTA_DE_VENTA';
            $invoice->customer_id = $customer->id;
            $invoice->issue_date = Carbon::now()->subDays(rand(0, 60));
            $invoice->due_date = Carbon::parse($invoice->issue_date)->addDays(30);
            $invoice->subtotal = $subtotal;
            $invoice->tax_amount = $taxAmount;
            $invoice->total_amount = $totalAmount;
            $invoice->workflow_status = rand(0, 1) ? 'paid' : 'pending';
            $invoice->total_paid = ($invoice->workflow_status === 'paid') ? $totalAmount : (rand(0, 1) ? $totalAmount * 0.5 : 0);
            $invoice->source = 'manual';
            $invoice->source_id = null;
            $invoice->status = 'A';
            $invoice->salesperson_id = $user->id;
            $invoice->save();

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

            $salesNoteCount++;
        }

        $salesNoteSequence->current_sequence = $currentSalesNoteSequence;
        $salesNoteSequence->save();

        $this->command->info("✓ {$salesNoteCount} notas de venta creadas");

        $this->command->info('');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('✓ Datos de PAUL NEWMAN creados exitosamente');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info("Cliente: {$customer->display_name} ({$customer->document_number})");
        $this->command->info("Órdenes de taller: {$orderCount}");
        $this->command->info("Facturas: {$invoiceCount}");
        $this->command->info("Notas de venta: {$salesNoteCount}");
        $this->command->info('═══════════════════════════════════════');
    }
}
