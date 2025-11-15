<x-layouts.dashboard-layout :meta="$meta ?? []">
    <x-ui.breadcrumb :title="gettext('Estados de taller')" :items="$breadcrumbItems" />

    @php
        $columns = [
            [
                'key' => 'category_name',
                'label' => gettext('Categoría'),
                'render' => 'text',
            ],
            [
                'key' => 'name',
                'label' => gettext('Nombre'),
            ],
            [
                'key' => 'description',
                'label' => gettext('Descripción'),
                'render' => 'text',
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

    <section
        class="w-full space-y-6 pb-12"
        data-workshop-states-root
        data-workshop-states-store-url="{{ route('taller.estados.store') }}"
        data-workshop-states-base-url="{{ url('workshop/states') }}"
        data-workshop-states-table-id="workshop-states-table"
        data-workshop-states-category-options-url="{{ route('taller.categorias.options') }}"
    >
        <x-ui.data-table
            tableId="workshop-states-table"
            apiUrl="{{ route('taller.estados.data') }}"
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
                    data-workshop-state-action="create"
                >
                    <i class="fa-solid fa-plus text-sm"></i>
                    {{ gettext('Nuevo') }}
                </button>
            </x-slot:headerActions>
        </x-ui.data-table>

        <!-- Form Modal -->
        <div
            class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
            data-workshop-state-modal="form"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-8 w-full max-w-xl">
                <div class="relative w-full overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white" data-workshop-state-modal-title></h3>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-workshop-state-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form data-workshop-state-form class="space-y-6 px-6 py-6" novalidate>
                        <input type="hidden" data-workshop-state-input="id">
                        <input type="hidden" data-workshop-state-input="status" value="A">

                        <div class="space-y-5">
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Categoría') }}</label>
                                <div
                                    class="relative"
                                    data-select
                                    data-select-name="category_id"
                                    data-select-manual="true"
                                    data-select-invalid="false"
                                >
                                    <input type="hidden" data-workshop-state-input="category_id" name="category_id" value="">
                                    <button
                                        type="button"
                                        class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-surface dark:bg-slate-900 dark:text-white"
                                        data-select-trigger
                                        data-select-placeholder="{{ gettext('Selecciona una categoría') }}"
                                        aria-haspopup="listbox"
                                        aria-expanded="false"
                                    >
                                        <span data-select-value class="truncate">{{ gettext('Selecciona una categoría') }}</span>
                                        <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
                                    </button>
                                    <div
                                        class="invisible absolute left-0 right-0 z-[90] mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                        data-select-dropdown
                                        role="listbox"
                                        hidden
                                    >
                                        <div class="max-h-60 overflow-y-auto py-2" data-workshop-state-category-options></div>
                                    </div>
                                </div>
                                <p data-workshop-state-error="category_id" class="mt-1 text-xs text-rose-500"></p>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Nombre') }}</label>
                                <input
                                    type="text"
                                    data-workshop-state-input="name"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                    maxlength="255"
                                    required
                                >
                                <p data-workshop-state-error="name" class="mt-1 text-xs text-rose-500"></p>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Descripción (opcional)') }}</label>
                                <textarea
                                    data-workshop-state-input="description"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                    rows="4"
                                    maxlength="1000"
                                ></textarea>
                                <p data-workshop-state-error="description" class="mt-1 text-xs text-rose-500"></p>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                                data-workshop-state-modal-close
                            >
                                {{ gettext('Cancelar') }}
                            </button>
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-inverted shadow-sm transition hover:bg-primary-strong disabled:cursor-not-allowed disabled:opacity-70"
                                data-workshop-state-submit
                            >
                                <span data-workshop-state-submit-label>{{ gettext('Guardar') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- View Modal -->
        <div
            class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
            data-workshop-state-modal="view"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-8 w-full max-w-xl">
                <div class="relative w-full overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white">{{ gettext('Detalle del registro') }}</h3>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-workshop-state-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="space-y-4 px-6 py-6" data-workshop-state-view>
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                            <div class="flex items-start gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary">
                                    <i class="fa-solid fa-layer-group"></i>
                                </span>
                                <div class="flex-1">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Categoría') }}</span>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white" data-workshop-state-view-field="category_name"></p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                            <div class="flex items-start gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary">
                                    <i class="fa-solid fa-flag"></i>
                                </span>
                                <div class="flex-1">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Nombre') }}</span>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white" data-workshop-state-view-field="name"></p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                            <div class="flex items-start gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary">
                                    <i class="fa-solid fa-align-left"></i>
                                </span>
                                <div class="flex-1">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Descripción') }}</span>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white" data-workshop-state-view-field="description"></p>
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
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white" data-workshop-state-view-field="status_label"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                            data-workshop-state-modal-close
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
            data-workshop-state-modal="delete"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-8 w-full max-w-md">
                <div class="relative w-full overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white">{{ gettext('Eliminar estado') }}</h3>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-workshop-state-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="space-y-4 px-6 py-6">
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            {{ gettext('¿Estás seguro de eliminar el estado? Esta acción no se puede deshacer.') }}
                        </p>
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                            <i class="fa-solid fa-circle-info mr-2"></i>
                            <span>{{ gettext('Se eliminará:') }} <strong data-workshop-state-delete-name></strong></span>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                            data-workshop-state-modal-close
                        >
                            {{ gettext('Cancelar') }}
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-70"
                            data-workshop-state-delete-confirm
                        >
                            {{ gettext('Eliminar') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.dashboard-layout>


