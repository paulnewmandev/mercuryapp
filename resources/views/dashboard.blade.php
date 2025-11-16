{{-- 
/**
 * @fileoverview Vista principal del dashboard con tablas de actividad reciente.
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

    <section class="grid gap-6 pb-12 w-full">
        {{-- Cards de totales --}}
        <div class="grid grid-cols-4 gap-4">
            @foreach ($cards ?? [] as $card)
                <article class="rounded-2xl bg-linear-to-br from-purple-500/80 to-indigo-600/90 p-5 text-white shadow-lg shadow-slate-900/10">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-white/80">{{ $card['label'] }}</p>
                            <p class="mt-2 text-3xl font-bold leading-none">{{ $card['value'] }}</p>
                        </div>
                        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-white/15 text-lg">
                            <i class="{{ $card['icon'] }}"></i>
                        </span>
                    </div>
                    <p class="mt-4 text-sm text-white/80">{{ $card['trend'] }}</p>
                </article>
            @endforeach
        </div>

        {{-- Gráficos de Ingresos y Egresos --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Gráfico de Ingresos --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/3">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ gettext('Ingresos por Mes') }}</h3>
                    <div class="text-sm font-semibold text-green-600 dark:text-green-400">
                        Total: USD {{ number_format($monthlyData['total_income'] ?? 0, 2) }}
                    </div>
                </div>
                <div class="relative h-80">
                    <canvas id="incomeChart"></canvas>
                </div>
            </div>

            {{-- Gráfico de Egresos --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/3">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ gettext('Egresos por Mes') }}</h3>
                    <div class="text-sm font-semibold text-red-600 dark:text-red-400">
                        Total: USD {{ number_format($monthlyData['total_expense'] ?? 0, 2) }}
                    </div>
                </div>
                <div class="relative h-80">
                    <canvas id="expenseChart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Últimas Órdenes de Trabajo --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/3">
                <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">{{ gettext('Últimas Órdenes de Trabajo') }}</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Orden') }}</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Cliente') }}</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Equipo') }}</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Estado') }}</th>
                            </tr>
                        </thead>
                        <tbody id="recent-orders-body">
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">{{ gettext('Cargando...') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Últimas Ventas --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/3">
                <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">{{ gettext('Últimas Ventas') }}</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Factura') }}</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Cliente') }}</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Fecha') }}</th>
                                <th class="px-4 py-2.5 text-right font-semibold text-gray-900 dark:text-white">{{ gettext('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody id="recent-sales-body">
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">{{ gettext('Cargando...') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Próximas Entregas --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/3">
                <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">{{ gettext('Próximas Entregas') }}</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Orden') }}</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Cliente') }}</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Equipo') }}</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Fecha Prometida') }}</th>
                            </tr>
                        </thead>
                        <tbody id="ready-to-deliver-body">
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">{{ gettext('Cargando...') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Últimos Movimientos de Inventario --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/3">
                <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white/90">{{ gettext('Últimos Movimientos de Inventario') }}</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-4 py-2.5 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Referencia') }}</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Origen') }}</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Destino') }}</th>
                                <th class="px-4 py-2.5 text-left font-semibold text-gray-900 dark:text-white">{{ gettext('Fecha') }}</th>
                            </tr>
                        </thead>
                        <tbody id="recent-transfers-body">
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">{{ gettext('Cargando...') }}</td>
                            </tr>
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
                console.log('Dashboard: Iniciando carga de datos...');
                
                // Inicializar gráficos
                initializeCharts();
                
                // Cargar actividad reciente
                loadRecentActivity();

                function initializeCharts() {
                    @php
                        $defaultData = [
                            'months' => [],
                            'incomes' => [],
                            'expenses' => [],
                            'total_income' => 0,
                            'total_expense' => 0
                        ];
                        $chartData = $monthlyData ?? $defaultData;
                    @endphp
                    const monthlyData = @json($chartData);
                    
                    // Gráfico de Ingresos (Barras Verticales)
                    const incomeCtx = document.getElementById('incomeChart');
                    if (incomeCtx && monthlyData.months && monthlyData.months.length > 0) {
                        new Chart(incomeCtx, {
                            type: 'bar',
                            data: {
                                labels: monthlyData.months,
                                datasets: [{
                                    label: 'Ingresos (USD)',
                                    data: monthlyData.incomes,
                                    backgroundColor: 'rgba(34, 197, 94, 0.8)',
                                    borderColor: 'rgba(34, 197, 94, 1)',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'top',
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return 'Ingresos: USD ' + context.parsed.y.toLocaleString('es-EC', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            callback: function(value) {
                                                return 'USD ' + value.toLocaleString('es-EC');
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }

                    // Gráfico de Egresos (Barras Horizontales)
                    const expenseCtx = document.getElementById('expenseChart');
                    if (expenseCtx && monthlyData.months && monthlyData.months.length > 0) {
                        new Chart(expenseCtx, {
                            type: 'bar',
                            data: {
                                labels: monthlyData.months,
                                datasets: [{
                                    label: 'Egresos (USD)',
                                    data: monthlyData.expenses,
                                    backgroundColor: 'rgba(239, 68, 68, 0.8)',
                                    borderColor: 'rgba(239, 68, 68, 1)',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                indexAxis: 'y',
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'top',
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return 'Egresos: USD ' + context.parsed.x.toLocaleString('es-EC', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        beginAtZero: true,
                                        ticks: {
                                            callback: function(value) {
                                                return 'USD ' + value.toLocaleString('es-EC');
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }
                }

                function loadRecentActivity() {
                    const url = '{{ route('dashboard.recent-activity') }}';
                    console.log('Dashboard: Cargando desde:', url);
                    
                    fetch(url, {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin'
                    })
                        .then(response => {
                            console.log('Dashboard: Respuesta recibida:', response.status);
                            if (!response.ok) {
                                throw new Error('Network response was not ok: ' + response.status);
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log('Dashboard: Datos recibidos:', data);
                            if (data.status === 'success') {
                                renderRecentActivity(data.data);
                            } else {
                                console.error('Dashboard: Estado de respuesta no exitoso:', data);
                                showError('Error al cargar datos');
                            }
                        })
                        .catch(error => {
                            console.error('Dashboard: Error loading recent activity:', error);
                            showError('Error de conexión: ' + error.message);
                        });
                }

                function showError(message) {
                    const containers = ['recent-orders-body', 'recent-sales-body', 'ready-to-deliver-body', 'recent-transfers-body'];
                    containers.forEach(id => {
                        const el = document.getElementById(id);
                        if (el) {
                            el.innerHTML = `<tr><td colspan="4" class="px-4 py-6 text-center text-red-500">${message}</td></tr>`;
                        }
                        });
                }

                function renderRecentActivity(activity) {
                    console.log('Dashboard: Renderizando datos:', activity);
                    
                    // Últimas Órdenes de Trabajo
                    const ordersBody = document.getElementById('recent-orders-body');
                    if (ordersBody) {
                        if (activity.recent_orders && activity.recent_orders.length > 0) {
                            ordersBody.innerHTML = activity.recent_orders.map(order => `
                                <tr class="border-b border-gray-100 dark:border-gray-700">
                                    <td class="px-4 py-2.5 text-gray-900 dark:text-white">
                                        <a href="/workshop/work-orders/${order.id}" class="text-primary hover:underline">${order.order_number || 'N/A'}</a>
                                    </td>
                                    <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400">${order.customer_name || 'N/A'}</td>
                                    <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400">${order.equipment || 'N/A'}</td>
                                    <td class="px-4 py-2.5">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">${order.state || 'N/A'}</span>
                                    </td>
                                </tr>
                            `).join('');
                        } else {
                            ordersBody.innerHTML = `<tr><td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">{{ gettext('No hay órdenes recientes') }}</td></tr>`;
                        }
                    }

                    // Últimas Ventas
                    const salesBody = document.getElementById('recent-sales-body');
                    if (salesBody) {
                        if (activity.recent_sales && activity.recent_sales.length > 0) {
                            salesBody.innerHTML = activity.recent_sales.map(sale => `
                                <tr class="border-b border-gray-100 dark:border-gray-700">
                                    <td class="px-4 py-2.5 text-gray-900 dark:text-white">
                                        <a href="/sales/invoices/${sale.id}" class="text-primary hover:underline">${sale.invoice_number || 'N/A'}</a>
                                    </td>
                                    <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400">${sale.customer_name || 'N/A'}</td>
                                    <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400">${sale.issue_date || 'N/A'}</td>
                                    <td class="px-4 py-2.5 text-right font-semibold text-gray-900 dark:text-white">${sale.total_amount || '0.00'}</td>
                                </tr>
                            `).join('');
                        } else {
                            salesBody.innerHTML = `<tr><td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">{{ gettext('No hay ventas recientes') }}</td></tr>`;
                        }
                    }

                    // Próximas Entregas
                    const deliverBody = document.getElementById('ready-to-deliver-body');
                    if (deliverBody) {
                        if (activity.ready_to_deliver && activity.ready_to_deliver.length > 0) {
                            deliverBody.innerHTML = activity.ready_to_deliver.map(order => `
                                <tr class="border-b border-gray-100 dark:border-gray-700">
                                    <td class="px-4 py-2.5 text-gray-900 dark:text-white">
                                        <a href="/workshop/work-orders/${order.id}" class="text-primary hover:underline">${order.order_number || 'N/A'}</a>
                                    </td>
                                    <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400">${order.customer_name || 'N/A'}</td>
                                    <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400">${order.equipment || 'N/A'}</td>
                                    <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400">${order.promised_at || 'N/A'}</td>
                                </tr>
                            `).join('');
                        } else {
                            deliverBody.innerHTML = `<tr><td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">{{ gettext('No hay entregas pendientes') }}</td></tr>`;
                        }
                    }

                    // Últimos Movimientos de Inventario
                    const transfersBody = document.getElementById('recent-transfers-body');
                    if (transfersBody) {
                        if (activity.recent_transfers && activity.recent_transfers.length > 0) {
                            transfersBody.innerHTML = activity.recent_transfers.map(transfer => `
                                <tr class="border-b border-gray-100 dark:border-gray-700">
                                    <td class="px-4 py-2.5 text-gray-900 dark:text-white">${transfer.reference || 'N/A'}</td>
                                    <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400">${transfer.origin || 'N/A'}</td>
                                    <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400">${transfer.destination || 'N/A'}</td>
                                    <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400">${transfer.date || 'N/A'}</td>
                                </tr>
                            `).join('');
                        } else {
                            transfersBody.innerHTML = `<tr><td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">{{ gettext('No hay movimientos recientes') }}</td></tr>`;
                        }
                    }
                }
            });
        </script>
    @endpush
</x-layouts.dashboard-layout>
