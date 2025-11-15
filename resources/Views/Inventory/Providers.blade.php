<x-layouts.dashboard-layout :meta="$meta ?? []">
    <x-ui.breadcrumb :title="gettext('Proveedores')" :items="$breadcrumbItems" />

    @php
        $columns = [
            [
                'key' => 'display_name',
                'label' => gettext('Nombre'),
                'sortable' => true,
            ],
            [
                'key' => 'identification_number',
                'label' => gettext('Identificación'),
                'sortable' => true,
            ],
            [
                'key' => 'phone_number',
                'label' => gettext('Teléfono'),
                'sortable' => true,
            ],
            [
                'key' => 'email',
                'label' => gettext('Correo'),
            ],
            [
                'key' => 'actions',
                'label' => gettext('Acciones'),
                'render' => 'actions',
                'sortable' => false,
                'align' => 'center',
            ],
        ];

        $identificationTypes = [
            'RUC' => gettext('RUC'),
            'CEDULA' => gettext('Cédula'),
            'PASAPORTE' => gettext('Pasaporte'),
        ];
    @endphp

    <section
        class="w-full space-y-6 pb-12"
        data-providers-root
        data-providers-store-url="{{ route('inventory.providers.store') }}"
        data-providers-base-url="{{ url('inventory/providers') }}"
        data-providers-table-id="providers-table"
        data-providers-api-url="{{ route('inventory.providers.data') }}"
    >
        <x-ui.data-table
            tableId="providers-table"
            apiUrl="{{ route('inventory.providers.data') }}"
            :columns="$columns"
            :perPageOptions="[5, 10, 20, 50, 100]"
            :perPageDefault="10"
            perPageSelectWidthClass="w-[120px]"
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
                    data-provider-action="create"
                >
                    <i class="fa-solid fa-plus text-sm"></i>
                    {{ gettext('Nuevo') }}
                </button>
            </x-slot:headerActions>
        </x-ui.data-table>

        {{-- Form Modal --}}
        <div
            class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
            data-provider-modal="form"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-8 w-full max-w-3xl">
                <div class="relative w-full overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white" data-provider-modal-title></h3>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-provider-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form data-provider-form class="space-y-6 px-6 py-6" novalidate>
                        <input type="hidden" data-provider-input="id">
                        <input type="hidden" data-provider-input="status" value="A">

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Tipo de proveedor') }}</label>
                                <div
                                    class="relative"
                                    data-select
                                    data-select-name="provider_type"
                                    data-select-invalid="false"
                                >
                                    <input type="hidden" data-provider-input="provider_type" value="individual">
                                    <button
                                        type="button"
                                        class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                        data-select-trigger
                                        data-select-placeholder="{{ gettext('Selecciona un tipo') }}"
                                        aria-haspopup="listbox"
                                        aria-expanded="false"
                                    >
                                        <span data-select-value class="truncate">{{ gettext('Persona natural') }}</span>
                                        <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
                                    </button>
                                    <div
                                        class="invisible absolute left-0 right-0 z-100 mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                        data-select-dropdown
                                        role="listbox"
                                        hidden
                                    >
                                        <div class="max-h-60 overflow-y-auto py-2">
                                            <button type="button" class="group flex w-full items-center justify-between px-4 py-2 text-left text-sm text-gray-800 transition hover:bg-primary-soft/20 data-[selected=true]:bg-primary-soft/30 data-[selected=true]:text-primary dark:text-white" data-select-option data-value="individual" data-label="{{ gettext('Persona natural') }}" role="option" data-selected="true">
                                                <span class="truncate">{{ gettext('Persona natural') }}</span>
                                                <i class="fa-solid fa-circle-check text-primary opacity-0 transition group-data-[selected=true]:opacity-100" data-select-check></i>
                                            </button>
                                            <button type="button" class="group flex w-full items-center justify-between px-4 py-2 text-left text-sm text-gray-800 transition hover:bg-primary-soft/20 data-[selected=true]:bg-primary-soft/30 data-[selected=true]:text-primary dark:text-white" data-select-option data-value="business" data-label="{{ gettext('Empresa') }}" role="option" data-selected="false">
                                                <span class="truncate">{{ gettext('Empresa') }}</span>
                                                <i class="fa-solid fa-circle-check text-primary opacity-0 transition group-data-[selected=true]:opacity-100" data-select-check></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <p data-provider-error="provider_type" class="mt-1 text-xs text-rose-500"></p>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Tipo de identificación') }}</label>
                                <div
                                    class="relative"
                                    data-select
                                    data-select-name="identification_type"
                                    data-select-invalid="false"
                                >
                                    <input type="hidden" data-provider-input="identification_type" value="RUC">
                                    <button
                                        type="button"
                                        class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                        data-select-trigger
                                        data-select-placeholder="{{ gettext('Selecciona un tipo') }}"
                                        aria-haspopup="listbox"
                                        aria-expanded="false"
                                    >
                                        <span data-select-value class="truncate">{{ gettext('RUC') }}</span>
                                        <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
                                    </button>
                                    <div
                                        class="invisible absolute left-0 right-0 z-100 mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                        data-select-dropdown
                                        role="listbox"
                                        hidden
                                    >
                                        <div class="max-h-60 overflow-y-auto py-2">
                                            @foreach ($identificationTypes as $value => $label)
                                                <button
                                                    type="button"
                                                    class="group flex w-full items-center justify-between px-4 py-2 text-left text-sm text-gray-800 transition hover:bg-primary-soft/20 data-[selected=true]:bg-primary-soft/30 data-[selected=true]:text-primary dark:text-white"
                                                    data-select-option
                                                    data-value="{{ $value }}"
                                                    data-label="{{ $label }}"
                                                    role="option"
                                                    data-selected="{{ $loop->first ? 'true' : 'false' }}"
                                                >
                                                    <span class="truncate">{{ $label }}</span>
                                                    <i class="fa-solid fa-circle-check text-primary opacity-0 transition group-data-[selected=true]:opacity-100" data-select-check></i>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <p data-provider-error="identification_type" class="mt-1 text-xs text-rose-500"></p>
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Número de identificación') }}</label>
                                <input
                                    type="text"
                                    data-provider-input="identification_number"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                    maxlength="30"
                                    required
                                >
                                <p data-provider-error="identification_number" class="mt-1 text-xs text-rose-500"></p>
                            </div>
                            <div data-provider-section="individual">
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Nombres') }}</label>
                                <input
                                    type="text"
                                    data-provider-input="first_name"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                    maxlength="120"
                                >
                                <p data-provider-error="first_name" class="mt-1 text-xs text-rose-500"></p>
                            </div>
                            <div data-provider-section="individual">
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Apellidos') }}</label>
                                <input
                                    type="text"
                                    data-provider-input="last_name"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                    maxlength="120"
                                >
                                <p data-provider-error="last_name" class="mt-1 text-xs text-rose-500"></p>
                            </div>
                            <div class="md:col-span-2 hidden" data-provider-section="business">
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Razón social') }}</label>
                                <input
                                    type="text"
                                    data-provider-input="business_name"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                    maxlength="255"
                                >
                                <p data-provider-error="business_name" class="mt-1 text-xs text-rose-500"></p>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Correo electrónico') }}</label>
                                <input
                                    type="email"
                                    data-provider-input="email"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                    maxlength="150"
                                >
                                <p data-provider-error="email" class="mt-1 text-xs text-rose-500"></p>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Teléfono') }}</label>
                                <input
                                    type="text"
                                    data-provider-input="phone_number"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                    maxlength="50"
                                >
                                <p data-provider-error="phone_number" class="mt-1 text-xs text-rose-500"></p>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                                data-provider-modal-close
                            >
                                {{ gettext('Cancelar') }}
                            </button>
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-inverted shadow-sm transition hover:bg-primary-strong disabled:cursor-not-allowed disabled:opacity-70"
                                data-provider-submit
                            >
                                <span data-provider-submit-label>{{ gettext('Guardar') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- View Modal --}}
        <div
            class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
            data-provider-modal="view"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-8 w-full max-w-3xl">
                <div class="relative w-full overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white">{{ gettext('Detalle del proveedor') }}</h3>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-provider-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="grid gap-4 px-6 py-6 md:grid-cols-2" data-provider-view>
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                            <div class="flex items-start gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary">
                                    <i class="fa-solid fa-user-tie"></i>
                                </span>
                                <div class="flex-1">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Nombre') }}</span>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white" data-provider-view-field="display_name"></p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                            <div class="flex items-start gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary">
                                    <i class="fa-solid fa-people-arrows"></i>
                                </span>
                                <div class="flex-1">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Identificación') }}</span>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                        <span data-provider-view-field="identification_type_label"></span>
                                        <span class="text-gray-500 dark:text-gray-400">·</span>
                                        <span data-provider-view-field="identification_number"></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                            <div class="flex items-start gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary">
                                    <i class="fa-solid fa-phone"></i>
                                </span>
                                <div class="flex-1">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Teléfono') }}</span>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white" data-provider-view-field="phone_number"></p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                            <div class="flex items-start gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary">
                                    <i class="fa-solid fa-at"></i>
                                </span>
                                <div class="flex-1">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Correo') }}</span>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white" data-provider-view-field="email"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                            data-provider-modal-close
                        >
                            {{ gettext('Cerrar') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Delete Modal --}}
        <div
            class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
            data-provider-modal="delete"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-8 w-full max-w-md">
                <div class="relative w-full overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white">{{ gettext('Eliminar proveedor') }}</h3>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-provider-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="space-y-4 px-6 py-6">
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            {{ gettext('¿Estás seguro de eliminar este proveedor? Esta acción no se puede deshacer.') }}
                        </p>
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                            <i class="fa-solid fa-circle-info mr-2"></i>
                            <span>{{ gettext('Se eliminará:') }} <strong data-provider-delete-name></strong></span>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                            data-provider-modal-close
                        >
                            {{ gettext('Cancelar') }}
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-70"
                            data-provider-delete-confirm
                        >
                            {{ gettext('Eliminar') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.dashboard-layout>

