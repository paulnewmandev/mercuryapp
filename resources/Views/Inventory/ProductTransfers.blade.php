<x-layouts.dashboard-layout :meta="$meta ?? []">
    <x-ui.breadcrumb :title="gettext('Movimientos de inventario')" :items="$breadcrumbItems" />

    @php
        $columns = [
            [
                'key' => 'movement_date_formatted',
                'label' => gettext('Fecha'),
            ],
            [
                'key' => 'reference',
                'label' => gettext('Referencia'),
            ],
            [
                'key' => 'origin_warehouse_name',
                'label' => gettext('Bodega origen'),
            ],
            [
                'key' => 'destination_warehouse_name',
                'label' => gettext('Bodega destino'),
            ],
            [
                'key' => 'items_count',
                'label' => gettext('Productos'),
                'align' => 'right',
            ],
            [
                'key' => 'actions',
                'label' => gettext('Acciones'),
                'render' => 'actions',
                'sortable' => false,
                'align' => 'center',
            ],
        ];
    @endphp

    <section
        class="w-full space-y-6 pb-12"
        data-product-transfers-root
        data-product-transfers-store-url="{{ route('inventory.product_transfers.store') }}"
        data-product-transfers-base-url="{{ url('inventory/product-transfers') }}"
        data-product-transfers-table-id="product-transfers-table"
        data-product-transfers-warehouses='@json($warehouses->map(fn ($warehouse) => [
            'id' => $warehouse->id,
            'label' => $warehouse->name,
        ]))'
        data-product-transfers-products='@json($products->map(fn ($product) => [
            'id' => $product->id,
            'label' => trim(($product->sku ? $product->sku . ' · ' : '') . $product->name),
        ]))'
    >
        <x-ui.data-table
            tableId="product-transfers-table"
            apiUrl="{{ route('inventory.product_transfers.data') }}"
            :columns="$columns"
            :perPageOptions="[5, 10, 20, 50, 100]"
            :perPageDefault="10"
            perPageSelectWidthClass="w-[100px]"
            searchPlaceholder="{{ gettext('Buscar...') }}"
            emptyTitle="{{ gettext('Sin datos registrados') }}"
            emptyDescription="{{ gettext('Cuando registres un elemento aparecerá en este listado.') }}"
            :strings="[
                'loading' => gettext('Cargando datos...'),
                'showing' => gettext('Mostrando %from% - %to% de %total% registros'),
                'empty' => gettext('No se encontraron registros con los filtros actuales'),
            ]"
        >
            <x-slot:headerActions>
                <a
                    href="{{ route('inventory.product_transfers.create') }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-inverted shadow-sm transition hover:bg-primary-strong"
                >
                    <i class="fa-solid fa-plus text-sm"></i>
                    {{ gettext('Nuevo') }}
                </a>
            </x-slot:headerActions>
        </x-ui.data-table>

        <!-- Form Modal -->
        <div
            class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
            data-product-transfer-modal="form"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-8 w-full max-w-4xl">
                <div class="relative w-full overflow-visible rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white" data-product-transfer-modal-title></h3>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-product-transfer-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form data-product-transfer-form class="space-y-6 px-6 py-6" novalidate>
                        <input type="hidden" data-product-transfer-input="id">
                        <input type="hidden" data-product-transfer-input="status" value="A">

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Fecha') }}</label>
                                <input
                                    type="date"
                                    data-product-transfer-input="movement_date"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                    required
                                >
                                <p data-product-transfer-error="movement_date" class="mt-1 text-xs text-rose-500"></p>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Referencia') }}</label>
                                <input
                                    type="text"
                                    data-product-transfer-input="reference"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                    maxlength="100"
                                >
                                <p data-product-transfer-error="reference" class="mt-1 text-xs text-rose-500"></p>
                            </div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Bodega origen') }}</label>
                                <div
                                    class="relative"
                                    data-select
                                    data-select-manual="true"
                                    data-select-name="origin_warehouse_id"
                                    data-select-invalid="false"
                                    data-product-transfer-select="origin"
                                >
                                    <input type="hidden" data-product-transfer-input="origin_warehouse_id" value="">
                                    <button
                                        type="button"
                                        class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                        data-select-trigger
                                        data-select-placeholder="{{ gettext('Selecciona una bodega') }}"
                                        aria-haspopup="listbox"
                                        aria-expanded="false"
                                    >
                                        <span data-select-value class="truncate">{{ gettext('Selecciona una bodega') }}</span>
                                        <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
                                    </button>
                                    <div
                                        class="invisible absolute left-0 right-0 z-[100] mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                        data-select-dropdown
                                        role="listbox"
                                        hidden
                                    >
                                        <div class="max-h-60 overflow-y-auto py-2" data-product-transfer-origin-options></div>
                                    </div>
                                </div>
                                <p data-product-transfer-error="origin_warehouse_id" class="mt-1 text-xs text-rose-500"></p>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Bodega destino') }}</label>
                                <div
                                    class="relative"
                                    data-select
                                    data-select-manual="true"
                                    data-select-name="destination_warehouse_id"
                                    data-select-invalid="false"
                                    data-product-transfer-select="destination"
                                >
                                    <input type="hidden" data-product-transfer-input="destination_warehouse_id" value="">
                                    <button
                                        type="button"
                                        class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                        data-select-trigger
                                        data-select-placeholder="{{ gettext('Selecciona una bodega') }}"
                                        aria-haspopup="listbox"
                                        aria-expanded="false"
                                    >
                                        <span data-select-value class="truncate">{{ gettext('Selecciona una bodega') }}</span>
                                        <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
                                    </button>
                                    <div
                                        class="invisible absolute left-0 right-0 z-[100] mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                        data-select-dropdown
                                        role="listbox"
                                        hidden
                                    >
                                        <div class="max-h-60 overflow-y-auto py-2" data-product-transfer-destination-options></div>
                                    </div>
                                </div>
                                <p data-product-transfer-error="destination_warehouse_id" class="mt-1 text-xs text-rose-500"></p>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Productos') }}</label>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-lg border border-primary px-3 py-1.5 text-xs font-semibold text-primary transition hover:bg-primary-soft/20 dark:border-primary/60 dark:text-primary"
                                    data-product-transfer-add-item
                                >
                                    <i class="fa-solid fa-plus text-[10px]"></i>
                                    {{ gettext('Agregar producto') }}
                                </button>
                            </div>
                            <p data-product-transfer-error="items" class="mt-1 text-xs text-rose-500"></p>

                            <div class="mt-3 space-y-3" data-product-transfer-items></div>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Notas') }}</label>
                            <textarea
                                data-product-transfer-input="notes"
                                class="min-h-[100px] w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                            ></textarea>
                            <p data-product-transfer-error="notes" class="mt-1 text-xs text-rose-500"></p>
                        </div>

                        <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                                data-product-transfer-modal-close
                            >
                                {{ gettext('Cancelar') }}
                            </button>
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-inverted shadow-sm transition hover:bg-primary-strong disabled:cursor-not-allowed disabled:opacity-70"
                                data-product-transfer-submit
                            >
                                <span data-product-transfer-submit-label>{{ gettext('Guardar') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- View Modal -->
        <div
            class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
            data-product-transfer-modal="view"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-8 w-full max-w-3xl">
                <div class="relative w-full overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white">{{ gettext('Detalle del movimiento') }}</h3>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-product-transfer-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="space-y-4 px-6 py-6" data-product-transfer-view>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Fecha') }}</span>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white" data-product-transfer-view-field="movement_date_formatted"></p>
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark	border-gray-800 dark:bg-slate-900/60">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Referencia') }}</span>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white" data-product-transfer-view-field="reference"></p>
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Bodega origen') }}</span>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white" data-product-transfer-view-field="origin_warehouse_name"></p>
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Bodega destino') }}</span>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white" data-product-transfer-view-field="destination_warehouse_name"></p>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Productos trasladados') }}</span>
                            <div class="mt-3 overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-slate-800 dark:text-gray-300">
                                        <tr>
                                            <th class="px-4 py-2 text-left font-semibold">{{ gettext('Producto') }}</th>
                                            <th class="px-4 py-2 text-left font-semibold">{{ gettext('SKU') }}</th>
                                            <th class="px-4 py-2 text-right font-semibold">{{ gettext('Cantidad') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-slate-900" data-product-transfer-view-items></tbody>
                                </table>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Notas') }}</span>
                            <p class="mt-1 whitespace-pre-wrap text-sm text-gray-900 dark:text-white" data-product-transfer-view-field="notes"></p>
                        </div>
                    </div>
                    <div class="flex justify-end border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                            data-product-transfer-modal-close
                        >
                            {{ gettext('Cerrar') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Modal -->
        <div
            class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
            data-product-transfer-modal="delete"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-8 w-full max-w-md">
                <div class="relative w-full overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white">{{ gettext('Eliminar movimiento') }}</h3>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify	center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-product-transfer-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="space-y-4 px-6 py-6">
                        <p class="text-sm text-gray-700 dark:text-gray-200">{{ gettext('¿Confirma que deseas eliminar este movimiento? Esta acción no se puede deshacer.') }}</p>
                        <div class="rounded-xl bg-gray-100 px-4 py-3 text-sm text-gray-700 dark:bg-slate-800 dark:text-gray-200" data-product-transfer-delete-reference></div>
                    </div>
                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                            data-product-transfer-modal-close
                        >
                            {{ gettext('Cancelar') }}
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-70"
                            data-product-transfer-delete-confirm
                        >
                            {{ gettext('Eliminar') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.dashboard-layout>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

