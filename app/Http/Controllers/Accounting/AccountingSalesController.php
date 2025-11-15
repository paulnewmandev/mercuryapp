<?php

namespace App\Http\Controllers\Accounting;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AccountingSalesController extends Controller
{
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Ventas',
            'description' => 'Consulta todas las facturas y notas de venta.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Contabilidad'), 'url' => route('accounting.receivables.index')],
            ['label' => gettext('Ventas')],
        ];

        return view('Accounting.Sales', [
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
                'message' => gettext('Ventas obtenidas correctamente.'),
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

        $allowedSorts = ['invoice_number', 'customer_name', 'issue_date', 'total_amount', 'workflow_status', 'document_type', 'created_at'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'issue_date';
        }

        $sortDirection = Str::lower($sortDirection) === 'desc' ? 'desc' : 'asc';
        $allowedPerPage = [5, 10, 20, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        // Obtener tanto facturas como notas de venta
        $query = Invoice::query()
            ->where('company_id', $companyId)
            ->whereIn('document_type', ['FACTURA', 'NOTA_DE_VENTA'])
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
            'message' => gettext('Ventas obtenidas correctamente.'),
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

        if (! in_array($invoice->document_type, ['FACTURA', 'NOTA_DE_VENTA'], true)) {
            abort(404);
        }

        // Redirigir a la vista correspondiente según el tipo de documento
        if ($invoice->document_type === 'FACTURA') {
            return redirect()->route('sales.invoices.show', $invoice);
        }

        return redirect()->route('sales.sales_notes.show', $invoice);
    }

    private function transformInvoice(Invoice $invoice): array
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
            'pending' => gettext('Pendiente'),
            'paid' => gettext('Pagada'),
            'cancelled' => gettext('Cancelada'),
        ];

        $documentTypeLabels = [
            'FACTURA' => gettext('Factura'),
            'NOTA_DE_VENTA' => gettext('Nota de Venta'),
        ];

        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'document_type' => $invoice->document_type,
            'document_type_label' => $documentTypeLabels[$invoice->document_type] ?? $invoice->document_type,
            'customer_name' => $customerName,
            'customer_document' => $customer?->document_number ?? '',
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

