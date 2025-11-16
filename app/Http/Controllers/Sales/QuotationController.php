<?php

namespace App\Http\Controllers\Sales;

use App\Contracts\SeoMetaManagerContract;
use App\Helpers\MailHelper;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\ItemPrice;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Dompdf\Dompdf;
use Dompdf\Options;

class QuotationController extends Controller
{
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Cotizaciones',
            'description' => 'Gestiona las cotizaciones de tu empresa.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Ventas')],
            ['label' => gettext('Cotizaciones')],
        ];

        return view('Sales.Quotations', [
            'meta' => $meta,
            'breadcrumbItems' => $breadcrumbItems,
        ]);
    }

    public function list(Request $request): JsonResponse
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
                'message' => gettext('Cotizaciones obtenidas correctamente.'),
                'data' => [
                    'items' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'per_page' => 10,
                        'last_page' => 1,
                        'total' => 0,
                        'from' => null,
                        'to' => null,
                    ],
                ],
            ]);
        }

        $sortBy = $request->string('sort_by', 'issue_date')->toString();
        $sortDirection = $request->string('sort_direction', 'desc')->toString();
        $perPage = (int) $request->integer('per_page', 10);
        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();

        $allowedSorts = ['invoice_number', 'customer_name', 'issue_date', 'total_amount', 'workflow_status', 'created_at'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'issue_date';
        }

        $sortDirection = Str::lower($sortDirection) === 'desc' ? 'desc' : 'asc';
        $allowedPerPage = [5, 10, 20, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $query = Invoice::query()
            ->where('company_id', $companyId)
            ->where('document_type', 'COTIZACIONES')
            ->with(['customer']);

        if ($search->isNotEmpty()) {
            $query->where(function ($q) use ($search): void {
                $q->whereRaw('LOWER(invoice_number) LIKE ?', ["%{$search}%"])
                    ->orWhereHas('customer', function ($customerQuery) use ($search): void {
                        $customerQuery->whereRaw('LOWER(CONCAT(first_name, " ", last_name)) LIKE ?', ["%{$search}%"])
                            ->orWhereRaw('LOWER(business_name) LIKE ?', ["%{$search}%"])
                            ->orWhereRaw('LOWER(document_number) LIKE ?', ["%{$search}%"]);
                    });
            });
        }

        if ($sortBy === 'customer_name') {
            $query->join('customers', 'invoices.customer_id', '=', 'customers.id')
                ->orderBy('customers.first_name', $sortDirection)
                ->select('invoices.*');
        } else {
            $query->orderBy("invoices.{$sortBy}", $sortDirection);
        }

        $paginator = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Invoice $invoice) => $this->transformQuotation($invoice));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Cotizaciones obtenidas correctamente.'),
            'data' => [
                'items' => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'last_page' => $paginator->lastPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ],
        ]);
    }

    public function create(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Nueva Cotización',
            'description' => 'Crea una nueva cotización.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Ventas'), 'url' => route('sales.quotations.index')],
            ['label' => gettext('Nueva Cotización')],
        ];

        return view('Sales.Quotations.Create', [
            'meta' => $meta,
            'breadcrumbItems' => $breadcrumbItems,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        if (! $companyId) {
            $company = Company::query()->first();
            $companyId = $company?->id;
        }

        if (! $companyId) {
            return redirect()->back()->withErrors(['error' => gettext('No se encontró la compañía.')]);
        }

        $validated = $request->validate([
            'customer_id' => ['required', 'uuid', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'uuid'],
            'items.*.item_type' => ['required', 'in:product,service'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            DB::beginTransaction();

            $branch = Branch::where('company_id', $companyId)->first();
            if (! $branch) {
                DB::rollBack();
                return redirect()->back()->withErrors(['error' => gettext('No se encontró una sucursal.')]);
            }

            // Generar número de cotización
            $sequence = DB::table('document_sequences')
                ->where('company_id', $companyId)
                ->where('document_type', 'COTIZACIONES')
                ->where('status', 'A')
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                DB::rollBack();
                return redirect()->back()->withErrors(['error' => gettext('No se encontró la secuencia de cotizaciones.')]);
            }

            $currentSequence = $sequence->current_sequence + 1;
            DB::table('document_sequences')
                ->where('id', $sequence->id)
                ->update(['current_sequence' => $currentSequence]);

            $quotationNumber = sprintf(
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

            // Crear cotización
            $quotation = Invoice::create([
                'id' => (string) Str::uuid(),
                'company_id' => $companyId,
                'branch_id' => $branch->id,
                'customer_id' => $validated['customer_id'],
                'salesperson_id' => $user->id,
                'invoice_number' => $quotationNumber,
                'document_type' => 'COTIZACIONES',
                'source' => 'manual',
                'source_id' => null,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'total_paid' => 0,
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
                'workflow_status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'status' => 'A',
            ]);

            // Crear items
            $itemIds = [];
            foreach ($validated['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id' => $quotation->id,
                    'item_id' => $item['item_id'],
                    'item_type' => $item['item_type'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    // 'subtotal' is a generated column, calculated automatically by MySQL
                ]);
                $itemIds[] = $item['item_id'];
            }

            DB::commit();

            // Enviar email si está marcado
            if ($request->has('send_email') && $request->boolean('send_email')) {
                $this->sendQuotationEmail($quotation, $itemIds);
            }

            return redirect()->route('sales.quotations.show', $quotation->id)
                ->with('success', gettext('Cotización creada correctamente.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => gettext('Error al crear la cotización: ') . $e->getMessage()]);
        }
    }

    public function edit(Invoice $quotation): View
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        if (! $companyId) {
            $company = Company::query()->first();
            $companyId = $company?->id;
        }

        if ($companyId && $quotation->company_id !== $companyId) {
            abort(404);
        }

        if ($quotation->document_type !== 'COTIZACIONES') {
            abort(404);
        }

        $quotation->load([
            'customer',
            'items'
        ]);

        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Editar Cotización',
            'description' => 'Edita la cotización.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Ventas'), 'url' => route('sales.quotations.index')],
            ['label' => gettext('Editar Cotización')],
        ];

        return view('Sales.Quotations.Edit', [
            'meta' => $meta,
            'breadcrumbItems' => $breadcrumbItems,
            'quotation' => $quotation,
        ]);
    }

    public function update(Request $request, Invoice $quotation): RedirectResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        if (! $companyId) {
            $company = Company::query()->first();
            $companyId = $company?->id;
        }

        if ($companyId && $quotation->company_id !== $companyId) {
            abort(404);
        }

        if ($quotation->document_type !== 'COTIZACIONES') {
            abort(404);
        }

        $validated = $request->validate([
            'customer_id' => ['required', 'uuid', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'uuid'],
            'items.*.item_type' => ['required', 'in:product,service'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'send_email' => ['nullable', 'boolean'],
        ]);

        try {
            DB::beginTransaction();

            // Calcular totales
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }
            $taxAmount = $subtotal * 0.15; // IVA 15%
            $totalAmount = $subtotal + $taxAmount;

            // Actualizar cotización
            $quotation->update([
                'customer_id' => $validated['customer_id'],
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Eliminar items existentes
            InvoiceItem::where('invoice_id', $quotation->id)->delete();

            // Crear nuevos items
            $itemIds = [];
            foreach ($validated['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id' => $quotation->id,
                    'item_id' => $item['item_id'],
                    'item_type' => $item['item_type'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    // 'subtotal' is a generated column, calculated automatically by MySQL
                ]);
                $itemIds[] = $item['item_id'];
            }

            DB::commit();

            // Enviar email si está marcado
            if ($request->has('send_email') && $request->boolean('send_email')) {
                $this->sendQuotationEmail($quotation->fresh(), $itemIds);
            }

            return redirect()->route('sales.quotations.show', $quotation->id)
                ->with('success', gettext('Cotización actualizada correctamente.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => gettext('Error al actualizar la cotización: ') . $e->getMessage()]);
        }
    }

    public function show(Invoice $quotation): View
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        if (! $companyId) {
            $company = Company::query()->first();
            $companyId = $company?->id;
        }

        if ($companyId && $quotation->company_id !== $companyId) {
            abort(404);
        }

        if ($quotation->document_type !== 'COTIZACIONES') {
            abort(404);
        }

        $quotation->load([
            'company',
            'branch',
            'customer',
            'salesperson',
            'items'
        ]);

        // Load products and services for items
        $itemIds = $quotation->items->pluck('item_id')->unique()->toArray();
        $products = Product::whereIn('id', $itemIds)->get()->keyBy('id');
        $services = Service::whereIn('id', $itemIds)->get()->keyBy('id');

        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Ver Cotización',
            'description' => 'Detalle de la cotización.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Ventas'), 'url' => route('sales.quotations.index')],
            ['label' => gettext('Ver Cotización')],
        ];

        return view('Sales.Quotations.Show', [
            'meta' => $meta,
            'breadcrumbItems' => $breadcrumbItems,
            'quotation' => $quotation,
            'products' => $products,
            'services' => $services,
        ]);
    }

    public function pdf(Invoice $quotation): Response
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        if (! $companyId) {
            $company = Company::query()->first();
            $companyId = $company?->id;
        }

        if ($companyId && $quotation->company_id !== $companyId) {
            abort(404);
        }

        if ($quotation->document_type !== 'COTIZACIONES') {
            abort(404);
        }

        $quotation->load([
            'company',
            'branch',
            'customer',
            'salesperson',
            'items'
        ]);

        // Load products and services for items
        $itemIds = $quotation->items->pluck('item_id')->unique()->toArray();
        $products = Product::whereIn('id', $itemIds)->get()->keyBy('id');
        $services = Service::whereIn('id', $itemIds)->get()->keyBy('id');

        $html = view('Sales.Quotations.PDF', [
            'quotation' => $quotation,
            'products' => $products,
            'services' => $services,
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->setDefaultFont('Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $output = $dompdf->output();
        if (! empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
            header_remove('Transfer-Encoding');
        }

        return response($output, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="cotizacion-'.$quotation->invoice_number.'.pdf"',
            'Content-Length' => strlen($output),
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

        $limit = (int) $request->integer('limit', 100);

        $query = Product::query()
            ->where('company_id', $companyId)
            ->where('status', 'A');

        if ($search->isNotEmpty()) {
            $query->where(function ($q) use ($search): void {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(sku) LIKE ?', ["%{$search}%"]);
            });
        }

        $products = $query->limit($limit)->get();

        $items = $products->map(function ($product) {
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

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $price,
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

        $limit = (int) $request->integer('limit', 100);

        $query = Service::query()
            ->where('company_id', $companyId)
            ->where('status', 'A');

        if ($search->isNotEmpty()) {
            $query->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
        }

        $services = $query->limit($limit)->get();

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

    private function sendQuotationEmail(Invoice $quotation, array $itemIds): void
    {
        $quotation->load('customer', 'items');
        $customer = $quotation->customer;
        if (! $customer || ! $customer->email) {
            return;
        }

        // Load products and services
        $products = Product::whereIn('id', $itemIds)->get()->keyBy('id');
        $services = Service::whereIn('id', $itemIds)->get()->keyBy('id');

        // Prepare items for email
        $items = [];
        foreach ($quotation->items as $item) {
            $name = '';
            if ($item->item_type === 'product') {
                $name = $products->get($item->item_id)?->name ?? 'Producto';
            } elseif ($item->item_type === 'service') {
                $name = $services->get($item->item_id)?->name ?? 'Servicio';
            }

            $items[] = [
                'name' => $name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->subtotal,
            ];
        }

        $customerName = $customer->display_name;
        $quotationNumber = $quotation->invoice_number;
        $issueDate = $quotation->issue_date->translatedFormat('d F, Y');
        $dueDate = $quotation->due_date ? $quotation->due_date->translatedFormat('d F, Y') : '-';
        $subtotal = $quotation->subtotal;
        $taxAmount = $quotation->tax_amount;
        $totalAmount = $quotation->total_amount;
        $notes = $quotation->notes;

        MailHelper::sendTemplate(
            $customer->email,
            gettext('Cotización') . ' - ' . $quotationNumber,
            'Emails.Quotation',
            [
                'customer_name' => $customerName,
                'quotation_number' => $quotationNumber,
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'items' => $items,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'notes' => $notes,
            ]
        );
    }

    private function transformQuotation(Invoice $invoice): array
    {
        $customer = $invoice->customer;
        $customerName = trim(collect([$customer?->first_name, $customer?->last_name])->filter()->implode(' '));
        if ($customerName === '') {
            $customerName = $customer?->business_name ?? '';
        }

        $issueDate = $invoice->issue_date instanceof Carbon
            ? $invoice->issue_date
            : ($invoice->issue_date ? Carbon::parse($invoice->issue_date) : null);

        $workflowStatusLabels = [
            'draft' => gettext('Borrador'),
            'approved' => gettext('Aprobada'),
            'rejected' => gettext('Rechazada'),
            'expired' => gettext('Expirada'),
        ];

        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'customer_name' => $customerName,
            'issue_date' => $issueDate?->toIso8601String(),
            'issue_date_formatted' => $issueDate?->format('Y-m-d'),
            'total_amount' => (string) $invoice->total_amount,
            'total_amount_formatted' => number_format($invoice->total_amount, 2, '.', ','),
            'workflow_status' => $invoice->workflow_status,
            'workflow_status_label' => $workflowStatusLabels[$invoice->workflow_status] ?? $invoice->workflow_status,
            'created_at' => optional($invoice->created_at)->toIso8601String(),
            'created_at_formatted' => optional($invoice->created_at)->translatedFormat('d M Y H:i'),
        ];
    }
}
