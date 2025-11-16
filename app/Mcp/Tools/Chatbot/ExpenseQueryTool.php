<?php

namespace App\Mcp\Tools\Chatbot;

use App\Models\Expense;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ExpenseQueryTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Consulta egresos del sistema. Puede filtrar por fecha o rango de fechas.
        Sinónimos: consultar egresos, ver egresos, listar egresos, egresos del mes, egresos por fecha, egresos entre fechas, gastos, consultar gastos.
        Ejemplos: "muéstrame los egresos de enero", "egresos del 1 al 31 de enero", "cuántos gastos hay en febrero".
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
        $query = Expense::where('company_id', $companyId)->where('status', 'A');

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

        $expenses = $query->with('type')
            ->orderBy('movement_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $totalAmount = $expenses->sum('amount_cents') / 100;
        $count = $expenses->count();

        $output = '<div class="space-y-4">';
        $output .= '<h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Egresos</h2>';
        
        // Resumen
        $output .= '<div class="bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800 p-4 mb-4">';
        $output .= '<div class="grid grid-cols-2 gap-4">';
        $output .= '<div><div class="text-sm text-red-600 dark:text-red-400 font-medium mb-1">Total de Egresos</div><div class="text-2xl font-bold text-red-900 dark:text-red-300">' . $count . '</div></div>';
        $output .= '<div><div class="text-sm text-red-600 dark:text-red-400 font-medium mb-1">Monto Total</div><div class="text-2xl font-bold text-red-900 dark:text-red-300">USD ' . number_format($totalAmount, 2) . '</div></div>';
        $output .= '</div></div>';

        if ($expenses->isEmpty()) {
            $output .= '<div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg"><p class="text-yellow-800 dark:text-yellow-200">No se encontraron egresos con los filtros especificados.</p></div>';
        } else {
            $output .= '<div class="space-y-3">';
            foreach ($expenses as $expense) {
                $output .= '<div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">';
                $output .= '<div class="flex items-start justify-between mb-2">';
                $output .= '<div class="flex-1">';
                $output .= '<div class="font-semibold text-gray-900 dark:text-white">' . htmlspecialchars($expense->concept) . '</div>';
                $output .= '<div class="text-sm text-gray-500 dark:text-gray-400">' . htmlspecialchars($expense->type?->name ?? 'Sin tipo') . '</div>';
                $output .= '</div>';
                $output .= '<div class="text-right">';
                $output .= '<div class="text-lg font-bold text-red-600 dark:text-red-400">' . $expense->amount_formatted . '</div>';
                $output .= '<div class="text-xs text-gray-500 dark:text-gray-400">' . $expense->movement_date->format('d/m/Y') . '</div>';
                $output .= '</div>';
                $output .= '</div>';
                
                if ($expense->description) {
                    $output .= '<div class="text-sm text-gray-600 dark:text-gray-300 mt-2">' . htmlspecialchars($expense->description) . '</div>';
                }
                
                if ($expense->reference) {
                    $output .= '<div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Ref: ' . htmlspecialchars($expense->reference) . '</div>';
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

