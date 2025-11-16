<?php

namespace App\Mcp\Tools\Chatbot;

use App\Models\Income;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class IncomeQueryTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Consulta ingresos del sistema. Puede filtrar por fecha o rango de fechas.
        Sinónimos: consultar ingresos, ver ingresos, listar ingresos, ingresos del mes, ingresos por fecha, ingresos entre fechas.
        Ejemplos: "muéstrame los ingresos de enero", "ingresos del 1 al 31 de enero", "cuántos ingresos hay en febrero".
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
        $limit = $arguments['limit'] ?? 50;

        // Preparar query
        $query = Income::where('company_id', $companyId)->where('status', 'A');

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

        $incomes = $query->with('type')
            ->orderBy('movement_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $totalAmount = $incomes->sum('amount_cents') / 100;
        $count = $incomes->count();

        $output = '<div class="space-y-4">';
        $output .= '<h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Ingresos</h2>';
        
        // Resumen
        $output .= '<div class="bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800 p-4 mb-4">';
        $output .= '<div class="grid grid-cols-2 gap-4">';
        $output .= '<div><div class="text-sm text-green-600 dark:text-green-400 font-medium mb-1">Total de Ingresos</div><div class="text-2xl font-bold text-green-900 dark:text-green-300">' . $count . '</div></div>';
        $output .= '<div><div class="text-sm text-green-600 dark:text-green-400 font-medium mb-1">Monto Total</div><div class="text-2xl font-bold text-green-900 dark:text-green-300">USD ' . number_format($totalAmount, 2) . '</div></div>';
        $output .= '</div></div>';

        if ($incomes->isEmpty()) {
            $output .= '<div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg"><p class="text-yellow-800 dark:text-yellow-200">No se encontraron ingresos con los filtros especificados.</p></div>';
        } else {
            $output .= '<div class="space-y-3">';
            foreach ($incomes as $income) {
                $output .= '<div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">';
                $output .= '<div class="flex items-start justify-between mb-2">';
                $output .= '<div class="flex-1">';
                $output .= '<div class="font-semibold text-gray-900 dark:text-white">' . htmlspecialchars($income->concept) . '</div>';
                $output .= '<div class="text-sm text-gray-500 dark:text-gray-400">' . htmlspecialchars($income->type?->name ?? 'Sin tipo') . '</div>';
                $output .= '</div>';
                $output .= '<div class="text-right">';
                $output .= '<div class="text-lg font-bold text-green-600 dark:text-green-400">' . $income->amount_formatted . '</div>';
                $output .= '<div class="text-xs text-gray-500 dark:text-gray-400">' . $income->movement_date->format('d/m/Y') . '</div>';
                $output .= '</div>';
                $output .= '</div>';
                
                if ($income->description) {
                    $output .= '<div class="text-sm text-gray-600 dark:text-gray-300 mt-2">' . htmlspecialchars($income->description) . '</div>';
                }
                
                if ($income->reference) {
                    $output .= '<div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Ref: ' . htmlspecialchars($income->reference) . '</div>';
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
            'limit' => $schema->integer()
                ->default(50)
                ->minimum(1)
                ->maximum(200)
                ->description('Número máximo de registros a mostrar'),
        ];
    }
}

