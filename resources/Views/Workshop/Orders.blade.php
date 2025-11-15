<x-layouts.dashboard-layout :meta="$meta ?? []">
    <x-ui.breadcrumb :title="gettext('Órdenes de trabajo')" :items="$breadcrumbItems" />

    <section 
        class="grid gap-6 pb-12 w-full"
        style="overflow-x: hidden; box-sizing: border-box;"
        data-workshop-orders-root
        data-workshop-orders-table-id="workshop-orders-table"
        data-workshop-orders-base-url="{{ url('workshop/work-orders') }}"
        data-workshop-orders-store-url="{{ route('taller.ordenes.store') }}"
        data-workshop-orders-options-url="{{ route('taller.ordenes.options') }}"
        data-workshop-orders-customers-url="{{ route('taller.ordenes.search.customers') }}"
        data-workshop-orders-equipments-url="{{ route('taller.ordenes.search.equipos') }}"
        data-workshop-orders-responsibles-url="{{ route('taller.ordenes.search.responsables') }}"
        data-workshop-orders-accessories-url="{{ route('taller.accesorios.options') }}"
        data-workshop-orders-accessories-create-url="{{ route('taller.accesorios.store') }}"
        data-workshop-orders-categories-url="{{ route('taller.categorias.options') }}"
        data-workshop-orders-states-url="{{ route('taller.estados.options') }}"
        data-workshop-orders-categories-data-url="{{ route('taller.categorias.data') }}"
        data-workshop-orders-states-data-url="{{ route('taller.estados.data') }}"
        data-workshop-orders-customer-create-url="{{ route('clientes.index') }}"
        data-workshop-orders-equipment-create-url="{{ route('taller.equipos') }}"
        data-workshop-orders-responsible-create-url="{{ route('security.users') }}"
    >
        {{-- Cards con estadísticas (solo en vista tabla) --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3" data-orders-cards-container data-view="table">
            @foreach ($cards ?? [] as $card)
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

        {{-- Controles de vista y filtros --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-slate-900">
            <div class="flex flex-wrap items-center justify-between gap-4">
                {{-- Toggle de vista --}}
                <div class="flex items-center gap-3">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ gettext('Vista') }}:</span>
                    <div class="inline-flex items-center rounded-lg border border-gray-300 bg-gray-50 p-1 dark:border-gray-600 dark:bg-slate-800">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition data-[active=true]:bg-white data-[active=true]:text-primary data-[active=true]:shadow-sm dark:data-[active=true]:bg-slate-700 dark:data-[active=true]:text-primary-100"
                            data-view-toggle="table"
                            data-active="true"
                        >
                            <i class="fa-solid fa-table text-xs"></i>
                            {{ gettext('Tabla') }}
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition data-[active=true]:bg-white data-[active=true]:text-primary data-[active=true]:shadow-sm dark:data-[active=true]:bg-slate-700 dark:data-[active=true]:text-primary-100"
                            data-view-toggle="kanban"
                            data-active="false"
                        >
                            <i class="fa-solid fa-columns text-xs"></i>
                            {{ gettext('Kanban') }}
                        </button>
                    </div>
                </div>

                {{-- Filtros --}}
                @if(isset($categories) && $categories->isNotEmpty())
                    @php
                        $categoriesData = $categories->map(function($cat) {
                            return [
                                'id' => $cat->id,
                                'name' => $cat->name,
                                'states' => $cat->states->map(function($state) {
                                    return [
                                        'id' => $state->id,
                                        'name' => $state->name,
                                    ];
                                })->values()->all(),
                            ];
                        })->values()->all();
                    @endphp
                    <div class="flex flex-wrap items-center gap-4" data-orders-filters data-categories-data="{{ json_encode($categoriesData) }}">
                        {{-- Select de categoría --}}
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ gettext('Categoría') }}:</label>
                            <div class="relative" data-select data-select-name="filter_category" data-select-manual="true">
                                <input type="hidden" data-filter-category-input value="">
                                <button
                                    type="button"
                                    class="flex w-48 items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-surface dark:bg-slate-900 dark:text-white"
                                    data-select-trigger
                                    data-select-placeholder="{{ gettext('Todas') }}"
                                >
                                    <span data-select-value class="truncate">{{ gettext('Todas') }}</span>
                                    <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
                                </button>
                                <div
                                    class="invisible absolute left-0 right-0 z-50 mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                    data-select-dropdown
                                    hidden
                                >
                                    <div class="max-h-60 overflow-y-auto py-2" data-filter-category-options></div>
                                </div>
                            </div>
                        </div>

                        {{-- Select de estado (solo visible en vista tabla) --}}
                        <div class="flex items-center gap-2" data-filter-state-container data-view="table">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ gettext('Estado') }}:</label>
                            <div class="relative" data-select data-select-name="filter_state" data-select-manual="true">
                                <input type="hidden" data-filter-state-input value="">
                                <button
                                    type="button"
                                    class="flex w-48 items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-surface dark:bg-slate-900 dark:text-white"
                                    data-select-trigger
                                    data-select-placeholder="{{ gettext('Todos') }}"
                                    disabled
                                >
                                    <span data-select-value class="truncate">{{ gettext('Todos') }}</span>
                                    <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
                                </button>
                                <div
                                    class="invisible absolute left-0 right-0 z-50 mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                    data-select-dropdown
                                    hidden
                                >
                                    <div class="max-h-60 overflow-y-auto py-2" data-filter-state-options></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    {{-- Vista Tabla --}}
    <div data-orders-view="table" data-orders-view-container>
        @php
            $columns = [
                [
                    'key' => 'order_number',
                    'label' => gettext('Número de orden'),
                    'render' => 'text',
                    'text_class' => 'text-sm font-semibold text-gray-900 dark:text-white',
                ],
                [
                    'key' => 'customer_name',
                    'label' => gettext('Cliente'),
                    'render' => 'text',
                ],
                [
                    'key' => 'equipment_label',
                    'label' => gettext('Equipo'),
                    'render' => 'text',
                ],
                [
                    'key' => 'state_name',
                    'label' => gettext('Estado de taller'),
                    'render' => 'text',
                ],
                [
                    'key' => 'priority',
                    'label' => gettext('Prioridad'),
                    'render' => 'badge',
                    'badge_class' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
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
                tableId="workshop-orders-table"
                apiUrl="{{ route('taller.ordenes.data') }}"
                :columns="$columns"
                :perPageOptions="[5, 10, 20, 50, 100]"
                :perPageDefault="10"
                searchPlaceholder="{{ gettext('Buscar por cliente, equipo o estado...') }}"
                emptyTitle="{{ gettext('Sin órdenes registradas') }}"
                emptyDescription="{{ gettext('Crea una nueva orden para comenzar a gestionar los trabajos del taller.') }}"
                :strings="[
                    'loading' => gettext('Cargando órdenes...'),
                    'showing' => gettext('Mostrando %from% - %to% de %total% órdenes'),
                    'empty' => gettext('No se encontraron órdenes con los filtros actuales'),
                ]"
            >
                <x-slot:headerActions>
                    <a
                        href="{{ route('taller.ordenes.create') }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-inverted shadow-sm transition hover:bg-primary-strong"
                    >
                        <i class="fa-solid fa-plus text-sm"></i>
                        {{ gettext('Nuevo') }}
                    </a>
                </x-slot:headerActions>
            </x-ui.data-table>
    </div>

    {{-- Vista Kanban --}}
    <div data-orders-view="kanban" data-orders-view-container class="hidden" style="width: 100%; max-width: 100%; overflow: hidden;">
        <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-slate-900" style="width: 100%; max-width: 100%; overflow: hidden;">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Tablero Kanban') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ gettext('Organiza y visualiza tus órdenes por estado') }}</p>
                </div>
                <a
                    href="{{ route('taller.ordenes.create') }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-inverted shadow-sm transition hover:bg-primary-strong"
                >
                    <i class="fa-solid fa-plus text-sm"></i>
                    {{ gettext('Nuevo') }}
                </a>
            </div>
            {{-- Contenedor del kanban con scroll horizontal SOLO en este card --}}
            <div style="width: 100%; max-width: 100%; overflow-x: auto; overflow-y: hidden;" data-orders-kanban-container>
                <div class="inline-flex gap-4 p-6" data-orders-kanban-columns style="min-width: max-content;">
                    {{-- Las columnas se generarán dinámicamente --}}
                    <div class="flex items-center justify-center min-h-[400px] w-full">
                        <div class="text-center">
                            <i class="fa-solid fa-spinner fa-spin text-3xl text-gray-400 mb-4"></i>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ gettext('Cargando tablero...') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </section>

    <!-- Form Modal -->
        <div
            class="fixed inset-0 z-[70] hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
            data-workshop-order-modal="form"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-6 w-full max-w-6xl">
                <div class="relative max-h-[90vh] overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white" data-workshop-order-modal-title></h3>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-workshop-order-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form data-workshop-order-form class="max-h-[90vh] overflow-y-auto px-6 py-6" novalidate>
                        <input type="hidden" data-workshop-order-input="id">
                        <input type="hidden" data-workshop-order-input="status" value="A">

                        <div class="grid gap-6">
                            <div class="grid gap-5 lg:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ gettext('Cliente') }} <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative" data-workshop-order-customer>
                                        <div class="flex w-full items-center gap-3 rounded-2xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 transition focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white" data-workshop-order-customer-control>
                                            <span class="text-gray-400 dark:text-gray-500">
                                                <i class="fa-solid fa-magnifying-glass"></i>
                                            </span>
                                            <input
                                                type="search"
                                                class="flex-1 border-none bg-transparent outline-none"
                                                placeholder="{{ gettext('Buscar por nombre o documento') }}"
                                                autocomplete="off"
                                                data-workshop-order-customer-search
                                            >
                                            <input type="hidden" data-workshop-order-input="customer_id" value="">
                                            <button
                                                type="button"
                                                class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-primary hover:text-inverted dark:bg-slate-800 dark:text-gray-200 dark:hover:bg-primary"
                                                data-workshop-order-customer-create
                                            >
                                                <i class="fa-solid fa-plus text-[11px]"></i>
                                                {{ gettext('Crear') }}
                                            </button>
                                        </div>
                                        <div
                                            class="invisible absolute left-0 right-0 z-[80] mt-2 w-full origin-top scale-95 transform overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                            data-workshop-order-customer-results
                                            hidden
                                        ></div>
                                    </div>
                                    <p data-workshop-order-error="customer_id" class="text-xs text-rose-500"></p>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ gettext('Equipo') }} <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative" data-workshop-order-equipment>
                                        <div class="flex w-full items-center gap-3 rounded-2xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 transition focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white" data-workshop-order-equipment-control>
                                            <span class="text-gray-400 dark:text-gray-500">
                                                <i class="fa-solid fa-magnifying-glass"></i>
                                            </span>
                                            <input
                                                type="search"
                                                class="flex-1 border-none bg-transparent outline-none"
                                                placeholder="{{ gettext('Buscar por identificador o marca') }}"
                                                autocomplete="off"
                                                data-workshop-order-equipment-search
                                            >
                                            <input type="hidden" data-workshop-order-input="equipment_id" value="">
                                            <button
                                                type="button"
                                                class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-primary hover:text-inverted dark:bg-slate-800 dark:text-gray-200 dark:hover:bg-primary"
                                                data-workshop-order-equipment-create
                                            >
                                                <i class="fa-solid fa-plus text-[11px]"></i>
                                                {{ gettext('Crear') }}
                                            </button>
                                        </div>
                                        <div
                                            class="invisible absolute left-0 right-0 z-[80] mt-2 w-full origin-top scale-95 transform overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                            data-workshop-order-equipment-results
                                            hidden
                                        ></div>
                                    </div>
                                    <p data-workshop-order-error="equipment_id" class="text-xs text-rose-500"></p>
                                </div>
                            </div>

                            <div class="grid gap-5 lg:grid-cols-3">
                                <div class="space-y-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ gettext('Categoría de taller') }} <span class="text-rose-500">*</span>
                                    </label>
                                    <div
                                        class="relative"
                                        data-select
                                        data-select-name="category_id"
                                        data-select-manual="true"
                                        data-select-invalid="false"
                                    >
                                        <input type="hidden" data-workshop-order-input="category_id" name="category_id" value="">
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
                                            class="invisible absolute left-0 right-0 z-[95] mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                            data-select-dropdown
                                            role="listbox"
                                            hidden
                                        >
                                            <div class="max-h-60 overflow-y-auto py-2" data-workshop-order-category-options></div>
                                        </div>
                                    </div>
                                    <p data-workshop-order-error="category_id" class="text-xs text-rose-500"></p>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ gettext('Estado de taller') }}
                                    </label>
                                    <div
                                        class="relative"
                                        data-select
                                        data-select-name="state_id"
                                        data-select-manual="true"
                                        data-select-invalid="false"
                                    >
                                        <input type="hidden" data-workshop-order-input="state_id" name="state_id" value="">
                                        <button
                                            type="button"
                                            class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-surface dark:bg-slate-900 dark:text-white"
                                            data-select-trigger
                                            data-select-placeholder="{{ gettext('Selecciona un estado') }}"
                                            aria-haspopup="listbox"
                                            aria-expanded="false"
                                        >
                                            <span data-select-value class="truncate">{{ gettext('Selecciona un estado') }}</span>
                                            <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
                                        </button>
                                        <div
                                            class="invisible absolute left-0 right-0 z-[95] mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                            data-select-dropdown
                                            role="listbox"
                                            hidden
                                        >
                                            <div class="max-h-60 overflow-y-auto py-2" data-workshop-order-state-options></div>
                                        </div>
                                    </div>
                                    <p data-workshop-order-error="state_id" class="text-xs text-rose-500"></p>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ gettext('Prioridad') }} <span class="text-rose-500">*</span>
                                    </label>
                                    <div
                                        class="relative"
                                        data-select
                                        data-select-name="priority"
                                        data-select-manual="true"
                                        data-select-invalid="false"
                                    >
                                        <input type="hidden" data-workshop-order-input="priority" name="priority" value="">
                                        <button
                                            type="button"
                                            class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-surface dark:bg-slate-900 dark:text-white"
                                            data-select-trigger
                                            data-select-placeholder="{{ gettext('Selecciona una prioridad') }}"
                                            aria-haspopup="listbox"
                                            aria-expanded="false"
                                        >
                                            <span data-select-value class="truncate">{{ gettext('Selecciona una prioridad') }}</span>
                                            <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
                                        </button>
                                        <div
                                            class="invisible absolute left-0 right-0 z-[95] mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                            data-select-dropdown
                                            role="listbox"
                                            hidden
                                        >
                                            <div class="max-h-60 overflow-y-auto py-2" data-workshop-order-priority-options></div>
                                        </div>
                                    </div>
                                    <p data-workshop-order-error="priority" class="text-xs text-rose-500"></p>
                                </div>
                            </div>


                            <div class="grid gap-5 lg:grid-cols-[1.5fr_1fr]">
                                <div class="space-y-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ gettext('Usuario responsable') }} <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative" data-workshop-order-responsible>
                                        <div class="flex w-full items-center gap-3 rounded-2xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 transition focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white" data-workshop-order-responsible-control>
                                            <span class="text-gray-400 dark:text-gray-500">
                                                <i class="fa-solid fa-user-gear"></i>
                                            </span>
                                            <input
                                                type="search"
                                                class="flex-1 border-none bg-transparent outline-none"
                                                placeholder="{{ gettext('Buscar por nombre o correo') }}"
                                                autocomplete="off"
                                                data-workshop-order-responsible-search
                                            >
                                            <input type="hidden" data-workshop-order-input="responsible_user_id" value="">
                                            <button
                                                type="button"
                                                class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-primary hover:text-inverted dark:bg-slate-800 dark:text-gray-200 dark:hover:bg-primary"
                                                data-workshop-order-responsible-create
                                            >
                                                <i class="fa-solid fa-plus text-[11px]"></i>
                                                {{ gettext('Crear') }}
                                            </button>
                                        </div>
                                        <div
                                            class="invisible absolute left-0 right-0 z-[80] mt-2 w-full origin-top scale-95 transform overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                            data-workshop-order-responsible-results
                                            hidden
                                        ></div>
                                    </div>
                                    <p data-workshop-order-error="responsible_user_id" class="text-xs text-rose-500"></p>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ gettext('Fecha prometida') }}
                                    </label>
                                    <div class="flex items-center gap-3 rounded-2xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 transition focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white">
                                        <span class="text-gray-400 dark:text-gray-500">
                                            <i class="fa-solid fa-calendar-days"></i>
                                        </span>
                                        <input
                                            type="datetime-local"
                                            data-workshop-order-input="promised_at"
                                            class="flex-1 border-none bg-transparent outline-none"
                                        >
                                    </div>
                                    <p data-workshop-order-error="promised_at" class="text-xs text-rose-500"></p>
                                </div>
                            </div>

                            <div class="grid gap-5 lg:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ gettext('Trabajo a realizar') }} <span class="text-rose-500">*</span>
                                    </label>
                                    <textarea
                                        data-workshop-order-input="work_summary"
                                        class="min-h-[120px] w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-900 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                        maxlength="255"
                                        required
                                    ></textarea>
                                    <p data-workshop-order-error="work_summary" class="text-xs text-rose-500"></p>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ gettext('Estado general del equipo') }}
                                    </label>
                                    <textarea
                                        data-workshop-order-input="general_condition"
                                        class="min-h-[120px] w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                    ></textarea>
                                    <p data-workshop-order-error="general_condition" class="text-xs text-rose-500"></p>
                                </div>
                            </div>

                            <div class="grid gap-5">
                                <div class="space-y-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ gettext('Descripción detallada del trabajo') }}
                                    </label>
                                    <textarea
                                        data-workshop-order-input="work_description"
                                        class="min-h-[140px] w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                    ></textarea>
                                    <p data-workshop-order-error="work_description" class="text-xs text-rose-500"></p>
                                </div>
                            </div>

                            <div class="grid gap-5 lg:grid-cols-3">
                                <div class="space-y-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ gettext('Diagnóstico realizado') }} <span class="text-rose-500">*</span>
                                    </label>
                                    <div
                                        class="relative"
                                        data-select
                                        data-select-name="diagnosis"
                                        data-select-manual="true"
                                        data-select-invalid="false"
                                    >
                                        <input type="hidden" data-workshop-order-input="diagnosis" name="diagnosis" value="">
                                        <button
                                            type="button"
                                            class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-surface dark:bg-slate-900 dark:text-white"
                                            data-select-trigger
                                            data-select-placeholder="{{ gettext('Selecciona una opción') }}"
                                            aria-haspopup="listbox"
                                            aria-expanded="false"
                                        >
                                            <span data-select-value class="truncate">{{ gettext('Selecciona una opción') }}</span>
                                            <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
                                        </button>
                                        <div
                                            class="invisible absolute left-0 right-0 z-[95] mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                            data-select-dropdown
                                            role="listbox"
                                            hidden
                                        >
                                            <div class="max-h-60 overflow-y-auto py-2" data-workshop-order-diagnosis-options></div>
                                        </div>
                                    </div>
                                    <p data-workshop-order-error="diagnosis" class="text-xs text-rose-500"></p>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ gettext('Cuenta con garantía') }} <span class="text-rose-500">*</span>
                                    </label>
                                    <div
                                        class="relative"
                                        data-select
                                        data-select-name="warranty"
                                        data-select-manual="true"
                                        data-select-invalid="false"
                                    >
                                        <input type="hidden" data-workshop-order-input="warranty" name="warranty" value="">
                                        <button
                                            type="button"
                                            class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-surface dark:bg-slate-900 dark:text-white"
                                            data-select-trigger
                                            data-select-placeholder="{{ gettext('Selecciona una opción') }}"
                                            aria-haspopup="listbox"
                                            aria-expanded="false"
                                        >
                                            <span data-select-value class="truncate">{{ gettext('Selecciona una opción') }}</span>
                                            <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
                                        </button>
                                        <div
                                            class="invisible absolute left-0 right-0 z-[95] mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                            data-select-dropdown
                                            role="listbox"
                                            hidden
                                        >
                                            <div class="max-h-60 overflow-y-auto py-2" data-workshop-order-warranty-options></div>
                                        </div>
                                    </div>
                                    <p data-workshop-order-error="warranty" class="text-xs text-rose-500"></p>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ gettext('Contraseña del equipo') }}
                                    </label>
                                    <input
                                        type="text"
                                        data-workshop-order-input="equipment_password"
                                        class="w-full rounded-2xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                        maxlength="255"
                                    >
                                    <p data-workshop-order-error="equipment_password" class="text-xs text-rose-500"></p>
                                </div>
                            </div>

                            <div class="grid gap-5 lg:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ gettext('Presupuesto estimado') }}
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        data-workshop-order-input="budget_amount"
                                        class="w-full rounded-2xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                    >
                                    <p data-workshop-order-error="budget_amount" class="text-xs text-rose-500"></p>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ gettext('Abono recibido') }}
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        data-workshop-order-input="advance_amount"
                                        class="w-full rounded-2xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                    >
                                    <p data-workshop-order-error="advance_amount" class="text-xs text-rose-500"></p>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ gettext('Accesorios entregados') }}
                                    </label>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">
                                        {{ gettext('Selecciona uno o varios accesorios. Usa el buscador para filtrar o crea nuevos registros.') }}
                                    </p>
                                </div>
                                <div class="space-y-3 rounded-3xl border border-dashed border-gray-200 p-4 dark:border-gray-700" data-workshop-order-accessories>
                                    <div class="flex flex-wrap gap-2" data-workshop-order-accessories-selected></div>
                                    <div class="flex flex-wrap items-center gap-3">
                                        <div class="relative flex-1">
                                            <input
                                                type="search"
                                                class="w-full rounded-2xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                                placeholder="{{ gettext('Buscar accesorio...') }}"
                                                autocomplete="off"
                                                data-workshop-order-accessories-search
                                            >
                                            <div
                                                class="invisible absolute left-0 right-0 z-[80] mt-2 w-full origin-top scale-95 transform overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                                data-workshop-order-accessories-results
                                                hidden
                                            ></div>
                                        </div>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-strong disabled:cursor-not-allowed disabled:opacity-60"
                                            data-workshop-order-accessories-create-button
                                        >
                                            <i class="fa-solid fa-plus text-sm"></i>
                                            {{ gettext('Crear accesorio') }}
                                        </button>
                                    </div>
                                </div>
                                <p data-workshop-order-error="accessories" class="text-xs text-rose-500"></p>
                            </div>

                        </div>

                        <div class="mt-6 flex items-center justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                                data-workshop-order-modal-close
                            >
                                {{ gettext('Cancelar') }}
                            </button>
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-strong disabled:cursor-not-allowed disabled:opacity-70"
                                data-workshop-order-submit
                            >
                                <span data-workshop-order-submit-label>{{ gettext('Guardar') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- View Modal -->
        <div
            class="fixed inset-0 z-[60] hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
            data-workshop-order-modal="view"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-6 w-full max-w-4xl">
                <div class="relative max-h-[88vh] overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white">{{ gettext('Detalle de la orden') }}</h3>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-workshop-order-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="grid max-h-[88vh] gap-4 overflow-y-auto px-6 py-6 lg:grid-cols-2">
                        <div class="space-y-4">
                            @foreach ([
                                ['icon' => 'fa-solid fa-user', 'label' => gettext('Cliente'), 'field' => 'customer_name'],
                                ['icon' => 'fa-solid fa-desktop', 'label' => gettext('Equipo'), 'field' => 'equipment_label'],
                                ['icon' => 'fa-solid fa-sitemap', 'label' => gettext('Categoría de taller'), 'field' => 'category_name'],
                                ['icon' => 'fa-solid fa-circle-dot', 'label' => gettext('Estado de taller'), 'field' => 'state_name'],
                                ['icon' => 'fa-solid fa-user-gear', 'label' => gettext('Responsable'), 'field' => 'responsible_name'],
                                ['icon' => 'fa-solid fa-flag', 'label' => gettext('Prioridad'), 'field' => 'priority'],
                                ['icon' => 'fa-solid fa-calendar-day', 'label' => gettext('Fecha prometida'), 'field' => 'promised_at_formatted'],
                            ] as $card)
                                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/70">
                                    <div class="flex items-start gap-3">
                                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary dark:bg-primary/20 dark:text-primary-100">
                                            <i class="{{ $card['icon'] }}"></i>
                                        </span>
                                        <div class="flex-1">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $card['label'] }}</span>
                                            <p class="mt-1 text-sm text-gray-900 dark:text-white" data-workshop-order-view-field="{{ $card['field'] }}">-</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="space-y-4">
                            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/70">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary dark:bg-primary/20 dark:text-primary-100">
                                        <i class="fa-solid fa-clipboard-list"></i>
                                    </span>
                                    <div class="flex-1">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Trabajo a realizar') }}</span>
                                        <p class="mt-1 whitespace-pre-line text-sm text-gray-900 dark:text-white" data-workshop-order-view-field="work_summary">-</p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/70">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary dark:bg-primary/20 dark:text-primary-100">
                                        <i class="fa-solid fa-align-left"></i>
                                    </span>
                                    <div class="flex-1">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Descripción del trabajo') }}</span>
                                        <p class="mt-1 whitespace-pre-line text-sm text-gray-900 dark:text-white" data-workshop-order-view-field="work_description">-</p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/70">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary dark:bg-primary/20 dark:text-primary-100">
                                        <i class="fa-solid fa-heart-pulse"></i>
                                    </span>
                                    <div class="flex-1">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Estado general') }}</span>
                                        <p class="mt-1 whitespace-pre-line text-sm text-gray-900 dark:text-white" data-workshop-order-view-field="general_condition">-</p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/70">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary dark:bg-primary/20 dark:text-primary-100">
                                        <i class="fa-solid fa-toolbox"></i>
                                    </span>
                                    <div class="flex-1">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Accesorios entregados') }}</span>
                                        <div class="mt-2 flex flex-wrap gap-2 text-sm text-gray-900 dark:text-white" data-workshop-order-view-field="accessories">
                                            <span>-</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/70">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary dark:bg-primary/20 dark:text-primary-100">
                                        <i class="fa-solid fa-stethoscope"></i>
                                    </span>
                                    <div class="flex-1">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Diagnóstico realizado') }}</span>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white" data-workshop-order-view-field="diagnosis_label">-</p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/70">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary dark:bg-primary/20 dark:text-primary-100">
                                        <i class="fa-solid fa-award"></i>
                                    </span>
                                    <div class="flex-1">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Cuenta con garantía') }}</span>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white" data-workshop-order-view-field="warranty_label">-</p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/70">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary dark:bg-primary/20 dark:text-primary-100">
                                        <i class="fa-solid fa-key"></i>
                                    </span>
                                    <div class="flex-1">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Contraseña del equipo') }}</span>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white" data-workshop-order-view-field="equipment_password">-</p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/70">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary dark:bg-primary/20 dark:text-primary-100">
                                        <i class="fa-solid fa-money-bill-wave"></i>
                                    </span>
                                    <div class="flex-1">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Presupuesto estimado') }}</span>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white" data-workshop-order-view-field="budget_amount_formatted">-</p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/70">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary dark:bg-primary/20 dark:text-primary-100">
                                        <i class="fa-solid fa-hand-holding-dollar"></i>
                                    </span>
                                    <div class="flex-1">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Abono recibido') }}</span>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white" data-workshop-order-view-field="advance_amount_formatted">-</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                            data-workshop-order-modal-close
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
            data-workshop-order-modal="delete"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-6 w-full max-w-md">
                <div class="overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white">{{ gettext('Eliminar orden') }}</h3>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-workshop-order-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="space-y-4 px-6 py-6">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ gettext('¿Estás seguro de eliminar la orden seleccionada? Esta acción no se puede deshacer.') }}
                        </p>
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                            <i class="fa-solid fa-circle-info mr-2"></i>
                            <span>{{ gettext('Se eliminará:') }} <strong data-workshop-order-delete-summary></strong></span>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                            data-workshop-order-modal-close
                        >
                            {{ gettext('Cancelar') }}
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-70"
                            data-workshop-order-delete-confirm
                        >
                            {{ gettext('Eliminar') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Accessory Create Modal -->
        <div
            class="fixed inset-0 z-[75] hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
            data-workshop-order-modal="accessory-create"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-6 w-full max-w-md">
                <div class="relative w-full overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white">{{ gettext('Crear accesorio') }}</h3>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-workshop-order-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form data-workshop-order-accessory-form class="px-6 py-6" novalidate>
                        <div class="space-y-4">
                            <div class="space-y-2">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ gettext('Nombre del accesorio') }} <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    data-workshop-order-accessory-input="name"
                                    class="w-full rounded-2xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                    maxlength="255"
                                    required
                                    autofocus
                                >
                                <p data-workshop-order-accessory-error="name" class="text-xs text-rose-500"></p>
                            </div>
                        </div>

                        <div class="mt-6 flex items-center justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                                data-workshop-order-modal-close
                            >
                                {{ gettext('Cancelar') }}
                            </button>
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-strong disabled:cursor-not-allowed disabled:opacity-70"
                                data-workshop-order-accessory-submit
                            >
                                <span data-workshop-order-accessory-submit-label>{{ gettext('Guardar') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-layouts.dashboard-layout>

