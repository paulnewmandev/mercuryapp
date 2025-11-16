<?php

namespace App\Mcp\Tools\Chatbot;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CustomerInvoicesQueryTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Consulta las facturas de un cliente, especialmente las pendientes de pago.
        Puede mostrar todas las facturas o solo las pendientes.
        Incluye número de factura, fecha, total, pagado, pendiente y fecha de vencimiento.
        Útil para consultas como: "¿Qué facturas tiene pendientes el cliente 1759474057?" o "Muéstrame las facturas de Juan"
    MARKDOWN;

    public function handle(Request $request): Response
    {
        if (!Auth::check()) {
            return Response::error('Usuario debe estar autenticado');
        }

        $arguments = $request->all();

        $user = Auth::user();
        $companyId = $user->company_id;

        if (!$companyId) {
            $companyId = \App\Models\Company::query()->first()?->id;
        }

        if (!$companyId) {
            return Response::error('No se encontró una compañía asociada');
        }

        $documentNumber = $arguments['document_number'] ?? null;
        $email = $arguments['email'] ?? null;
        $customerId = $arguments['customer_id'] ?? null;
        $onlyPending = $arguments['only_pending'] ?? true;

        if (!$documentNumber && !$email && !$customerId) {
            return Response::error('Debe proporcionar document_number, email o customer_id');
        }

        // Buscar cliente
        $query = Customer::where('company_id', $companyId);

        if ($customerId) {
            $query->where('id', $customerId);
        } elseif ($documentNumber) {
            $query->where('document_number', $documentNumber);
        } elseif ($email) {
            $query->where('email', $email);
        }

        $customer = $query->first();

        if (!$customer) {
            return Response::text("No se encontró un cliente con los criterios proporcionados.");
        }

        // Buscar facturas
        $invoicesQuery = Invoice::where('company_id', $companyId)
            ->where('customer_id', $customer->id);

        if ($onlyPending) {
            $invoicesQuery->whereColumn('total_amount', '>', 'total_paid');
        }

        $invoices = $invoicesQuery->with(['customer', 'branch'])
            ->orderBy('issue_date', 'desc')
            ->limit(50)
            ->get();

        if ($invoices->isEmpty()) {
            $status = $onlyPending ? 'pendientes' : '';
            return Response::text("El cliente no tiene facturas {$status}.");
        }

        // Generar HTML formateado
        $output = '<div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 mb-6">';
        $output .= '<h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Facturas/Compras del Cliente</h2>';
        $output .= '<div class="mb-4 space-y-2">';
        $output .= '<p class="text-gray-700 dark:text-gray-300"><strong>Cliente:</strong> ' . htmlspecialchars($customer->display_name) . '</p>';
        $output .= '<p class="text-gray-700 dark:text-gray-300"><strong>Documento:</strong> ' . htmlspecialchars($customer->document_type) . ' - ' . htmlspecialchars($customer->document_number) . '</p>';
        $output .= '<p class="text-gray-700 dark:text-gray-300"><strong>Filtro:</strong> ' . ($onlyPending ? 'Solo pendientes' : 'Todas') . '</p>';
        $output .= '<p class="text-gray-700 dark:text-gray-300"><strong>Total:</strong> ' . $invoices->count() . ' factura(s)</p>';
        $output .= '</div>';

        $totalPending = 0;
        $totalPaid = 0;
        $totalAmount = 0;

        $output .= '<div class="overflow-x-auto">';
        $output .= '<table class="w-full min-w-[900px] divide-y divide-gray-200 dark:divide-gray-700">';
        $output .= '<thead class="bg-gray-50 dark:bg-gray-900">';
        $output .= '<tr>';
        $output .= '<th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Factura</th>';
        $output .= '<th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tipo</th>';
        $output .= '<th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha</th>';
        $output .= '<th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>';
        $output .= '<th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pagado</th>';
        $output .= '<th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pendiente</th>';
        $output .= '<th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>';
        $output .= '</tr>';
        $output .= '</thead>';
        $output .= '<tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-gray-700">';

        foreach ($invoices as $invoice) {
            $balance = $invoice->total_amount - $invoice->total_paid;
            $totalPending += $balance;
            $totalPaid += $invoice->total_paid;
            $totalAmount += $invoice->total_amount;

            $statusClass = $balance > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400';
            $statusText = $balance > 0 ? 'Pendiente' : 'Pagada';

            $output .= '<tr class="hover:bg-gray-50 dark:hover:bg-gray-700">';
            $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">' . htmlspecialchars($invoice->invoice_number) . '</td>';
            $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">' . htmlspecialchars($invoice->document_type ?? 'FACTURA') . '</td>';
            $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">' . $invoice->issue_date->format('d/m/Y') . '</td>';
            $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">USD ' . number_format($invoice->total_amount, 2) . '</td>';
            $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-600 dark:text-gray-400">USD ' . number_format($invoice->total_paid, 2) . '</td>';
            $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold ' . ($balance > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400') . '">USD ' . number_format($balance, 2) . '</td>';
            $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm ' . $statusClass . '">' . $statusText . '</td>';
            $output .= '</tr>';
        }

        $output .= '</tbody>';
        $output .= '</table>';
        $output .= '</div>';

        // Resumen
        $output .= '<div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">';
        $output .= '<div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">';
        $output .= '<div class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-1">Total Facturado</div>';
        $output .= '<div class="text-2xl font-bold text-blue-700 dark:text-blue-400">USD ' . number_format($totalAmount, 2) . '</div>';
        $output .= '</div>';
        $output .= '<div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">';
        $output .= '<div class="text-sm font-semibold text-green-900 dark:text-green-300 mb-1">Total Pagado</div>';
        $output .= '<div class="text-2xl font-bold text-green-700 dark:text-green-400">USD ' . number_format($totalPaid, 2) . '</div>';
        $output .= '</div>';
        $output .= '<div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">';
        $output .= '<div class="text-sm font-semibold text-red-900 dark:text-red-300 mb-1">Total Pendiente</div>';
        $output .= '<div class="text-2xl font-bold text-red-700 dark:text-red-400">USD ' . number_format($totalPending, 2) . '</div>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';

        return Response::text($output);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'document_number' => $schema->string()
                ->nullable()
                ->description('Número de documento del cliente'),
            'email' => $schema->string()
                ->nullable()
                ->description('Email del cliente'),
            'customer_id' => $schema->string()
                ->nullable()
                ->description('ID del cliente'),
            'only_pending' => $schema->boolean()
                ->default(true)
                ->description('Si es true, solo muestra facturas pendientes de pago'),
        ];
    }
}

