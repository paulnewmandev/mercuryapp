<?php

namespace App\Http\Controllers\Sales;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Service;
use App\Services\ElectronicBillingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly SeoMetaManagerContract $seoMetaManager,
        private readonly ElectronicBillingService $billingService
    ) {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Facturas',
            'description' => 'Gestiona las facturas de tu empresa.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Ventas')],
            ['label' => gettext('Facturas')],
        ];

        return view('Sales.Invoices', [
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
                'message' => gettext('Facturas obtenidas correctamente.'),
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
            ->where('document_type', 'FACTURA')
            ->with(['customer', 'salesperson']);

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
            ->through(fn (Invoice $invoice) => $this->transformInvoice($invoice));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Facturas obtenidas correctamente.'),
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

    public function show(Invoice $invoice): View
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        if (! $companyId) {
            $company = Company::query()->first();
            $companyId = $company?->id;
        }

        if ($companyId && $invoice->company_id !== $companyId) {
            abort(404);
        }

        if ($invoice->document_type !== 'FACTURA') {
            abort(404);
        }

        $invoice->load([
            'company',
            'branch',
            'customer',
            'salesperson',
            'items',
            'payments.paymentMethod'
        ]);

        // Load products and services for items
        $itemIds = $invoice->items->pluck('item_id')->unique()->toArray();
        $products = Product::whereIn('id', $itemIds)->get()->keyBy('id');
        $services = Service::whereIn('id', $itemIds)->get()->keyBy('id');

        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Ver Factura',
            'description' => 'Detalle de la factura.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Ventas'), 'url' => route('sales.invoices.index')],
            ['label' => gettext('Ver Factura')],
        ];

        return view('Sales.Invoices.Show', [
            'meta' => $meta,
            'breadcrumbItems' => $breadcrumbItems,
            'invoice' => $invoice,
            'products' => $products,
            'services' => $services,
        ]);
    }

    private function transformInvoice(Invoice $invoice, bool $detailed = false): array
    {
        $customer = $invoice->customer;
        $customerName = trim(collect([$customer?->first_name, $customer?->last_name])->filter()->implode(' '));
        if ($customerName === '') {
            $customerName = $customer?->business_name ?? '';
        }

        $issueDate = $invoice->issue_date instanceof Carbon
            ? $invoice->issue_date
            : ($invoice->issue_date ? Carbon::parse($invoice->issue_date) : null);

        $dueDate = $invoice->due_date instanceof Carbon
            ? $invoice->due_date
            : ($invoice->due_date ? Carbon::parse($invoice->due_date) : null);

        $workflowStatusLabels = [
            'draft' => gettext('Borrador'),
            'pending' => gettext('Pendiente'),
            'paid' => gettext('Pagada'),
            'cancelled' => gettext('Cancelada'),
        ];

        $data = [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'customer_name' => $customerName,
            'customer_document' => $customer?->document_number ?? '',
            'issue_date' => $issueDate?->toIso8601String(),
            'issue_date_formatted' => $issueDate?->format('Y-m-d'),
            'due_date' => $dueDate?->toIso8601String(),
            'due_date_formatted' => $dueDate?->translatedFormat('d M Y'),
            'subtotal' => (string) $invoice->subtotal,
            'subtotal_formatted' => 'USD ' . number_format($invoice->subtotal, 2, '.', ','),
            'tax_amount' => (string) $invoice->tax_amount,
            'tax_amount_formatted' => 'USD ' . number_format($invoice->tax_amount, 2, '.', ','),
            'total_amount' => (string) $invoice->total_amount,
            'total_amount_formatted' => number_format($invoice->total_amount, 2, '.', ','),
            'total_paid' => (string) $invoice->total_paid,
            'total_paid_formatted' => 'USD ' . number_format($invoice->total_paid, 2, '.', ','),
            'balance_due' => (string) $invoice->balance_due,
            'balance_due_formatted' => 'USD ' . number_format($invoice->balance_due, 2, '.', ','),
            'workflow_status' => $invoice->workflow_status,
            'workflow_status_label' => $workflowStatusLabels[$invoice->workflow_status] ?? $invoice->workflow_status,
            'notes' => $invoice->notes,
            'salesperson_name' => $invoice->salesperson?->full_name ?? '',
            'created_at' => optional($invoice->created_at)->toIso8601String(),
            'created_at_formatted' => optional($invoice->created_at)->translatedFormat('d M Y H:i'),
        ];

        if ($detailed) {
            $itemIds = $invoice->items->pluck('item_id')->unique()->toArray();
            $products = Product::whereIn('id', $itemIds)->get()->keyBy('id');
            $services = Service::whereIn('id', $itemIds)->get()->keyBy('id');

            $data['items'] = $invoice->items->map(function ($item) use ($products, $services) {
                $name = '';
                if ($item->item_type === 'product') {
                    $name = $products->get($item->item_id)?->name ?? 'Producto';
                } elseif ($item->item_type === 'service') {
                    $name = $services->get($item->item_id)?->name ?? 'Servicio';
                }

                return [
                    'item_id' => $item->item_id,
                    'item_type' => $item->item_type,
                    'name' => $name,
                    'quantity' => $item->quantity,
                    'unit_price' => (string) $item->unit_price,
                    'unit_price_formatted' => 'USD ' . number_format($item->unit_price, 2, '.', ','),
                    'subtotal' => (string) $item->subtotal,
                    'subtotal_formatted' => 'USD ' . number_format($item->subtotal, 2, '.', ','),
                ];
            })->toArray();

            $data['payments'] = $invoice->payments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'payment_method_name' => $payment->paymentMethod?->name ?? '',
                    'amount' => (string) $payment->amount,
                    'amount_formatted' => 'USD ' . number_format($payment->amount, 2, '.', ','),
                    'payment_date' => optional($payment->payment_date)->toIso8601String(),
                    'payment_date_formatted' => optional($payment->payment_date)->translatedFormat('d M Y'),
                    'reference' => $payment->reference,
                    'notes' => $payment->notes,
                ];
            })->toArray();
        }

        // Agregar información de facturación electrónica
        $sriStatusLabels = [
            'draft' => gettext('Borrador'),
            'received' => gettext('Recibida'),
            'authorized' => gettext('Autorizada'),
            'rejected' => gettext('Rechazada'),
            'cancelled' => gettext('Cancelada'),
        ];
        
        $data['sri_status'] = $invoice->sri_status;
        $data['sri_status_label'] = $sriStatusLabels[$invoice->sri_status] ?? $invoice->sri_status ?? '';
        $data['access_key'] = $invoice->access_key;
        $data['authorization_number'] = $invoice->authorization_number;
        $data['authorized_at'] = $invoice->authorized_at?->toIso8601String();
        $data['can_authorize'] = $invoice->document_type === 'FACTURA' && $invoice->sri_status !== 'authorized' && $invoice->sri_status !== 'cancelled';
        
        return $data;
    }
    
    public function authorize(Invoice $invoice): JsonResponse
    {
        try {
            // Verificar que sea una factura
            if ($invoice->document_type !== 'FACTURA') {
                return response()->json([
                    'status' => 'error',
                    'message' => gettext('Solo las facturas pueden ser autorizadas.'),
                ], 400);
            }
            
            // Cargar relaciones necesarias
            $invoice->load(['company', 'branch', 'customer', 'items']);
            
            // Verificar que tenga XML firmado
            if (!$invoice->xml_signed) {
                // Generar XML y firmar si no existe
                $this->billingService->processInvoice($invoice, false);
                $invoice->refresh();
            }
            
            // Autorizar en el SRI
            $result = $this->billingService->authorizeWithSRI($invoice);
            
            if ($result['success']) {
                // Actualizar factura
                $invoice->update([
                    'sri_status' => 'authorized',
                    'authorization_number' => $result['authorization_number'],
                    'authorized_at' => $result['authorization_date'],
                    'xml_authorized' => $result['xml_authorized'],
                ]);
                
                // Generar PDF
                $pdfPath = $this->billingService->generateInvoicePdf($invoice);
                
                // Enviar email al cliente
                try {
                    $this->sendInvoiceEmail($invoice);
                } catch (\Exception $e) {
                    Log::warning("Error al enviar email de factura: " . $e->getMessage());
                }
                
                return response()->json([
                    'status' => 'success',
                    'message' => gettext('Factura autorizada correctamente.'),
                    'data' => [
                        'authorization_number' => $result['authorization_number'],
                        'authorization_date' => $result['authorization_date']->toIso8601String(),
                        'pdf_path' => $pdfPath,
                    ],
                ]);
            } else {
                // Actualizar con errores
                $invoice->update([
                    'sri_status' => 'rejected',
                    'sri_errors' => $result['errors'],
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => gettext('Error al autorizar la factura en el SRI.'),
                    'data' => [
                        'errors' => $result['errors'],
                    ],
                ], 400);
            }
            
        } catch (\Exception $e) {
            Log::error("Error al autorizar factura: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => gettext('Error al autorizar la factura: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function pdf(Invoice $invoice)
    {
        try {
            $invoice->load(['company', 'branch', 'customer', 'items']);
            
            $itemIds = $invoice->items->pluck('item_id')->unique()->toArray();
            $products = Product::whereIn('id', $itemIds)->get()->keyBy('id');
            $services = Service::whereIn('id', $itemIds)->get()->keyBy('id');
            
            $pdfPath = $this->billingService->generateInvoicePdf($invoice);
            $fullPath = Storage::path($pdfPath);
            
            if (!file_exists($fullPath)) {
                abort(404);
            }
            
            return response()->file($fullPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="factura-' . $invoice->invoice_number . '.pdf"',
            ]);
        } catch (\Exception $e) {
            Log::error("Error al generar PDF de factura: " . $e->getMessage());
            abort(500);
        }
    }

    private function sendInvoiceEmail(Invoice $invoice): void
    {
        $customer = $invoice->customer;
        $company = $invoice->company;
        
        if (!$customer || !$customer->email) {
            Log::warning("No se puede enviar email: cliente sin email");
            return;
        }
        
        $invoice->load(['company', 'branch', 'customer', 'items']);
        
        $itemIds = $invoice->items->pluck('item_id')->unique()->toArray();
        $products = Product::whereIn('id', $itemIds)->get()->keyBy('id');
        $services = Service::whereIn('id', $itemIds)->get()->keyBy('id');
        
        // Generar PDF si no existe
        $pdfPath = $this->billingService->generateInvoicePdf($invoice);
        $pdfFullPath = Storage::path($pdfPath);
        
        // Obtener XML autorizado
        $xmlContent = $invoice->xml_authorized ?? $invoice->xml_signed;
        $xmlPath = null;
        if ($xmlContent) {
            $xmlPath = "invoices/{$invoice->id}/invoice.xml";
            Storage::put($xmlPath, $xmlContent);
        }
        
        Mail::send('Emails.Invoice', [
            'invoice' => $invoice,
            'company' => $company,
            'customer' => $customer,
        ], function ($message) use ($invoice, $company, $customer, $pdfFullPath, $xmlPath) {
            $message->to($customer->email, $customer->display_name)
                ->subject('Factura Electrónica ' . $invoice->invoice_number . ' - ' . ($company->name ?? 'MercuryApp'));
            
            // Adjuntar PDF
            if (file_exists($pdfFullPath)) {
                $message->attach($pdfFullPath, [
                    'as' => 'factura-' . $invoice->invoice_number . '.pdf',
                    'mime' => 'application/pdf',
                ]);
            }
            
            // Adjuntar XML
            if ($xmlPath && Storage::exists($xmlPath)) {
                $message->attach(Storage::path($xmlPath), [
                    'as' => 'factura-' . $invoice->invoice_number . '.xml',
                    'mime' => 'application/xml',
                ]);
            }
        });
    }
}
