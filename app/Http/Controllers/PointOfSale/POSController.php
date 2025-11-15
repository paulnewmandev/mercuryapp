<?php

namespace App\Http\Controllers\PointOfSale;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\ItemPrice;
use App\Models\PriceList;
use App\Models\ReceivableCategory;
use App\Models\ReceivableEntry;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkshopOrder;
use App\Services\ElectronicBillingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class POSController extends Controller
{
    public function __construct(
        private readonly SeoMetaManagerContract $seoMetaManager,
        private readonly ElectronicBillingService $billingService
    ) {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Punto de Venta',
            'description' => 'Sistema de punto de venta moderno y dinámico.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Punto de Venta')],
        ];

        $user = Auth::user();
        $companyId = $user?->company_id;

        if (! $companyId) {
            $company = Company::query()->first();
            $companyId = $company?->id;
        }

        return view('PointOfSale.POS', [
            'meta' => $meta,
            'breadcrumbItems' => $breadcrumbItems,
        ]);
    }

    public function searchCustomers(Request $request): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        if (! $companyId) {
            $company = Company::query()->first();
            $companyId = $company?->id;
        }

        if (! $companyId) {
            return response()->json([
                'status' => 'success',
                'message' => gettext('Clientes obtenidos correctamente.'),
                'data' => ['items' => []],
            ]);
        }

        $search = Str::of($request->string('search')->toString())
            ->trim()
            ->lower();

        $limit = (int) $request->integer('limit', 20);

        $query = Customer::query()
            ->where('company_id', $companyId)
            ->where('status', 'A');

        if ($search->isNotEmpty()) {
            $query->where(function ($q) use ($search): void {
                $q->whereRaw('LOWER(CONCAT(first_name, " ", last_name)) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(business_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(document_number) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
            });
        }

        $customers = $query->limit($limit)->get();

        $items = $customers->map(function ($customer) {
            return [
                'id' => $customer->id,
                'name' => $customer->display_name,
                'document_number' => $customer->document_number,
                'email' => $customer->email,
                'phone_number' => $customer->phone_number,
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => gettext('Clientes obtenidos correctamente.'),
            'data' => ['items' => $items],
        ]);
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        if (! $companyId) {
            $company = Company::query()->first();
            $companyId = $company?->id;
        }

        if (! $companyId) {
            return response()->json([
                'status' => 'success',
                'message' => gettext('Productos obtenidos correctamente.'),
                'data' => ['items' => []],
            ]);
        }

        $search = Str::of($request->string('search')->toString())
            ->trim()
            ->lower();

        $limit = (int) $request->integer('limit', 20);

        $query = Product::query()
            ->where('company_id', $companyId)
            ->where('status', 'A')
            ->where('show_in_pos', true);

        if ($search->isNotEmpty()) {
            $query->where(function ($q) use ($search): void {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(sku) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(barcode) LIKE ?', ["%{$search}%"]);
            });
        }

        $products = $query->with('category')->limit($limit)->get();

        $items = $products->map(function ($product) {
            // Obtener precio desde la lista de precios POS
            $price = 0;
            if ($product->price_list_pos_id) {
                $priceList = PriceList::find($product->price_list_pos_id);
                if ($priceList) {
                    $itemPrice = ItemPrice::where('price_list_id', $priceList->id)
                        ->where('item_id', $product->id)
                        ->where('item_type', 'product')
                        ->first();
                    if ($itemPrice) {
                        $price = (float) $itemPrice->value;
                    }
                }
            }
            
            // Obtener stock total del producto
            $stock = ProductStock::where('product_id', $product->id)
                ->sum('quantity');

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'image_url' => $product->image_url,
                'price' => $price,
                'stock' => (int) $stock,
                'type' => 'product',
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => gettext('Productos obtenidos correctamente.'),
            'data' => ['items' => $items],
        ]);
    }

    public function searchServices(Request $request): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        if (! $companyId) {
            $company = Company::query()->first();
            $companyId = $company?->id;
        }

        if (! $companyId) {
            return response()->json([
                'status' => 'success',
                'message' => gettext('Servicios obtenidos correctamente.'),
                'data' => ['items' => []],
            ]);
        }

        $search = Str::of($request->string('search')->toString())
            ->trim()
            ->lower();

        $limit = (int) $request->integer('limit', 20);

        $query = Service::query()
            ->where('company_id', $companyId)
            ->where('status', 'A');

        if ($search->isNotEmpty()) {
            $query->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
        }

        $services = $query->with('category')->limit($limit)->get();

        $items = $services->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'price' => (float) ($service->price ?? 0),
                'type' => 'service',
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => gettext('Servicios obtenidos correctamente.'),
            'data' => ['items' => $items],
        ]);
    }

    public function searchUsers(Request $request): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        if (! $companyId) {
            $company = Company::query()->first();
            $companyId = $company?->id;
        }

        if (! $companyId) {
            return response()->json([
                'status' => 'success',
                'message' => gettext('Usuarios obtenidos correctamente.'),
                'data' => ['items' => []],
            ]);
        }

        $search = Str::of($request->string('search')->toString())
            ->trim()
            ->lower();

        $limit = (int) $request->integer('limit', 20);

        $query = User::query()
            ->where('company_id', $companyId)
            ->where('status', 'A');

        if ($search->isNotEmpty()) {
            $query->where(function ($q) use ($search): void {
                $q->whereRaw('LOWER(CONCAT(first_name, " ", last_name)) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
            });
        }

        $users = $query->limit($limit)->get();

        $items = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->full_name,
                'email' => $user->email,
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => gettext('Usuarios obtenidos correctamente.'),
            'data' => ['items' => $items],
        ]);
    }

    public function searchWorkOrders(Request $request): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        if (! $companyId) {
            $company = Company::query()->first();
            $companyId = $company?->id;
        }

        if (! $companyId) {
            return response()->json([
                'status' => 'success',
                'message' => gettext('Órdenes obtenidas correctamente.'),
                'data' => ['items' => []],
            ]);
        }

        $search = Str::of($request->string('search')->toString())
            ->trim()
            ->lower();
        $customerId = $request->string('customer_id')->toString();

        $limit = (int) $request->integer('limit', 20);

        $query = WorkshopOrder::query()
            ->where('company_id', $companyId)
            ->where('status', 'A')
            ->with(['customer', 'items.product', 'services.service', 'advances']);

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        if ($search->isNotEmpty()) {
            $query->where(function ($q) use ($search): void {
                $q->whereRaw('LOWER(order_number) LIKE ?', ["%{$search}%"]);
            });
        }

        $orders = $query->limit($limit)->get();

        $items = $orders->map(function ($order) {
            // Calcular total de abonos
            $totalAdvances = (float) $order->advances->sum('amount');
            
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_id' => $order->customer_id,
                'customer_name' => $order->customer?->display_name ?? '',
                'total_cost' => (float) ($order->total_cost ?? 0),
                'total_paid' => (float) ($order->total_paid ?? 0),
                'total_advances' => $totalAdvances,
                'balance' => (float) ($order->balance ?? 0),
                'items' => $order->items->map(function ($item) {
                    return [
                        'item_id' => $item->product_id,
                        'item_type' => 'product',
                        'name' => $item->product?->name ?? 'Producto',
                        'sku' => $item->product?->sku ?? '',
                        'quantity' => $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'subtotal' => (float) $item->subtotal,
                    ];
                })->toArray(),
                'services' => $order->services->map(function ($service) {
                    return [
                        'item_id' => $service->service_id,
                        'item_type' => 'service',
                        'name' => $service->service?->name ?? 'Servicio',
                        'sku' => '',
                        'quantity' => $service->quantity,
                        'unit_price' => (float) $service->unit_price,
                        'subtotal' => (float) $service->subtotal,
                    ];
                })->toArray(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => gettext('Órdenes obtenidas correctamente.'),
            'data' => ['items' => $items],
        ]);
    }

    public function paymentMethods(): JsonResponse
    {
        // Métodos de pago estáticos
        $items = [
            ['id' => 'EFECTIVO', 'name' => 'EFECTIVO'],
            ['id' => 'TRANSFERENCIA', 'name' => 'TRANSFERENCIA'],
            ['id' => 'TARJETA', 'name' => 'TARJETA'],
        ];

        return response()->json([
            'status' => 'success',
            'message' => gettext('Métodos de pago obtenidos correctamente.'),
            'data' => ['payment_methods' => $items],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        if (! $companyId) {
            $company = Company::query()->first();
            $companyId = $company?->id;
        }

        if (! $companyId) {
            return response()->json([
                'status' => 'error',
                'message' => gettext('No se encontró la compañía.'),
            ], 400);
        }

        $validated = $request->validate([
            'customer_id' => ['required', 'uuid', 'exists:customers,id'],
            'salesperson_id' => ['nullable', 'uuid', 'exists:users,id'],
            'work_order_id' => ['nullable', 'uuid', 'exists:workshop_orders,id'],
            'document_type' => ['required', 'string', 'in:FACTURA,NOTA DE VENTA'],
            'work_order_advance' => ['nullable', 'numeric', 'min:0'],
            'payment_methods' => ['required', 'array', 'min:1'],
            'payment_methods.*.type' => ['required', 'string', 'in:EFECTIVO,TRANSFERENCIA,TARJETA'],
            'payment_methods.*.amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'uuid'],
            'items.*.item_type' => ['required', 'in:product,service'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            DB::beginTransaction();

            // Obtener sucursal (usar la primera disponible)
            $branch = Branch::where('company_id', $companyId)->first();

            if (! $branch) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => gettext('No se encontró una sucursal.'),
                ], 400);
            }

            // Generar número de documento según tipo
            $documentType = $validated['document_type'];
            $sequence = DB::table('document_sequences')
                ->where('company_id', $companyId)
                ->where('document_type', $documentType)
                ->where('status', 'A')
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => gettext('No se encontró la secuencia de documentos para ') . $documentType,
                ], 400);
            }

            $currentSequence = $sequence->current_sequence + 1;
            DB::table('document_sequences')
                ->where('id', $sequence->id)
                ->update(['current_sequence' => $currentSequence]);

            $invoiceNumber = sprintf(
                '%s-%s-%s-%06d',
                $sequence->establishment_code,
                $sequence->emission_point_code,
                date('Y'),
                $currentSequence
            );

            // Calcular totales
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }
            $taxAmount = $subtotal * 0.15; // IVA 15%
            $totalAmount = $subtotal + $taxAmount;
            
            // Aplicar abono de orden de trabajo si existe
            $workOrderAdvance = (float) ($validated['work_order_advance'] ?? 0);
            $remainingAfterAdvance = max(0, $totalAmount - $workOrderAdvance);
            
            // Calcular total pagado con métodos de pago
            $totalPaid = 0;
            foreach ($validated['payment_methods'] as $paymentMethod) {
                $totalPaid += (float) $paymentMethod['amount'];
            }
            
            // Determinar estado de la factura
            $workflowStatus = ($totalPaid >= $remainingAfterAdvance) ? 'paid' : 'pending';
            $totalPaidFinal = min($totalPaid, $remainingAfterAdvance); // No puede exceder el restante

            // Crear factura/nota de venta
            $invoice = Invoice::create([
                'id' => (string) Str::uuid(),
                'company_id' => $companyId,
                'branch_id' => $branch->id,
                'customer_id' => $validated['customer_id'],
                'salesperson_id' => $validated['salesperson_id'] ?? null,
                'invoice_number' => $invoiceNumber,
                'document_type' => $documentType,
                'source' => $validated['work_order_id'] ? 'workshop_order' : 'pos',
                'source_id' => $validated['work_order_id'],
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'total_paid' => $totalPaidFinal + $workOrderAdvance, // Total pagado incluyendo abono
                'issue_date' => now(),
                'workflow_status' => $workflowStatus,
                'status' => 'A',
                'notes' => $validated['notes'] ?? null,
            ]);
            
            // Crear pagos de factura (múltiples métodos)
            $remainingToPay = $remainingAfterAdvance;
            foreach ($validated['payment_methods'] as $paymentMethod) {
                $paymentAmount = min((float) $paymentMethod['amount'], $remainingToPay);
                if ($paymentAmount > 0) {
                    InvoicePayment::create([
                        'id' => (string) Str::uuid(),
                        'invoice_id' => $invoice->id,
                        'payment_method_id' => $paymentMethod['type'],
                        'amount' => $paymentAmount,
                        'payment_date' => now(),
                        'status' => 'A',
                    ]);
                    $remainingToPay -= $paymentAmount;
                }
            }
            
            // Si hay saldo pendiente, crear cuenta por cobrar
            $balanceDue = $remainingAfterAdvance - $totalPaidFinal;
            if ($balanceDue > 0) {
                // Buscar categoría de cuentas por cobrar (usar la primera disponible o crear una por defecto)
                $receivableCategory = ReceivableCategory::where('company_id', $companyId)
                    ->where('status', 'A')
                    ->first();
                
                if ($receivableCategory) {
                    ReceivableEntry::create([
                        'id' => (string) Str::uuid(),
                        'company_id' => $companyId,
                        'receivable_category_id' => $receivableCategory->id,
                        'movement_date' => now(),
                        'concept' => gettext('Factura pendiente: ') . $invoiceNumber,
                        'description' => gettext('Saldo pendiente de factura generada desde POS'),
                        'amount_cents' => (int) ($balanceDue * 100),
                        'currency_code' => 'USD',
                        'reference' => $invoiceNumber,
                        'is_collected' => false,
                    ]);
                }
            }

            // Crear items de factura
            foreach ($validated['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_id' => $item['item_id'],
                    'item_type' => $item['item_type'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            DB::commit();
            
            // Si es FACTURA, generar XML y PDF automáticamente
            if ($documentType === 'FACTURA') {
                try {
                    // Recargar invoice con relaciones
                    $invoice->load(['company', 'branch', 'customer', 'items']);
                    
                    // Generar XML y firmar (no autorizar automáticamente)
                    $result = $this->billingService->processInvoice($invoice, false);
                    
                    if ($result['success']) {
                        Log::info("XML y PDF generados correctamente para factura {$invoice->invoice_number}");
                    } else {
                        Log::warning("Error al generar XML/PDF de factura: " . ($result['error'] ?? 'Unknown error'));
                    }
                } catch (\Exception $e) {
                    // Log error pero no fallar la creación de la factura
                    Log::error("Error al generar XML/PDF de factura: " . $e->getMessage());
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => gettext('Venta procesada correctamente.'),
                'data' => [
                    'invoice' => [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'document_type' => $invoice->document_type,
                        'total_amount' => (string) $invoice->total_amount,
                        'total_paid' => (string) $invoice->total_paid,
                        'workflow_status' => $invoice->workflow_status,
                    ],
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => gettext('Error al procesar la venta: ') . $e->getMessage(),
            ], 500);
        }
    }
}

