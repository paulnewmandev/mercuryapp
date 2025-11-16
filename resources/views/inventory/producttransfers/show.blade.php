<x-layouts.dashboard-layout :meta="$meta ?? []">
    <x-ui.breadcrumb
        :title="gettext('Detalle del movimiento')"
        :items="$breadcrumbItems"
    />

    <section class="grid gap-6 pb-12">
        <article class="rounded-3xl border border-gray-200 bg-white/90 p-6 shadow-sm backdrop-blur dark:border-gray-800 dark:bg-slate-900/80">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ gettext('Movimiento de inventario') }}</h1>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ $transferData['movement_date_formatted'] ?? '—' }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a
                        href="{{ route('inventory.product_transfers.edit', $transfer) }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-inverted shadow-sm transition hover:bg-primary-strong"
                    >
                        <i class="fa-regular fa-pen-to-square"></i>
                        {{ gettext('Editar') }}
                    </a>
                    <a
                        href="{{ route('inventory.product_transfers.index') }}"
                        class="inline-flex items-center gap-2 rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                    >
                        <i class="fa-solid fa-arrow-left"></i>
                        {{ gettext('Volver') }}
                    </a>
                </div>
            </div>
        </article>

        <div class="grid gap-6 lg:grid-cols-2">
            <article class="rounded-3xl border border-gray-200 bg-white/90 p-6 shadow-sm backdrop-blur dark:border-gray-800 dark:bg-slate-900/70">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Información general') }}</h2>
                <dl class="mt-5 space-y-4 text-sm text-gray-600 dark:text-gray-300">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="font-semibold text-gray-500 dark:text-gray-400">{{ gettext('Fecha') }}</dt>
                        <dd>{{ $transferData['movement_date_formatted'] ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="font-semibold text-gray-500 dark:text-gray-400">{{ gettext('Referencia') }}</dt>
                        <dd>{{ $transferData['reference'] ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="font-semibold text-gray-500 dark:text-gray-400">{{ gettext('Bodega origen') }}</dt>
                        <dd>{{ $transferData['origin_warehouse_name'] ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="font-semibold text-gray-500 dark:text-gray-400">{{ gettext('Bodega destino') }}</dt>
                        <dd>{{ $transferData['destination_warehouse_name'] ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="font-semibold text-gray-500 dark:text-gray-400">{{ gettext('Estado') }}</dt>
                        <dd>{{ $transferData['status_label'] ?? '—' }}</dd>
                    </div>
                    @if (!empty($transferData['notes']))
                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <dt class="font-semibold text-gray-500 dark:text-gray-400 mb-2">{{ gettext('Notas') }}</dt>
                            <dd class="whitespace-pre-wrap text-gray-900 dark:text-white">{{ $transferData['notes'] }}</dd>
                        </div>
                    @endif
                </dl>
            </article>

            <article class="rounded-3xl border border-gray-200 bg-white/90 p-6 shadow-sm backdrop-blur dark:border-gray-800 dark:bg-slate-900/70">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Productos trasladados') }}</h2>
                <div class="mt-5 overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-slate-800 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">{{ gettext('Producto') }}</th>
                                <th class="px-4 py-3 text-left font-semibold">{{ gettext('SKU') }}</th>
                                <th class="px-4 py-3 text-right font-semibold">{{ gettext('Cantidad') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-slate-900">
                            @forelse ($transferData['items'] ?? [] as $item)
                                <tr>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $item['product_name'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-300">{{ $item['product_sku'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($item['quantity'] ?? 0) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-center text-sm text-gray-500 dark:text-gray-300">
                                        {{ gettext('No hay productos registrados') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </div>
    </section>
</x-layouts.dashboard-layout>

