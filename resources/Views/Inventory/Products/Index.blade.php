<x-layouts.dashboard-layout :meta="$meta ?? []">
    <x-ui.breadcrumb :title="gettext('Productos')" :items="$breadcrumbItems" />

    <section
        class="grid gap-6 pb-12"
        data-products-index-root
        data-products-table-id="products-table"
        data-products-base-url="{{ url('inventory/products') }}"
    >
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($cards as $card)
                <article class="h-full rounded-2xl bg-gradient-to-br from-purple-500/80 to-indigo-600/90 p-5 text-white shadow-lg shadow-slate-900/10">
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

        @php
            $columns = [
                [
                    'key' => 'sku',
                    'label' => gettext('SKU'),
                    'text_class' => 'text-xs font-semibold tracking-widest text-heading',
                ],
                [
                    'key' => 'name',
                    'label' => gettext('Producto'),
                    'text_class' => 'text-xs text-heading',
                ],
                [
                    'key' => 'category_name',
                    'label' => gettext('Categoría'),
                    'text_class' => 'text-xs text-heading',
                ],
                [
                    'key' => 'stock_quantity',
                    'label' => gettext('Stock'),
                    'align' => 'center',
                    'text_class' => 'text-xs font-semibold text-heading',
                ],
                [
                    'key' => 'status',
                    'label' => gettext('Estado'),
                    'render' => 'status',
                    'align' => 'center',
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

        <x-ui.data-table
            tableId="products-table"
            apiUrl="{{ route('inventory.products.data') }}"
            :columns="$columns"
            :perPageOptions="[5, 10, 20, 50, 100]"
            :perPageDefault="10"
            perPageSelectWidthClass="w-[100px]"
            searchPlaceholder="{{ gettext('Buscar producto, línea o categoría...') }}"
            emptyTitle="{{ gettext('Sin productos registrados') }}"
            emptyDescription="{{ gettext('Registra tu primer producto para visualizarlo en este listado.') }}"
            :strings="[
                'loading' => gettext('Cargando productos...'),
                'showing' => gettext('Mostrando %from% - %to% de %total% productos'),
                'empty' => gettext('No se encontraron productos con los filtros establecidos.'),
            ]"
        >
            <x-slot:headerActions>
                <a
                    href="{{ route('inventory.products.create') }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-inverted shadow-sm transition hover:bg-primary-strong"
                >
                    <i class="fa-solid fa-plus"></i>
                    {{ gettext('Nuevo') }}
                </a>
            </x-slot:headerActions>
        </x-ui.data-table>

        <div
            class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-sm"
            data-product-modal="delete"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-8 flex min-h-full items-center justify-center">
                <div class="w-full max-w-md">
                <div class="relative w-full overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white">{{ gettext('Eliminar producto') }}</h3>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-product-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="space-y-4 px-6 py-6">
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            {{ gettext('¿Estás seguro de eliminar este producto? Esta acción no se puede deshacer.') }}
                        </p>
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                            <i class="fa-solid fa-circle-info mr-2"></i>
                            <span>{{ gettext('Se eliminará:') }} <strong data-product-delete-name></strong></span>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                            data-product-modal-close
                        >
                            {{ gettext('Cancelar') }}
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-70"
                            data-product-delete-confirm
                        >
                            {{ gettext('Eliminar') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.dashboard-layout>

