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

    <div class="mx-auto max-w-7xl p-4 pb-20 md:p-6 md:pb-6" data-dashboard-root>
        <div class="grid grid-cols-12 gap-4 md:gap-6">
            {{-- Filtro de Fechas --}}
            <div class="col-span-12 mb-4">
                <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ gettext('Filtros de Fecha') }}</h2>
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="dashboard-date-filter rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-slate-700 dark:text-gray-300 dark:hover:bg-slate-600"
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
            </div>

            <div class="col-span-12 space-y-6 xl:col-span-7">
                {{-- Metric Group One --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6" id="kpi-cards">
                    {{-- Se cargarán dinámicamente --}}
                </div>

                {{-- Chart One: Evolución de Ingresos --}}
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pt-5 sm:px-6 sm:pt-6 dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                            {{ gettext('Evolución de Ingresos') }}
                        </h3>
                    </div>
                    <div class="max-w-full overflow-x-auto custom-scrollbar">
                        <div id="chartOne" class="-ml-5 h-full min-w-[690px] pl-2 xl:min-w-full" style="min-height: 195px;"></div>
                    </div>
                </div>
            </div>

            <div class="col-span-12 xl:col-span-5">
                {{-- Chart Two: Estadísticas --}}
                <div class="rounded-2xl border border-gray-200 bg-gray-100 dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="shadow-default rounded-2xl bg-white px-5 pb-11 pt-5 dark:bg-gray-900 sm:px-6 sm:pt-6">
                        <div class="flex justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                                    {{ gettext('Órdenes por Estado') }}
                                </h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {{ gettext('Distribución actual') }}
                                </p>
                            </div>
                        </div>
                        <div class="relative max-h-[195px]">
                            <div id="chartTwo" class="h-full" style="min-height: 229px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-12">
                {{-- Chart Three: Estadísticas Detalladas --}}
                <div class="rounded-2xl border border-gray-200 bg-white px-5 pb-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6">
                    <div class="flex flex-col gap-5 mb-6 sm:flex-row sm:justify-between">
                        <div class="w-full">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                                {{ gettext('Estadísticas') }}
                            </h3>
                            <p class="mt-1 text-gray-500 text-sm dark:text-gray-400">
                                {{ gettext('Análisis detallado de servicios y categorías') }}
                            </p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        {{-- Dispositivos por Categoría --}}
                        <div>
                            <div id="chartDevices" class="h-full" style="min-height: 300px;"></div>
                        </div>
                        {{-- Top Servicios --}}
                        <div>
                            <div id="chartServices" class="h-full" style="min-height: 300px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tablas de Actividad Reciente --}}
            <div class="col-span-12 grid grid-cols-1 gap-6 lg:grid-cols-2">
                {{-- Últimas Órdenes de Trabajo --}}
                <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                    <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">{{ gettext('Últimas Órdenes de Trabajo') }}</h3>
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
                <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                    <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">{{ gettext('Últimas Ventas') }}</h3>
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
                <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                    <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">{{ gettext('Próximas Entregas') }}</h3>
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
                <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                    <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">{{ gettext('Últimos Movimientos de Inventario') }}</h3>
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
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js"></script>
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
                            b.classList.remove('bg-primary', 'text-white');
                            b.classList.add('bg-white', 'text-gray-700');
                        });
                        
                        // Agregar clase activa al seleccionado
                        this.classList.add('bg-primary', 'text-white');
                        this.classList.remove('bg-white', 'text-gray-700');
                        
                        currentRange = this.dataset.range;
                        loadDashboardData();
                    });
                });

                // Activar el botón por defecto
                const defaultBtn = root.querySelector('[data-range="7"]');
                if (defaultBtn) {
                    defaultBtn.classList.add('bg-primary', 'text-white');
                    defaultBtn.classList.remove('bg-white', 'text-gray-700');
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
                            value: kpis.today_income.toLocaleString('es-EC', { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
                            change: null,
                            icon: `<svg class="fill-gray-800 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C12.5523 2 13 2.44772 13 3V10.5858L17.2929 6.29289C17.6834 5.90237 18.3166 5.90237 18.7071 6.29289C19.0976 6.68342 19.0976 7.31658 18.7071 7.70711L12.7071 13.7071C12.3166 14.0976 11.6834 14.0976 11.2929 13.7071L5.29289 7.70711C4.90237 7.31658 4.90237 6.68342 5.29289 6.29289C5.68342 5.90237 6.31658 5.90237 6.70711 6.29289L11 10.5858V3C11 2.44772 11.4477 2 12 2ZM4 14C4 13.4477 4.44772 13 5 13H19C19.5523 13 20 13.4477 20 14C20 14.5523 19.5523 15 19 15H5C4.44772 15 4 14.5523 4 14ZM4 18C4 17.4477 4.44772 17 5 17H19C19.5523 17 20 17.4477 20 18C20 18.5523 19.5523 19 19 19H5C4.44772 19 4 18.5523 4 18Z" fill=""/>
                            </svg>`,
                            color: 'text-success-600',
                            bgColor: 'bg-success-50',
                            darkColor: 'dark:text-success-500',
                            darkBg: 'dark:bg-success-500/15'
                        },
                        {
                            title: gettext('Ingresos del Mes'),
                            value: kpis.month_income.toLocaleString('es-EC', { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
                            change: null,
                            icon: `<svg class="fill-gray-800 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M3 3C3 2.44772 3.44772 2 4 2H20C20.5523 2 21 2.44772 21 3V21C21 21.5523 20.5523 22 20 22H4C3.44772 22 3 21.5523 3 21V3ZM5 4V20H19V4H5ZM7 6H17V8H7V6ZM7 10H17V12H7V10ZM7 14H13V16H7V14Z" fill=""/>
                            </svg>`,
                            color: 'text-success-600',
                            bgColor: 'bg-success-50',
                            darkColor: 'dark:text-success-500',
                            darkBg: 'dark:bg-success-500/15'
                        },
                        {
                            title: gettext('Órdenes Activas'),
                            value: kpis.active_orders,
                            change: null,
                            icon: `<svg class="fill-gray-800 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M4 4C4 2.89543 4.89543 2 6 2H18C19.1046 2 20 2.89543 20 4V20C20 21.1046 19.1046 22 18 22H6C4.89543 22 4 21.1046 4 20V4ZM6 4V20H18V4H6ZM8 6H16V8H8V6ZM8 10H16V12H8V10ZM8 14H13V16H8V14Z" fill=""/>
                            </svg>`,
                            color: kpis.active_orders > 20 ? 'text-error-600' : kpis.active_orders > 10 ? 'text-warning-600' : 'text-success-600',
                            bgColor: kpis.active_orders > 20 ? 'bg-error-50' : kpis.active_orders > 10 ? 'bg-warning-50' : 'bg-success-50',
                            darkColor: kpis.active_orders > 20 ? 'dark:text-error-500' : kpis.active_orders > 10 ? 'dark:text-warning-500' : 'dark:text-success-500',
                            darkBg: kpis.active_orders > 20 ? 'dark:bg-error-500/15' : kpis.active_orders > 10 ? 'dark:bg-warning-500/15' : 'dark:bg-success-500/15'
                        },
                        {
                            title: gettext('Órdenes Completadas Hoy'),
                            value: kpis.completed_today,
                            change: null,
                            icon: `<svg class="fill-gray-800 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2ZM4 12C4 7.58172 7.58172 4 12 4C16.4183 4 20 7.58172 20 12C20 16.4183 16.4183 20 12 20C7.58172 20 4 16.4183 4 12ZM15.7071 9.29289C16.0976 9.68342 16.0976 10.3166 15.7071 10.7071L11.7071 14.7071C11.3166 15.0976 10.6834 15.0976 10.2929 14.7071L8.29289 12.7071C7.90237 12.3166 7.90237 11.6834 8.29289 11.2929C8.68342 10.9024 9.31658 10.9024 9.70711 11.2929L11 12.5858L14.2929 9.29289C14.6834 8.90237 15.3166 8.90237 15.7071 9.29289Z" fill=""/>
                            </svg>`,
                            color: 'text-success-600',
                            bgColor: 'bg-success-50',
                            darkColor: 'dark:text-success-500',
                            darkBg: 'dark:bg-success-500/15'
                        },
                    ];

                    container.innerHTML = cards.map(card => `
                        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-800">
                                ${card.icon}
                            </div>
                            <div class="mt-5 flex items-end justify-between">
                                <div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">${card.title}</span>
                                    <h4 class="mt-2 text-lg font-bold text-gray-800 dark:text-white/90">
                                        ${card.value}
                                    </h4>
                                </div>
                                ${card.change ? `
                                    <span class="flex items-center gap-1 rounded-full ${card.bgColor} ${card.darkBg} py-0.5 pl-2 pr-2.5 text-sm font-medium ${card.color} ${card.darkColor}">
                                        <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M5.56462 1.62393C5.70193 1.47072 5.90135 1.37432 6.12329 1.37432C6.1236 1.37432 6.12391 1.37432 6.12422 1.37432C6.31631 1.37415 6.50845 1.44731 6.65505 1.59381L9.65514 4.5918C9.94814 4.88459 9.94831 5.35947 9.65552 5.65246C9.36273 5.94546 8.88785 5.94562 8.59486 5.65283L6.87329 3.93247L6.87329 10.125C6.87329 10.5392 6.53751 10.875 6.12329 10.875C5.70908 10.875 5.37329 10.5392 5.37329 10.125L5.37329 3.93578L3.65516 5.65282C3.36218 5.94562 2.8873 5.94547 2.5945 5.65248C2.3017 5.35949 2.30185 4.88462 2.59484 4.59182L5.56462 1.62393Z" fill=""></path>
                                        </svg>
                                        ${card.change}
                                    </span>
                                ` : ''}
                            </div>
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
                    // Destruir gráficos existentes ANTES de crear nuevos (corrige el crecimiento infinito)
                    Object.keys(charts).forEach(key => {
                        if (charts[key] && typeof charts[key].destroy === 'function') {
                            charts[key].destroy();
                        }
                    });
                    charts = {};

                    // Chart One: Evolución de Ingresos (Bar Chart)
                    const chartOneEl = document.getElementById('chartOne');
                    if (chartOneEl) {
                        const labels = chartData.income_evolution.map(item => item.date);
                        const values = chartData.income_evolution.map(item => item.total);
                        
                        charts.chartOne = new ApexCharts(chartOneEl, {
                            series: [{
                                name: gettext('Ingresos (USD)'),
                                data: values
                            }],
                            chart: {
                                type: 'bar',
                                height: 180,
                                toolbar: { show: false }
                            },
                            plotOptions: {
                                bar: {
                                    borderRadius: 4,
                                    columnWidth: '55%',
                                }
                            },
                            dataLabels: { enabled: false },
                            stroke: { show: false },
                            xaxis: { categories: labels },
                            yaxis: {
                                labels: {
                                    formatter: function(val) {
                                        return 'USD ' + val.toLocaleString('es-EC');
                                    }
                                }
                            },
                            fill: {
                                type: 'gradient',
                                gradient: {
                                    shade: 'light',
                                    type: 'vertical',
                                    shadeIntensity: 0.4,
                                    gradientToColors: ['#465fff'],
                                    inverseColors: false,
                                    opacityFrom: 0.8,
                                    opacityTo: 0.2,
                                    stops: [0, 100]
                                }
                            },
                            colors: ['#465fff'],
                            tooltip: {
                                y: {
                                    formatter: function(val) {
                                        return 'USD ' + val.toLocaleString('es-EC');
                                    }
                                }
                            }
                        });
                        charts.chartOne.render();
                    }

                    // Chart Two: Órdenes por Estado (Donut Chart)
                    const chartTwoEl = document.getElementById('chartTwo');
                    if (chartTwoEl) {
                        const labels = chartData.orders_by_state.map(item => item.state);
                        const values = chartData.orders_by_state.map(item => item.count);
                        
                        charts.chartTwo = new ApexCharts(chartTwoEl, {
                            series: values,
                            chart: {
                                type: 'donut',
                                height: 229
                            },
                            labels: labels,
                            colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                            legend: {
                                position: 'bottom'
                            },
                            dataLabels: { enabled: false },
                            plotOptions: {
                                pie: {
                                    donut: {
                                        size: '75%'
                                    }
                                }
                            }
                        });
                        charts.chartTwo.render();
                    }

                    // Chart Devices: Dispositivos por Categoría
                    const chartDevicesEl = document.getElementById('chartDevices');
                    if (chartDevicesEl) {
                        const labels = chartData.devices_by_category.map(item => item.category);
                        const values = chartData.devices_by_category.map(item => item.count);
                        
                        charts.chartDevices = new ApexCharts(chartDevicesEl, {
                            series: values,
                            chart: {
                                type: 'pie',
                                height: 300
                            },
                            labels: labels,
                            colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'],
                            legend: {
                                position: 'bottom'
                            }
                        });
                        charts.chartDevices.render();
                    }

                    // Chart Services: Top Servicios (Bar Chart Horizontal)
                    const chartServicesEl = document.getElementById('chartServices');
                    if (chartServicesEl) {
                        const labels = chartData.top_services.map(item => item.service);
                        const values = chartData.top_services.map(item => item.count);
                        
                        charts.chartServices = new ApexCharts(chartServicesEl, {
                            series: [{
                                name: gettext('Cantidad'),
                                data: values
                            }],
                            chart: {
                                type: 'bar',
                                height: 300,
                                horizontal: true
                            },
                            plotOptions: {
                                bar: {
                                    borderRadius: 4,
                                }
                            },
                            dataLabels: { enabled: true },
                            xaxis: {
                                categories: labels
                            },
                            colors: ['#465fff']
                        });
                        charts.chartServices.render();
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