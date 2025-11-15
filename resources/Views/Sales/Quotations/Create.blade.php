<x-layouts.dashboard-layout :meta="$meta ?? []">
    <x-ui.breadcrumb :title="gettext('Nueva Cotización')" :items="$breadcrumbItems" />

    <section class="w-full space-y-6 pb-12" data-quotation-form-root>
        <form method="POST" action="{{ route('sales.quotations.store') }}" class="space-y-6" novalidate>
            @csrf

            {{-- Información del Cliente --}}
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-slate-900/40">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Información del Cliente') }}</h2>
                </div>
                <div class="px-6 py-6">
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ gettext('Cliente') }} <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative" data-quotation-customer>
                            <div class="flex w-full items-center gap-3 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 transition focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white">
                                <i class="fa-solid fa-magnifying-glass text-gray-400"></i>
                                <input
                                    type="search"
                                    class="flex-1 border-none bg-transparent outline-none"
                                    placeholder="{{ gettext('Buscar por nombre o documento') }}"
                                    autocomplete="off"
                                    data-quotation-customer-search
                                    required
                                >
                                <input type="hidden" name="customer_id" data-quotation-customer-id required>
                            </div>
                            <div
                                class="invisible absolute left-0 right-0 z-50 mt-2 w-full origin-top scale-95 transform overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                data-quotation-customer-results
                                hidden
                            >
                                <div class="max-h-80 overflow-y-auto py-2" data-quotation-customer-options>
                                    <!-- Options will be rendered here -->
                                </div>
                            </div>
                        </div>
                        @error('customer_id')
                            <p class="text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="px-6 py-4">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input
                                type="checkbox"
                                name="send_email"
                                value="1"
                                class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary dark:border-gray-600 dark:bg-slate-800"
                            >
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                {{ gettext('Enviar email a cliente') }}
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Productos y Servicios --}}
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-slate-900/40">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Productos y Servicios') }}</h2>
                </div>
                <div class="px-6 py-6">
                    <div class="mb-4 flex gap-2">
                        <button
                            type="button"
                            data-quotation-tab="products"
                            class="flex-1 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-strong"
                        >
                            {{ gettext('Productos') }}
                        </button>
                        <button
                            type="button"
                            data-quotation-tab="services"
                            class="flex-1 rounded-lg bg-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-300 dark:bg-slate-700 dark:text-gray-300 dark:hover:bg-slate-600"
                        >
                            {{ gettext('Servicios') }}
                        </button>
                    </div>

                    <div class="mb-4">
                        <input
                            type="search"
                            data-quotation-item-search
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                            placeholder="{{ gettext('Buscar productos por nombre o SKU...') }}"
                            autocomplete="off"
                        >
                    </div>

                    <div
                        class="grid max-h-96 grid-cols-2 gap-3 overflow-y-auto rounded-lg border border-gray-200 p-4 dark:border-gray-700 md:grid-cols-3 lg:grid-cols-4"
                        data-quotation-items-grid
                    >
                        <div class="col-span-full rounded-xl border-2 border-dashed border-gray-300 p-12 text-center dark:border-gray-700">
                            <i class="fa-solid fa-box-open mb-4 text-5xl text-gray-400"></i>
                            <p class="text-gray-500 dark:text-gray-400">{{ gettext('Busca productos o servicios para agregar') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Items Agregados --}}
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-slate-900/40">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Items Agregados') }}</h2>
                </div>
                <div class="px-6 py-6">
                    <div class="space-y-3" data-quotation-cart-items>
                        <div class="rounded-lg border-2 border-dashed border-gray-300 p-8 text-center dark:border-gray-700">
                            <i class="fa-solid fa-shopping-cart mb-3 text-4xl text-gray-400"></i>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ gettext('No hay items agregados') }}</p>
                        </div>
                    </div>

                    <div class="mt-6 border-t border-gray-200 pt-4 dark:border-gray-700">
                        <div class="flex items-center justify-between text-lg font-semibold">
                            <span class="text-gray-900 dark:text-white">{{ gettext('Total') }}</span>
                            <span class="text-primary" data-quotation-total>USD 0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Notas --}}
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-slate-900/40">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Notas') }}</h2>
                </div>
                <div class="px-6 py-6">
                    <textarea
                        name="notes"
                        rows="4"
                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                        placeholder="{{ gettext('Notas adicionales sobre la cotización...') }}"
                    ></textarea>
                </div>
            </div>

            {{-- Botones de Acción --}}
            <div class="flex items-center justify-end gap-4">
                <a
                    href="{{ route('sales.quotations.index') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-6 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300 dark:hover:bg-slate-800"
                >
                    {{ gettext('Cancelar') }}
                </a>
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-2.5 text-sm font-semibold text-inverted shadow-sm transition hover:bg-primary-strong"
                >
                    <i class="fa-solid fa-save text-sm"></i>
                    {{ gettext('Guardar Cotización') }}
                </button>
            </div>
        </form>
    </section>

    @push('scripts')
        <script>
            // JavaScript para manejar la cotización se agregará en App.js
            // Por ahora, la funcionalidad básica está lista
        </script>
    @endpush
</x-layouts.dashboard-layout>

