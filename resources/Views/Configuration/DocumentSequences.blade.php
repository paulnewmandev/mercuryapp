<x-layouts.dashboard-layout :meta="$meta ?? []">
    <x-ui.breadcrumb :title="gettext('Secuenciales')" :items="$breadcrumbItems" />

    @php
        $columns = [
            [
                'key' => 'document_type_label',
                'label' => gettext('Tipo de documento'),
            ],
            [
                'key' => 'formatted_sequence',
                'label' => gettext('Secuencial'),
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
        data-document-sequences-root
        data-document-sequences-store-url="{{ route('configuration.document_sequences.store') }}"
        data-document-sequences-base-url="{{ url('configuration/document-sequences') }}"
        data-document-sequences-table-id="document-sequences-table"
        data-document-sequences-types='@json($documentTypes)'
    >
        <x-ui.data-table
            tableId="document-sequences-table"
            apiUrl="{{ route('configuration.document_sequences.data') }}"
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
                    data-document-sequence-action="create"
                >
                    <i class="fa-solid fa-plus text-sm"></i>
                    {{ gettext('Nuevo') }}
                </button>
            </x-slot:headerActions>
        </x-ui.data-table>

        <!-- Form Modal -->
        <div
            class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
            data-document-sequence-modal="form"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-8 w-full max-w-2xl">
                <div class="relative w-full overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white" data-document-sequence-modal-title></h3>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-document-sequence-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form data-document-sequence-form class="space-y-6 px-6 py-6" novalidate>
                        <input type="hidden" data-document-sequence-input="id">
                        <input type="hidden" data-document-sequence-input="status" value="A">

                        <div class="grid gap-5">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Tipo de documento') }}</label>
                                <div
                                    class="relative"
                                    data-custom-select
                                    data-select-manual="true"
                                    data-document-sequence-select="document_type"
                                >
                                    <button
                                        type="button"
                                        class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 transition hover:border-primary focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                        data-custom-select-trigger
                                    >
                                        <span data-custom-select-label>{{ gettext('Selecciona...') }}</span>
                                        <i class="fa-solid fa-chevron-down text-xs"></i>
                                    </button>
                                    <div class="absolute left-0 right-0 z-[100] mt-2 hidden max-h-56 overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-slate-900" data-custom-select-options></div>
                                    <input type="hidden" data-document-sequence-input="document_type">
                                </div>
                                <p data-document-sequence-error="document_type" class="text-xs text-rose-500"></p>
                            </div>

                            <div class="grid gap-5 sm:grid-cols-3">
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Establecimiento') }}</label>
                                    <input
                                        type="text"
                                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                        data-document-sequence-input="establishment_code"
                                        maxlength="5"
                                        required
                                    >
                                    <p data-document-sequence-error="establishment_code" class="text-xs text-rose-500"></p>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Punto de emisión') }}</label>
                                    <input
                                        type="text"
                                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                        data-document-sequence-input="emission_point_code"
                                        maxlength="5"
                                        required
                                    >
                                    <p data-document-sequence-error="emission_point_code" class="text-xs text-rose-500"></p>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Secuencial actual') }}</label>
                                    <input
                                        type="number"
                                        min="1"
                                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                        data-document-sequence-input="current_sequence"
                                        required
                                    >
                                    <p data-document-sequence-error="current_sequence" class="text-xs text-rose-500"></p>
                                </div>
                            </div>

                            <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-4 text-center text-sm text-gray-600 dark:border-gray-700 dark:bg-slate-900/50 dark:text-gray-300">
                                <span class="font-semibold text-gray-800 dark:text-white">{{ gettext('Resultado') }}:</span>
                                <span class="ml-2 font-mono text-base" data-document-sequence-preview>---</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                                data-document-sequence-modal-close
                            >
                                {{ gettext('Cancelar') }}
                            </button>
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-inverted shadow-sm transition hover:bg-primary-strong disabled:cursor-not-allowed disabled:opacity-70"
                                data-document-sequence-submit
                            >
                                <span data-document-sequence-submit-label>{{ gettext('Guardar') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- View Modal -->
        <div
            class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
            data-document-sequence-modal="view"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-8 w-full max-w-2xl">
                <div class="relative w-full overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white">{{ gettext('Detalle del registro') }}</h3>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-document-sequence-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="grid gap-4 px-6 py-6" data-document-sequence-view>
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                            <div class="flex items-start gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary">
                                    <i class="fa-solid fa-file-lines"></i>
                                </span>
                                <div class="flex-1">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Tipo de documento') }}</span>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white" data-document-sequence-view-field="document_type_label"></p>
                                </div>
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Establecimiento') }}</span>
                                <p class="mt-1 font-mono text-sm text-gray-900 dark:text-white" data-document-sequence-view-field="establishment_code"></p>
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Punto de emisión') }}</span>
                                <p class="mt-1 font-mono text-sm text-gray-900 dark:text-white" data-document-sequence-view-field="emission_point_code"></p>
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Secuencial actual') }}</span>
                                <p class="mt-1 font-mono text-sm text-gray-900 dark:text-white" data-document-sequence-view-field="current_sequence"></p>
                            </div>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-white p-4 text-center shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Formato completo') }}</span>
                            <p class="mt-2 font-mono text-base text-gray-900 dark:text-white" data-document-sequence-view-field="formatted_sequence"></p>
                        </div>
                    </div>
                    <div class="flex justify-end border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                            data-document-sequence-modal-close
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
            data-document-sequence-modal="delete"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-8 w-full max-w-md">
                <div class="relative w-full overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white">{{ gettext('Eliminar secuencial') }}</h3>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-document-sequence-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="space-y-4 px-6 py-6">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ gettext('¿Confirmas que deseas eliminar este secuencial? Esta acción no se puede deshacer.') }}
                        </p>
                        <div class="rounded-xl bg-gray-100 px-4 py-3 font-medium text-gray-800 dark:bg-slate-800 dark:text-white" data-document-sequence-delete-name></div>
                    </div>
                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                            data-document-sequence-modal-close
                        >
                            {{ gettext('Cancelar') }}
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-70"
                            data-document-sequence-delete-confirm
                        >
                            {{ gettext('Eliminar') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.dashboard-layout>

