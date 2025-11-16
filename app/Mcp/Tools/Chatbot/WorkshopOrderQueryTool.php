<?php

namespace App\Mcp\Tools\Chatbot;

use App\Models\WorkshopOrder;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class WorkshopOrderQueryTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Obtiene los datos completos de una orden de trabajo por su número, ID, o cliente.
        Incluye estado, cliente, equipos, items, servicios, avances y totales.
        Útil para consultas como: "¿Cuál es el estado de la orden 001-001-0000001?" o "Muéstrame la orden del cliente Juan"
    MARKDOWN;

    public function handle(Request $request): Response
    {
        if (!Auth::check()) {
            return Response::error('Usuario debe estar autenticado');
        }

        // Acceder a los argumentos usando all()
        $arguments = $request->all();
        $orderNumber = $arguments['order_number'] ?? null;
        $orderId = $arguments['order_id'] ?? null;
        $customerDocument = $arguments['customer_document'] ?? null;
        $dateFrom = $arguments['date_from'] ?? null;
        $dateTo = $arguments['date_to'] ?? null;

        if (!$orderNumber && !$orderId && !$customerDocument && !$dateFrom) {
            return Response::error('Debe proporcionar order_number, order_id, customer_document o date_from');
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

        $query = WorkshopOrder::where('company_id', $companyId);

        if ($orderNumber) {
            $query->where('order_number', $orderNumber);
        }
        if ($orderId) {
            $query->where('id', $orderId);
        }
        if ($customerDocument) {
            $query->whereHas('customer', function($q) use ($customerDocument) {
                $q->where('document_number', $customerDocument);
            });
        }
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Si se busca por número específico, mostrar detalles completos
        if ($orderNumber || $orderId) {
            $orders = $query->with([
            'customer',
            'responsible',
            'equipment.brand',
            'equipment.model',
            'state',
            'category',
            'items.product',
            'services.service',
            'notes',
            'advances',
        ])->limit(1)->get();

            if ($orders->isEmpty()) {
                return Response::text("No se encontraron órdenes de trabajo con los criterios proporcionados.");
            }

            $output = '';
            foreach ($orders as $order) {
            // Calcular totales si no están calculados
            $itemsTotal = $order->items()->where('status', 'A')->sum('subtotal') ?? 0;
            $servicesTotal = $order->services()->where('status', 'A')->sum('subtotal') ?? 0;
            $totalCost = $order->total_cost ?? ($itemsTotal + $servicesTotal);
            $totalPaid = $order->total_paid ?? $order->advances()->where('status', 'A')->sum('amount') ?? 0;
            $balance = $order->balance ?? ($totalCost - $totalPaid);

            $output .= '<div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 mb-6">';
            $output .= '<h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Orden de Trabajo: ' . htmlspecialchars($order->order_number ?? 'Sin número') . '</h2>';
            
            // Información básica
            $output .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">';
            
            // Estado
            $output .= '<div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">';
            $output .= '<div class="font-semibold text-blue-900 dark:text-blue-300 mb-2">Estado</div>';
            $output .= '<div class="text-blue-700 dark:text-blue-400">' . htmlspecialchars($order->state?->name ?? 'Sin estado') . '</div>';
            if ($order->state?->description) {
                $output .= '<div class="text-sm text-blue-600 dark:text-blue-500 mt-1">' . htmlspecialchars($order->state->description) . '</div>';
            }
            $output .= '</div>';

            // Categoría
            if ($order->category) {
                $output .= '<div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">';
                $output .= '<div class="font-semibold text-purple-900 dark:text-purple-300 mb-2">Categoría</div>';
                $output .= '<div class="text-purple-700 dark:text-purple-400">' . htmlspecialchars($order->category->name) . '</div>';
                $output .= '</div>';
            }

            // Prioridad
            if ($order->priority) {
                $priorityCard = match($order->priority) {
                    'Urgente' => '<div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800"><div class="font-semibold text-red-900 dark:text-red-300 mb-2">Prioridad</div><div class="text-red-700 dark:text-red-400">' . htmlspecialchars($order->priority) . '</div></div>',
                    'Alta' => '<div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4 border border-orange-200 dark:border-orange-800"><div class="font-semibold text-orange-900 dark:text-orange-300 mb-2">Prioridad</div><div class="text-orange-700 dark:text-orange-400">' . htmlspecialchars($order->priority) . '</div></div>',
                    default => '<div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-800"><div class="font-semibold text-yellow-900 dark:text-yellow-300 mb-2">Prioridad</div><div class="text-yellow-700 dark:text-yellow-400">' . htmlspecialchars($order->priority) . '</div></div>',
                };
                $output .= $priorityCard;
            }

            // Fecha prometida
            if ($order->promised_at) {
                $output .= '<div class="bg-gray-50 dark:bg-gray-900/20 rounded-lg p-4 border border-gray-200 dark:border-gray-800">';
                $output .= '<div class="font-semibold text-gray-900 dark:text-gray-300 mb-2">Fecha Prometida</div>';
                $output .= '<div class="text-gray-700 dark:text-gray-400">' . $order->promised_at->format('d/m/Y') . '</div>';
                $output .= '</div>';
            }

            $output .= '</div>';

            // Cliente
            if ($order->customer) {
                $output .= '<div class="mb-6">';
                $output .= '<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Cliente</h3>';
                $output .= '<div class="bg-gray-50 dark:bg-gray-900/20 rounded-lg p-4 border border-gray-200 dark:border-gray-800">';
                $output .= '<div class="space-y-2">';
                $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Nombre:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($order->customer->first_name . ' ' . $order->customer->last_name) . '</span></div>';
                $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Documento:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($order->customer->document_number ?? 'N/A') . '</span></div>';
                $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Email:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($order->customer->email ?? 'N/A') . '</span></div>';
                $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Teléfono:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($order->customer->phone_number ?? 'N/A') . '</span></div>';
                $output .= '</div></div></div>';
            }

            // Equipo
            if ($order->equipment) {
                $output .= '<div class="mb-6">';
                $output .= '<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Equipo</h3>';
                $output .= '<div class="bg-gray-50 dark:bg-gray-900/20 rounded-lg p-4 border border-gray-200 dark:border-gray-800">';
                $equipmentName = $order->equipment->name ?? 'Sin nombre';
                if ($order->equipment->brand) {
                    $equipmentName .= ' - ' . $order->equipment->brand->name;
                }
                if ($order->equipment->model) {
                    $equipmentName .= ' ' . $order->equipment->model->name;
                }
                $output .= '<div class="text-gray-900 dark:text-white">' . htmlspecialchars($equipmentName) . '</div>';
                if ($order->equipment->identifier) {
                    $output .= '<div class="text-sm text-gray-600 dark:text-gray-400 mt-1">ID: ' . htmlspecialchars($order->equipment->identifier) . '</div>';
                }
                $output .= '</div></div>';
            }

            // Responsable
            if ($order->responsible) {
                $output .= '<div class="mb-6">';
                $output .= '<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Responsable</h3>';
                $output .= '<div class="bg-gray-50 dark:bg-gray-900/20 rounded-lg p-4 border border-gray-200 dark:border-gray-800">';
                $output .= '<div class="text-gray-900 dark:text-white">' . htmlspecialchars($order->responsible->first_name . ' ' . $order->responsible->last_name) . '</div>';
                $output .= '</div></div>';
            }

            // Resumen financiero
            $output .= '<div class="mb-6">';
            $output .= '<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Resumen Financiero</h3>';
            $output .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
            
            $output .= '<div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">';
            $output .= '<div class="font-semibold text-green-900 dark:text-green-300 mb-2">Costo Total</div>';
            $output .= '<div class="text-2xl font-bold text-green-700 dark:text-green-400">USD ' . number_format($totalCost, 2) . '</div>';
            $output .= '<div class="text-sm text-green-600 dark:text-green-500 mt-1">Productos: USD ' . number_format($itemsTotal, 2) . ' + Servicios: USD ' . number_format($servicesTotal, 2) . '</div>';
            $output .= '</div>';

            $output .= '<div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">';
            $output .= '<div class="font-semibold text-blue-900 dark:text-blue-300 mb-2">Total Pagado</div>';
            $output .= '<div class="text-2xl font-bold text-blue-700 dark:text-blue-400">USD ' . number_format($totalPaid, 2) . '</div>';
            $output .= '</div>';

            $output .= '<div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4 border border-orange-200 dark:border-orange-800">';
            $output .= '<div class="font-semibold text-orange-900 dark:text-orange-300 mb-2">Balance Pendiente</div>';
            $output .= '<div class="text-2xl font-bold text-orange-700 dark:text-orange-400">USD ' . number_format($balance, 2) . '</div>';
            $output .= '</div>';

            if ($order->budget_amount) {
                $output .= '<div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">';
                $output .= '<div class="font-semibold text-purple-900 dark:text-purple-300 mb-2">Presupuesto</div>';
                $output .= '<div class="text-2xl font-bold text-purple-700 dark:text-purple-400">USD ' . number_format($order->budget_amount, 2) . '</div>';
                $output .= '</div>';
            }

            $output .= '</div></div>';

            // Items
            if ($order->items->where('status', 'A')->count() > 0) {
                $output .= '<div class="mb-6">';
                $output .= '<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Productos</h3>';
                $output .= '<div class="overflow-x-auto">';
                $output .= '<table class="w-full min-w-[800px] divide-y divide-gray-200 dark:divide-gray-700">';
                $output .= '<thead class="bg-gray-50 dark:bg-gray-900">';
                $output .= '<tr>';
                $output .= '<th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Producto</th>';
                $output .= '<th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">SKU</th>';
                $output .= '<th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cantidad</th>';
                $output .= '<th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Precio Unit.</th>';
                $output .= '<th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Subtotal</th>';
                $output .= '</tr>';
                $output .= '</thead><tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-gray-700">';
                foreach ($order->items->where('status', 'A') as $item) {
                    $output .= '<tr class="hover:bg-gray-50 dark:hover:bg-gray-700">';
                    $output .= '<td class="px-6 py-4 text-sm text-gray-900 dark:text-white">' . htmlspecialchars($item->product?->name ?? 'Producto eliminado') . '</td>';
                    $output .= '<td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">' . htmlspecialchars($item->product?->sku ?? '-') . '</td>';
                    $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">' . number_format($item->quantity, 0) . '</td>';
                    $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">USD ' . number_format($item->unit_price, 2) . '</td>';
                    $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-gray-900 dark:text-white">USD ' . number_format($item->subtotal, 2) . '</td>';
                    $output .= '</tr>';
                }
                $output .= '</tbody></table></div></div>';
            }

            // Servicios
            if ($order->services->where('status', 'A')->count() > 0) {
                $output .= '<div class="mb-6">';
                $output .= '<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Servicios</h3>';
                $output .= '<div class="overflow-x-auto">';
                $output .= '<table class="w-full min-w-[700px] divide-y divide-gray-200 dark:divide-gray-700">';
                $output .= '<thead class="bg-gray-50 dark:bg-gray-900">';
                $output .= '<tr>';
                $output .= '<th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Servicio</th>';
                $output .= '<th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Descripción</th>';
                $output .= '<th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cantidad</th>';
                $output .= '<th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Precio Unit.</th>';
                $output .= '<th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Subtotal</th>';
                $output .= '</tr>';
                $output .= '</thead><tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-gray-700">';
                foreach ($order->services->where('status', 'A') as $service) {
                    $output .= '<tr class="hover:bg-gray-50 dark:hover:bg-gray-700">';
                    $output .= '<td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">' . htmlspecialchars($service->service?->name ?? 'Servicio eliminado') . '</td>';
                    $output .= '<td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">' . htmlspecialchars($service->service?->description ?? '-') . '</td>';
                    $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">' . number_format($service->quantity, 0) . '</td>';
                    $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">USD ' . number_format($service->unit_price, 2) . '</td>';
                    $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-gray-900 dark:text-white">USD ' . number_format($service->subtotal, 2) . '</td>';
                    $output .= '</tr>';
                }
                $output .= '</tbody></table></div></div>';
            }

            // Avances
            if ($order->advances->where('status', 'A')->count() > 0) {
                $output .= '<div class="mb-6">';
                $output .= '<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Abonos</h3>';
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
                $output .= '</thead><tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-gray-700">';
                foreach ($order->advances->where('status', 'A') as $advance) {
                    $paymentMethodName = 'N/A';
                    if ($advance->payment_method_id) {
                        $paymentMethod = \App\Models\PaymentMethod::find($advance->payment_method_id);
                        $paymentMethodName = $paymentMethod ? $paymentMethod->name : $advance->payment_method_id;
                    }
                    
                    $output .= '<tr class="hover:bg-gray-50 dark:hover:bg-gray-700">';
                    $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">' . $advance->payment_date->format('d/m/Y') . '</td>';
                    $output .= '<td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">' . htmlspecialchars($paymentMethodName) . '</td>';
                    $output .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-green-600 dark:text-green-400">USD ' . number_format($advance->amount, 2) . '</td>';
                    $output .= '<td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">' . htmlspecialchars($advance->reference ?? '-') . '</td>';
                    $output .= '<td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">' . htmlspecialchars($advance->notes ?? '-') . '</td>';
                    $output .= '</tr>';
                }
                $output .= '</tbody></table></div></div>';
            }

            // Notas
            if ($order->note) {
                $output .= '<div class="mb-6">';
                $output .= '<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Nota</h3>';
                $output .= '<div class="bg-gray-50 dark:bg-gray-900/20 rounded-lg p-4 border border-gray-200 dark:border-gray-800">';
                $output .= '<div class="text-gray-900 dark:text-white whitespace-pre-wrap">' . htmlspecialchars($order->note) . '</div>';
                $output .= '</div></div>';
            }

            // Información adicional
            $output .= '<div class="mb-6">';
            $output .= '<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Información Adicional</h3>';
            $output .= '<div class="bg-gray-50 dark:bg-gray-900/20 rounded-lg p-4 border border-gray-200 dark:border-gray-800">';
            $output .= '<div class="space-y-2">';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Fecha de Creación:</span><span class="text-gray-900 dark:text-white">' . $order->created_at->format('d/m/Y H:i') . '</span></div>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Diagnóstico:</span><span class="text-gray-900 dark:text-white">' . ($order->diagnosis ? 'Sí' : 'No') . '</span></div>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Garantía:</span><span class="text-gray-900 dark:text-white">' . ($order->warranty ? 'Sí' : 'No') . '</span></div>';
            if ($order->equipment_password) {
                $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Contraseña Equipo:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($order->equipment_password) . '</span></div>';
            }
            $output .= '</div></div></div>';

            $output .= '</div>';
        }

        return Response::text($output);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'order_number' => $schema->string()
                ->nullable()
                ->description('Número de la orden de trabajo (ej: 001-001-0000001)'),
            'order_id' => $schema->string()
                ->nullable()
                ->description('ID de la orden de trabajo'),
            'customer_document' => $schema->string()
                ->nullable()
                ->description('Número de documento del cliente para buscar sus órdenes'),
            'date_from' => $schema->string()
                ->nullable()
                ->description('Fecha inicial para búsqueda (formato: YYYY-MM-DD). Si solo se proporciona date_from y es hoy, busca órdenes del día de hoy. Si se proporciona date_from y date_to, busca en ese rango. Si solo se proporciona date_from, busca desde esa fecha hasta hoy.'),
            'date_to' => $schema->string()
                ->nullable()
                ->description('Fecha final para búsqueda (formato: YYYY-MM-DD)'),
        ];
    }
}

