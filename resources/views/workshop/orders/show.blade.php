<x-layouts.dashboard-layout :meta="$meta ?? []">
    <x-ui.breadcrumb :title="$order->order_number ?? gettext('Ver orden')" :items="$breadcrumbItems" />

    <section class="w-full space-y-6 pb-12">
        <!-- Encabezado con acciones -->
        <div class="flex items-center justify-between rounded-2xl border border-gray-200 bg-white px-6 py-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/40">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $order->order_number }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ gettext('Creada el') }} {{ $order->created_at->translatedFormat('d M Y H:i') }}
                    @if($order->promised_at)
                        · {{ gettext('Prometida para') }} {{ $order->promised_at->translatedFormat('d M Y H:i') }}
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a
                    href="{{ route('workshop.orders.label', $order) }}"
                    target="_blank"
                    class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:bg-slate-800 dark:text-gray-300"
                >
                    <i class="fa-solid fa-tag text-sm"></i>
                    {{ gettext('Etiqueta') }}
                </a>
                <a
                    href="{{ route('workshop.orders.ticket', $order) }}"
                    target="_blank"
                    class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:bg-slate-800 dark:text-gray-300"
                >
                    <i class="fa-solid fa-receipt text-sm"></i>
                    {{ gettext('Ticket') }}
                </a>
                <a
                    href="{{ route('taller.ordenes.edit', $order) }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:bg-slate-800 dark:text-gray-300"
                >
                    <i class="fa-regular fa-pen-to-square text-sm"></i>
                    {{ gettext('Editar') }}
                </a>
                <a
                    href="{{ route('taller.ordenes_de_trabajo') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:bg-slate-800 dark:text-gray-300"
                >
                    <i class="fa-solid fa-arrow-left text-sm"></i>
                    {{ gettext('Volver') }}
                </a>
            </div>
        </div>

        <!-- Información Básica -->
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-slate-900/40">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Información básica') }}</h2>
            </div>

            <div class="px-6 py-6">
                <div class="grid gap-6 lg:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Cliente') }}</label>
                        <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $order->customer?->display_name ?? 'N/A' }}</p>
                        @if($order->customer?->document_number)
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ gettext('Documento') }}: {{ $order->customer->document_number }}</p>
                        @endif
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Equipo') }}</label>
                        <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                            {{ ($order->equipment?->brand?->name ?? '') . ' · ' . ($order->equipment?->model?->name ?? '') . ' · ' . ($order->equipment?->identifier ?? 'N/A') }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Categoría de taller') }}</label>
                        <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $order->category?->name ?? 'N/A' }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Estado de taller') }}</label>
                        <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $order->state?->name ?? 'N/A' }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Prioridad') }}</label>
                        <span class="mt-1 inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            {{ $order->priority }}
                        </span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Usuario responsable') }}</label>
                        <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $order->responsible?->display_name ?? 'N/A' }}</p>
                    </div>

                    @if($order->note)
                        <div class="lg:col-span-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Nota') }}</label>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ $order->note }}</p>
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Diagnóstico realizado') }}</label>
                        <span class="mt-1 inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $order->diagnosis ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200' }}">
                            {{ $order->diagnosis ? gettext('Sí') : gettext('No') }}
                        </span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Cuenta con garantía') }}</label>
                        <span class="mt-1 inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $order->warranty ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200' }}">
                            {{ $order->warranty ? gettext('Sí') : gettext('No') }}
                        </span>
                    </div>

                    @if($order->equipment_password)
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Contraseña del equipo') }}</label>
                            <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $order->equipment_password }}</p>
                        </div>
                    @endif

                    @if($order->budget_amount > 0)
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Presupuesto estimado') }}</label>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                {{ number_format($order->budget_amount, 2, '.', ',') }}
                            </p>
                        </div>
                    @endif

                    @if($order->advance_amount > 0)
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Abono recibido') }}</label>
                            <p class="mt-1 text-sm font-semibold text-green-600 dark:text-green-400">
                                {{ number_format($order->advance_amount, 2, '.', ',') }}
                            </p>
                        </div>
                    @endif
                </div>

                @if($order->accessories->isNotEmpty())
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-800">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">{{ gettext('Accesorios entregados') }}</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($order->accessories as $accessory)
                                <span class="inline-flex items-center gap-2 rounded-lg bg-primary/10 px-3 py-1.5 text-sm font-medium text-primary">
                                    {{ $accessory->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Notas -->
        @if($order->notes->where('status', 'A')->isNotEmpty())
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-slate-900/40">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Notas') }}</h2>
                </div>

                <div class="px-6 py-6">
                    <div class="space-y-4">
                        @foreach($order->notes->where('status', 'A') as $note)
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-slate-800/50">
                                <p class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ $note->note }}</p>
                                <div class="mt-3 flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                    <span class="flex items-center gap-1.5">
                                        <i class="fa-solid fa-user"></i>
                                        {{ $note->user->display_name }}
                                    </span>
                                    <span class="flex items-center gap-1.5">
                                        <i class="fa-solid fa-clock"></i>
                                        {{ $note->created_at->translatedFormat('d M Y H:i') }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Productos -->
        @if($order->items->where('status', 'A')->isNotEmpty())
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-slate-900/40">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Productos') }}</h2>
                </div>

                <div class="overflow-x-auto px-6 py-6">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Producto') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Cantidad') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Precio unitario') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Subtotal') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($order->items->where('status', 'A') as $item)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->product?->name ?? 'N/A' }}</p>
                                            @if($item->product?->sku)
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ gettext('SKU') }}: {{ $item->product->sku }}</p>
                                            @endif
                                            @if($item->notes)
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $item->notes }}</p>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->quantity }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ number_format($item->unit_price, 2, '.', ',') }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($item->subtotal, 2, '.', ',') }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Servicios -->
        @if($order->services->where('status', 'A')->isNotEmpty())
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-slate-900/40">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Servicios') }}</h2>
                </div>

                <div class="overflow-x-auto px-6 py-6">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Servicio') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Cantidad') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Precio unitario') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Subtotal') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($order->services->where('status', 'A') as $service)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $service->service?->name ?? 'N/A' }}</p>
                                            @if($service->service?->description)
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $service->service->description }}</p>
                                            @endif
                                            @if($service->notes)
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $service->notes }}</p>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $service->quantity }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ number_format($service->unit_price, 2, '.', ',') }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($service->subtotal, 2, '.', ',') }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Resumen de Costos -->
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-slate-900/40">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Resumen de costos') }}</h2>
            </div>

            <div class="px-6 py-6">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ gettext('Subtotal productos') }}</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ number_format($order->items->where('status', 'A')->sum('subtotal'), 2, '.', ',') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ gettext('Subtotal servicios') }}</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ number_format($order->services->where('status', 'A')->sum('subtotal'), 2, '.', ',') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700">
                        <span class="text-base font-semibold text-gray-900 dark:text-white">{{ gettext('Costo total') }}</span>
                        <span class="text-base font-bold text-gray-900 dark:text-white">
                            {{ number_format($order->total_cost ?? ($order->items->where('status', 'A')->sum('subtotal') + $order->services->where('status', 'A')->sum('subtotal')), 2, '.', ',') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ gettext('Total pagado (abonos)') }}</span>
                        <span class="text-sm font-semibold text-green-600 dark:text-green-400">
                            {{ number_format($order->total_paid ?? $order->advances->where('status', 'A')->sum('amount'), 2, '.', ',') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700">
                        <span class="text-base font-semibold {{ ($order->balance ?? 0) >= 0 ? 'text-gray-900' : 'text-rose-600' }} dark:{{ ($order->balance ?? 0) >= 0 ? 'text-white' : 'text-rose-400' }}">
                            {{ gettext('Balance pendiente') }}
                        </span>
                        <span class="text-base font-bold {{ ($order->balance ?? 0) >= 0 ? 'text-gray-900' : 'text-rose-600' }} dark:{{ ($order->balance ?? 0) >= 0 ? 'text-white' : 'text-rose-400' }}">
                            {{ number_format($order->balance ?? (($order->items->where('status', 'A')->sum('subtotal') + $order->services->where('status', 'A')->sum('subtotal')) - $order->advances->where('status', 'A')->sum('amount')), 2, '.', ',') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Abonos -->
        @if($order->advances->where('status', 'A')->isNotEmpty())
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-slate-900/40">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Abonos') }}</h2>
                </div>

                <div class="overflow-x-auto px-6 py-6">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Fecha') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Método de pago') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Monto') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Referencia') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Notas') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($order->advances->where('status', 'A') as $advance)
                                <tr>
                                    <td class="px-4 py-3">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $advance->payment_date?->translatedFormat('d M Y') ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $advance->payment_method_id ? gettext($advance->payment_method_id) : 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="text-sm font-semibold text-green-600 dark:text-green-400">
                                            {{ number_format($advance->amount, 2, '.', ',') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-sm text-gray-900 dark:text-white">{{ $advance->reference ?? 'N/A' }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $advance->notes ?? 'N/A' }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </section>
</x-layouts.dashboard-layout>

