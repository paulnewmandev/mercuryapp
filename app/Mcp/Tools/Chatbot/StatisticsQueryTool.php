<?php

namespace App\Mcp\Tools\Chatbot;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Invoice;
use App\Models\PayableEntry;
use App\Models\Product;
use App\Models\ReceivableEntry;
use App\Models\WorkshopOrder;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class StatisticsQueryTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Obtiene estadísticas generales del sistema incluyendo:
        - Total de clientes, productos, órdenes de reparación
        - Cuentas por cobrar pendientes
        - Cuentas por pagar pendientes
        - Total de ingresos
        - Total de egresos
        - Puede filtrar por fecha específica o rango de fechas
        Útil para consultas como: "¿Cuántos clientes hay?" o "¿Cuánto hay pendiente por cobrar este mes?"
        También puede consultar solo ingresos o egresos: "dime el total de ingresos de hoy", "dime el total de egresos de hoy", "dime el total de ingresos del mes de enero", etc.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        if (!Auth::check()) {
            return Response::error('Usuario debe estar autenticado');
        }

        $user = Auth::user();
        $companyId = $user->company_id;

        if (!$companyId) {
            $companyId = \App\Models\Company::query()->first()?->id;
        }

        if (!$companyId) {
            return Response::error('No se encontró una compañía asociada');
        }

        // Acceder a los argumentos directamente
        $arguments = $request->arguments ?? [];
        $statType = $arguments['stat_type'] ?? 'all';
        $dateFrom = $arguments['date_from'] ?? null;
        $dateTo = $arguments['date_to'] ?? null;
        $month = $arguments['month'] ?? null;
        $year = $arguments['year'] ?? null;
        $queryType = $arguments['query_type'] ?? null; // 'income', 'expense', 'both'

        // Si se solicita solo ingresos o egresos, preparar filtro de fecha
        if ($queryType === 'income' || $queryType === 'expense' || $queryType === 'both') {
            // Si dateFrom es hoy o no se proporciona, usar hoy
            if (!$dateFrom && !$dateTo && !$month && !$year) {
                $dateFrom = now()->format('Y-m-d');
                $dateTo = now()->format('Y-m-d');
            } elseif ($dateFrom && !$dateTo) {
                // Si solo hay dateFrom, verificar si es hoy
                if ($dateFrom === now()->format('Y-m-d')) {
                    $dateTo = now()->format('Y-m-d');
                } else {
                    $dateTo = now()->format('Y-m-d');
                }
            }
            $dateFilter = $this->prepareDateFilter($dateFrom, $dateTo, $month, $year);
            
            $output = '<div class="space-y-6">';
            if ($queryType === 'income') {
                $output .= '<h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Total de Ingresos</h2>';
                $output .= $this->getIncomeTotal($companyId, $dateFilter);
            } elseif ($queryType === 'expense') {
                $output .= '<h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Total de Egresos</h2>';
                $output .= $this->getExpenseTotal($companyId, $dateFilter);
            } else {
                $output .= '<h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Ingresos y Egresos</h2>';
                $output .= $this->getIncomeTotal($companyId, $dateFilter);
                $output .= $this->getExpenseTotal($companyId, $dateFilter);
            }
            $output .= '</div>';
            return Response::text($output);
        }

        // Preparar filtros de fecha
        $dateFilter = $this->prepareDateFilter($dateFrom, $dateTo, $month, $year);

        $output = '<div class="space-y-6">';
        $output .= '<h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Estadísticas del Sistema</h2>';

        // Totales generales (no dependen de fechas)
        if ($statType === 'all' || $statType === 'totals') {
            $output .= $this->getGeneralTotals($companyId);
        }

        // Estadísticas financieras (pueden tener filtros de fecha)
        if ($statType === 'all' || $statType === 'financial') {
            $output .= $this->getFinancialStats($companyId, $dateFilter);
        }

        // Estadísticas por mes
        if ($statType === 'monthly' && $month && $year) {
            $output .= $this->getMonthlyStats($companyId, $month, $year);
        }

        $output .= '</div>';

        return Response::text($output);
    }

    private function getIncomeTotal(string $companyId, ?array $dateFilter): string
    {
        $incomesQuery = Income::where('company_id', $companyId)
            ->where('status', 'A');

        if ($dateFilter) {
            if ($dateFilter['from']) {
                $incomesQuery->whereDate('movement_date', '>=', $dateFilter['from']);
            }
            if ($dateFilter['to']) {
                $incomesQuery->whereDate('movement_date', '<=', $dateFilter['to']);
            }
        }

        $incomesTotal = $incomesQuery->sum('amount_cents') / 100;
        $incomesCount = $incomesQuery->count();

        $dateRange = '';
        if ($dateFilter) {
            if ($dateFilter['from'] && $dateFilter['to']) {
                if ($dateFilter['from'] === $dateFilter['to']) {
                    $dateRange = ' del ' . date('d/m/Y', strtotime($dateFilter['from']));
                } else {
                    $dateRange = ' del ' . date('d/m/Y', strtotime($dateFilter['from'])) . ' al ' . date('d/m/Y', strtotime($dateFilter['to']));
                }
            } elseif ($dateFilter['from']) {
                $dateRange = ' desde el ' . date('d/m/Y', strtotime($dateFilter['from']));
            } elseif ($dateFilter['to']) {
                $dateRange = ' hasta el ' . date('d/m/Y', strtotime($dateFilter['to']));
            }
        }

        $output = '<div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-6 border border-green-200 dark:border-green-800">';
        $output .= '<div class="font-semibold text-green-900 dark:text-green-300 mb-2">Total de Ingresos' . $dateRange . '</div>';
        $output .= '<div class="text-3xl font-bold text-green-700 dark:text-green-400 mb-2">USD ' . number_format($incomesTotal, 2) . '</div>';
        $output .= '<div class="text-sm text-green-600 dark:text-green-500">Cantidad de registros: ' . $incomesCount . '</div>';
        $output .= '</div>';

        return $output;
    }

    private function getExpenseTotal(string $companyId, ?array $dateFilter): string
    {
        $expensesQuery = Expense::where('company_id', $companyId)
            ->where('status', 'A');

        if ($dateFilter) {
            if ($dateFilter['from']) {
                $expensesQuery->whereDate('movement_date', '>=', $dateFilter['from']);
            }
            if ($dateFilter['to']) {
                $expensesQuery->whereDate('movement_date', '<=', $dateFilter['to']);
            }
        }

        $expensesTotal = $expensesQuery->sum('amount_cents') / 100;
        $expensesCount = $expensesQuery->count();

        $dateRange = '';
        if ($dateFilter) {
            if ($dateFilter['from'] && $dateFilter['to']) {
                if ($dateFilter['from'] === $dateFilter['to']) {
                    $dateRange = ' del ' . date('d/m/Y', strtotime($dateFilter['from']));
                } else {
                    $dateRange = ' del ' . date('d/m/Y', strtotime($dateFilter['from'])) . ' al ' . date('d/m/Y', strtotime($dateFilter['to']));
                }
            } elseif ($dateFilter['from']) {
                $dateRange = ' desde el ' . date('d/m/Y', strtotime($dateFilter['from']));
            } elseif ($dateFilter['to']) {
                $dateRange = ' hasta el ' . date('d/m/Y', strtotime($dateFilter['to']));
            }
        }

        $output = '<div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-6 border border-red-200 dark:border-red-800">';
        $output .= '<div class="font-semibold text-red-900 dark:text-red-300 mb-2">Total de Egresos' . $dateRange . '</div>';
        $output .= '<div class="text-3xl font-bold text-red-700 dark:text-red-400 mb-2">USD ' . number_format($expensesTotal, 2) . '</div>';
        $output .= '<div class="text-sm text-red-600 dark:text-red-500">Cantidad de registros: ' . $expensesCount . '</div>';
        $output .= '</div>';

        return $output;
    }

    private function getGeneralTotals(string $companyId): string
    {
        $totalCustomers = Customer::where('company_id', $companyId)->count();
        $totalProducts = Product::where('company_id', $companyId)->where('status', 'A')->count();
        $totalOrders = WorkshopOrder::where('company_id', $companyId)->count();

        $output = '<div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">';
        $output .= '<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Totales Generales</h3>';
        $output .= '<div class="grid grid-cols-1 md:grid-cols-3 gap-4">';
        $output .= '<div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4"><div class="text-sm text-blue-600 dark:text-blue-400 font-medium mb-1">Total de Clientes</div><div class="text-2xl font-bold text-blue-900 dark:text-blue-300">' . $totalCustomers . '</div></div>';
        $output .= '<div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4"><div class="text-sm text-green-600 dark:text-green-400 font-medium mb-1">Productos Activos</div><div class="text-2xl font-bold text-green-900 dark:text-green-300">' . $totalProducts . '</div></div>';
        $output .= '<div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4"><div class="text-sm text-purple-600 dark:text-purple-400 font-medium mb-1">Órdenes de Reparación</div><div class="text-2xl font-bold text-purple-900 dark:text-purple-300">' . $totalOrders . '</div></div>';
        $output .= '</div></div>';

        return $output;
    }

    private function getFinancialStats(string $companyId, ?array $dateFilter): string
    {
        // Cuentas por cobrar pendientes
        $receivablesQuery = ReceivableEntry::where('company_id', $companyId)
            ->where('is_collected', false);

        if ($dateFilter) {
            if ($dateFilter['from']) {
                $receivablesQuery->where('movement_date', '>=', $dateFilter['from']);
            }
            if ($dateFilter['to']) {
                $receivablesQuery->where('movement_date', '<=', $dateFilter['to']);
            }
        }

        $receivablesTotal = $receivablesQuery->sum('amount_cents') / 100;
        $receivablesCount = $receivablesQuery->count();

        // Cuentas por pagar pendientes
        $payablesQuery = PayableEntry::where('company_id', $companyId)
            ->where('is_paid', false);

        if ($dateFilter) {
            if ($dateFilter['from']) {
                $payablesQuery->where('movement_date', '>=', $dateFilter['from']);
            }
            if ($dateFilter['to']) {
                $payablesQuery->where('movement_date', '<=', $dateFilter['to']);
            }
        }

        $payablesTotal = $payablesQuery->sum('amount_cents') / 100;
        $payablesCount = $payablesQuery->count();

        // Ingresos
        $incomesQuery = Income::where('company_id', $companyId)
            ->where('status', 'A');

        if ($dateFilter) {
            if ($dateFilter['from']) {
                $incomesQuery->where('movement_date', '>=', $dateFilter['from']);
            }
            if ($dateFilter['to']) {
                $incomesQuery->where('movement_date', '<=', $dateFilter['to']);
            }
        }

        $incomesTotal = $incomesQuery->sum('amount_cents') / 100;
        $incomesCount = $incomesQuery->count();

        // Egresos
        $expensesQuery = Expense::where('company_id', $companyId)
            ->where('status', 'A');

        if ($dateFilter) {
            if ($dateFilter['from']) {
                $expensesQuery->where('movement_date', '>=', $dateFilter['from']);
            }
            if ($dateFilter['to']) {
                $expensesQuery->where('movement_date', '<=', $dateFilter['to']);
            }
        }

        $expensesTotal = $expensesQuery->sum('amount_cents') / 100;
        $expensesCount = $expensesQuery->count();

        // Facturas pendientes
        $invoicesQuery = Invoice::where('company_id', $companyId)
            ->whereColumn('total_amount', '>', 'total_paid');

        if ($dateFilter) {
            if ($dateFilter['from']) {
                $invoicesQuery->where('issue_date', '>=', $dateFilter['from']);
            }
            if ($dateFilter['to']) {
                $invoicesQuery->where('issue_date', '<=', $dateFilter['to']);
            }
        }

        $invoicesBalance = $invoicesQuery->sum(DB::raw('total_amount - total_paid'));
        $invoicesCount = $invoicesQuery->count();

        $dateRange = '';
        if ($dateFilter) {
            if ($dateFilter['from'] && $dateFilter['to']) {
                $dateRange = ' <span class="text-sm text-gray-500 dark:text-gray-400">(del ' . htmlspecialchars($dateFilter['from']) . ' al ' . htmlspecialchars($dateFilter['to']) . ')</span>';
            } elseif ($dateFilter['from']) {
                $dateRange = ' <span class="text-sm text-gray-500 dark:text-gray-400">(desde ' . htmlspecialchars($dateFilter['from']) . ')</span>';
            } elseif ($dateFilter['to']) {
                $dateRange = ' <span class="text-sm text-gray-500 dark:text-gray-400">(hasta ' . htmlspecialchars($dateFilter['to']) . ')</span>';
            }
        }

        $output = '<div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 mt-6">';
        $output .= '<h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Estadísticas Financieras' . $dateRange . '</h3>';
        $output .= '<div class="space-y-4">';

        // Cuentas por cobrar pendientes
        $output .= '<div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">';
        $output .= '<div class="font-semibold text-blue-900 dark:text-blue-300 mb-2">Cuentas por Cobrar Pendientes</div>';
        $output .= '<div class="text-sm text-blue-700 dark:text-blue-400 space-y-1">';
        $output .= '<div>Total: <span class="font-bold text-lg">USD ' . number_format($receivablesTotal, 2) . '</span></div>';
        $output .= '<div>Cantidad de registros: <span class="font-semibold">' . $receivablesCount . '</span></div>';
        $output .= '</div></div>';

        // Cuentas por pagar pendientes
        $output .= '<div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4 border border-orange-200 dark:border-orange-800">';
        $output .= '<div class="font-semibold text-orange-900 dark:text-orange-300 mb-2">Cuentas por Pagar Pendientes</div>';
        $output .= '<div class="text-sm text-orange-700 dark:text-orange-400 space-y-1">';
        $output .= '<div>Total: <span class="font-bold text-lg">USD ' . number_format($payablesTotal, 2) . '</span></div>';
        $output .= '<div>Cantidad de registros: <span class="font-semibold">' . $payablesCount . '</span></div>';
        $output .= '</div></div>';

        // Ingresos
        $output .= '<div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">';
        $output .= '<div class="font-semibold text-green-900 dark:text-green-300 mb-2">Ingresos</div>';
        $output .= '<div class="text-sm text-green-700 dark:text-green-400 space-y-1">';
        $output .= '<div>Total: <span class="font-bold text-lg">USD ' . number_format($incomesTotal, 2) . '</span></div>';
        $output .= '<div>Cantidad de registros: <span class="font-semibold">' . $incomesCount . '</span></div>';
        $output .= '</div></div>';

        // Egresos
        $output .= '<div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">';
        $output .= '<div class="font-semibold text-red-900 dark:text-red-300 mb-2">Egresos</div>';
        $output .= '<div class="text-sm text-red-700 dark:text-red-400 space-y-1">';
        $output .= '<div>Total: <span class="font-bold text-lg">USD ' . number_format($expensesTotal, 2) . '</span></div>';
        $output .= '<div>Cantidad de registros: <span class="font-semibold">' . $expensesCount . '</span></div>';
        $output .= '</div></div>';

        // Facturas pendientes
        $output .= '<div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-800">';
        $output .= '<div class="font-semibold text-yellow-900 dark:text-yellow-300 mb-2">Facturas Pendientes</div>';
        $output .= '<div class="text-sm text-yellow-700 dark:text-yellow-400 space-y-1">';
        $output .= '<div>Saldo pendiente: <span class="font-bold text-lg">USD ' . number_format($invoicesBalance, 2) . '</span></div>';
        $output .= '<div>Cantidad de facturas: <span class="font-semibold">' . $invoicesCount . '</span></div>';
        $output .= '</div></div>';

        $output .= '</div></div>';

        return $output;
    }

    private function getMonthlyStats(string $companyId, int $month, int $year): string
    {
        $dateFrom = "{$year}-{$month}-01";
        $lastDay = date('t', strtotime($dateFrom));
        $dateTo = "{$year}-{$month}-{$lastDay}";

        $output = "### Estadísticas del Mes " . date('F Y', strtotime($dateFrom)) . "\n\n";

        // Ingresos del mes
        $incomesTotal = Income::where('company_id', $companyId)
            ->where('status', 'A')
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->sum('amount_cents') / 100;

        // Egresos del mes
        $expensesTotal = Expense::where('company_id', $companyId)
            ->where('status', 'A')
            ->whereBetween('movement_date', [$dateFrom, $dateTo])
            ->sum('amount_cents') / 100;

        // Cuentas por cobrar pendientes al final del mes
        $receivablesTotal = ReceivableEntry::where('company_id', $companyId)
            ->where('is_collected', false)
            ->where('movement_date', '<=', $dateTo)
            ->sum('amount_cents') / 100;

        // Cuentas por pagar pendientes al final del mes
        $payablesTotal = PayableEntry::where('company_id', $companyId)
            ->where('is_paid', false)
            ->where('movement_date', '<=', $dateTo)
            ->sum('amount_cents') / 100;

        $output .= "- **Ingresos del mes:** USD " . number_format($incomesTotal, 2) . "\n";
        $output .= "- **Egresos del mes:** USD " . number_format($expensesTotal, 2) . "\n";
        $output .= "- **Balance del mes:** USD " . number_format($incomesTotal - $expensesTotal, 2) . "\n";
        $output .= "- **Cuentas por cobrar pendientes:** USD " . number_format($receivablesTotal, 2) . "\n";
        $output .= "- **Cuentas por pagar pendientes:** USD " . number_format($payablesTotal, 2) . "\n\n";

        return $output;
    }

    private function prepareDateFilter(?string $dateFrom, ?string $dateTo, ?int $month, ?int $year): ?array
    {
        if ($month && $year) {
            $dateFrom = "{$year}-{$month}-01";
            $lastDay = date('t', strtotime($dateFrom));
            $dateTo = "{$year}-{$month}-{$lastDay}";
        }

        if (!$dateFrom && !$dateTo) {
            return null;
        }

        return [
            'from' => $dateFrom,
            'to' => $dateTo,
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'stat_type' => $schema->string()
                ->enum(['all', 'totals', 'financial', 'monthly'])
                ->default('all')
                ->description('Tipo de estadísticas a obtener: all (todas), totals (solo totales), financial (solo financieras), monthly (por mes)'),
            'query_type' => $schema->string()
                ->enum(['income', 'expense', 'both'])
                ->nullable()
                ->description('Tipo de consulta específica: "income" para solo ingresos, "expense" para solo egresos, "both" para ambos. Si se proporciona, solo retorna ingresos/egresos según el tipo. Útil para consultas como "dime el total de ingresos de hoy" o "dime el total de egresos del mes".'),
            'date_from' => $schema->string()
                ->nullable()
                ->description('Fecha de inicio para filtrar (formato: YYYY-MM-DD). Si es hoy y se usa query_type, busca solo del día de hoy. Si no se proporciona date_to y se usa query_type, busca desde date_from hasta hoy.'),
            'date_to' => $schema->string()
                ->nullable()
                ->description('Fecha de fin para filtrar (formato: YYYY-MM-DD)'),
            'month' => $schema->integer()
                ->nullable()
                ->minimum(1)
                ->maximum(12)
                ->description('Mes para estadísticas mensuales (1-12)'),
            'year' => $schema->integer()
                ->nullable()
                ->minimum(2000)
                ->maximum(2100)
                ->description('Año para estadísticas mensuales (ej: 2025)'),
        ];
    }
}

