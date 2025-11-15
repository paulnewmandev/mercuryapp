<?php

namespace App\Http\Controllers\Dashboard;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Invoice;
use App\Models\ProductTransfer;
use App\Models\ReceivableEntry;
use App\Models\WorkshopOrder;
use App\Models\WorkshopOrderService;
use App\Models\WorkshopState;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    public function __invoke(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Panel principal',
            'description' => 'Visualiza métricas clave de tu negocio y gestiona las operaciones activas.',
        ])->toArray();

        return view('Dashboard', compact('meta'));
    }

    /**
     * Obtiene todos los KPIs del dashboard principal
     */
    public function kpis(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $companyId = $user->company_id;

            if (!$companyId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Usuario sin compañía asignada',
                    'data' => [
                        'today_income' => 0,
                        'month_income' => 0,
                        'active_orders' => 0,
                        'completed_today' => 0,
                        'average_ticket' => 0,
                        'overdue_receivables' => 0,
                        'avg_repair_time' => 0,
                    ],
                ], 200);
            }

            $dateRange = $this->getDateRange($request);
            $startDate = $dateRange['start'];
            $endDate = $dateRange['end'];

            // Ingresos del Día / Mes
            $todayIncome = Invoice::where('company_id', $companyId)
                ->where('document_type', 'FACTURA')
                ->whereDate('issue_date', today())
                ->where('status', 'A')
                ->sum('total_amount');

            $monthIncome = Invoice::where('company_id', $companyId)
                ->where('document_type', 'FACTURA')
                ->whereBetween('issue_date', [
                    now()->startOfMonth(),
                    now()->endOfMonth()
                ])
                ->where('status', 'A')
                ->sum('total_amount');

            // Órdenes de Trabajo Activas (excluyendo entregadas y canceladas)
            $activeOrders = WorkshopOrder::where('workshop_orders.company_id', $companyId)
                ->where('workshop_orders.status', 'A')
                ->join('workshop_states', 'workshop_orders.state_id', '=', 'workshop_states.id')
                ->where('workshop_states.name', '!=', 'Entregado')
                ->where('workshop_states.name', '!=', 'Cancelado')
                ->count(DB::raw('DISTINCT workshop_orders.id'));

            // Órdenes Completadas Hoy
            $completedToday = WorkshopOrder::where('workshop_orders.company_id', $companyId)
                ->whereDate('workshop_orders.updated_at', today())
                ->join('workshop_states', 'workshop_orders.state_id', '=', 'workshop_states.id')
                ->where('workshop_states.name', 'LIKE', '%Entregado%')
                ->where('workshop_orders.status', 'A')
                ->count(DB::raw('DISTINCT workshop_orders.id'));

            // Tickets Promedio de Venta
            $totalInvoices = Invoice::where('company_id', $companyId)
                ->where('document_type', 'FACTURA')
                ->whereBetween('issue_date', [$startDate, $endDate])
                ->where('status', 'A')
                ->count();

            $totalRevenue = Invoice::where('company_id', $companyId)
                ->where('document_type', 'FACTURA')
                ->whereBetween('issue_date', [$startDate, $endDate])
                ->where('status', 'A')
                ->sum('total_amount');

            $averageTicket = $totalInvoices > 0 ? ($totalRevenue / $totalInvoices) : 0;

            // Cuentas por Cobrar Vencidas
            $overdueReceivables = ReceivableEntry::where('company_id', $companyId)
                ->where('is_collected', false)
                ->where('movement_date', '<', today())
                ->sum(DB::raw('amount_cents / 100'));

            // Tiempo Promedio de Reparación (en días)
            $avgRepairTime = WorkshopOrder::where('workshop_orders.company_id', $companyId)
                ->join('workshop_states', 'workshop_orders.state_id', '=', 'workshop_states.id')
                ->where('workshop_states.name', 'LIKE', '%Entregado%')
                ->where('workshop_orders.status', 'A')
                ->whereBetween('workshop_orders.updated_at', [$startDate, $endDate])
                ->selectRaw('AVG(DATEDIFF(workshop_orders.updated_at, workshop_orders.created_at)) as avg_days')
                ->value('avg_days') ?? 0;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'today_income' => round($todayIncome ?? 0, 2),
                    'month_income' => round($monthIncome ?? 0, 2),
                    'active_orders' => $activeOrders ?? 0,
                    'completed_today' => $completedToday ?? 0,
                    'average_ticket' => round($averageTicket ?? 0, 2),
                    'overdue_receivables' => round($overdueReceivables ?? 0, 2),
                    'avg_repair_time' => round($avgRepairTime ?? 0, 1),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en dashboard KPIs: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al cargar KPIs',
                'data' => [
                    'today_income' => 0,
                    'month_income' => 0,
                    'active_orders' => 0,
                    'completed_today' => 0,
                    'average_ticket' => 0,
                    'overdue_receivables' => 0,
                    'avg_repair_time' => 0,
                ],
            ], 200);
        }
    }

    /**
     * Obtiene datos para gráficos
     */
    public function charts(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $companyId = $user->company_id;

            if (!$companyId) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'income_evolution' => [],
                        'orders_by_state' => [],
                        'devices_by_category' => [],
                        'top_services' => [],
                    ],
                ], 200);
            }

            $dateRange = $this->getDateRange($request);
            $startDate = $dateRange['start'];
            $endDate = $dateRange['end'];
            $days = $dateRange['days'];

            // Evolución de Ingresos (últimos N días)
            $incomeEvolution = Invoice::where('company_id', $companyId)
                ->where('document_type', 'FACTURA')
                ->whereBetween('issue_date', [$startDate, $endDate])
                ->where('status', 'A')
                ->selectRaw('DATE(issue_date) as date, SUM(total_amount) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => Carbon::parse($item->date)->format('d/m'),
                        'total' => (float) $item->total,
                    ];
                });

            // Órdenes de Trabajo por Estado
            $ordersByState = WorkshopOrder::where('workshop_orders.company_id', $companyId)
                ->where('workshop_orders.status', 'A')
                ->join('workshop_states', 'workshop_orders.state_id', '=', 'workshop_states.id')
                ->selectRaw('workshop_states.name as state, COUNT(DISTINCT workshop_orders.id) as count')
                ->groupBy('workshop_states.name')
                ->get()
                ->map(function ($item) {
                    return [
                        'state' => $item->state,
                        'count' => (int) $item->count,
                    ];
                });

            // Dispositivos Reparados por Tipo (por categoría)
            $devicesByCategory = WorkshopOrder::where('workshop_orders.company_id', $companyId)
                ->where('workshop_orders.status', 'A')
                ->join('workshop_states', 'workshop_orders.state_id', '=', 'workshop_states.id')
                ->where('workshop_states.name', 'LIKE', '%Entregado%')
                ->join('workshop_categories', 'workshop_orders.category_id', '=', 'workshop_categories.id')
                ->selectRaw('workshop_categories.name as category, COUNT(DISTINCT workshop_orders.id) as count')
                ->groupBy('workshop_categories.name')
                ->get()
                ->map(function ($item) {
                    return [
                        'category' => $item->category,
                        'count' => (int) $item->count,
                    ];
                });

            // Top 5 Servicios Más Solicitados
            $topServices = WorkshopOrderService::where('workshop_order_services.company_id', $companyId)
                ->where('workshop_order_services.status', 'A')
                ->join('services', 'workshop_order_services.service_id', '=', 'services.id')
                ->selectRaw('services.name as service, COUNT(*) as count')
                ->groupBy('services.name')
                ->orderByDesc('count')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'service' => $item->service,
                        'count' => (int) $item->count,
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'income_evolution' => $incomeEvolution ?? [],
                    'orders_by_state' => $ordersByState ?? [],
                    'devices_by_category' => $devicesByCategory ?? [],
                    'top_services' => $topServices ?? [],
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en dashboard charts: ' . $e->getMessage());
            return response()->json([
                'status' => 'success',
                'data' => [
                    'income_evolution' => [],
                    'orders_by_state' => [],
                    'devices_by_category' => [],
                    'top_services' => [],
                ],
            ], 200);
        }
    }

    /**
     * Obtiene tablas de actividad reciente
     */
    public function recentActivity(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $companyId = $user->company_id;

            if (!$companyId) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'recent_orders' => [],
                        'recent_sales' => [],
                        'ready_to_deliver' => [],
                        'recent_transfers' => [],
                    ],
                ], 200);
            }

            // Últimas Órdenes de Trabajo Actualizadas
            $recentOrders = WorkshopOrder::where('workshop_orders.company_id', $companyId)
                ->where('workshop_orders.status', 'A')
                ->with(['customer', 'state', 'equipment.brand', 'equipment.model'])
                ->orderByDesc('workshop_orders.updated_at')
                ->limit(10)
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'customer_name' => $order->customer?->display_name ?? 'N/A',
                        'equipment' => ($order->equipment?->brand?->name ?? '') . ' ' . ($order->equipment?->model?->name ?? ''),
                        'state' => $order->state?->name ?? 'N/A',
                        'updated_at' => $order->updated_at->diffForHumans(),
                    ];
                });

            // Últimas Ventas Realizadas
            $recentSales = Invoice::where('company_id', $companyId)
                ->where('document_type', 'FACTURA')
                ->where('status', 'A')
                ->with('customer')
                ->orderByDesc('issue_date')
                ->limit(10)
                ->get()
                ->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'customer_name' => $invoice->customer?->display_name ?? 'N/A',
                        'total_amount' => number_format($invoice->total_amount, 2, '.', ','),
                        'issue_date' => $invoice->issue_date->format('d/m/Y'),
                    ];
                });

            // Próximas Entregas de Equipos (Listo para Entregar)
            $readyToDeliver = WorkshopOrder::where('workshop_orders.company_id', $companyId)
                ->where('workshop_orders.status', 'A')
                ->join('workshop_states', 'workshop_orders.state_id', '=', 'workshop_states.id')
                ->where(function ($query) {
                    $query->where('workshop_states.name', 'LIKE', '%Listo%')
                        ->orWhere('workshop_states.name', 'LIKE', '%Entregar%');
                })
                ->with(['customer', 'equipment.brand', 'equipment.model'])
                ->select('workshop_orders.*')
                ->orderBy('workshop_orders.promised_at')
                ->limit(10)
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'customer_name' => $order->customer?->display_name ?? 'N/A',
                        'equipment' => ($order->equipment?->brand?->name ?? '') . ' ' . ($order->equipment?->model?->name ?? ''),
                        'promised_at' => $order->promised_at ? $order->promised_at->format('d/m/Y') : 'N/A',
                    ];
                });

            // Últimos Movimientos de Inventario Críticos (transferencias recientes)
            $recentTransfers = ProductTransfer::where('company_id', $companyId)
                ->where('status', 'A')
                ->with(['originWarehouse', 'destinationWarehouse'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(function ($transfer) {
                    return [
                        'id' => $transfer->id,
                        'reference' => $transfer->reference ?? 'N/A',
                        'origin' => $transfer->originWarehouse?->name ?? 'N/A',
                        'destination' => $transfer->destinationWarehouse?->name ?? 'N/A',
                        'date' => $transfer->movement_date->format('d/m/Y'),
                        'created_at' => $transfer->created_at->diffForHumans(),
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'recent_orders' => $recentOrders ?? [],
                    'recent_sales' => $recentSales ?? [],
                    'ready_to_deliver' => $readyToDeliver ?? [],
                    'recent_transfers' => $recentTransfers ?? [],
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en dashboard recent activity: ' . $e->getMessage());
            return response()->json([
                'status' => 'success',
                'data' => [
                    'recent_orders' => [],
                    'recent_sales' => [],
                    'ready_to_deliver' => [],
                    'recent_transfers' => [],
                ],
            ], 200);
        }
    }

    /**
     * Obtiene el rango de fechas desde el request
     */
    private function getDateRange(Request $request): array
    {
        $range = $request->input('range', '7'); // Por defecto últimos 7 días

        switch ($range) {
            case 'today':
                return [
                    'start' => today(),
                    'end' => today(),
                    'days' => 1,
                ];
            case 'yesterday':
                return [
                    'start' => today()->subDay(),
                    'end' => today()->subDay(),
                    'days' => 1,
                ];
            case '7':
                return [
                    'start' => today()->subDays(6),
                    'end' => today(),
                    'days' => 7,
                ];
            case '30':
                return [
                    'start' => today()->subDays(29),
                    'end' => today(),
                    'days' => 30,
                ];
            case '90':
                return [
                    'start' => today()->subDays(89),
                    'end' => today(),
                    'days' => 90,
                ];
            case 'month':
                return [
                    'start' => now()->startOfMonth(),
                    'end' => now()->endOfMonth(),
                    'days' => now()->daysInMonth,
                ];
            case 'last_month':
                return [
                    'start' => now()->subMonth()->startOfMonth(),
                    'end' => now()->subMonth()->endOfMonth(),
                    'days' => now()->subMonth()->daysInMonth,
                ];
            default:
                return [
                    'start' => today()->subDays(6),
                    'end' => today(),
                    'days' => 7,
                ];
        }
    }
}
