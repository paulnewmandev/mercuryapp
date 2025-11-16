<?php

namespace App\Mcp\Tools\Chatbot;

use App\Models\WorkshopOrder;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class WorkshopOrderStatusTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Consulta el estado de una orden de reparación por su número. Muestra el estado actual, cliente, equipo, y otros detalles relevantes.
        Sinónimos: estado de la orden, estado orden, qué estado tiene la orden, en qué estado está la orden, consultar estado orden.
        Ejemplos: "dime el estado de la orden 001-001-0000001", "cuál es el estado de la orden 001-001-0000001".
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

        $orderNumber = $arguments['order_number'] ?? null;

        if (!$orderNumber) {
            return Response::text('<div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-800 dark:text-red-200">Se requiere el número de orden.</p></div>');
        }

        $order = WorkshopOrder::where('company_id', $companyId)
            ->where('order_number', $orderNumber)
            ->with(['customer', 'state', 'equipment.brand', 'equipment.model', 'responsible'])
            ->first();

        if (!$order) {
            return Response::text('<div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg"><p class="text-yellow-800 dark:text-yellow-200">No se encontró una orden con el número: ' . htmlspecialchars($orderNumber) . '</p></div>');
        }

        $output = '<div class="space-y-4">';
        $output .= '<h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Estado de la Orden</h2>';
        
        $output .= '<div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-3">';
        $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Número:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($order->order_number) . '</span></div>';
        
        if ($order->state) {
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Estado:</span><span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">' . htmlspecialchars($order->state->name) . '</span></div>';
        } else {
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Estado:</span><span class="text-gray-500 dark:text-gray-400">Sin estado asignado</span></div>';
        }
        
        if ($order->customer) {
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Cliente:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($order->customer->display_name) . '</span></div>';
        }
        
        if ($order->equipment) {
            $equipmentName = collect([
                $order->equipment->brand?->name,
                $order->equipment->model?->name,
                $order->equipment->identifier,
            ])->filter()->implode(' · ');
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Equipo:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($equipmentName) . '</span></div>';
        }
        
        if ($order->responsible) {
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Responsable:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($order->responsible->name) . '</span></div>';
        }
        
        if ($order->promised_at) {
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Fecha Prometida:</span><span class="text-gray-900 dark:text-white">' . $order->promised_at->format('d/m/Y') . '</span></div>';
        }
        
        if ($order->priority) {
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Prioridad:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($order->priority) . '</span></div>';
        }
        
        $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Diagnóstico:</span><span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ' . ($order->diagnosis ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400') . '">' . ($order->diagnosis ? 'Sí' : 'No') . '</span></div>';
        
        $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Garantía:</span><span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ' . ($order->warranty ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400') . '">' . ($order->warranty ? 'Sí' : 'No') . '</span></div>';
        
        if ($order->total_cost) {
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Costo Total:</span><span class="text-gray-900 dark:text-white font-semibold">USD ' . number_format($order->total_cost, 2) . '</span></div>';
        }
        
        if ($order->balance) {
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[140px]">Saldo Pendiente:</span><span class="text-orange-600 dark:text-orange-400 font-semibold">USD ' . number_format($order->balance, 2) . '</span></div>';
        }
        
        $output .= '</div></div>';

        return Response::text($output);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'order_number' => $schema->string()
                ->description('Número de orden (ej: 001-001-0000001)'),
        ];
    }
}

