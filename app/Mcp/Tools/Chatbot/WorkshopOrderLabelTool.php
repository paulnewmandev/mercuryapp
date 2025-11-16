<?php

namespace App\Mcp\Tools\Chatbot;

use App\Models\WorkshopOrder;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class WorkshopOrderLabelTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Obtiene el enlace PDF de la etiqueta de una orden de trabajo. Requiere el número de orden.
        Sinónimos: etiqueta de la orden, etiqueta PDF orden, código de barras orden, label orden, imprimir etiqueta orden.
        Ejemplos: "dame la etiqueta de la orden 001-001-0000001", "muéstrame la etiqueta PDF de la orden 001-001-0000001".
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
            ->with(['customer'])
            ->first();

        if (!$order) {
            return Response::text('<div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg"><p class="text-yellow-800 dark:text-yellow-200">No se encontró una orden con el número: ' . htmlspecialchars($orderNumber) . '</p></div>');
        }

        if (empty($order->order_number)) {
            return Response::text('<div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg"><p class="text-yellow-800 dark:text-yellow-200">La orden no tiene número configurado, por lo que no se puede generar la etiqueta.</p></div>');
        }

        $labelUrl = route('workshop.orders.label', $order->id);

        $output = '<div class="space-y-4">';
        $output .= '<div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">';
        $output .= '<p class="text-green-800 dark:text-green-200 font-semibold mb-2">✓ Etiqueta encontrada</p>';
        $output .= '</div>';
        
        $output .= '<div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-3">';
        $output .= '<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Orden de Trabajo</h3>';
        $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Número:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($order->order_number) . '</span></div>';
        if ($order->customer) {
            $output .= '<div class="flex items-start gap-2"><span class="font-semibold text-gray-700 dark:text-gray-300 min-w-[120px]">Cliente:</span><span class="text-gray-900 dark:text-white">' . htmlspecialchars($order->customer->display_name) . '</span></div>';
        }
        $output .= '<div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">';
        $output .= '<a href="' . htmlspecialchars($labelUrl) . '" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-strong">';
        $output .= '<i class="fa-solid fa-file-pdf"></i>';
        $output .= 'Ver Etiqueta PDF';
        $output .= '</a>';
        $output .= '</div>';
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

