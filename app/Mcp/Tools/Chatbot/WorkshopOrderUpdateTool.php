<?php

namespace App\Mcp\Tools\Chatbot;

use App\Models\WorkshopOrder;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class WorkshopOrderUpdateTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Actualiza datos de una orden de reparación existente. Puede actualizar: estado, diagnóstico, garantía, nota, fecha prometida, prioridad, etc.
        Requiere el número de orden para identificarla.
        Sinónimos: actualizar orden, modificar orden, cambiar datos de la orden, editar orden, cambiar estado de la orden, actualizar diagnóstico.
        Ejemplos: "actualiza el estado de la orden 001-001-0000001 a Completada", "cambia la fecha prometida de la orden 001-001-0000001".
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

        // Identificar orden
        $orderNumber = $arguments['order_number'] ?? null;
        $orderId = $arguments['order_id'] ?? null;

        if (!$orderNumber && !$orderId) {
            return Response::error('Debe proporcionar order_number o order_id para identificar la orden');
        }

        // Buscar orden
        $query = WorkshopOrder::where('company_id', $companyId);

        if ($orderId) {
            $query->where('id', $orderId);
        } elseif ($orderNumber) {
            $query->where('order_number', $orderNumber);
        }

        $order = $query->with(['customer', 'state', 'equipment'])->first();

        if (!$order) {
            return Response::text('<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">No se encontró la orden especificada.</p></div>');
        }

        // Preparar datos a actualizar
        $updateData = [];

        if (isset($arguments['note'])) {
            $updateData['note'] = $arguments['note'];
        }

        if (isset($arguments['diagnosis'])) {
            $updateData['diagnosis'] = (bool) $arguments['diagnosis'];
        }

        if (isset($arguments['warranty'])) {
            $updateData['warranty'] = (bool) $arguments['warranty'];
        }

        if (isset($arguments['priority'])) {
            $updateData['priority'] = $arguments['priority'];
        }

        if (isset($arguments['promised_at'])) {
            try {
                $updateData['promised_at'] = \Carbon\Carbon::parse($arguments['promised_at']);
            } catch (\Exception $e) {
                return Response::text('<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">Fecha inválida. Use el formato YYYY-MM-DD.</p></div>');
            }
        }

        if (isset($arguments['state_id'])) {
            // Validar que el estado existe
            $stateExists = \App\Models\WorkshopState::where('id', $arguments['state_id'])
                ->where('company_id', $companyId)
                ->exists();
            
            if (!$stateExists) {
                return Response::text('<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">El estado especificado no existe.</p></div>');
            }
            
            $updateData['state_id'] = $arguments['state_id'];
        }

        if (empty($updateData)) {
            return Response::text('<div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg"><p class="text-yellow-800 dark:text-yellow-200">No se proporcionaron datos para actualizar.</p></div>');
        }

        try {
            $order->update($updateData);
            $order->refresh()->load(['customer', 'state', 'equipment']);

            $output = '<div class="space-y-4">';
            $output .= '<div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">';
            $output .= '<p class="text-green-800 dark:text-green-200 font-semibold mb-2">✓ Orden actualizada correctamente</p>';
            $output .= '</div>';
            
            $output .= '<div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-2">';
            $output .= '<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Datos Actualizados</h3>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Número:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($order->order_number) . '</span></div>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Cliente:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($order->customer?->display_name ?? 'N/A') . '</span></div>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Estado:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($order->state?->name ?? 'Sin estado') . '</span></div>';
            
            if ($order->promised_at) {
                $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Fecha Prometida:</span><span class="text-gray-900 dark:text-white">' . $order->promised_at->format('d/m/Y') . '</span></div>';
            }
            
            if ($order->priority) {
                $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Prioridad:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($order->priority) . '</span></div>';
            }
            
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Diagnóstico:</span><span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ' . ($order->diagnosis ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400') . '">' . ($order->diagnosis ? 'Sí' : 'No') . '</span></div>';
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Garantía:</span><span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ' . ($order->warranty ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400') . '">' . ($order->warranty ? 'Sí' : 'No') . '</span></div>';
            
            if ($order->note) {
                $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Nota:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($order->note) . '</span></div>';
            }
            
            $output .= '</div></div>';

            return Response::text($output);
        } catch (\Exception $e) {
            Log::error("Error al actualizar orden: " . $e->getMessage());
            return Response::text('<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">Error al actualizar la orden: ' . htmlspecialchars($e->getMessage()) . '</p></div>');
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'order_number' => $schema->string()
                ->nullable()
                ->description('Número de orden a actualizar (ej: 001-001-0000001)'),
            'order_id' => $schema->string()
                ->nullable()
                ->description('ID de la orden a actualizar'),
            'note' => $schema->string()
                ->nullable()
                ->description('Nueva nota para la orden'),
            'diagnosis' => $schema->boolean()
                ->nullable()
                ->description('Si tiene diagnóstico (true/false)'),
            'warranty' => $schema->boolean()
                ->nullable()
                ->description('Si tiene garantía (true/false)'),
            'priority' => $schema->string()
                ->nullable()
                ->description('Nueva prioridad de la orden'),
            'promised_at' => $schema->string()
                ->nullable()
                ->description('Nueva fecha prometida (formato: YYYY-MM-DD)'),
            'state_id' => $schema->string()
                ->nullable()
                ->description('ID del nuevo estado de la orden'),
        ];
    }
}

