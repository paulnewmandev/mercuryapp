<?php

namespace App\Mcp\Tools\Chatbot;

use App\Models\Invoice;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class InvoiceQueryTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Obtiene TODOS los detalles completos de una factura o nota de venta por su número.
        Incluye información completa: cliente, vendedor, items (productos y servicios), pagos, totales, fechas, estado, etc.
        Útil para consultas como: "dame los detalles de la factura 001-001-000000032" o "muéstrame todos los detalles de la factura X"
    MARKDOWN;

    public function handle(Request $request): Response
    {
        if (!Auth::check()) {
            return Response::error('Usuario debe estar autenticado');
        }

        $arguments = $request->all();
        $invoiceNumber = $arguments['invoice_number'] ?? null;
        $invoiceId = $arguments['invoice_id'] ?? null;
        $customerDocument = $arguments['customer_document'] ?? null;
        $dateFrom = $arguments['date_from'] ?? null;
        $dateTo = $arguments['date_to'] ?? null;

        if (!$invoiceNumber && !$invoiceId && !$customerDocument && !$dateFrom) {
            return Response::error('Debe proporcionar invoice_number, invoice_id, customer_document o date_from');
        }

        // Si solo se proporciona date_from y es hoy, establecer date_to también
        if ($dateFrom && !$dateTo && $dateFrom === now()->format('Y-m-d')) {
            $dateTo = now()->format('Y-m-d');
        }

        $user = Auth::user();
        $companyId = $user->company_id;

        if (!$companyId) {
            $companyId = \App\Models\Company::query()->first()?->id;
        }

        if (!$companyId) {
            return Response::error('No se encontró una compañía asociada');
        }

        $query = Invoice::where('company_id', $companyId);

        if ($invoiceNumber) {
            $query->where('invoice_number', $invoiceNumber);
        }
        if ($invoiceId) {
            $query->where('id', $invoiceId);
        }
        if ($customerDocument) {
            $query->whereHas('customer', function($q) use ($customerDocument) {
                $q->where('document_number', $customerDocument);
            });
        }
        if ($dateFrom) {
            $query->whereDate('issue_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('issue_date', '<=', $dateTo);
        }

        // Si se busca por número específico, mostrar detalles completos
        if ($invoiceNumber || $invoiceId) {
            $invoices = $query->with([
                'customer',
                'salesperson',
                'branch',
                'items.product',
                'items.service',
                'payments',
            ])
            ->orderBy('issue_date', 'desc')
            ->limit(1)
            ->get();

            if ($invoices->isEmpty()) {
                return Response::text('<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">No se encontraron facturas con los criterios proporcionados.</p></div>');
            }

            $output = '';
            foreach ($invoices as $invoice) {
            $balance = $invoice->total_amount - $invoice->total_paid;
            $daysOverdue = $invoice->due_date ? now()->diffInDays($invoice->due_date, false) : 0;
            
            $output .= '<div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 mb-6">';
            $output .= '<h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">' . htmlspecialchars($invoice->document_type ?? 'FACTURA') . ': ' . htmlspecialchars($invoice->invoice_number ?? 'Sin número') . '</h2>';
            
            // Información básica
            $output .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">';
            
            // Información del documento
            $output .= '<div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">';
            $output .= '<div class="font-semibold text-blue-900 dark:text-blue-300 mb-2">Información del Documento</div>';
            $output .= '<div class="space-y-1 text-sm text-blue-700 dark:text-blue-400">';
            $output .= '<div><strong>Tipo:</strong> ' . htmlspecialchars($invoice->document_type ?? 'FACTURA') . '</div>';
            $output .= '<div><strong>Número:</strong> ' . htmlspecialchars($invoice->invoice_number ?? 'N/A') . '</div>';
            $output .= '<div><strong>Fecha de emisión:</strong> ' . ($invoice->issue_date ? $invoice->issue_date->format('d/m/Y') : 'N/A') . '</div>';
            if ($invoice->due_date) {
                $output .= '<div><strong>Fecha de vencimiento:</strong> ' . $invoice->due_date->format('d/m/Y');
                if ($daysOverdue > 0) {
                    $output .= ' <span class="text-red-600 dark:text-red-400">(' . $daysOverdue . ' días vencidos)</span>';
                }
                $output .= '</div>';
            }
            $output .= '<div><strong>Estado:</strong> <span class="' . ($balance > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400') . ' font-semibold">' . ($balance > 0 ? 'Pendiente' : 'Pagada') . '</span></div>';
            if ($invoice->workflow_status) {
                $output .= '<div><strong>Estado workflow:</strong> ' . htmlspecialchars($invoice->workflow_status) . '</div>';
            }
            $output .= '</div>';
            $output .= '</div>';
            
            // Información del cliente
            if ($invoice->customer) {
                $output .= '<div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">';
                $output .= '<div class="font-semibold text-green-900 dark:text-green-300 mb-2">Cliente</div>';
                $output .= '<div class="space-y-1 text-sm text-green-700 dark:text-green-400">';
                $output .= '<div><strong>Nombre:</strong> ' . htmlspecialchars($invoice->customer->display_name ?? $invoice->customer->name ?? 'N/A') . '</div>';
                $output .= '<div><strong>Documento:</strong> ' . htmlspecialchars($invoice->customer->document_type ?? '') . ' - ' . htmlspecialchars($invoice->customer->document_number ?? 'N/A') . '</div>';
                if ($invoice->customer->email) {
                    $output .= '<div><strong>Email:</strong> ' . htmlspecialchars($invoice->customer->email) . '</div>';
                }
                if ($invoice->customer->phone_number) {
                    $output .= '<div><strong>Teléfono:</strong> ' . htmlspecialchars($invoice->customer->phone_number) . '</div>';
                }
                if ($invoice->customer->address) {
                    $output .= '<div><strong>Dirección:</strong> ' . htmlspecialchars($invoice->customer->address) . '</div>';
                }
                $output .= '</div>';
                $output .= '</div>';
            }
            
            // Información del vendedor
            if ($invoice->salesperson) {
                $output .= '<div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">';
                $output .= '<div class="font-semibold text-purple-900 dark:text-purple-300 mb-2">Vendedor</div>';
                $output .= '<div class="text-sm text-purple-700 dark:text-purple-400">';
                $output .= htmlspecialchars($invoice->salesperson->first_name ?? '') . ' ' . htmlspecialchars($invoice->salesperson->last_name ?? '');
                if ($invoice->salesperson->email) {
                    $output .= ' (' . htmlspecialchars($invoice->salesperson->email) . ')';
                }
                $output .= '</div>';
                $output .= '</div>';
            }
            
            // Sucursal
            if ($invoice->branch) {
                $output .= '<div class="bg-gray-50 dark:bg-gray-900/20 rounded-lg p-4 border border-gray-200 dark:border-gray-800">';
                $output .= '<div class="font-semibold text-gray-900 dark:text-gray-300 mb-2">Sucursal</div>';
                $output .= '<div class="text-sm text-gray-700 dark:text-gray-400">' . htmlspecialchars($invoice->branch->name ?? 'N/A') . '</div>';
                $output .= '</div>';
            }
            
            $output .= '</div>';
            
            // Items de la factura
            if ($invoice->items->isNotEmpty()) {
                $output .= '<div class="mb-6">';
                $output .= '<h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Items de la Factura</h3>';
                $output .= '<div class="overflow-x-auto">';
                $output .= '<table class="w-full min-w-[800px] divide-y divide-gray-200 dark:divide-gray-700">';
                $output .= '<thead class="bg-gray-50 dark:bg-gray-900">';
                $output .= '<tr>';
                $output .= '<th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tipo</th>';
                $output .= '<th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Item</th>';
                $output .= '<th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Descripción</th>';
                $output .= '<th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cantidad</th>';
                $output .= '<th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Precio Unitario</th>';
                $output .= '<th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Subtotal</th>';
                $output .= '</tr>';
                $output .= '</thead>';
                $output .= '<tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-gray-700">';
                
                foreach ($invoice->items as $item) {
                    $itemName = 'N/A';
                    $itemDescription = '';
                    $itemSku = '';
                    
                    if ($item->item_type === 'product' && $item->product) {
                        $itemName = htmlspecialchars($item->product->name ?? 'Producto eliminado');
                        $itemSku = htmlspecialchars($item->product->sku ?? '');
                        $itemDescription = htmlspecialchars($item->product->description ?? '');
                    } elseif ($item->item_type === 'service' && $item->service) {
                        $itemName = htmlspecialchars($item->service->name ?? 'Servicio eliminado');
                        $itemDescription = htmlspecialchars($item->service->description ?? '');
                    }
                    
                    $output .= '<tr class="hover:bg-gray-50 dark:hover:bg-gray-700">';
                    $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">';
                    $output .= '<span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold ' . ($item->item_type === 'product' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200') . '">';
                    $output .= htmlspecialchars($item->item_type === 'product' ? 'Producto' : 'Servicio');
                    $output .= '</span>';
                    $output .= '</td>';
                    $output .= '<td class="px-6 py-4 text-sm text-gray-900 dark:text-white">';
                    $output .= '<div class="font-medium">' . $itemName . '</div>';
                    if ($itemSku) {
                        $output .= '<div class="text-xs text-gray-500 dark:text-gray-400">SKU: ' . $itemSku . '</div>';
                    }
                    $output .= '</td>';
                    $output .= '<td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">' . ($itemDescription ?: '-') . '</td>';
                    $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">' . number_format($item->quantity, 0) . '</td>';
                    $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">USD ' . number_format($item->unit_price, 2) . '</td>';
                    $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-gray-900 dark:text-white">USD ' . number_format($item->subtotal ?? ($item->quantity * $item->unit_price), 2) . '</td>';
                    $output .= '</tr>';
                }
                
                $output .= '</tbody>';
                $output .= '</table>';
                $output .= '</div>';
                $output .= '</div>';
            }
            
            // Pagos
            if ($invoice->payments->isNotEmpty()) {
                $output .= '<div class="mb-6">';
                $output .= '<h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Pagos Realizados</h3>';
                $output .= '<div class="overflow-x-auto">';
                $output .= '<table class="w-full min-w-[600px] divide-y divide-gray-200 dark:divide-gray-700">';
                $output .= '<thead class="bg-gray-50 dark:bg-gray-900">';
                $output .= '<tr>';
                $output .= '<th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha</th>';
                $output .= '<th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Método de Pago</th>';
                $output .= '<th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Monto</th>';
                $output .= '<th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Referencia</th>';
                $output .= '<th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Notas</th>';
                $output .= '</tr>';
                $output .= '</thead>';
                $output .= '<tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-gray-700">';
                
                foreach ($invoice->payments as $payment) {
                    $paymentMethodName = 'N/A';
                    if ($payment->payment_method_id) {
                        $paymentMethod = \App\Models\PaymentMethod::find($payment->payment_method_id);
                        $paymentMethodName = $paymentMethod ? $paymentMethod->name : $payment->payment_method_id;
                    }
                    
                    $output .= '<tr class="hover:bg-gray-50 dark:hover:bg-gray-700">';
                    $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">' . ($payment->payment_date ? $payment->payment_date->format('d/m/Y H:i') : 'N/A') . '</td>';
                    $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">' . htmlspecialchars($paymentMethodName) . '</td>';
                    $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-green-600 dark:text-green-400">USD ' . number_format($payment->amount, 2) . '</td>';
                    $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">' . htmlspecialchars($payment->reference ?? '-') . '</td>';
                    $output .= '<td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">' . htmlspecialchars($payment->notes ?? '-') . '</td>';
                    $output .= '</tr>';
                }
                
                $output .= '</tbody>';
                $output .= '</table>';
                $output .= '</div>';
                $output .= '</div>';
            }
            
            // Resumen financiero
            $output .= '<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">';
            $output .= '<div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">';
            $output .= '<div class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-1">Subtotal</div>';
            $output .= '<div class="text-2xl font-bold text-blue-700 dark:text-blue-400">USD ' . number_format($invoice->subtotal ?? 0, 2) . '</div>';
            $output .= '</div>';
            $output .= '<div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">';
            $output .= '<div class="text-sm font-semibold text-purple-900 dark:text-purple-300 mb-1">Impuestos</div>';
            $output .= '<div class="text-2xl font-bold text-purple-700 dark:text-purple-400">USD ' . number_format($invoice->tax_amount ?? 0, 2) . '</div>';
            $output .= '</div>';
            $output .= '<div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">';
            $output .= '<div class="text-sm font-semibold text-green-900 dark:text-green-300 mb-1">Total Pagado</div>';
            $output .= '<div class="text-2xl font-bold text-green-700 dark:text-green-400">USD ' . number_format($invoice->total_paid ?? 0, 2) . '</div>';
            $output .= '</div>';
            $output .= '<div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">';
            $output .= '<div class="text-sm font-semibold text-red-900 dark:text-red-300 mb-1">Total Pendiente</div>';
            $output .= '<div class="text-2xl font-bold text-red-700 dark:text-red-400">USD ' . number_format($balance, 2) . '</div>';
            $output .= '</div>';
            $output .= '</div>';
            
            // Total general
            $output .= '<div class="bg-gray-50 dark:bg-gray-900/20 rounded-lg p-6 border-2 border-gray-300 dark:border-gray-700">';
            $output .= '<div class="flex justify-between items-center">';
            $output .= '<div class="text-lg font-semibold text-gray-900 dark:text-white">Total de la Factura</div>';
            $output .= '<div class="text-3xl font-bold text-gray-900 dark:text-white">USD ' . number_format($invoice->total_amount ?? 0, 2) . '</div>';
            $output .= '</div>';
            $output .= '</div>';
            
            // Notas adicionales
            if ($invoice->notes) {
                $output .= '<div class="mt-6 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-800">';
                $output .= '<div class="font-semibold text-yellow-900 dark:text-yellow-300 mb-2">Notas</div>';
                $output .= '<div class="text-sm text-yellow-800 dark:text-yellow-400">' . nl2br(htmlspecialchars($invoice->notes)) . '</div>';
                $output .= '</div>';
            }
            
            $output .= '</div>';
            }
            return Response::text($output);
        }

        // Si se busca por fecha o cliente, mostrar lista
        $documentType = $arguments['document_type'] ?? null;
        if ($documentType) {
            $query->where('document_type', $documentType);
        }

        $invoices = $query->with(['customer', 'branch'])
            ->orderBy('issue_date', 'desc')
            ->limit(100)
            ->get();

        if ($invoices->isEmpty()) {
            $typeText = $documentType ? ' ' . strtolower($documentType) : '';
            return Response::text('<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">No se encontraron' . $typeText . ' con los criterios proporcionados.</p></div>');
        }

        // Generar lista HTML
        $output = '<div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 mb-6">';
        $title = $documentType ? ucfirst(strtolower($documentType)) . 's' : 'Facturas y Notas de Venta';
        $output .= '<h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">' . $title . '</h2>';
        
        if ($dateFrom || $dateTo) {
            $dateText = '';
            if ($dateFrom && $dateTo) {
                $dateText = ' del ' . date('d/m/Y', strtotime($dateFrom)) . ' al ' . date('d/m/Y', strtotime($dateTo));
            } elseif ($dateFrom) {
                $dateText = ' desde el ' . date('d/m/Y', strtotime($dateFrom));
            } elseif ($dateTo) {
                $dateText = ' hasta el ' . date('d/m/Y', strtotime($dateTo));
            }
            $output .= '<p class="text-gray-700 dark:text-gray-300 mb-4"><strong>Período:</strong>' . $dateText . '</p>';
        }
        
        $output .= '<p class="text-gray-700 dark:text-gray-300 mb-4"><strong>Total:</strong> ' . $invoices->count() . ' documento(s)</p>';

        $totalAmount = 0;
        $totalPaid = 0;
        $totalPending = 0;

        $output .= '<div class="overflow-x-auto">';
        $output .= '<table class="w-full min-w-[900px] divide-y divide-gray-200 dark:divide-gray-700">';
        $output .= '<thead class="bg-gray-50 dark:bg-gray-900">';
        $output .= '<tr>';
        $output .= '<th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Número</th>';
        $output .= '<th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tipo</th>';
        $output .= '<th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha</th>';
        $output .= '<th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cliente</th>';
        $output .= '<th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>';
        $output .= '<th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pagado</th>';
        $output .= '<th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pendiente</th>';
        $output .= '<th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>';
        $output .= '</tr>';
        $output .= '</thead>';
        $output .= '<tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-gray-700">';

        foreach ($invoices as $invoice) {
            $balance = $invoice->total_amount - $invoice->total_paid;
            $totalAmount += $invoice->total_amount;
            $totalPaid += $invoice->total_paid;
            $totalPending += $balance;

            $statusClass = $balance > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400';
            $statusText = $balance > 0 ? 'Pendiente' : 'Pagada';

            $output .= '<tr class="hover:bg-gray-50 dark:hover:bg-gray-700">';
            $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">' . htmlspecialchars($invoice->invoice_number) . '</td>';
            $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">' . htmlspecialchars($invoice->document_type ?? 'FACTURA') . '</td>';
            $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">' . $invoice->issue_date->format('d/m/Y') . '</td>';
            $output .= '<td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">' . htmlspecialchars($invoice->customer?->display_name ?? 'N/A') . '</td>';
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
            'invoice_number' => $schema->string()
                ->nullable()
                ->description('Número de la factura o nota de venta (ej: 001-001-000000032)'),
            'invoice_id' => $schema->string()
                ->nullable()
                ->description('ID de la factura'),
            'customer_document' => $schema->string()
                ->nullable()
                ->description('Documento del cliente para buscar sus facturas'),
            'date_from' => $schema->string()
                ->nullable()
                ->description('Fecha inicial para búsqueda (formato: YYYY-MM-DD)'),
            'date_to' => $schema->string()
                ->nullable()
                ->description('Fecha final para búsqueda (formato: YYYY-MM-DD). Si solo se proporciona date_from y es hoy, busca facturas del día de hoy. Si se proporciona date_from y date_to, busca en ese rango. Si solo se proporciona date_from, busca desde esa fecha hasta hoy.'),
            'document_type' => $schema->string()
                ->nullable()
                ->description('Tipo de documento: "FACTURA" para facturas, "NOTA_DE_VENTA" para notas de venta. Si no se proporciona, busca ambos tipos.'),
        ];
    }
}
