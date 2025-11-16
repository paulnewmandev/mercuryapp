<?php

namespace App\Mcp\Tools\Chatbot;

use App\Models\Customer;
use App\Models\WorkshopOrder;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CustomerOrdersQueryTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Consulta las órdenes de reparación de un cliente.
        Muestra todas las órdenes o solo las de un estado específico.
        Incluye número de orden, estado, fecha, equipo, responsable, total, abono y restante.
        Útil para consultas como: "¿Qué órdenes de reparación tiene el cliente 1759474057?" o "¿En qué estado están las órdenes de Juan?"
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
        $stateName = $arguments['state_name'] ?? null;

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

        // Buscar órdenes
        $ordersQuery = WorkshopOrder::where('company_id', $companyId)
            ->where('customer_id', $customer->id);

        if ($stateName) {
            $ordersQuery->whereHas('state', function($q) use ($stateName) {
                $q->where('name', 'like', "%{$stateName}%");
            });
        }

        $orders = $ordersQuery->with([
            'customer',
            'responsible',
            'equipment.brand',
            'equipment.model',
            'state',
        ])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        if ($orders->isEmpty()) {
            $stateFilter = $stateName ? " en estado '{$stateName}'" : '';
            return Response::text("El cliente no tiene órdenes de reparación{$stateFilter}.");
        }

        // Generar HTML formateado
        $output = '<div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 mb-6">';
        $output .= '<h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Órdenes de Reparación del Cliente</h2>';
        $output .= '<div class="mb-4 space-y-2">';
        $output .= '<p class="text-gray-700 dark:text-gray-300"><strong>Cliente:</strong> ' . htmlspecialchars($customer->display_name) . '</p>';
        $output .= '<p class="text-gray-700 dark:text-gray-300"><strong>Documento:</strong> ' . htmlspecialchars($customer->document_type) . ' - ' . htmlspecialchars($customer->document_number) . '</p>';
        if ($stateName) {
            $output .= '<p class="text-gray-700 dark:text-gray-300"><strong>Filtro:</strong> Estado \'' . htmlspecialchars($stateName) . '\'</p>';
        }
        $output .= '<p class="text-gray-700 dark:text-gray-300"><strong>Total:</strong> ' . $orders->count() . ' orden(es)</p>';
        $output .= '</div>';

        $totalCost = 0;
        $totalPaid = 0;
        $totalBalance = 0;

        $output .= '<div class="overflow-x-auto">';
        $output .= '<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">';
        $output .= '<thead class="bg-gray-50 dark:bg-gray-900">';
        $output .= '<tr>';
        $output .= '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Orden</th>';
        $output .= '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>';
        $output .= '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Equipo</th>';
        $output .= '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha</th>';
        $output .= '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Prioridad</th>';
        $output .= '<th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>';
        $output .= '<th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Abono</th>';
        $output .= '<th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Restante</th>';
        $output .= '</tr>';
        $output .= '</thead>';
        $output .= '<tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-gray-700">';

        foreach ($orders as $order) {
            $totalCost += $order->total_cost ?? 0;
            $totalPaid += $order->advance_amount ?? 0;
            $totalBalance += $order->balance ?? 0;

            $equipmentName = '';
            if ($order->equipment) {
                $equipmentName = $order->equipment->name ?? '';
                $brandName = $order->equipment->brand?->name ?? '';
                $modelName = $order->equipment->model?->name ?? '';
                if ($brandName || $modelName) {
                    $equipmentName .= ' - ' . trim(($brandName ?? '') . ' ' . ($modelName ?? ''));
                }
            }

            $priorityClass = match($order->priority) {
                'Urgente' => 'text-red-600 dark:text-red-400',
                'Alta' => 'text-orange-600 dark:text-orange-400',
                default => 'text-gray-600 dark:text-gray-400',
            };

            $output .= '<tr class="hover:bg-gray-50 dark:hover:bg-gray-700">';
            $output .= '<td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">' . htmlspecialchars($order->order_number ?? 'N/A') . '</td>';
            $output .= '<td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">' . htmlspecialchars($order->state?->name ?? 'Sin estado') . '</td>';
            $output .= '<td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">' . htmlspecialchars($equipmentName ?: 'N/A') . '</td>';
            $output .= '<td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">' . $order->created_at->format('d/m/Y') . '</td>';
            $output .= '<td class="px-4 py-3 whitespace-nowrap text-sm font-semibold ' . $priorityClass . '">' . htmlspecialchars($order->priority ?? 'Normal') . '</td>';
            $output .= '<td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">USD ' . number_format($order->total_cost ?? 0, 2) . '</td>';
            $output .= '<td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-600 dark:text-gray-400">USD ' . number_format($order->advance_amount ?? 0, 2) . '</td>';
            $output .= '<td class="px-4 py-3 whitespace-nowrap text-sm text-right font-semibold ' . (($order->balance ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400') . '">USD ' . number_format($order->balance ?? 0, 2) . '</td>';
            $output .= '</tr>';
        }

        $output .= '</tbody>';
        $output .= '</table>';
        $output .= '</div>';

        // Resumen
        $output .= '<div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">';
        $output .= '<div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">';
        $output .= '<div class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-1">Total Costo</div>';
        $output .= '<div class="text-2xl font-bold text-blue-700 dark:text-blue-400">USD ' . number_format($totalCost, 2) . '</div>';
        $output .= '</div>';
        $output .= '<div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">';
        $output .= '<div class="text-sm font-semibold text-green-900 dark:text-green-300 mb-1">Total Abonado</div>';
        $output .= '<div class="text-2xl font-bold text-green-700 dark:text-green-400">USD ' . number_format($totalPaid, 2) . '</div>';
        $output .= '</div>';
        $output .= '<div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">';
        $output .= '<div class="text-sm font-semibold text-red-900 dark:text-red-300 mb-1">Total Restante</div>';
        $output .= '<div class="text-2xl font-bold text-red-700 dark:text-red-400">USD ' . number_format($totalBalance, 2) . '</div>';
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
            'state_name' => $schema->string()
                ->nullable()
                ->description('Filtrar por nombre del estado (ej: "En proceso", "Completada")'),
        ];
    }
}

