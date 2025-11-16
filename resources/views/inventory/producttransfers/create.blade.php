<x-layouts.dashboard-layout :meta="$meta ?? []">
    <x-ui.breadcrumb
        :title="gettext('Nuevo movimiento')"
        :items="$breadcrumbItems"
    />

    <section
        class="w-full space-y-6 pb-12"
        data-product-transfers-root
        data-product-transfers-store-url="{{ route('inventory.product_transfers.store') }}"
        data-product-transfers-base-url="{{ url('inventory/product-transfers') }}"
        data-product-transfers-table-id="product-transfers-table"
        data-product-transfers-warehouses='{!! json_encode($warehouses->map(fn ($warehouse) => [
            'id' => $warehouse->id,
            'label' => $warehouse->name,
        ])->values()->all(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}'
        data-product-transfers-products='{!! json_encode($products->map(fn ($product) => [
            'id' => $product->id,
            'label' => trim(($product->sku ? $product->sku . ' · ' : '') . $product->name),
        ])->values()->all(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}'
    >
        <article class="rounded-3xl border border-gray-200 bg-white/90 p-6 shadow-sm backdrop-blur dark:border-gray-800 dark:bg-slate-900/80">
            <div class="flex items-center justify-between mb-6">
                <a
                    href="{{ route('inventory.product_transfers.index') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                >
                    <i class="fa-solid fa-arrow-left"></i>
                    {{ gettext('Volver') }}
                </a>
            </div>

            <form
                method="POST"
                action="{{ route('inventory.product_transfers.store') }}"
                data-product-transfer-form
                class="space-y-6"
                novalidate
            >
                @csrf
                <input type="hidden" name="status" data-product-transfer-input="status" value="A">

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Fecha') }}</label>
                        <input
                            type="date"
                            name="movement_date"
                            data-product-transfer-input="movement_date"
                            value="{{ old('movement_date', date('Y-m-d')) }}"
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                            required
                        >
                        <p data-product-transfer-error="movement_date" class="mt-1 text-xs text-rose-500"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Referencia') }}</label>
                        <input
                            type="text"
                            name="reference"
                            data-product-transfer-input="reference"
                            value="{{ old('reference', '') }}"
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
                            <input type="hidden" name="origin_warehouse_id" data-product-transfer-input="origin_warehouse_id" value="{{ old('origin_warehouse_id', '') }}">
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
                            <input type="hidden" name="destination_warehouse_id" data-product-transfer-input="destination_warehouse_id" value="{{ old('destination_warehouse_id', '') }}">
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
                            {{ gettext('Agregar') }}
                        </button>
                    </div>
                    <p data-product-transfer-error="items" class="mt-1 text-xs text-rose-500"></p>

                    <div class="mt-3 space-y-3" data-product-transfer-items></div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Notas') }}</label>
                    <textarea
                        name="notes"
                        data-product-transfer-input="notes"
                        class="min-h-[100px] w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                    >{{ old('notes', '') }}</textarea>
                    <p data-product-transfer-error="notes" class="mt-1 text-xs text-rose-500"></p>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                    <a
                        href="{{ route('inventory.product_transfers.index') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                    >
                        {{ gettext('Cancelar') }}
                    </a>
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-inverted shadow-sm transition hover:bg-primary-strong disabled:cursor-not-allowed disabled:opacity-70"
                        data-product-transfer-submit
                    >
                        <span data-product-transfer-submit-label>{{ gettext('Guardar') }}</span>
                    </button>
                </div>
            </form>
        </article>
    </section>
</x-layouts.dashboard-layout>

