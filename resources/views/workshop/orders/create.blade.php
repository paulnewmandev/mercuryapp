<x-layouts.dashboard-layout :meta="$meta ?? []">
    <x-ui.breadcrumb :title="gettext('Crear orden de trabajo')" :items="$breadcrumbItems" />

    <section class="w-full space-y-6 pb-12" data-workshop-order-form-root>
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-slate-900/40">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Información básica') }}</h2>
            </div>

            <form method="POST" action="{{ route('taller.ordenes.store') }}" class="space-y-6 px-6 py-6" novalidate>
                @csrf
                <input type="hidden" name="status" value="A">

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ gettext('Cliente') }} <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative" data-select data-select-name="customer_id" data-select-searchable="true" data-select-manual="true">
                            <input type="hidden" name="customer_id" data-select-input data-order-customer-id required>
                            <button
                                type="button"
                                class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-surface dark:bg-slate-900 dark:text-white"
                                data-select-trigger
                                data-select-placeholder="{{ gettext('Selecciona un cliente') }}"
                            >
                                <span data-select-value class="truncate">{{ gettext('Selecciona un cliente') }}</span>
                                <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
                            </button>
                            <div
                                class="invisible absolute left-0 right-0 z-50 mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                data-select-dropdown
                                hidden
                            >
                                <div class="border-b border-gray-200 p-2 dark:border-gray-700">
                                    <input
                                        type="search"
                                        data-order-customer-search
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                        placeholder="{{ gettext('Buscar por nombre o documento...') }}"
                                        autocomplete="off"
                                    >
                                </div>
                                <div class="max-h-60 overflow-y-auto py-2" data-order-customer-options></div>
                            </div>
                        </div>
                        @error('customer_id')
                            <p class="text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ gettext('Equipo') }} <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative" data-select data-select-name="equipment_id" data-select-searchable="true" data-select-manual="true">
                            <input type="hidden" name="equipment_id" data-select-input data-order-equipment-id required>
                            <button
                                type="button"
                                class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-surface dark:bg-slate-900 dark:text-white"
                                data-select-trigger
                                data-select-placeholder="{{ gettext('Selecciona un equipo') }}"
                            >
                                <span data-select-value class="truncate">{{ gettext('Selecciona un equipo') }}</span>
                                <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
                            </button>
                            <div
                                class="invisible absolute left-0 right-0 z-50 mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                data-select-dropdown
                                hidden
                            >
                                <div class="border-b border-gray-200 p-2 dark:border-gray-700">
                                    <input
                                        type="search"
                                        data-order-equipment-search
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                        placeholder="{{ gettext('Buscar por identificador o marca...') }}"
                                        autocomplete="off"
                                    >
                                </div>
                                <div class="max-h-60 overflow-y-auto py-2" data-order-equipment-options></div>
                            </div>
                        </div>
                        @error('equipment_id')
                            <p class="text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ gettext('Categoría de taller') }} <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative" data-select data-select-name="category_id" data-select-manual="true">
                            <input type="hidden" name="category_id" data-select-input required>
                            <button
                                type="button"
                                class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-surface dark:bg-slate-900 dark:text-white"
                                data-select-trigger
                                data-select-placeholder="{{ gettext('Selecciona una categoría') }}"
                            >
                                <span data-select-value class="truncate">{{ gettext('Selecciona una categoría') }}</span>
                                <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
                            </button>
                            <div
                                class="invisible absolute left-0 right-0 z-50 mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                data-select-dropdown
                                hidden
                            >
                                <div class="max-h-60 overflow-y-auto py-2" data-order-category-options></div>
                            </div>
                        </div>
                        @error('category_id')
                            <p class="text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ gettext('Estado de taller') }}
                        </label>
                        <div class="relative" data-select data-select-name="state_id" data-select-manual="true">
                            <input type="hidden" name="state_id" data-select-input>
                            <button
                                type="button"
                                class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-surface dark:bg-slate-900 dark:text-white"
                                data-select-trigger
                                data-select-placeholder="{{ gettext('Selecciona un estado') }}"
                                disabled
                            >
                                <span data-select-value class="truncate">{{ gettext('Selecciona un estado') }}</span>
                                <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
                            </button>
                            <div
                                class="invisible absolute left-0 right-0 z-50 mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                data-select-dropdown
                                hidden
                            >
                                <div class="max-h-60 overflow-y-auto py-2" data-order-state-options></div>
                            </div>
                        </div>
                        @error('state_id')
                            <p class="text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ gettext('Prioridad') }} <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative" data-select data-select-name="priority" data-select-manual="true">
                            <input type="hidden" name="priority" data-select-input value="Normal" required>
                            <button
                                type="button"
                                class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-surface dark:bg-slate-900 dark:text-white"
                                data-select-trigger
                                data-select-placeholder="{{ gettext('Selecciona una prioridad') }}"
                            >
                                <span data-select-value class="truncate">{{ gettext('Normal') }}</span>
                                <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
                            </button>
                            <div
                                class="invisible absolute left-0 right-0 z-50 mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                data-select-dropdown
                                hidden
                            >
                                <div class="max-h-60 overflow-y-auto py-2" data-order-priority-options></div>
                            </div>
                        </div>
                        @error('priority')
                            <p class="text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ gettext('Usuario responsable') }} <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative" data-select data-select-name="responsible_user_id" data-select-searchable="true" data-select-manual="true">
                            <input type="hidden" name="responsible_user_id" data-select-input data-order-responsible-id required>
                            <button
                                type="button"
                                class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-surface dark:bg-slate-900 dark:text-white"
                                data-select-trigger
                                data-select-placeholder="{{ gettext('Selecciona un responsable') }}"
                            >
                                <span data-select-value class="truncate">{{ gettext('Selecciona un responsable') }}</span>
                                <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
                            </button>
                            <div
                                class="invisible absolute left-0 right-0 z-50 mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                data-select-dropdown
                                hidden
                            >
                                <div class="border-b border-gray-200 p-2 dark:border-gray-700">
                                    <input
                                        type="search"
                                        data-order-responsible-search
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                        placeholder="{{ gettext('Buscar por nombre o correo...') }}"
                                        autocomplete="off"
                                    >
                                </div>
                                <div class="max-h-60 overflow-y-auto py-2" data-order-responsible-options></div>
                            </div>
                        </div>
                        @error('responsible_user_id')
                            <p class="text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ gettext('Fecha prometida') }}
                        </label>
                        <input
                            type="datetime-local"
                            name="promised_at"
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                        >
                        @error('promised_at')
                            <p class="text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ gettext('Nota') }}
                    </label>
                    <textarea
                        name="note"
                        rows="4"
                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                        placeholder="{{ gettext('Escribe una nota sobre esta orden...') }}"
                    >{{ old('note') }}</textarea>
                    @error('note')
                        <p class="text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-6 lg:grid-cols-3">
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ gettext('Diagnóstico realizado') }} <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative" data-select data-select-name="diagnosis" data-select-manual="true">
                            <input type="hidden" name="diagnosis" data-select-input value="0" required>
                            <button
                                type="button"
                                class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-surface dark:bg-slate-900 dark:text-white"
                                data-select-trigger
                                data-select-placeholder="{{ gettext('Selecciona una opción') }}"
                            >
                                <span data-select-value class="truncate">{{ gettext('No') }}</span>
                                <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
                            </button>
                            <div
                                class="invisible absolute left-0 right-0 z-50 mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                data-select-dropdown
                                hidden
                            >
                                <div class="max-h-60 overflow-y-auto py-2" data-order-diagnosis-options></div>
                            </div>
                        </div>
                        @error('diagnosis')
                            <p class="text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ gettext('Cuenta con garantía') }} <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative" data-select data-select-name="warranty" data-select-manual="true">
                            <input type="hidden" name="warranty" data-select-input value="0" required>
                            <button
                                type="button"
                                class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-surface dark:bg-slate-900 dark:text-white"
                                data-select-trigger
                                data-select-placeholder="{{ gettext('Selecciona una opción') }}"
                            >
                                <span data-select-value class="truncate">{{ gettext('No') }}</span>
                                <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
                            </button>
                            <div
                                class="invisible absolute left-0 right-0 z-50 mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                data-select-dropdown
                                hidden
                            >
                                <div class="max-h-60 overflow-y-auto py-2" data-order-warranty-options></div>
                            </div>
                        </div>
                        @error('warranty')
                            <p class="text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ gettext('Contraseña del equipo') }}
                        </label>
                        <input
                            type="text"
                            name="equipment_password"
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                            maxlength="255"
                            value="{{ old('equipment_password') }}"
                        >
                        @error('equipment_password')
                            <p class="text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ gettext('Presupuesto estimado') }}
                        </label>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">USD</span>
                            <input type="hidden" name="budget_currency" value="USD">
                            <input
                                type="number"
                                name="budget_amount"
                                step="0.01"
                                min="0"
                                class="flex-1 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                value="{{ old('budget_amount') }}"
                                placeholder="0.00"
                            >
                        </div>
                        @error('budget_amount')
                            <p class="text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ gettext('Abono recibido') }}
                        </label>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">USD</span>
                            <input type="hidden" name="advance_currency" value="USD">
                            <input
                                type="number"
                                name="advance_amount"
                                step="0.01"
                                min="0"
                                class="flex-1 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                value="{{ old('advance_amount') }}"
                                placeholder="0.00"
                            >
                        </div>
                        @error('advance_amount')
                            <p class="text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ gettext('Accesorios entregados') }}
                        </label>
                    </div>
                    <div class="space-y-3 rounded-xl border border-dashed border-gray-200 p-4 dark:border-gray-700" data-order-accessories>
                        <div class="flex flex-wrap gap-2" data-order-accessories-selected></div>
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="relative flex-1">
                                <input
                                    type="search"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                    placeholder="{{ gettext('Buscar accesorio...') }}"
                                    autocomplete="off"
                                    data-order-accessories-search
                                >
                                <div
                                    class="invisible absolute left-0 right-0 z-50 mt-2 w-full origin-top scale-95 transform overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                    data-order-accessories-results
                                    hidden
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-6 dark:border-gray-800">
                    <a
                        href="{{ route('taller.ordenes_de_trabajo') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-5 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                    >
                        {{ gettext('Cancelar') }}
                    </a>
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-strong disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        {{ gettext('Crear') }}
                    </button>
                </div>
            </form>
        </div>
    </section>

</x-layouts.dashboard-layout>

