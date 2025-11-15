<x-layouts.dashboard-layout :meta="$meta ?? []">
    @php
        $config = $customerPageConfig ?? [
            'customerType' => 'individual',
            'title' => gettext('Clientes'),
            'tableId' => 'customers-table',
            'apiUrl' => route('clientes.data', ['customer_type' => 'individual']),
            'categoryOptions' => [],
        ];

        $isBusiness = $config['customerType'] === 'business';
        $newButtonLabel = $isBusiness ? gettext('Nuevo') : gettext('Nuevo');

        $columns = [
            [
                'key' => 'display_name',
                'label' => $isBusiness ? gettext('Empresa') : gettext('Nombre'),
                'sortable' => true,
            ],
            [
                'key' => 'category_name',
                'label' => gettext('Categoría'),
                'sortable' => true,
            ],
            [
                'key' => 'document_number',
                'label' => gettext('Documento'),
            ],
            [
                'key' => 'phone_number',
                'label' => gettext('Teléfono'),
                'sortable' => true,
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

    <x-ui.breadcrumb :title="$config['title'] ?? gettext('Clientes')" :items="$breadcrumbItems" />

    <section
        class="w-full space-y-6 pb-12"
        data-customers-root
        data-customers-store-url="{{ route('clientes.store') }}"
        data-customers-base-url="{{ url('customers') }}"
        data-customers-table-id="{{ $config['tableId'] }}"
        data-customers-api-url="{{ $config['apiUrl'] }}"
        data-customers-type="{{ $config['customerType'] }}"
        data-customers-validate-document-url="{{ route('clientes.validate.document') }}"
        data-customers-validate-email-url="{{ route('clientes.validate.email') }}"
        data-customers-categories-options='@json($config['categoryOptions'])'
        data-customers-categories-options-url="{{ route('clientes.categorias.options') }}"
    >
        <x-ui.data-table
            :tableId="$config['tableId']"
            :apiUrl="$config['apiUrl']"
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
                    data-customer-action="create"
                >
                    <i class="fa-solid fa-plus text-sm"></i>
                    {{ $newButtonLabel }}
                </button>
            </x-slot:headerActions>
        </x-ui.data-table>

        {{-- Form Modal --}}
        <div
            class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
            data-customer-modal="form"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-8 w-full max-w-3xl">
                <div class="relative w-full overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white" data-customer-modal-title></h3>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-customer-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form data-customer-form class="space-y-6 px-6 py-6" novalidate>
                        <input type="hidden" data-customer-input="id">
                        <input type="hidden" data-customer-input="status" value="A">

                        <div class="space-y-5">
<div class="grid gap-5">
    <div data-customer-type-wrapper>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Tipo de cliente') }}</label>
        <div
            class="relative"
            data-select
            data-select-name="customer_type"
            data-select-invalid="false"
        >
            <input type="hidden" data-customer-input="customer_type" value="individual">
            <button
                type="button"
                class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                data-select-trigger
                data-select-placeholder="{{ gettext('Selecciona un tipo de cliente') }}"
                aria-haspopup="listbox"
                aria-expanded="false"
            >
                <span data-select-value class="truncate">{{ gettext('Persona natural') }}</span>
                <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
            </button>
            <div
                class="invisible absolute left-0 right-0 z-[100] mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
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
        <p data-customer-error="customer_type" class="mt-1 text-xs text-rose-500"></p>
    </div>
</div>
<div class="grid gap-5 md:grid-cols-3">
    <div>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Categoría') }}</label>
        <div
            class="relative"
            data-select
            data-select-name="category_id"
            data-select-invalid="false"
        >
            <input type="hidden" data-customer-input="category_id" value="">
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
                class="invisible absolute left-0 right-0 z-[110] mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                data-select-dropdown
                role="listbox"
                hidden
            >
                <div class="max-h-60 overflow-y-auto py-2" data-customer-category-options></div>
            </div>
        </div>
        <p data-customer-error="category_id" class="mt-1 text-xs text-rose-500"></p>
    </div>
    <div>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Tipo de documento') }}</label>
        <div
            class="relative"
            data-select
            data-select-name="document_type"
            data-select-invalid="false"
        >
            <input type="hidden" data-customer-input="document_type" value="RUC">
            <button
                type="button"
                class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                data-select-trigger
                data-select-placeholder="{{ gettext('Selecciona un tipo de documento') }}"
                aria-haspopup="listbox"
                aria-expanded="false"
            >
                <span data-select-value class="truncate">{{ gettext('RUC') }}</span>
                <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
            </button>
            <div
                class="invisible absolute left-0 right-0 z-[100] mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                data-select-dropdown
                role="listbox"
                hidden
            >
                <div class="max-h-60 overflow-y-auto py-2">
                    <button type="button" class="group flex w-full items-center justify-between px-4 py-2 text-left text-sm text-gray-800 transition hover:bg-primary-soft/20 data-[selected=true]:bg-primary-soft/30 data-[selected=true]:text-primary dark:text-white" data-select-option data-value="RUC" data-label="{{ gettext('RUC') }}" role="option" data-selected="true">
                        <span class="truncate">{{ gettext('RUC') }}</span>
                        <i class="fa-solid fa-circle-check text-primary opacity-0 transition group-data-[selected=true]:opacity-100" data-select-check></i>
                    </button>
                    <button type="button" class="group flex w-full items-center justify-between px-4 py-2 text-left text-sm text-gray-800 transition hover:bg-primary-soft/20 data-[selected=true]:bg-primary-soft/30 data-[selected=true]:text-primary dark:text-white" data-select-option data-value="CEDULA" data-label="{{ gettext('Cédula') }}" role="option">
                        <span class="truncate">{{ gettext('Cédula') }}</span>
                        <i class="fa-solid fa-circle-check text-primary opacity-0 transition group-data-[selected=true]:opacity-100" data-select-check></i>
                    </button>
                    <button type="button" class="group flex w-full items-center justify-between px-4 py-2 text-left text-sm text-gray-800 transition hover:bg-primary-soft/20 data-[selected=true]:bg-primary-soft/30 data-[selected=true]:text-primary dark:text-white" data-select-option data-value="PASAPORTE" data-label="{{ gettext('Pasaporte') }}" role="option">
                        <span class="truncate">{{ gettext('Pasaporte') }}</span>
                        <i class="fa-solid fa-circle-check text-primary opacity-0 transition group-data-[selected=true]:opacity-100" data-select-check></i>
                    </button>
                </div>
            </div>
        </div>
        <p data-customer-error="document_type" class="mt-1 text-xs text-rose-500"></p>
    </div>
    <div>
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Número de documento') }}</label>
        <div class="relative">
            <input
                type="text"
                data-customer-input="document_number"
                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                maxlength="20"
                required
            >
            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-emerald-500 opacity-0 transition" data-customer-valid-icon="document_number">
                <i class="fa-solid fa-circle-check"></i>
            </span>
        </div>
        <p data-customer-error="document_number" class="mt-1 text-xs text-rose-500"></p>
    </div>
</div>
<div class="grid gap-5 md:grid-cols-2">
    <div data-customer-section="individual">
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Nombres') }}</label>
        <input
            type="text"
            data-customer-input="first_name"
            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
            maxlength="100"
        >
        <p data-customer-error="first_name" class="mt-1 text-xs text-rose-500"></p>
    </div>
    <div data-customer-section="individual">
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Apellidos') }}</label>
        <input
            type="text"
            data-customer-input="last_name"
            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
            maxlength="100"
        >
        <p data-customer-error="last_name" class="mt-1 text-xs text-rose-500"></p>
    </div>
    <div class="hidden md:col-span-2" data-customer-section="business">
        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Razón social') }}</label>
        <input
            type="text"
            data-customer-input="business_name"
            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
            maxlength="255"
        >
        <p data-customer-error="business_name" class="mt-1 text-xs text-rose-500"></p>
    </div>
</div>
                            <div class="grid gap-5 md:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Teléfono') }}</label>
                                    <input
                                        type="text"
                                        data-customer-input="phone_number"
                                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                        maxlength="50"
                                    >
                                    <p data-customer-error="phone_number" class="mt-1 text-xs text-rose-500"></p>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Correo electrónico') }}</label>
                                    <div class="relative">
                                        <input
                                            type="email"
                                            data-customer-input="email"
                                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                        >
                                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-emerald-500 opacity-0 transition" data-customer-valid-icon="email">
                                            <i class="fa-solid fa-circle-check"></i>
                                        </span>
                                    </div>
                                    <p data-customer-error="email" class="mt-1 text-xs text-rose-500"></p>
                                </div>
                            </div>
                            <div class="grid gap-5 md:grid-cols-2">
                                <div data-customer-section="individual">
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Sexo') }}</label>
                                    <div
                                        class="relative"
                                        data-select
                                        data-select-name="sex"
                                        data-select-invalid="false"
                                    >
                                        <input type="hidden" data-customer-input="sex" value="">
                                        <button
                                            type="button"
                                            class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                            data-select-trigger
                                            data-select-placeholder="{{ gettext('Selecciona una opción') }}"
                                            aria-haspopup="listbox"
                                            aria-expanded="false"
                                        >
                                            <span data-select-value class="truncate">{{ gettext('No especifica') }}</span>
                                            <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
                                        </button>
                                        <div
                                            class="invisible absolute left-0 right-0 z-[100] mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                            data-select-dropdown
                                            role="listbox"
                                            hidden
                                        >
                                            <div class="max-h-56 overflow-y-auto py-2">
                                                <button type="button" class="group flex w-full items-center justify-between px-4 py-2 text-left text-sm text-gray-800 transition hover:bg-primary-soft/20 data-[selected=true]:bg-primary-soft/30 data-[selected=true]:text-primary dark:text-white" data-select-option data-value="" data-label="{{ gettext('No especifica') }}" role="option" data-selected="true">
                                                    <span class="truncate">{{ gettext('No especifica') }}</span>
                                                    <i class="fa-solid fa-circle-check text-primary opacity-0 transition group-data-[selected=true]:opacity-100" data-select-check></i>
                                                </button>
                                                <button type="button" class="group flex w-full items-center justify-between px-4 py-2 text-left text-sm text-gray-800 transition hover:bg-primary-soft/20 data-[selected=true]:bg-primary-soft/30 data-[selected=true]:text-primary dark:text-white" data-select-option data-value="MASCULINO" data-label="{{ gettext('Masculino') }}">
                                                    <span class="truncate">{{ gettext('Masculino') }}</span>
                                                    <i class="fa-solid fa-circle-check text-primary opacity-0 transition group-data-[selected=true]:opacity-100" data-select-check></i>
                                                </button>
                                                <button type="button" class="group flex w-full items-center justify-between px-4 py-2 text-left text-sm text-gray-800 transition hover:bg-primary-soft/20 data-[selected=true]:bg-primary-soft/30 data-[selected=true]:text-primary dark:text-white" data-select-option data-value="FEMENINO" data-label="{{ gettext('Femenino') }}">
                                                    <span class="truncate">{{ gettext('Femenino') }}</span>
                                                    <i class="fa-solid fa-circle-check text-primary opacity-0 transition group-data-[selected=true]:opacity-100" data-select-check></i>
                                                </button>
                                                <button type="button" class="group flex w-full items-center justify-between px-4 py-2 text-left text-sm text-gray-800 transition hover:bg-primary-soft/20 data-[selected=true]:bg-primary-soft/30 data-[selected=true]:text-primary dark:text-white" data-select-option data-value="OTRO" data-label="{{ gettext('Otro') }}">
                                                    <span class="truncate">{{ gettext('Otro') }}</span>
                                                    <i class="fa-solid fa-circle-check text-primary opacity-0 transition group-data-[selected=true]:opacity-100" data-select-check></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <p data-customer-error="sex" class="mt-1 text-xs text-rose-500"></p>
                                </div>
                                <div data-customer-section="individual">
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Fecha de nacimiento') }}</label>
                                    <input
                                        type="date"
                                        data-customer-input="birth_date"
                                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                    >
                                    <p data-customer-error="birth_date" class="mt-1 text-xs text-rose-500"></p>
                                </div>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Dirección') }}</label>
                                <input
                                    type="text"
                                    data-customer-input="address"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                    maxlength="255"
                                >
                                <p data-customer-error="address" class="mt-1 text-xs text-rose-500"></p>
                            </div>
                            <div class="grid gap-5 md:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Clave de portal') }}</label>
                                    <div class="relative">
                                        <input
                                            type="password"
                                            data-customer-input="portal_password"
                                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 pr-12 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                            maxlength="100"
                                            autocomplete="new-password"
                                            placeholder="{{ gettext('Ingresa una clave temporal') }}"
                                        >
                                        <button
                                            type="button"
                                            class="absolute inset-y-0 right-3 inline-flex items-center justify-center text-sm text-gray-500 transition hover:text-primary focus:outline-none"
                                            data-customer-password-toggle
                                            aria-label="{{ gettext('Mostrar u ocultar la clave') }}"
                                            data-state="hidden"
                                        >
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </div>
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ gettext('Debe tener al menos 8 caracteres, un número y un símbolo. Deja el campo vacío para mantener la clave actual.') }}</p>
                                    <p data-customer-error="portal_password" class="mt-1 text-xs text-rose-500"></p>
                                </div>
                                <div class="space-y-4 rounded-xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Accesos al portal') }}</span>
                                    <div class="flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3 dark:bg-slate-800/60">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ gettext('Acceso B2B') }}</p>
                                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ gettext('Permite que este cliente ingrese al portal B2B.') }}</p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-300" data-customer-toggle-label="b2b_access">{{ gettext('Inactivo') }}</span>
                                            <button
                                                type="button"
                                                class="relative inline-flex h-6 w-11 items-center rounded-full border border-gray-300 bg-gray-200 transition data-[active=true]:border-primary data-[active=true]:bg-primary data-[active=true]:shadow-lg dark:border-gray-600 dark:bg-slate-700 data-[active=true]:dark:bg-primary"
                                                data-customer-toggle="b2b_access"
                                                aria-pressed="false"
                                                data-active="false"
                                            >
                                                <span class="absolute left-1 h-4 w-4 rounded-full bg-white transform transition-transform duration-200 data-[active=true]:translate-x-5" data-customer-toggle-handle data-active="false"></span>
                                                <span class="sr-only">{{ gettext('Alternar acceso B2B') }}</span>
                                            </button>
                                            <input type="hidden" data-customer-input="b2b_access" value="0">
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3 dark:bg-slate-800/60">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ gettext('Acceso B2C') }}</p>
                                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ gettext('Permite que este cliente ingrese al portal B2C.') }}</p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-300" data-customer-toggle-label="b2c_access">{{ gettext('Inactivo') }}</span>
                                            <button
                                                type="button"
                                                class="relative inline-flex h-6 w-11 items-center rounded-full border border-gray-300 bg-gray-200 transition data-[active=true]:border-primary data-[active=true]:bg-primary data-[active=true]:shadow-lg dark:border-gray-600 dark:bg-slate-700 data-[active=true]:dark:bg-primary"
                                                data-customer-toggle="b2c_access"
                                                aria-pressed="false"
                                                data-active="false"
                                            >
                                                <span class="absolute left-1 h-4 w-4 rounded-full bg-white transform transition-transform duration-200 data-[active=true]:translate-x-5" data-customer-toggle-handle data-active="false"></span>
                                                <span class="sr-only">{{ gettext('Alternar acceso B2C') }}</span>
                                            </button>
                                            <input type="hidden" data-customer-input="b2c_access" value="0">
                                        </div>
                                    </div>
                                    <p data-customer-error="b2b_access" class="text-xs text-rose-500"></p>
                                    <p data-customer-error="b2c_access" class="text-xs text-rose-500"></p>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                                data-customer-modal-close
                            >
                                {{ gettext('Cancelar') }}
                            </button>
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-inverted shadow-sm transition hover:bg-primary-strong disabled:cursor-not-allowed disabled:opacity-70"
                                data-customer-submit
                            >
                                <span data-customer-submit-label>{{ gettext('Guardar') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- View Modal --}}
        <div
            class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
            data-customer-modal="view"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-8 w-full max-w-3xl">
                <div class="relative w-full overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white">{{ gettext('Detalle del cliente') }}</h3>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-customer-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="grid gap-4 px-6 py-6 md:grid-cols-2" data-customer-view>
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                            <div class="flex items-start gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary">
                                    <i class="fa-solid fa-layer-group"></i>
                                </span>
                                <div class="flex-1">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Categoría') }}</span>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white" data-customer-view-field="category_name"></p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                            <div class="flex items-start gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary">
                                    <i class="fa-solid fa-id-card"></i>
                                </span>
                                <div class="flex-1">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Documento') }}</span>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                        <span data-customer-view-field="document_type"></span>
                                        <span class="text-gray-500 dark:text-gray-400">·</span>
                                        <span data-customer-view-field="document_number"></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                            <div class="flex items-start gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary">
                                    <i class="fa-solid fa-user"></i>
                                </span>
                                <div class="flex-1">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Nombre') }}</span>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white" data-customer-view-field="display_name"></p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                            <div class="flex items-start gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary">
                                    <i class="fa-solid fa-venus-mars"></i>
                                </span>
                                <div class="flex-1">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Sexo') }}</span>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white" data-customer-view-field="sex_label"></p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                            <div class="flex items-start gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary">
                                    <i class="fa-solid fa-cake-candles"></i>
                                </span>
                                <div class="flex-1">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Fecha de nacimiento') }}</span>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white" data-customer-view-field="birth_date_label"></p>
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
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white" data-customer-view-field="email"></p>
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
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white" data-customer-view-field="phone_number"></p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                            <div class="flex items-start gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary">
                                    <i class="fa-solid fa-globe"></i>
                                </span>
                                <div class="flex-1">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Accesos al portal') }}</span>
                                    <div class="mt-3 space-y-1 text-sm text-gray-900 dark:text-white">
                                        <div class="flex items-center justify-between">
                                            <span>{{ gettext('Acceso B2B') }}</span>
                                            <span class="font-semibold" data-customer-view-field="b2b_access_label"></span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span>{{ gettext('Acceso B2C') }}</span>
                                            <span class="font-semibold" data-customer-view-field="b2c_access_label"></span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span>{{ gettext('Clave de portal') }}</span>
                                            <span class="font-semibold" data-customer-view-field="portal_access_label"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="md:col-span-2 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                            <div class="flex items-start gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary">
                                    <i class="fa-solid fa-location-dot"></i>
                                </span>
                                <div class="flex-1">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Dirección') }}</span>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white" data-customer-view-field="address"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                    <div class="flex justify-end border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                            data-customer-modal-close
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
            data-customer-modal="delete"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-8 w-full max-w-md">
                <div class="relative w-full overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white">{{ gettext('Eliminar cliente') }}</h3>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-customer-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="space-y-4 px-6 py-6">
                        <input type="hidden" data-customer-delete-id>
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            {{ gettext('¿Estás seguro de eliminar este cliente? Esta acción no se puede deshacer.') }}
                        </p>
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                            <i class="fa-solid fa-circle-info mr-2"></i>
                            <span>{{ gettext('Se eliminará:') }} <strong data-customer-delete-name></strong></span>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                            data-customer-modal-close
                        >
                            {{ gettext('Cancelar') }}
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-70"
                            data-customer-delete-confirm
                        >
                            {{ gettext('Eliminar') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.dashboard-layout>
