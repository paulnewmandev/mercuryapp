<x-layouts.dashboard-layout :meta="$meta ?? []">
    <x-ui.breadcrumb :title="gettext('Cuentas por cobrar')" :items="$breadcrumbItems" />

    @php
        $columns = [
            [
                'key' => 'movement_date',
                'label' => gettext('Fecha'),
            ],
            [
                'key' => 'receivable_category_name',
                'label' => gettext('Categoría'),
            ],
            [
                'key' => 'concept',
                'label' => gettext('Concepto'),
            ],
            [
                'key' => 'amount_formatted',
                'label' => gettext('Monto'),
                'align' => 'right',
                'sortable' => false,
            ],
            [
                'key' => 'is_collected',
                'label' => gettext('Estado'),
                'render' => 'status_badge',
                'align' => 'center',
                'sortable' => false,
                'trueLabel' => gettext('Cobrada'),
                'falseLabel' => gettext('Pendiente'),
                'trueClasses' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300',
                'falseClasses' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-200',
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
        data-receivables-root
        data-receivables-store-url="{{ route('accounting.receivables.store') }}"
        data-receivables-base-url="{{ url('accounting/receivables') }}"
        data-receivables-table-id="receivables-table"
        data-receivables-types='@json($categories)'
        data-receivables-options-url="{{ route('accounting.receivables.options') }}"
    >
        <x-ui.data-table
            tableId="receivables-table"
            apiUrl="{{ route('accounting.receivables.data') }}"
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
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-inverted shadow-sm transition hover:bg-primary-strong"
                    data-receivable-action="create"
                >
                    <i class="fa-solid fa-plus text-sm"></i>
                    {{ gettext('Nuevo') }}
                </button>
            </x-slot:headerActions>
        </x-ui.data-table>

        <div
            class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
            data-receivable-modal="form"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-8 w-full max-w-3xl">
                <div class="relative w-full overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white" data-receivable-modal-title></h3>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-receivable-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form data-receivable-form class="space-y-6 px-6 py-6" novalidate>
                        <input type="hidden" data-receivable-input="id">

                        <div class="grid gap-5 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Categoría') }}</label>
                                <div
                                    class="relative"
                                    data-select
                                    data-select-name="receivable_category_id"
                                    data-select-invalid="false"
                                >
                                    <input type="hidden" data-receivable-input="receivable_category_id" value="">
                                    <button
                                        type="button"
                                        class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                        data-select-trigger
                                        data-select-placeholder="{{ gettext('Selecciona una categoría') }}"
                                        aria-haspopup="listbox"
                                        aria-expanded="false"
                                    >
                                        <span data-select-value class="truncate">{{ gettext('Selecciona una categoría') }}</span>
                                        <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
                                    </button>
                                    <div
                                        class="invisible absolute left-0 right-0 z-40 mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                        data-select-dropdown
                                        role="listbox"
                                        hidden
                                    >
                                        <div class="max-h-60 overflow-y-auto py-2" data-receivable-type-options></div>
                                    </div>
                                </div>
                                <p data-receivable-error="receivable_category_id" class="mt-1 text-xs text-rose-500"></p>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Fecha') }}</label>
                                <input
                                    type="date"
                                    data-receivable-input="movement_date"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                    required
                                >
                                <p data-receivable-error="movement_date" class="mt-1 text-xs text-rose-500"></p>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Monto') }}</label>
                                <div class="relative">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        data-receivable-input="amount"
                                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 pr-16 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                        required
                                    >
                                    <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-xs font-semibold text-gray-500 dark:text-gray-400" data-receivable-amount-currency>USD</span>
                                </div>
                                <p data-receivable-error="amount" class="mt-1 text-xs text-rose-500"></p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Concepto') }}</label>
                                <input
                                    type="text"
                                    data-receivable-input="concept"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                    maxlength="255"
                                    required
                                >
                                <p data-receivable-error="concept" class="mt-1 text-xs text-rose-500"></p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Referencia') }}</label>
                                <input
                                    type="text"
                                    data-receivable-input="reference"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                    maxlength="100"
                                >
                                <p data-receivable-error="reference" class="mt-1 text-xs text-rose-500"></p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Descripción') }}</label>
                                <textarea
                                    data-receivable-input="description"
                                    class="min-h-[120px] w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                ></textarea>
                                <p data-receivable-error="description" class="mt-1 text-xs text-rose-500"></p>
                            </div>

                            <div class="md:col-span-2" data-receivable-status-field>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Estado') }}</label>
                                <div
                                    class="relative"
                                    data-select
                                    data-select-name="is_collected"
                                    data-select-invalid="false"
                                >
                                    <input type="hidden" data-receivable-input="is_collected" value="0">
                                    <button
                                        type="button"
                                        class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                        data-select-trigger
                                        data-select-placeholder="{{ gettext('Selecciona un estado') }}"
                                        aria-haspopup="listbox"
                                        aria-expanded="false"
                                    >
                                        <span data-select-value class="truncate">{{ gettext('Pendiente') }}</span>
                                        <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
                                    </button>
                                    <div
                                        class="invisible absolute left-0 right-0 z-[100] mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                        data-select-dropdown
                                        role="listbox"
                                        hidden
                                    >
                                        <div class="max-h-60 overflow-y-auto py-2">
                                            <button
                                                type="button"
                                                class="group flex w-full items-center justify-between px-4 py-2 text-left text-sm text-gray-800 transition hover:bg-primary-soft/20 data-[selected=true]:bg-primary-soft/30 data-[selected=true]:text-primary dark:text-white"
                                                data-select-option
                                                data-value="0"
                                                data-label="{{ gettext('Pendiente') }}"
                                                role="option"
                                                data-selected="true"
                                            >
                                                <span class="truncate">{{ gettext('Pendiente') }}</span>
                                                <i class="fa-solid fa-circle-check text-primary opacity-0 transition group-data-[selected=true]:opacity-100" data-select-check></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="group flex w-full items-center justify-between px-4 py-2 text-left text-sm text-gray-800 transition hover:bg-primary-soft/20 data-[selected=true]:bg-primary-soft/30 data-[selected=true]:text-primary dark:text-white"
                                                data-select-option
                                                data-value="1"
                                                data-label="{{ gettext('Cobrada') }}"
                                                role="option"
                                                data-selected="false"
                                            >
                                                <span class="truncate">{{ gettext('Cobrada') }}</span>
                                                <i class="fa-solid fa-circle-check text-primary opacity-0 transition group-data-[selected=true]:opacity-100" data-select-check></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <p data-receivable-error="is_collected" class="mt-1 text-xs text-rose-500"></p>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                                data-receivable-modal-close
                            >
                                {{ gettext('Cancelar') }}
                            </button>
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-inverted shadow-sm transition hover:bg-primary-strong disabled:cursor-not-allowed disabled:opacity-70"
                                data-receivable-submit
                            >
                                <span data-receivable-submit-label>{{ gettext('Guardar') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div
            class="fixed inset-0 z-40 hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
            data-receivable-modal="view"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-8 w-full max-w-3xl">
                <div class="relative w-full overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white">{{ gettext('Detalle de la cuenta por cobrar') }}</h3>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-receivable-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="space-y-4 px-6 py-6" data-receivable-view>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary">
                                        <i class="fa-solid fa-tag"></i>
                                    </span>
                                    <div class="flex-1">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Categoría') }}</span>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white" data-receivable-view-field="receivable_category_name">-</p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary">
                                        <i class="fa-solid fa-calendar-days"></i>
                                    </span>
                                    <div class="flex-1">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Fecha') }}</span>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white" data-receivable-view-field="movement_date_formatted">-</p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary">
                                        <i class="fa-solid fa-money-bill-wave"></i>
                                    </span>
                                    <div class="flex-1">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Monto') }}</span>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white" data-receivable-view-field="amount_formatted">-</p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary">
                                        <i class="fa-solid fa-circle-check"></i>
                                    </span>
                                    <div class="flex-1">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Estado') }}</span>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white" data-receivable-view-field="status_label">-</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400" data-receivable-view-field="collected_at_label"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary">
                                        <i class="fa-solid fa-hashtag"></i>
                                    </span>
                                    <div class="flex-1">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Referencia') }}</span>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white" data-receivable-view-field="reference">-</p>
                                    </div>
                                </div>
                            </div>
                            <div class="sm:col-span-2 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary">
                                        <i class="fa-solid fa-align-left"></i>
                                    </span>
                                    <div class="flex-1">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Descripción') }}</span>
                                        <p class="mt-1 whitespace-pre-line text-sm text-gray-900 dark:text-white" data-receivable-view-field="description">-</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                            data-receivable-modal-close
                        >
                            {{ gettext('Cerrar') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
            data-receivable-modal="delete"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-8 w-full max-w-md">
                <div class="relative w-full overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white">{{ gettext('Eliminar cuenta por cobrar') }}</h3>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-receivable-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="space-y-4 px-6 py-6">
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            {{ gettext('¿Estás seguro de eliminar la cuenta por cobrar seleccionada? Esta acción no se puede deshacer.') }}
                        </p>
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                            <i class="fa-solid fa-circle-info mr-2"></i>
                            <span>{{ gettext('Se eliminará:') }} <strong data-receivable-delete-concept></strong></span>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                            data-receivable-modal-close
                        >
                            {{ gettext('Cancelar') }}
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-70"
                            data-receivable-delete-confirm
                        >
                            {{ gettext('Eliminar') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.dashboard-layout>

