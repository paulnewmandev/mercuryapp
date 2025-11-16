<?php

namespace App\Mcp\Tools\Chatbot;

use App\Models\Invoice;
use App\Models\Product;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ChartGeneratorTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Genera gráficos HTML usando Chart.js. Puede crear gráficos de barras, líneas, pastel, etc.
        Soporta datos de ventas, productos más vendidos, ingresos por mes, etc.
        Sinónimos: crear gráfico, generar gráfico, gráfico de barras, gráfico de líneas, gráfico circular, visualización.
        Ejemplos: "crea un gráfico del producto más vendido por mes", "genera un gráfico de ventas del año".
    MARKDOWN;

    public function handle(Request $request): Response
    {
        if (!Auth::check()) {
            return Response::error('Usuario debe estar autenticado');
        }

        $arguments = $request->all();
        $chartType = $arguments['chart_type'] ?? 'bar';
        $dataType = $arguments['data_type'] ?? 'sales';
        $period = $arguments['period'] ?? 'year';
        $filters = $arguments['filters'] ?? [];

        $user = Auth::user();
        $companyId = $user->company_id;

        if (!$companyId) {
            $companyId = \App\Models\Company::query()->first()?->id;
        }

        if (!$companyId) {
            return Response::error('No se encontró una compañía asociada');
        }

        $html = $this->generateChart($chartType, $dataType, $companyId, $period, $filters);

        return Response::text($html);
    }

    private function generateChart(string $chartType, string $dataType, string $companyId, string $period, array $filters): string
    {
        $chartId = 'chart_' . uniqid();
        $data = $this->getChartData($dataType, $companyId, $period, $filters);

        $output = "## Gráfico de Datos\n\n";
        $output .= "<div class=\"bg-white dark:bg-slate-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700\">\n";
        $output .= "<canvas id=\"{$chartId}\" width=\"400\" height=\"200\"></canvas>\n";
        $output .= "</div>\n\n";
        $output .= "<script src=\"https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js\"></script>\n";
        $output .= "<script>\n";
        $output .= "document.addEventListener('DOMContentLoaded', function() {\n";
        $output .= "    const ctx = document.getElementById('{$chartId}');\n";
        $output .= "    if (!ctx) return;\n";
        $output .= "    new Chart(ctx, {\n";
        $output .= "        type: '{$chartType}',\n";
        $output .= "        data: " . json_encode($data, JSON_PRETTY_PRINT) . ",\n";
        $output .= "        options: {\n";
        $output .= "            responsive: true,\n";
        $output .= "            plugins: {\n";
        $output .= "                legend: {\n";
        $output .= "                    position: 'top',\n";
        $output .= "                },\n";
        $output .= "                title: {\n";
        $output .= "                    display: true,\n";
        $output .= "                    text: '" . addslashes($this->getChartTitle($dataType, $period)) . "'\n";
        $output .= "                }\n";
        $output .= "            }\n";
        $output .= "        }\n";
        $output .= "    });\n";
        $output .= "});\n";
        $output .= "</script>\n";

        return $output;
    }

    private function getChartData(string $dataType, string $companyId, string $period, array $filters): array
    {
        switch ($dataType) {
            case 'sales_by_month':
                return $this->getSalesByMonthData($companyId, $period);
            
            case 'top_products':
                return $this->getTopProductsData($companyId, $period, $filters);
            
            case 'revenue_by_month':
                return $this->getRevenueByMonthData($companyId, $period);
            
            default:
                return [
                    'labels' => ['Sin datos'],
                    'datasets' => [[
                        'label' => 'Datos',
                        'data' => [0],
                        'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                        'borderColor' => 'rgba(54, 162, 235, 1)',
                        'borderWidth' => 1
                    ]]
                ];
        }
    }

    private function getSalesByMonthData(string $companyId, string $period): array
    {
        $months = [];
        $sales = [];

        $startDate = match($period) {
            'year' => now()->startOfYear(),
            'quarter' => now()->startOfQuarter(),
            'month' => now()->startOfMonth(),
            default => now()->subYear()->startOfYear(),
        };

        $endDate = now();

        $invoices = Invoice::where('company_id', $companyId)
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->selectRaw('DATE_FORMAT(issue_date, "%Y-%m") as month, SUM(total_amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        foreach ($invoices as $invoice) {
            $date = \Carbon\Carbon::createFromFormat('Y-m', $invoice->month);
            $months[] = $date->format('M Y');
            $sales[] = (float) $invoice->total;
        }

        return [
            'labels' => $months,
            'datasets' => [[
                'label' => 'Ventas',
                'data' => $sales,
                'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                'borderColor' => 'rgba(54, 162, 235, 1)',
                'borderWidth' => 1
            ]]
        ];
    }

    private function getTopProductsData(string $companyId, string $period, array $filters): array
    {
        $startDate = match($period) {
            'year' => now()->startOfYear(),
            'quarter' => now()->startOfQuarter(),
            'month' => now()->startOfMonth(),
            default => now()->subYear()->startOfYear(),
        };

        $endDate = now();

        $topProducts = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('products', function($join) {
                $join->on('invoice_items.item_id', '=', 'products.id')
                     ->where('invoice_items.item_type', '=', 'product');
            })
            ->where('invoices.company_id', $companyId)
            ->whereBetween('invoices.issue_date', [$startDate, $endDate])
            ->select('products.name', DB::raw('SUM(invoice_items.quantity) as total_sold'))
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_sold', 'desc')
            ->limit(10)
            ->get();
        
        // Si no hay datos, intentar con un rango más amplio
        if ($topProducts->isEmpty() && $period === 'year') {
            $startDate = now()->subYear()->startOfYear();
            $topProducts = DB::table('invoice_items')
                ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->join('products', function($join) {
                    $join->on('invoice_items.item_id', '=', 'products.id')
                         ->where('invoice_items.item_type', '=', 'product');
                })
                ->where('invoices.company_id', $companyId)
                ->whereBetween('invoices.issue_date', [$startDate, $endDate])
                ->select('products.name', DB::raw('SUM(invoice_items.quantity) as total_sold'))
                ->groupBy('products.id', 'products.name')
                ->orderBy('total_sold', 'desc')
                ->limit(10)
                ->get();
        }

        $labels = [];
        $data = [];

        foreach ($topProducts as $product) {
            $labels[] = $product->name;
            $data[] = (int) $product->total_sold;
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Unidades Vendidas',
                'data' => $data,
                'backgroundColor' => [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(153, 102, 255, 0.2)',
                ],
                'borderColor' => [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                ],
                'borderWidth' => 1
            ]]
        ];
    }

    private function getRevenueByMonthData(string $companyId, string $period): array
    {
        $months = [];
        $revenue = [];

        $startDate = match($period) {
            'year' => now()->startOfYear(),
            'quarter' => now()->startOfQuarter(),
            'month' => now()->startOfMonth(),
            default => now()->subYear()->startOfYear(),
        };

        $endDate = now();

        $invoices = Invoice::where('company_id', $companyId)
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->selectRaw('DATE_FORMAT(issue_date, "%Y-%m") as month, SUM(total_amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        foreach ($invoices as $invoice) {
            $date = \Carbon\Carbon::createFromFormat('Y-m', $invoice->month);
            $months[] = $date->format('M Y');
            $revenue[] = (float) $invoice->total;
        }

        return [
            'labels' => $months,
            'datasets' => [[
                'label' => 'Ingresos',
                'data' => $revenue,
                'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                'borderColor' => 'rgba(75, 192, 192, 1)',
                'borderWidth' => 2,
                'fill' => true
            ]]
        ];
    }

    private function getChartTitle(string $dataType, string $period): string
    {
        $titles = [
            'sales_by_month' => 'Ventas por Mes',
            'top_products' => 'Productos Más Vendidos',
            'revenue_by_month' => 'Ingresos por Mes',
        ];

        $title = $titles[$dataType] ?? 'Gráfico de Datos';
        $periodLabel = match($period) {
            'year' => 'Año Actual',
            'quarter' => 'Trimestre Actual',
            'month' => 'Mes Actual',
            default => 'Último Año',
        };

        return "{$title} - {$periodLabel}";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'chart_type' => $schema->string()
                ->enum(['bar', 'line', 'pie', 'doughnut'])
                ->default('bar')
                ->description('Tipo de gráfico: bar (barras), line (líneas), pie (pastel), doughnut (rosquilla)'),
            'data_type' => $schema->string()
                ->enum(['sales_by_month', 'top_products', 'revenue_by_month'])
                ->default('sales_by_month')
                ->description('Tipo de datos: sales_by_month (ventas por mes), top_products (productos más vendidos), revenue_by_month (ingresos por mes)'),
            'period' => $schema->string()
                ->enum(['year', 'quarter', 'month', 'all'])
                ->default('year')
                ->description('Período de tiempo: year (año actual), quarter (trimestre), month (mes actual), all (todo)'),
            'filters' => $schema->object()
                ->nullable()
                ->description('Filtros opcionales según el tipo de datos'),
        ];
    }
}

