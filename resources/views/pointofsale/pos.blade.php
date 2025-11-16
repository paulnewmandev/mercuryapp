<x-layouts.dashboard-layout :meta="$meta ?? []">
    <div class="fixed inset-0 z-40 overflow-hidden bg-gray-50 dark:bg-slate-900" data-pos-root>
        {{-- Header fijo --}}
        <header class="flex h-16 items-center justify-between border-b border-gray-200 bg-white px-6 shadow-sm dark:border-gray-800 dark:bg-slate-900">
            <div class="flex items-center gap-4">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ gettext('Punto de Venta') }}</h1>
                <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">{{ gettext('POS') }}</span>
            </div>
            <div class="flex items-center gap-4">
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-200 dark:bg-slate-800 dark:text-gray-300 dark:hover:bg-slate-700"
                    onclick="window.location.href='{{ route('dashboard') }}'"
                >
                    <i class="fa-solid fa-arrow-left"></i>
                    {{ gettext('Salir') }}
                </button>
            </div>
        </header>

        <div class="grid h-[calc(100vh-4rem)] grid-cols-[2fr_1fr] gap-5 p-5">
            
            {{-- Panel izquierdo - Productos y Búsqueda --}}
            <div class="flex flex-col overflow-hidden">
                <div class="mb-4 rounded-lg bg-white p-5 shadow-md dark:bg-slate-800">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Información de Venta') }}</h2>
                    
                    {{-- Selects en una sola fila --}}
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        {{-- Selección de vendedor --}}
                        <div>
                            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                {{ gettext('Vendedor') }}
                            </label>
                            <div class="relative" data-pos-salesperson>
                                <button
                                    type="button"
                                    data-pos-salesperson-trigger
                                    class="flex w-full items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white touch-manipulation"
                                >
                                    <span data-pos-salesperson-display class="truncate">{{ gettext('Seleccionar vendedor...') }}</span>
                                    <i class="fa-solid fa-chevron-down text-xs text-gray-400 ml-2 flex-shrink-0"></i>
                                </button>
                                <input type="hidden" data-pos-salesperson-id>
                                <div
                                    class="absolute left-0 right-0 top-full z-50 mt-2 hidden max-h-96 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-slate-800"
                                    data-pos-salesperson-dropdown
                                >
                                    <div class="border-b border-gray-200 p-3 dark:border-gray-700">
                                        <input
                                            type="search"
                                            data-pos-salesperson-search
                                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                            placeholder="{{ gettext('Buscar vendedor...') }}"
                                            autocomplete="off"
                                        >
                                    </div>
                                    <div class="max-h-64 overflow-y-auto" data-pos-salesperson-results></div>
                                </div>
                                <div class="mt-2 flex items-center gap-2" data-pos-salesperson-selected style="display: none;">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white truncate" data-pos-salesperson-name></span>
                                    <button
                                        type="button"
                                        data-pos-salesperson-clear
                                        class="text-rose-500 hover:text-rose-600 flex-shrink-0"
                                    >
                                        <i class="fa-solid fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Selección de cliente --}}
                        <div>
                            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                {{ gettext('Cliente') }} <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative" data-pos-customer>
                                <button
                                    type="button"
                                    data-pos-customer-trigger
                                    class="flex w-full items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white touch-manipulation"
                                >
                                    <span data-pos-customer-display class="truncate">{{ gettext('Seleccionar cliente...') }}</span>
                                    <i class="fa-solid fa-chevron-down text-xs text-gray-400 ml-2 flex-shrink-0"></i>
                                </button>
                                <input type="hidden" data-pos-customer-id required>
                                <div
                                    class="absolute left-0 right-0 top-full z-50 mt-2 hidden max-h-96 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-slate-800"
                                    data-pos-customer-dropdown
                                >
                                    <div class="border-b border-gray-200 p-3 dark:border-gray-700">
                                        <input
                                            type="search"
                                            data-pos-customer-search
                                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                            placeholder="{{ gettext('Buscar cliente...') }}"
                                            autocomplete="off"
                                        >
                                    </div>
                                    <div class="max-h-64 overflow-y-auto" data-pos-customer-results></div>
                                </div>
                                <div class="mt-2 flex items-center gap-2" data-pos-customer-selected style="display: none;">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white truncate" data-pos-customer-name></span>
                                    <button
                                        type="button"
                                        data-pos-customer-clear
                                        class="text-rose-500 hover:text-rose-600 flex-shrink-0"
                                    >
                                        <i class="fa-solid fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Cargar orden de trabajo --}}
                        <div>
                            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                {{ gettext('Orden de Trabajo') }} <span class="text-xs font-normal text-gray-400">(Opcional)</span>
                            </label>
                            <div class="relative" data-pos-work-order>
                                <button
                                    type="button"
                                    data-pos-work-order-trigger
                                    class="flex w-full items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white touch-manipulation"
                                >
                                    <span data-pos-work-order-display class="truncate">{{ gettext('Buscar orden...') }}</span>
                                    <i class="fa-solid fa-chevron-down text-xs text-gray-400 ml-2 flex-shrink-0"></i>
                                </button>
                                <input type="hidden" data-pos-work-order-id>
                                <div
                                    class="absolute left-0 right-0 top-full z-50 mt-2 hidden max-h-96 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-slate-800"
                                    data-pos-work-order-dropdown
                                >
                                    <div class="border-b border-gray-200 p-3 dark:border-gray-700">
                                        <input
                                            type="search"
                                            data-pos-work-order-search
                                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                            placeholder="{{ gettext('Buscar por número de orden...') }}"
                                            autocomplete="off"
                                        >
                                    </div>
                                    <div class="max-h-64 overflow-y-auto" data-pos-work-order-results></div>
                                </div>
                                <div class="mt-2 rounded-lg bg-primary/10 p-3" data-pos-work-order-selected style="display: none;">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate" data-pos-work-order-number></p>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 truncate" data-pos-work-order-customer></p>
                                        </div>
                                        <button
                                            type="button"
                                            data-pos-work-order-clear
                                            class="text-rose-500 hover:text-rose-600 flex-shrink-0"
                                        >
                                            <i class="fa-solid fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Búsqueda de productos y servicios --}}
                <div class="mb-4 rounded-lg bg-white p-5 shadow-md dark:bg-slate-800">
                    <div class="mb-4 flex gap-2">
                        <button
                            type="button"
                            data-pos-tab="products"
                            class="flex-1 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-strong active:scale-95"
                        >
                            {{ gettext('Productos') }}
                        </button>
                        <button
                            type="button"
                            data-pos-tab="services"
                            class="flex-1 rounded-lg bg-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-300 dark:bg-slate-700 dark:text-gray-300 dark:hover:bg-slate-600 active:scale-95"
                        >
                            {{ gettext('Servicios') }}
                        </button>
                    </div>
                    <div class="relative" data-pos-item>
                        <input
                            type="search"
                            data-pos-item-search
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-base text-gray-900 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                            placeholder="{{ gettext('Buscar productos por nombre o SKU...') }}"
                            autocomplete="off"
                        >
                        <div
                            class="absolute left-0 right-0 top-full z-50 mt-2 hidden max-h-96 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-slate-800"
                            data-pos-item-dropdown
                        >
                            <div class="max-h-80 overflow-y-auto" data-pos-item-results></div>
                        </div>
                    </div>
                </div>

                {{-- Grid de productos/servicios --}}
                <div class="flex-1 overflow-y-auto rounded-lg bg-white p-5 shadow-md dark:bg-slate-800" style="touch-action: pan-y;">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white" data-pos-products-title>{{ gettext('Productos Disponibles') }}</h2>
                    <div
                        class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"
                        data-pos-products-grid
                    >
                        <div class="col-span-full rounded-xl border-2 border-dashed border-gray-300 p-12 text-center dark:border-gray-700">
                            <i class="fa-solid fa-box-open mb-4 text-5xl text-gray-400"></i>
                            <p class="text-gray-500 dark:text-gray-400">{{ gettext('Busca productos o servicios para agregar') }}</p>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Panel derecho - Carrito y Resumen --}}
            <div class="flex flex-col overflow-hidden">
                <div class="flex h-full flex-col rounded-lg bg-white shadow-md dark:bg-slate-800">
                    
                    {{-- Lista de items del carrito --}}
                    <div class="flex-1 overflow-y-auto p-4" style="touch-action: pan-y; min-height: 200px;">
                        <div class="space-y-2" data-pos-cart-items>
                            <div class="rounded-lg border-2 border-dashed border-gray-300 p-8 text-center dark:border-gray-700">
                                <i class="fa-solid fa-shopping-cart mb-3 text-4xl text-gray-400"></i>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ gettext('Carrito vacío') }}</p>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Totales --}}
                    <div class="border-t border-gray-200 p-4 dark:border-gray-700">
                        <div class="mb-3 space-y-2">
                            <div class="flex items-center justify-between text-base">
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ gettext('Subtotal') }}</span>
                                <span class="font-semibold text-gray-900 dark:text-white" data-pos-subtotal>USD 0.00</span>
                            </div>
                            <div class="flex items-center justify-between text-base">
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ gettext('IVA (15%)') }}</span>
                                <span class="font-semibold text-gray-900 dark:text-white" data-pos-tax>USD 0.00</span>
                            </div>
                            <div class="flex items-center justify-between border-t-2 border-gray-300 pt-2 text-xl font-bold dark:border-gray-700">
                                <span class="text-gray-900 dark:text-white">{{ gettext('TOTAL') }}</span>
                                <span class="text-primary" data-pos-total>USD 0.00</span>
                            </div>
                            {{-- Abono de orden de trabajo --}}
                            <div class="flex items-center justify-between text-base text-amber-600 dark:text-amber-400" data-pos-advance style="display: none;">
                                <span class="font-medium">{{ gettext('Abono aplicado') }}</span>
                                <span class="font-semibold" data-pos-advance-amount>USD 0.00</span>
                            </div>
                            {{-- Restante a pagar --}}
                            <div class="flex items-center justify-between border-t-2 border-primary pt-2 text-lg font-bold text-primary" data-pos-remaining style="display: none;">
                                <span>{{ gettext('RESTANTE A PAGAR') }}</span>
                                <span data-pos-remaining-amount>USD 0.00</span>
                            </div>
                        </div>
                        
                        <button
                            type="button"
                            data-pos-complete-sale
                            class="w-full rounded-lg bg-primary px-6 py-4 text-lg font-bold text-white shadow-lg transition hover:bg-primary-strong active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
                            disabled
                        >
                            <i class="fa-solid fa-check-circle mr-2"></i>
                            {{ gettext('Completar Venta') }}
                        </button>
                        
                        <button
                            type="button"
                            data-pos-clear-cart
                            class="mt-2 w-full rounded-lg bg-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-300 dark:bg-slate-700 dark:text-gray-300 dark:hover:bg-slate-600 active:scale-95"
                        >
                            <i class="fa-solid fa-trash mr-2"></i>
                            {{ gettext('Limpiar Carrito') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Modal de Pago --}}
        <div id="modal-payment" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/50" data-pos-payment-modal>
            <div class="w-full max-w-md rounded-lg bg-white shadow-xl dark:bg-slate-800">
            <div class="rounded-t-lg bg-gradient-to-r from-primary to-primary-strong p-5 text-white">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold">
                        <i class="fa-solid fa-money-bill-wave mr-2"></i>
                        {{ gettext('Procesar Pago') }}
                    </h2>
                    <button
                        type="button"
                        data-pos-payment-close
                        class="rounded-lg p-2 transition hover:bg-white/20 active:scale-95"
                    >
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-5">
                {{-- Resumen de totales --}}
                <div class="mb-5 rounded-lg bg-gray-50 p-4 dark:bg-slate-900/50">
                    <div class="mb-3 flex items-center justify-between text-base">
                        <span class="text-gray-700 dark:text-gray-300">{{ gettext('Subtotal') }}</span>
                        <span class="font-semibold text-gray-900 dark:text-white" data-pos-payment-subtotal>USD 0.00</span>
                    </div>
                    <div class="mb-3 flex items-center justify-between text-base">
                        <span class="text-gray-700 dark:text-gray-300">{{ gettext('IVA (15%)') }}</span>
                        <span class="font-semibold text-gray-900 dark:text-white" data-pos-payment-tax>USD 0.00</span>
                    </div>
                    <div class="mb-3 flex items-center justify-between border-t-2 border-gray-300 pt-3 text-xl font-bold dark:border-gray-700">
                        <span class="text-gray-900 dark:text-white">{{ gettext('TOTAL') }}</span>
                        <span class="text-primary" data-pos-payment-total>USD 0.00</span>
                    </div>
                    {{-- Abono aplicado --}}
                    <div class="mb-3 flex items-center justify-between text-base text-amber-600 dark:text-amber-400" data-pos-payment-advance style="display: none;">
                        <span class="font-medium">{{ gettext('Abono aplicado') }}</span>
                        <span class="font-semibold" data-pos-payment-advance-amount>USD 0.00</span>
                    </div>
                    {{-- Restante a pagar --}}
                    <div class="flex items-center justify-between border-t-2 border-primary pt-3 text-xl font-bold text-primary" data-pos-payment-remaining style="display: none;">
                        <span>{{ gettext('RESTANTE A PAGAR') }}</span>
                        <span data-pos-payment-remaining-amount>USD 0.00</span>
                    </div>
                </div>
                
                {{-- Tipo de documento --}}
                <div class="mb-4">
                    <label class="mb-2 flex items-center gap-2 cursor-pointer">
                        <input
                            type="checkbox"
                            data-pos-document-type
                            class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary dark:border-gray-600 dark:bg-slate-800"
                        >
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            {{ gettext('Convertir en Nota de Venta') }}
                        </span>
                    </label>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ gettext('Si está marcado, se creará una Nota de Venta. Si no, se creará una Factura.') }}
                    </p>
                </div>
                
                {{-- Métodos de pago múltiples --}}
                <div class="mb-4">
                    <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                        <i class="fa-solid fa-credit-card mr-2"></i>
                        {{ gettext('Métodos de Pago') }}
                    </label>
                    <div class="space-y-3" data-pos-payment-methods>
                        {{-- Se agregarán dinámicamente --}}
                    </div>
                    <button
                        type="button"
                        data-pos-add-payment-method
                        class="mt-2 w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-slate-800 dark:text-gray-300 dark:hover:bg-slate-700"
                    >
                        <i class="fa-solid fa-plus mr-2"></i>
                        {{ gettext('Agregar Método de Pago') }}
                    </button>
                    <div class="mt-3 flex items-center justify-between border-t-2 border-gray-300 pt-3 font-bold dark:border-gray-700">
                        <span class="text-gray-900 dark:text-white">{{ gettext('Total Pagado') }}</span>
                        <span class="text-primary" data-pos-payment-total-paid>USD 0.00</span>
                    </div>
                </div>
                
                {{-- Notas --}}
                <div class="mb-5">
                    <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                        <i class="fa-solid fa-note-sticky mr-2"></i>
                        {{ gettext('Notas') }} <span class="text-xs font-normal text-gray-400">(Opcional)</span>
                    </label>
                    <textarea
                        data-pos-payment-notes
                        rows="3"
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                        placeholder="{{ gettext('Información adicional de la venta...') }}"
                    ></textarea>
                </div>
                
                {{-- Botones de acción --}}
                <div class="flex gap-3">
                    <button
                        type="button"
                        data-pos-payment-confirm
                        class="flex-1 rounded-lg bg-primary px-6 py-3 text-base font-bold text-white shadow-lg transition hover:bg-primary-strong active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <i class="fa-solid fa-check mr-2"></i>
                        {{ gettext('Confirmar Venta') }}
                    </button>
                    <button
                        type="button"
                        data-pos-payment-close
                        class="rounded-lg bg-gray-200 px-4 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-300 dark:bg-slate-700 dark:text-gray-300 dark:hover:bg-slate-600 active:scale-95"
                    >
                        {{ gettext('Cancelar') }}
                    </button>
                </div>
            </div>
        </div>
    </div>


</x-layouts.dashboard-layout>
