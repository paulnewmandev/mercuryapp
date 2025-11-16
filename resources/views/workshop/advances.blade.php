<x-layouts.dashboard-layout :meta="$meta ?? []">
    <x-ui.breadcrumb :title="gettext('Abonos de órdenes')" :items="$breadcrumbItems" />

    @php
        $columns = [
            [
                'key' => 'order_number',
                'label' => gettext('Orden'),
                'render' => 'text',
                'text_class' => 'text-sm font-semibold text-gray-900 dark:text-white',
            ],
            [
                'key' => 'customer_name',
                'label' => gettext('Cliente'),
                'render' => 'text',
            ],
            [
                'key' => 'payment_date_formatted',
                'label' => gettext('Fecha'),
                'render' => 'text',
            ],
            [
                'key' => 'amount_formatted',
                'label' => gettext('Monto'),
                'render' => 'text',
                'text_class' => 'text-sm font-semibold text-gray-900 dark:text-white',
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
        data-workshop-advances-root
        data-workshop-advances-table-id="workshop-advances-table"
        data-workshop-advances-base-url="{{ url('workshop/advances') }}"
        data-workshop-advances-store-url="{{ route('taller.abonos.store') }}"
        data-workshop-advances-options-url="{{ route('taller.abonos.options') }}"
        data-workshop-advances-orders-url="{{ route('taller.abonos.search.ordenes') }}"
    >
        <x-ui.data-table
            tableId="workshop-advances-table"
            apiUrl="{{ route('taller.abonos.data') }}"
            :columns="$columns"
            :perPageOptions="[5, 10, 20, 50, 100]"
            :perPageDefault="10"
            searchPlaceholder="{{ gettext('Buscar por número de orden o cliente...') }}"
            emptyTitle="{{ gettext('Sin abonos registrados') }}"
            emptyDescription="{{ gettext('Crea un nuevo abono para registrar un pago de una orden de trabajo.') }}"
            :strings="[
                'loading' => gettext('Cargando abonos...'),
                'showing' => gettext('Mostrando %from% - %to% de %total% abonos'),
                'empty' => gettext('No se encontraron abonos con los filtros actuales'),
            ]"
        >
            <x-slot:headerActions>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-inverted shadow-sm transition hover:bg-primary-strong"
                    data-workshop-advance-action="create"
                >
                    <i class="fa-solid fa-plus text-sm"></i>
                    {{ gettext('Nuevo') }}
                </button>
            </x-slot:headerActions>
        </x-ui.data-table>

        <!-- Form Modal -->
        <div
            class="fixed inset-0 z-[70] hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
            data-workshop-advance-modal="form"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-6 w-full max-w-4xl">
                <div class="relative max-h-[90vh] overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white" data-workshop-advance-modal-title></h3>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-workshop-advance-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form data-workshop-advance-form class="max-h-[90vh] overflow-y-auto px-6 py-6" novalidate>
                        <input type="hidden" data-workshop-advance-input="id">
                        <input type="hidden" data-workshop-advance-input="status" value="A">

                        <div class="grid gap-6">
                            <div class="space-y-2">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ gettext('Orden de trabajo') }} <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative" data-workshop-advance-order>
                                    <div class="flex w-full items-center gap-3 rounded-2xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 transition focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white" data-workshop-advance-order-control>
                                        <span class="text-gray-400 dark:text-gray-500">
                                            <i class="fa-solid fa-magnifying-glass"></i>
                                        </span>
                                        <input
                                            type="search"
                                            class="flex-1 border-none bg-transparent outline-none"
                                            placeholder="{{ gettext('Buscar por orden, cliente o equipo') }}"
                                            autocomplete="off"
                                            data-workshop-advance-order-search
                                        >
                                        <input type="hidden" data-workshop-advance-input="order_id" value="">
                                    </div>
                                    <div
                                        class="invisible absolute left-0 right-0 z-[80] mt-2 w-full origin-top scale-95 transform overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                        data-workshop-advance-order-results
                                        hidden
                                    ></div>
                                </div>
                                <p data-workshop-advance-error="order_id" class="text-xs text-rose-500"></p>
                            </div>

                            <div class="grid gap-5 lg:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ gettext('Monto') }} <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        data-workshop-advance-input="amount"
                                        class="w-full rounded-2xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                        required
                                    >
                                    <p data-workshop-advance-error="amount" class="text-xs text-rose-500"></p>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ gettext('Fecha de pago') }} <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="flex items-center gap-3 rounded-2xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 transition focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white">
                                        <span class="text-gray-400 dark:text-gray-500">
                                            <i class="fa-solid fa-calendar-days"></i>
                                        </span>
                                        <input
                                            type="datetime-local"
                                            data-workshop-advance-input="payment_date"
                                            class="flex-1 border-none bg-transparent outline-none"
                                            required
                                        >
                                    </div>
                                    <p data-workshop-advance-error="payment_date" class="text-xs text-rose-500"></p>
                                </div>
                            </div>

                            <div class="grid gap-5 lg:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ gettext('Método de pago') }}
                                    </label>
                                    <div
                                        class="relative"
                                        data-select
                                        data-select-name="payment_method_id"
                                        data-select-manual="true"
                                        data-select-invalid="false"
                                    >
                                        <input type="hidden" data-workshop-advance-input="payment_method_id" name="payment_method_id" value="">
                                        <button
                                            type="button"
                                            class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-surface dark:bg-slate-900 dark:text-white"
                                            data-select-trigger
                                            data-select-placeholder="{{ gettext('Selecciona un método de pago') }}"
                                            aria-haspopup="listbox"
                                            aria-expanded="false"
                                        >
                                            <span data-select-value class="truncate">{{ gettext('Selecciona un método de pago') }}</span>
                                            <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
                                        </button>
                                        <div
                                            class="invisible absolute left-0 right-0 z-[95] mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                            data-select-dropdown
                                            role="listbox"
                                            hidden
                                        >
                                            <div class="max-h-60 overflow-y-auto py-2" data-workshop-advance-payment-method-options></div>
                                        </div>
                                    </div>
                                    <p data-workshop-advance-error="payment_method_id" class="text-xs text-rose-500"></p>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ gettext('Referencia') }}
                                    </label>
                                    <input
                                        type="text"
                                        data-workshop-advance-input="reference"
                                        class="w-full rounded-2xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                        maxlength="255"
                                    >
                                    <p data-workshop-advance-error="reference" class="text-xs text-rose-500"></p>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ gettext('Notas') }}
                                </label>
                                <textarea
                                    data-workshop-advance-input="notes"
                                    class="min-h-[100px] w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                ></textarea>
                                <p data-workshop-advance-error="notes" class="text-xs text-rose-500"></p>
                            </div>
                        </div>

                        <div class="mt-6 flex items-center justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                                data-workshop-advance-modal-close
                            >
                                {{ gettext('Cancelar') }}
                            </button>
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-strong disabled:cursor-not-allowed disabled:opacity-70"
                                data-workshop-advance-submit
                            >
                                <span data-workshop-advance-submit-label>{{ gettext('Guardar') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- View Modal -->
        <div
            class="fixed inset-0 z-[60] hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
            data-workshop-advance-modal="view"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-6 w-full max-w-4xl">
                <div class="relative max-h-[88vh] overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white">{{ gettext('Detalle del abono') }}</h3>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-workshop-advance-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="grid max-h-[88vh] gap-4 overflow-y-auto px-6 py-6 lg:grid-cols-2">
                        @foreach ([
                            ['icon' => 'fa-solid fa-clipboard-list', 'label' => gettext('Orden de trabajo'), 'field' => 'order_number'],
                            ['icon' => 'fa-solid fa-user', 'label' => gettext('Cliente'), 'field' => 'customer_name'],
                            ['icon' => 'fa-solid fa-desktop', 'label' => gettext('Equipo'), 'field' => 'equipment_label'],
                            ['icon' => 'fa-solid fa-money-bill-wave', 'label' => gettext('Monto'), 'field' => 'amount_formatted'],
                            ['icon' => 'fa-solid fa-calendar-day', 'label' => gettext('Fecha de pago'), 'field' => 'payment_date_formatted'],
                            ['icon' => 'fa-solid fa-credit-card', 'label' => gettext('Método de pago'), 'field' => 'payment_method_name'],
                            ['icon' => 'fa-solid fa-hashtag', 'label' => gettext('Referencia'), 'field' => 'reference'],
                            ['icon' => 'fa-solid fa-circle-check', 'label' => gettext('Estado'), 'field' => 'status_label'],
                        ] as $card)
                            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/70">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary dark:bg-primary/20 dark:text-primary-100">
                                        <i class="{{ $card['icon'] }}"></i>
                                    </span>
                                    <div class="flex-1">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $card['label'] }}</span>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white" data-workshop-advance-view-field="{{ $card['field'] }}">-</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <div class="lg:col-span-2 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/70">
                            <div class="flex items-start gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary dark:bg-primary/20 dark:text-primary-100">
                                    <i class="fa-solid fa-note-sticky"></i>
                                </span>
                                <div class="flex-1">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Notas') }}</span>
                                    <p class="mt-1 whitespace-pre-line text-sm text-gray-900 dark:text-white" data-workshop-advance-view-field="notes">-</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                            data-workshop-advance-modal-close
                        >
                            {{ gettext('Cerrar') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Modal -->
        <div
            class="fixed inset-0 z-[65] hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
            data-workshop-advance-modal="delete"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-6 w-full max-w-md">
                <div class="overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white">{{ gettext('Eliminar abono') }}</h3>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-workshop-advance-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="space-y-4 px-6 py-6">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ gettext('¿Estás seguro de eliminar el abono seleccionado? Esta acción no se puede deshacer.') }}
                        </p>
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                            <i class="fa-solid fa-circle-info mr-2"></i>
                            <span>{{ gettext('Se eliminará:') }} <strong data-workshop-advance-delete-amount></strong></span>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                            data-workshop-advance-modal-close
                        >
                            {{ gettext('Cancelar') }}
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-70"
                            data-workshop-advance-delete-confirm
                        >
                            {{ gettext('Eliminar') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.dashboard-layout>

