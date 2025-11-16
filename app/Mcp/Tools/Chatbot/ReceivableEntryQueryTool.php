<?php

namespace App\Mcp\Tools\Chatbot;

use App\Models\ReceivableEntry;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ReceivableEntryQueryTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Consulta cuentas por cobrar del sistema. Puede filtrar por fecha, rango de fechas, o solo pendientes.
        Sinónimos: consultar cuentas por cobrar, ver cuentas por cobrar, listar cuentas por cobrar, cuentas por cobrar del mes, cuentas por cobrar pendientes.
        Ejemplos: "muéstrame las cuentas por cobrar de enero", "cuentas por cobrar pendientes", "cuentas por cobrar del 1 al 31 de enero".
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

        $dateFrom = $arguments['date_from'] ?? null;
        $dateTo = $arguments['date_to'] ?? null;
        $month = $arguments['month'] ?? null;
        $year = $arguments['year'] ?? null;
        $onlyPending = $arguments['only_pending'] ?? false;
        $limit = $arguments['limit'] ?? 50;

        // Preparar query
        $query = ReceivableEntry::where('company_id', $companyId);

        if ($onlyPending) {
            $query->where('is_collected', false);
        }

        // Aplicar filtros de fecha
        if ($month && $year) {
            $query->whereYear('movement_date', $year)
                  ->whereMonth('movement_date', $month);
        } elseif ($dateFrom && $dateTo) {
            $query->whereBetween('movement_date', [$dateFrom, $dateTo]);
        } elseif ($dateFrom) {
            $query->where('movement_date', '>=', $dateFrom);
        } elseif ($dateTo) {
            $query->where('movement_date', '<=', $dateTo);
        }

        $receivables = $query->with('category')
            ->orderBy('movement_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $totalAmount = $receivables->sum('amount_cents') / 100;
        $pendingAmount = $receivables->where('is_collected', false)->sum('amount_cents') / 100;
        $count = $receivables->count();
        $pendingCount = $receivables->where('is_collected', false)->count();

        $output = '<div class="space-y-4">';
        $output .= '<h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Cuentas por Cobrar</h2>';
        
        // Resumen
        $output .= '<div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 p-4 mb-4">';
        $output .= '<div class="grid grid-cols-2 gap-4">';
        $output .= '<div><div class="text-sm text-blue-600 dark:text-blue-400 font-medium mb-1">Total de Registros</div><div class="text-2xl font-bold text-blue-900 dark:text-blue-300">' . $count . '</div></div>';
        $output .= '<div><div class="text-sm text-blue-600 dark:text-blue-400 font-medium mb-1">Monto Total</div><div class="text-2xl font-bold text-blue-900 dark:text-blue-300">USD ' . number_format($totalAmount, 2) . '</div></div>';
        $output .= '</div>';
        if ($pendingCount > 0) {
            $output .= '<div class="mt-3 pt-3 border-t border-blue-200 dark:border-blue-700">';
            $output .= '<div class="grid grid-cols-2 gap-4">';
            $output .= '<div><div class="text-sm text-yellow-600 dark:text-yellow-400 font-medium mb-1">Pendientes</div><div class="text-xl font-bold text-yellow-900 dark:text-yellow-300">' . $pendingCount . '</div></div>';
            $output .= '<div><div class="text-sm text-yellow-600 dark:text-yellow-400 font-medium mb-1">Monto Pendiente</div><div class="text-xl font-bold text-yellow-900 dark:text-yellow-300">USD ' . number_format($pendingAmount, 2) . '</div></div>';
            $output .= '</div></div>';
        }
        $output .= '</div>';

        if ($receivables->isEmpty()) {
            $output .= '<div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg"><p class="text-yellow-800 dark:text-yellow-200">No se encontraron cuentas por cobrar con los filtros especificados.</p></div>';
        } else {
            $output .= '<div class="space-y-3">';
            foreach ($receivables as $receivable) {
                $output .= '<div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">';
                $output .= '<div class="flex items-start justify-between mb-2">';
                $output .= '<div class="flex-1">';
                $output .= '<div class="font-semibold text-gray-900 dark:text-white">' . htmlspecialchars($receivable->concept) . '</div>';
                $output .= '<div class="text-sm text-gray-500 dark:text-gray-400">' . htmlspecialchars($receivable->category?->name ?? 'Sin categoría') . '</div>';
                $output .= '</div>';
                $output .= '<div class="text-right">';
                $output .= '<div class="text-lg font-bold text-blue-600 dark:text-blue-400">' . $receivable->amount_formatted . '</div>';
                $output .= '<div class="text-xs text-gray-500 dark:text-gray-400">' . $receivable->movement_date->format('d/m/Y') . '</div>';
                $output .= '<div class="mt-1">';
                $output .= '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ' . ($receivable->is_collected ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400') . '">' . ($receivable->is_collected ? 'Cobrada' : 'Pendiente') . '</span>';
                $output .= '</div>';
                $output .= '</div>';
                $output .= '</div>';
                
                if ($receivable->description) {
                    $output .= '<div class="text-sm text-gray-600 dark:text-gray-300 mt-2">' . htmlspecialchars($receivable->description) . '</div>';
                }
                
                if ($receivable->reference) {
                    $output .= '<div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Ref: ' . htmlspecialchars($receivable->reference) . '</div>';
                }
                
                $output .= '</div>';
            }
            $output .= '</div>';
        }

        $output .= '</div>';

        return Response::text($output);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'date_from' => $schema->string()
                ->nullable()
                ->description('Fecha de inicio (formato: YYYY-MM-DD)'),
            'date_to' => $schema->string()
                ->nullable()
                ->description('Fecha de fin (formato: YYYY-MM-DD)'),
            'month' => $schema->integer()
                ->nullable()
                ->minimum(1)
                ->maximum(12)
                ->description('Mes (1-12)'),
            'year' => $schema->integer()
                ->nullable()
                ->minimum(2000)
                ->maximum(2100)
                ->description('Año (ej: 2025)'),
            'only_pending' => $schema->boolean()
                ->default(false)
                ->description('Si es true, solo muestra cuentas pendientes de cobro'),
            'limit' => $schema->integer()
                ->default(50)
                ->minimum(1)
                ->maximum(200)
                ->description('Número máximo de registros a mostrar'),
        ];
    }
}

