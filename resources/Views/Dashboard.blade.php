{{-- 
/**
 * @fileoverview Vista principal del dashboard con métricas, gráficos y tablas de actividad.
 */
--}}

@php
    $breadcrumbItems = [
        ['label' => gettext('Inicio'), 'url' => route('dashboard')],
        ['label' => gettext('Panel principal')],
    ];
@endphp

<x-layouts.dashboard-layout :meta="$meta ?? []">
    <x-ui.breadcrumb :title="gettext('Panel principal')" :items="$breadcrumbItems" />

    <section class="w-full space-y-6 pb-12" data-dashboard-root>
        {{-- Filtro de Fechas --}}
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-slate-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Filtros de Fecha') }}</h2>
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    class="dashboard-date-filter rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 active:bg-primary active:text-white dark:border-gray-600 dark:bg-slate-700 dark:text-gray-300 dark:hover:bg-slate-600"
                    data-range="today"
                >
                    {{ gettext('Hoy') }}
                </button>
                <button
                    type="button"
                    class="dashboard-date-filter rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-slate-700 dark:text-gray-300 dark:hover:bg-slate-600"
                    data-range="yesterday"
                >
                    {{ gettext('Ayer') }}
                </button>
                <button
                    type="button"
                    class="dashboard-date-filter rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-slate-700 dark:text-gray-300 dark:hover:bg-slate-600"
                    data-range="7"
                >
                    {{ gettext('Últimos 7 días') }}
                </button>
                <button
                    type="button"
                    class="dashboard-date-filter rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-slate-700 dark:text-gray-300 dark:hover:bg-slate-600"
                    data-range="30"
                >
                    {{ gettext('Últimos 30 días') }}
                </button>
                <button
                    type="button"
                    class="dashboard-date-filter rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-slate-700 dark:text-gray-300 dark:hover:bg-slate-600"
                    data-range="90"
                >
                    {{ gettext('Últimos 90 días') }}
                </button>
                <button
                    type="button"
                    class="dashboard-date-filter rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-slate-700 dark:text-gray-300 dark:hover:bg-slate-600"
                    data-range="month"
                >
                    {{ gettext('Este mes') }}
                </button>
                <button
                    type="button"
                    class="dashboard-date-filter rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-slate-700 dark:text-gray-300 dark:hover:bg-slate-600"
                    data-range="last_month"
                >
                    {{ gettext('Mes pasado') }}
                </button>
            </div>
        </div>

        {{-- Tarjetas de KPIs --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4" id="kpi-cards">
            {{-- Se cargarán dinámicamente --}}
        </div>

        {{-- Gráficos --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Evolución de Ingresos --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-slate-800">
                <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Evolución de Ingresos') }}</h3>
                <canvas id="incomeEvolutionChart" height="300"></canvas>
            </div>

            {{-- Órdenes de Trabajo por Estado --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-slate-800">
                <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Órdenes de Trabajo por Estado') }}</h3>
                <canvas id="ordersByStateChart" height="300"></canvas>
            </div>

            {{-- Dispositivos Reparados por Tipo --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-slate-800">
                <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Dispositivos Reparados por Tipo') }}</h3>
                <canvas id="devicesByCategoryChart" height="300"></canvas>
            </div>

            {{-- Top 5 Servicios Más Solicitados --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-slate-800">
                <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Top 5 Servicios Más Solicitados') }}</h3>
                <canvas id="topServicesChart" height="300"></canvas>
            </div>

        </div>

        {{-- Tablas de Actividad Reciente --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Últimas Órdenes de Trabajo --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-slate-800">
                <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Últimas Órdenes de Trabajo') }}</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm" id="recent-orders-table">
                        <thead class="border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Orden') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Cliente') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Equipo') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Estado') }}</th>
                            </tr>
                        </thead>
                        <tbody id="recent-orders-body">
                            {{-- Se cargará dinámicamente --}}
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Últimas Ventas --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-slate-800">
                <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Últimas Ventas') }}</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm" id="recent-sales-table">
                        <thead class="border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Factura') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Cliente') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Fecha') }}</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ gettext('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody id="recent-sales-body">
                            {{-- Se cargará dinámicamente --}}
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Próximas Entregas --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-slate-800">
                <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Próximas Entregas') }}</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm" id="ready-to-deliver-table">
                        <thead class="border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Orden') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Cliente') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Equipo') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Fecha Prometida') }}</th>
                            </tr>
                        </thead>
                        <tbody id="ready-to-deliver-body">
                            {{-- Se cargará dinámicamente --}}
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Últimos Movimientos de Inventario --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-slate-800">
                <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Últimos Movimientos de Inventario') }}</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm" id="recent-transfers-table">
                        <thead class="border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Referencia') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Origen') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Destino') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Fecha') }}</th>
                            </tr>
                        </thead>
                        <tbody id="recent-transfers-body">
                            {{-- Se cargará dinámicamente --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const root = document.querySelector('[data-dashboard-root]');
                if (!root) return;

                let currentRange = '7';
                let charts = {};

                // Inicializar dashboard
                loadDashboardData();

                // Event listeners para filtros de fecha
                root.querySelectorAll('.dashboard-date-filter').forEach(btn => {
                    btn.addEventListener('click', function() {
                        // Remover clase activa de todos
                        root.querySelectorAll('.dashboard-date-filter').forEach(b => {
                            b.classList.remove('active', 'bg-primary', 'text-white');
                            b.classList.add('bg-white', 'text-gray-700');
                        });
                        
                        // Agregar clase activa al seleccionado
                        this.classList.add('active', 'bg-primary', 'text-white');
                        this.classList.remove('bg-white', 'text-gray-700');
                        
                        currentRange = this.dataset.range;
                        loadDashboardData();
                    });
                });

                // Activar el botón por defecto
                const defaultBtn = root.querySelector('[data-range="7"]');
                if (defaultBtn) {
                    defaultBtn.click();
                }

                function loadDashboardData() {
                    loadKPIs();
                    loadCharts();
                    loadRecentActivity();
                }

                function loadKPIs() {
                    fetch(`{{ route('dashboard.kpis') }}?range=${currentRange}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                renderKPICards(data.data);
                            }
                        })
                        .catch(error => {
                            console.error('Error loading KPIs:', error);
                        });
                }

                function renderKPICards(kpis) {
                    const container = document.getElementById('kpi-cards');
                    if (!container) return;

                    const cards = [
                        {
                            title: gettext('Ingresos del Día'),
                            value: `USD ${kpis.today_income.toLocaleString('es-EC', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
                            icon: 'fa-solid fa-dollar-sign',
                            color: 'bg-green-500',
                        },
                        {
                            title: gettext('Ingresos del Mes'),
                            value: `USD ${kpis.month_income.toLocaleString('es-EC', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
                            icon: 'fa-solid fa-chart-line',
                            color: 'bg-blue-500',
                        },
                        {
                            title: gettext('Órdenes Activas'),
                            value: kpis.active_orders,
                            icon: 'fa-solid fa-clipboard-list',
                            color: 'bg-yellow-500',
                            alert: kpis.active_orders > 20 ? 'high' : kpis.active_orders > 10 ? 'medium' : 'low',
                        },
                        {
                            title: gettext('Órdenes Completadas Hoy'),
                            value: kpis.completed_today,
                            icon: 'fa-solid fa-check-circle',
                            color: 'bg-green-500',
                        },
                        {
                            title: gettext('Ticket Promedio'),
                            value: `USD ${kpis.average_ticket.toLocaleString('es-EC', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
                            icon: 'fa-solid fa-receipt',
                            color: 'bg-purple-500',
                        },
                        {
                            title: gettext('Cuentas por Cobrar Vencidas'),
                            value: `USD ${kpis.overdue_receivables.toLocaleString('es-EC', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
                            icon: 'fa-solid fa-exclamation-triangle',
                            color: kpis.overdue_receivables > 1000 ? 'bg-red-500' : kpis.overdue_receivables > 500 ? 'bg-yellow-500' : 'bg-green-500',
                            alert: kpis.overdue_receivables > 1000 ? 'high' : kpis.overdue_receivables > 500 ? 'medium' : 'low',
                        },
                        {
                            title: gettext('Tiempo Promedio de Reparación'),
                            value: `${kpis.avg_repair_time} ${gettext('días')}`,
                            icon: 'fa-solid fa-clock',
                            color: kpis.avg_repair_time > 7 ? 'bg-red-500' : kpis.avg_repair_time > 4 ? 'bg-yellow-500' : 'bg-green-500',
                            alert: kpis.avg_repair_time > 7 ? 'high' : kpis.avg_repair_time > 4 ? 'medium' : 'low',
                        },
                    ];

                    container.innerHTML = cards.map(card => `
                        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-slate-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">${card.title}</p>
                                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">${card.value}</p>
                                </div>
                                <div class="rounded-full ${card.color} p-3 text-white">
                                    <i class="${card.icon} text-xl"></i>
                                </div>
                            </div>
                            ${card.alert ? `
                                <div class="mt-4 flex items-center gap-2">
                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ${
                                        card.alert === 'high' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' :
                                        card.alert === 'medium' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' :
                                        'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                    }">
                                        ${card.alert === 'high' ? gettext('Alto') : card.alert === 'medium' ? gettext('Medio') : gettext('Bajo')}
                                    </span>
                                </div>
                            ` : ''}
                        </div>
                    `).join('');
                }

                function loadCharts() {
                    fetch(`{{ route('dashboard.charts') }}?range=${currentRange}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                renderCharts(data.data);
                            }
                        })
                        .catch(error => {
                            console.error('Error loading charts:', error);
                        });
                }

                function renderCharts(chartData) {
                    // Destruir gráficos existentes
                    Object.values(charts).forEach(chart => {
                        if (chart) chart.destroy();
                    });
                    charts = {};

                    // Evolución de Ingresos
                    const incomeCtx = document.getElementById('incomeEvolutionChart');
                    if (incomeCtx) {
                        charts.incomeEvolution = new Chart(incomeCtx, {
                            type: 'line',
                            data: {
                                labels: chartData.income_evolution.map(item => item.date),
                                datasets: [{
                                    label: gettext('Ingresos (USD)'),
                                    data: chartData.income_evolution.map(item => item.total),
                                    borderColor: 'rgb(59, 130, 246)',
                                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                    tension: 0.4,
                                    fill: true,
                                }],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: true,
                                    },
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            callback: function(value) {
                                                return 'USD ' + value.toLocaleString('es-EC');
                                            },
                                        },
                                    },
                                },
                            },
                        });
                    }

                    // Órdenes por Estado
                    const ordersStateCtx = document.getElementById('ordersByStateChart');
                    if (ordersStateCtx) {
                        charts.ordersByState = new Chart(ordersStateCtx, {
                            type: 'doughnut',
                            data: {
                                labels: chartData.orders_by_state.map(item => item.state),
                                datasets: [{
                                    data: chartData.orders_by_state.map(item => item.count),
                                    backgroundColor: [
                                        'rgb(59, 130, 246)',
                                        'rgb(16, 185, 129)',
                                        'rgb(245, 158, 11)',
                                        'rgb(239, 68, 68)',
                                        'rgb(139, 92, 246)',
                                    ],
                                }],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                    },
                                },
                            },
                        });
                    }

                    // Dispositivos por Categoría
                    const devicesCtx = document.getElementById('devicesByCategoryChart');
                    if (devicesCtx) {
                        charts.devicesByCategory = new Chart(devicesCtx, {
                            type: 'pie',
                            data: {
                                labels: chartData.devices_by_category.map(item => item.category),
                                datasets: [{
                                    data: chartData.devices_by_category.map(item => item.count),
                                    backgroundColor: [
                                        'rgb(59, 130, 246)',
                                        'rgb(16, 185, 129)',
                                        'rgb(245, 158, 11)',
                                        'rgb(239, 68, 68)',
                                        'rgb(139, 92, 246)',
                                        'rgb(236, 72, 153)',
                                    ],
                                }],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                    },
                                },
                            },
                        });
                    }

                    // Top 5 Servicios
                    const servicesCtx = document.getElementById('topServicesChart');
                    if (servicesCtx) {
                        charts.topServices = new Chart(servicesCtx, {
                            type: 'bar',
                            data: {
                                labels: chartData.top_services.map(item => item.service),
                                datasets: [{
                                    label: gettext('Cantidad'),
                                    data: chartData.top_services.map(item => item.count),
                                    backgroundColor: 'rgb(59, 130, 246)',
                                }],
                            },
                            options: {
                                indexAxis: 'y',
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false,
                                    },
                                },
                                scales: {
                                    x: {
                                        beginAtZero: true,
                                    },
                                },
                            },
                        });
                    }

                }

                function loadRecentActivity() {
                    fetch(`{{ route('dashboard.recent-activity') }}?range=${currentRange}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                renderRecentActivity(data.data);
                            }
                        })
                        .catch(error => {
                            console.error('Error loading recent activity:', error);
                        });
                }

                function renderRecentActivity(activity) {
                    // Últimas Órdenes
                    const ordersBody = document.getElementById('recent-orders-body');
                    if (ordersBody) {
                        ordersBody.innerHTML = activity.recent_orders.length > 0
                            ? activity.recent_orders.map(order => `
                                <tr class="border-b border-gray-100 dark:border-gray-700">
                                    <td class="px-4 py-3 text-gray-900 dark:text-white">
                                        <a href="/workshop/work-orders/${order.id}" class="text-primary hover:underline">${order.order_number}</a>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">${order.customer_name}</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">${order.equipment || 'N/A'}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">${order.state}</span>
                                    </td>
                                </tr>
                            `).join('')
                            : `<tr><td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">${gettext('No hay órdenes recientes')}</td></tr>`;
                    }

                    // Últimas Ventas
                    const salesBody = document.getElementById('recent-sales-body');
                    if (salesBody) {
                        salesBody.innerHTML = activity.recent_sales.length > 0
                            ? activity.recent_sales.map(sale => `
                                <tr class="border-b border-gray-100 dark:border-gray-700">
                                    <td class="px-4 py-3 text-gray-900 dark:text-white">
                                        <a href="/sales/invoices/${sale.id}" class="text-primary hover:underline">${sale.invoice_number}</a>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">${sale.customer_name}</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">${sale.issue_date}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">USD ${sale.total_amount}</td>
                                </tr>
                            `).join('')
                            : `<tr><td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">${gettext('No hay ventas recientes')}</td></tr>`;
                    }

                    // Próximas Entregas
                    const deliverBody = document.getElementById('ready-to-deliver-body');
                    if (deliverBody) {
                        deliverBody.innerHTML = activity.ready_to_deliver.length > 0
                            ? activity.ready_to_deliver.map(order => `
                                <tr class="border-b border-gray-100 dark:border-gray-700">
                                    <td class="px-4 py-3 text-gray-900 dark:text-white">
                                        <a href="/workshop/work-orders/${order.id}" class="text-primary hover:underline">${order.order_number}</a>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">${order.customer_name}</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">${order.equipment || 'N/A'}</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">${order.promised_at}</td>
                                </tr>
                            `).join('')
                            : `<tr><td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">${gettext('No hay entregas pendientes')}</td></tr>`;
                    }

                    // Últimos Movimientos
                    const transfersBody = document.getElementById('recent-transfers-body');
                    if (transfersBody) {
                        transfersBody.innerHTML = activity.recent_transfers.length > 0
                            ? activity.recent_transfers.map(transfer => `
                                <tr class="border-b border-gray-100 dark:border-gray-700">
                                    <td class="px-4 py-3 text-gray-900 dark:text-white">${transfer.reference}</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">${transfer.origin}</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">${transfer.destination}</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">${transfer.date}</td>
                                </tr>
                            `).join('')
                            : `<tr><td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">${gettext('No hay movimientos recientes')}</td></tr>`;
                    }
                }

                // Función helper para gettext (si no está disponible globalmente)
                function gettext(text) {
                    return text; // En producción, esto debería usar la función real de gettext
                }
            });
        </script>
    @endpush
</x-layouts.dashboard-layout>
