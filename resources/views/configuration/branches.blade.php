<x-layouts.dashboard-layout :meta="$meta ?? []">
    <x-ui.breadcrumb :title="gettext('Sucursales')" :items="$breadcrumbItems" />

    @php
        $columns = [
            [
                'key' => 'code',
                'label' => gettext('Código'),
            ],
            [
                'key' => 'name',
                'label' => gettext('Nombre comercial'),
            ],
            [
                'key' => 'address',
                'label' => gettext('Dirección'),
            ],
            [
                'key' => 'email',
                'label' => gettext('Correo electrónico'),
            ],
            [
                'key' => 'phone_number',
                'label' => gettext('Teléfono'),
                'align' => 'center',
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
        data-branches-root
        data-branches-store-url="{{ route('configuration.branches.store') }}"
        data-branches-base-url="{{ url('configuration/branches') }}"
        data-branches-table-id="branches-table"
        data-branches-maps-key="AIzaSyDqyp7ZhVq5Oj7sLwmP_QeLs26GWS4oQIs"
    >
        <x-ui.data-table
            tableId="branches-table"
            apiUrl="{{ route('configuration.branches.data') }}"
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
                    data-branch-action="create"
                >
                    <i class="fa-solid fa-plus text-sm"></i>
                    {{ gettext('Nuevo') }}
                </button>
            </x-slot:headerActions>
        </x-ui.data-table>

        <!-- Form Modal -->
        <div
            class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
            data-branch-modal="form"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-8 w-full max-w-3xl">
                <div class="relative w-full overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <div class="space-y-1">
                            <h3 class="text-base font-medium text-gray-900 dark:text-white" data-branch-modal-title></h3>
            </div>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-branch-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form data-branch-form class="space-y-6 px-6 py-6" novalidate>
                        <input type="hidden" data-branch-input="id">

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Código') }}</label>
                                <input
                                    type="text"
                                    data-branch-input="code"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                    maxlength="50"
                                    required
                                >
                                <p data-branch-error="code" class="mt-1 text-xs text-rose-500"></p>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Nombre') }}</label>
                                <input
                                    type="text"
                                    data-branch-input="name"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                    maxlength="255"
                                    required
                                >
                                <p data-branch-error="name" class="mt-1 text-xs text-rose-500"></p>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Dirección') }}</label>
                                <input
                                    type="text"
                                    data-branch-input="address"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                    maxlength="255"
                                >
                                <p data-branch-error="address" class="mt-1 text-xs text-rose-500"></p>
                            </div>
                            <div class="sm:col-span-2 grid gap-5 sm:grid-cols-3">
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Sitio web') }}</label>
                        <input
                                        type="url"
                                        data-branch-input="website"
                                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                        maxlength="255"
                                    >
                                    <p data-branch-error="website" class="mt-1 text-xs text-rose-500"></p>
                    </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Correo electrónico') }}</label>
                                    <input
                                        type="email"
                                        data-branch-input="email"
                                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                        maxlength="255"
                                    >
                                    <p data-branch-error="email" class="mt-1 text-xs text-rose-500"></p>
                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Teléfono') }}</label>
                                    <input
                                        type="text"
                                        data-branch-input="phone_number"
                                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark;border-gray-700 dark:bg-slate-900 dark:text-white"
                                        maxlength="50"
                                    >
                                    <p data-branch-error="phone_number" class="mt-1 text-xs text-rose-500"></p>
                                </div>
                            </div>
                            <input type="hidden" data-branch-input="latitude">
                            <input type="hidden" data-branch-input="longitude">
                            <input type="hidden" data-branch-input="status">
                            <div class="sm:col-span-2 space-y-3">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ gettext('Buscar ubicación') }}</label>
                                <input
                                    type="text"
                                    data-branch-map-search
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                    placeholder="{{ gettext('Ingresa una dirección o lugar...') }}"
                                >
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ gettext('Escribe una dirección, selecciona una sugerencia o mueve el pin para ajustar la ubicación.') }}</p>
                                <div
                                    data-branch-map
                                    class="h-64 w-full overflow-hidden rounded-xl border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-slate-800"
                                ></div>
                                <p data-branch-map-feedback class="text-xs text-gray-500 dark:text-gray-400"></p>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                                data-branch-modal-close
                            >
                                {{ gettext('Cancelar') }}
                            </button>
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-inverted shadow-sm transition hover:bg-primary-strong disabled:cursor-not-allowed disabled:opacity-70"
                                data-branch-submit
                            >
                                <span data-branch-submit-label>{{ gettext('Guardar') }}</span>
                                    </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- View Modal -->
        <div
            class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
            data-branch-modal="view"
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
                            data-branch-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                                                </button>
                    </div>
                    <div class="space-y-4 px-6 py-6" data-branch-view>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary">
                                        <i class="fa-solid fa-hashtag"></i>
                                    </span>
                                    <div class="flex-1">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Código') }}</span>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white" data-branch-view-field="code"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary">
                                        <i class="fa-solid fa-building"></i>
                                    </span>
                                    <div class="flex-1">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Nombre') }}</span>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white" data-branch-view-field="name"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-slate-900/60">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-primary">
                                        <i class="fa-regular fa-envelope"></i>
                                    </span>
                                    <div class="flex-1">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Correo electrónico') }}</span>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white" data-branch-view-field="email"></p>
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
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white" data-branch-view-field="phone_number"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-slate-900/60 sm:col-span-2">
                                <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-800">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Ubicación en mapa') }}</span>
                                </div>
                                <div class="p-4">
                                    <img
                                        data-branch-view-map
                                        src=""
                                        alt="{{ gettext('Mapa de la ubicación') }}"
                                        class="h-64 w-full rounded-xl object-cover"
                                        loading="lazy"
                                    >
                                </div>
                </div>
                                                </div>
                                            </div>
                    <div class="flex justify-end border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                            data-branch-modal-close
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
            data-branch-modal="delete"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-8 w-full max-w-lg">
                <div class="relative w-full overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white">{{ gettext('Eliminar registro') }}</h3>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-branch-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="space-y-4 px-6 py-6">
                        <p class="text-sm text-gray-700 dark:text-gray-200">{{ gettext('¿Confirma que deseas eliminar este registro? Esta acción no se puede deshacer.') }}</p>
                        <div class="rounded-xl bg-gray-100 px-4 py-3 text-sm text-gray-700 dark:bg-slate-800 dark:text-gray-200" data-branch-delete-name></div>
                    </div>
                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                            data-branch-modal-close
                        >
                            {{ gettext('Cancelar') }}
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-70"
                            data-branch-delete-confirm
                        >
                            {{ gettext('Eliminar') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.dashboard-layout>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

