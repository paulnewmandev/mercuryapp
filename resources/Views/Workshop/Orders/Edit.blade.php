<x-layouts.dashboard-layout :meta="$meta ?? []">
    <x-ui.breadcrumb :title="$order->order_number ?? gettext('Editar orden')" :items="$breadcrumbItems" />

    <section class="w-full space-y-6 pb-12" data-workshop-order-edit-root data-order-id="{{ $order->id }}">
        <!-- Información Básica -->
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-slate-900/40">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Información básica') }}</h2>
            </div>

            <form method="POST" action="{{ route('taller.ordenes.update', $order) }}" class="space-y-6 px-6 py-6" novalidate>
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="{{ $order->status }}">

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ gettext('Cliente') }} <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative" data-order-customer>
                            <div class="flex w-full items-center gap-3 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 transition focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white">
                                <i class="fa-solid fa-magnifying-glass text-gray-400"></i>
                                <input
                                    type="search"
                                    class="flex-1 border-none bg-transparent outline-none"
                                    placeholder="{{ gettext('Buscar por nombre o documento') }}"
                                    autocomplete="off"
                                    data-order-customer-search
                                    value="{{ $order->customer?->display_name ?? '' }}"
                                    required
                                >
                                <input type="hidden" name="customer_id" data-order-customer-id value="{{ $order->customer_id }}" required>
                                <a
                                    href="{{ route('clientes.index') }}"
                                    target="_blank"
                                    class="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-primary hover:text-white dark:bg-slate-800 dark:text-gray-200"
                                >
                                    <i class="fa-solid fa-plus text-[11px]"></i>
                                    {{ gettext('Crear') }}
                                </a>
                            </div>
                            <div
                                class="invisible absolute left-0 right-0 z-50 mt-2 w-full origin-top scale-95 transform overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                data-order-customer-results
                                hidden
                            ></div>
                        </div>
                        @error('customer_id')
                            <p class="text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ gettext('Equipo') }} <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative" data-order-equipment>
                            <div class="flex w-full items-center gap-3 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 transition focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white">
                                <i class="fa-solid fa-magnifying-glass text-gray-400"></i>
                                <input
                                    type="search"
                                    class="flex-1 border-none bg-transparent outline-none"
                                    placeholder="{{ gettext('Buscar por identificador o marca') }}"
                                    autocomplete="off"
                                    data-order-equipment-search
                                    value="{{ ($order->equipment?->brand?->name ?? '') . ' · ' . ($order->equipment?->model?->name ?? '') . ' · ' . ($order->equipment?->identifier ?? '') }}"
                                    required
                                >
                                <input type="hidden" name="equipment_id" data-order-equipment-id value="{{ $order->equipment_id }}" required>
                                <a
                                    href="{{ route('taller.equipos') }}"
                                    target="_blank"
                                    class="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-primary hover:text-white dark:bg-slate-800 dark:text-gray-200"
                                >
                                    <i class="fa-solid fa-plus text-[11px]"></i>
                                    {{ gettext('Crear') }}
                                </a>
                            </div>
                            <div
                                class="invisible absolute left-0 right-0 z-50 mt-2 w-full origin-top scale-95 transform overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                data-order-equipment-results
                                hidden
                            ></div>
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
                            <input type="hidden" name="category_id" data-select-input value="{{ $order->category_id }}" required>
                            <button
                                type="button"
                                class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-surface dark:bg-slate-900 dark:text-white"
                                data-select-trigger
                                data-select-placeholder="{{ gettext('Selecciona una categoría') }}"
                            >
                                <span data-select-value class="truncate">{{ $order->category?->name ?? gettext('Selecciona una categoría') }}</span>
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
                            <input type="hidden" name="state_id" data-select-input value="{{ $order->state_id ?? '' }}">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-surface dark:bg-slate-900 dark:text-white"
                                data-select-trigger
                                data-select-placeholder="{{ gettext('Selecciona un estado') }}"
                            >
                                <span data-select-value class="truncate">{{ $order->state?->name ?? gettext('Selecciona un estado') }}</span>
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
                            <input type="hidden" name="priority" data-select-input value="{{ $order->priority }}" required>
                            <button
                                type="button"
                                class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-surface dark:bg-slate-900 dark:text-white"
                                data-select-trigger
                                data-select-placeholder="{{ gettext('Selecciona una prioridad') }}"
                            >
                                <span data-select-value class="truncate">{{ $order->priority }}</span>
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
                        <div class="relative" data-order-responsible>
                            <div class="flex w-full items-center gap-3 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 transition focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white">
                                <i class="fa-solid fa-user-gear text-gray-400"></i>
                                <input
                                    type="search"
                                    class="flex-1 border-none bg-transparent outline-none"
                                    placeholder="{{ gettext('Buscar por nombre o correo') }}"
                                    autocomplete="off"
                                    data-order-responsible-search
                                    value="{{ $order->responsible?->display_name ?? '' }}"
                                    required
                                >
                                <input type="hidden" name="responsible_user_id" data-order-responsible-id value="{{ $order->responsible_user_id }}" required>
                                <a
                                    href="{{ route('security.users') }}"
                                    target="_blank"
                                    class="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-primary hover:text-white dark:bg-slate-800 dark:text-gray-200"
                                >
                                    <i class="fa-solid fa-plus text-[11px]"></i>
                                    {{ gettext('Crear') }}
                                </a>
                            </div>
                            <div
                                class="invisible absolute left-0 right-0 z-50 mt-2 w-full origin-top scale-95 transform overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                data-order-responsible-results
                                hidden
                            ></div>
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
                            value="{{ $order->promised_at ? $order->promised_at->format('Y-m-d\TH:i') : '' }}"
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
                    >{{ old('note', $order->note) }}</textarea>
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
                            <input type="hidden" name="diagnosis" data-select-input value="{{ $order->diagnosis ? '1' : '0' }}" required>
                            <button
                                type="button"
                                class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-surface dark:bg-slate-900 dark:text-white"
                                data-select-trigger
                                data-select-placeholder="{{ gettext('Selecciona una opción') }}"
                            >
                                <span data-select-value class="truncate">{{ $order->diagnosis ? gettext('Sí') : gettext('No') }}</span>
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
                            <input type="hidden" name="warranty" data-select-input value="{{ $order->warranty ? '1' : '0' }}" required>
                            <button
                                type="button"
                                class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-surface dark:bg-slate-900 dark:text-white"
                                data-select-trigger
                                data-select-placeholder="{{ gettext('Selecciona una opción') }}"
                            >
                                <span data-select-value class="truncate">{{ $order->warranty ? gettext('Sí') : gettext('No') }}</span>
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
                            value="{{ old('equipment_password', $order->equipment_password) }}"
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
                                value="{{ old('budget_amount', $order->budget_amount) }}"
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
                                value="{{ old('advance_amount', $order->advance_amount) }}"
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
                        <div class="flex flex-wrap gap-2" data-order-accessories-selected>
                            @foreach($order->accessories as $accessory)
                                <span class="inline-flex items-center gap-2 rounded-lg bg-primary/10 px-3 py-1.5 text-sm font-medium text-primary">
                                    {{ $accessory->name }}
                                    <input type="hidden" name="accessories[]" value="{{ $accessory->id }}">
                                </span>
                            @endforeach
                        </div>
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
                        href="{{ route('taller.ordenes.show', $order) }}"
                        class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-5 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                    >
                        {{ gettext('Cancelar') }}
                    </a>
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-strong disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        {{ gettext('Guardar cambios') }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Notas -->
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-slate-900/40">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Notas') }}</h2>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-strong"
                        data-order-note-add
                    >
                        <i class="fa-solid fa-plus text-sm"></i>
                        {{ gettext('Agregar nota') }}
                    </button>
                </div>
            </div>

            <div class="px-6 py-6">
                <div class="space-y-4" data-order-notes-list>
                    @forelse($order->notes->where('status', 'A') as $note)
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-slate-800/50" data-order-note-item data-note-id="{{ $note->id }}">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $note->note }}</p>
                                    <div class="mt-2 flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                        <span class="flex items-center gap-1.5">
                                            <i class="fa-solid fa-user"></i>
                                            {{ $note->user->display_name }}
                                        </span>
                                        <span class="flex items-center gap-1.5">
                                            <i class="fa-solid fa-clock"></i>
                                            {{ $note->created_at->translatedFormat('d M Y H:i') }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-300 text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                                        data-order-note-edit
                                        data-note-id="{{ $note->id }}"
                                        data-note-text="{{ $note->note }}"
                                    >
                                        <i class="fa-regular fa-pen-to-square text-xs"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-rose-300 text-rose-600 transition hover:border-rose-500 hover:bg-rose-50 dark:border-rose-700 dark:text-rose-400"
                                        data-order-note-delete
                                        data-note-id="{{ $note->id }}"
                                    >
                                        <i class="fa-regular fa-trash-can text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ gettext('No hay notas registradas') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Productos -->
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-slate-900/40">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Productos') }}</h2>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-strong"
                        data-order-item-add
                    >
                        <i class="fa-solid fa-plus text-sm"></i>
                        {{ gettext('Agregar producto') }}
                    </button>
                </div>
            </div>

            <div class="px-6 py-6">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Producto') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Cantidad') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Precio unitario') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Subtotal') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Acciones') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700" data-order-items-list>
                            @forelse($order->items->where('status', 'A') as $item)
                                <tr data-order-item-row data-item-id="{{ $item->id }}">
                                    <td class="px-4 py-3">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->product?->name ?? 'N/A' }}</p>
                                            @if($item->product?->sku)
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ gettext('SKU') }}: {{ $item->product->sku }}</p>
                                            @endif
                                            @if($item->notes)
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $item->notes }}</p>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->quantity }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ number_format($item->unit_price, 2, '.', ',') }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($item->subtotal, 2, '.', ',') }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button
                                                type="button"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-300 text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                                                data-order-item-edit
                                                data-item-id="{{ $item->id }}"
                                                data-item-product-id="{{ $item->product_id }}"
                                                data-item-product-name="{{ $item->product?->name ?? '' }}"
                                                data-item-quantity="{{ $item->quantity }}"
                                                data-item-unit-price="{{ $item->unit_price }}"
                                                data-item-notes="{{ $item->notes ?? '' }}"
                                            >
                                                <i class="fa-regular fa-pen-to-square text-xs"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-rose-300 text-rose-600 transition hover:border-rose-500 hover:bg-rose-50 dark:border-rose-700 dark:text-rose-400"
                                                data-order-item-delete
                                                data-item-id="{{ $item->id }}"
                                            >
                                                <i class="fa-regular fa-trash-can text-xs"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        {{ gettext('No hay productos agregados') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Servicios -->
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-slate-900/40">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Servicios') }}</h2>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-strong"
                        data-order-service-add
                    >
                        <i class="fa-solid fa-plus text-sm"></i>
                        {{ gettext('Agregar servicio') }}
                    </button>
                </div>
            </div>

            <div class="px-6 py-6">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Servicio') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Cantidad') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Precio unitario') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Subtotal') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Acciones') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700" data-order-services-list>
                            @forelse($order->services->where('status', 'A') as $service)
                                <tr data-order-service-row data-service-id="{{ $service->id }}">
                                    <td class="px-4 py-3">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $service->service?->name ?? 'N/A' }}</p>
                                            @if($service->service?->description)
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $service->service->description }}</p>
                                            @endif
                                            @if($service->notes)
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $service->notes }}</p>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $service->quantity }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ number_format($service->unit_price, 2, '.', ',') }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($service->subtotal, 2, '.', ',') }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button
                                                type="button"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-300 text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                                                data-order-service-edit
                                                data-service-id="{{ $service->id }}"
                                                data-service-service-id="{{ $service->service_id }}"
                                                data-service-service-name="{{ $service->service?->name ?? '' }}"
                                                data-service-quantity="{{ $service->quantity }}"
                                                data-service-unit-price="{{ $service->unit_price }}"
                                                data-service-notes="{{ $service->notes ?? '' }}"
                                            >
                                                <i class="fa-regular fa-pen-to-square text-xs"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-rose-300 text-rose-600 transition hover:border-rose-500 hover:bg-rose-50 dark:border-rose-700 dark:text-rose-400"
                                                data-order-service-delete
                                                data-service-id="{{ $service->id }}"
                                            >
                                                <i class="fa-regular fa-trash-can text-xs"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        {{ gettext('No hay servicios agregados') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Resumen de Costos -->
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-slate-900/40">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Resumen de costos') }}</h2>
            </div>

            <div class="px-6 py-6">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ gettext('Subtotal productos') }}</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white" data-order-items-total>
                            {{ number_format($order->items->where('status', 'A')->sum('subtotal'), 2, '.', ',') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ gettext('Subtotal servicios') }}</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white" data-order-services-total>
                            {{ number_format($order->services->where('status', 'A')->sum('subtotal'), 2, '.', ',') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700">
                        <span class="text-base font-semibold text-gray-900 dark:text-white">{{ gettext('Costo total') }}</span>
                        <span class="text-base font-bold text-gray-900 dark:text-white" data-order-total-cost>
                            {{ number_format($order->total_cost ?? ($order->items->where('status', 'A')->sum('subtotal') + $order->services->where('status', 'A')->sum('subtotal')), 2, '.', ',') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ gettext('Total pagado (abonos)') }}</span>
                        <span class="text-sm font-semibold text-green-600 dark:text-green-400" data-order-total-paid>
                            {{ number_format($order->total_paid ?? $order->advances->where('status', 'A')->sum('amount'), 2, '.', ',') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700">
                        <span class="text-base font-semibold {{ ($order->balance ?? 0) >= 0 ? 'text-gray-900' : 'text-rose-600' }} dark:{{ ($order->balance ?? 0) >= 0 ? 'text-white' : 'text-rose-400' }}">
                            {{ gettext('Balance pendiente') }}
                        </span>
                        <span class="text-base font-bold {{ ($order->balance ?? 0) >= 0 ? 'text-gray-900' : 'text-rose-600' }} dark:{{ ($order->balance ?? 0) >= 0 ? 'text-white' : 'text-rose-400' }}" data-order-balance>
                            {{ number_format($order->balance ?? (($order->items->where('status', 'A')->sum('subtotal') + $order->services->where('status', 'A')->sum('subtotal')) - $order->advances->where('status', 'A')->sum('amount')), 2, '.', ',') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal Agregar/Editar Nota -->
    <div
        class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
        data-order-note-modal
        role="dialog"
        aria-modal="true"
    >
        <div class="mx-4 my-8 w-full max-w-2xl">
            <div class="relative w-full overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                    <h3 class="text-base font-medium text-gray-900 dark:text-white" data-order-note-modal-title>{{ gettext('Agregar nota') }}</h3>
                    <button
                        type="button"
                        class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                        data-order-note-modal-close
                        aria-label="{{ gettext('Cerrar') }}"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form data-order-note-form class="space-y-6 px-6 py-6" novalidate>
                    <input type="hidden" data-order-note-id>
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ gettext('Nota') }} <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            data-order-note-text
                            rows="6"
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                            placeholder="{{ gettext('Escribe tu nota...') }}"
                            required
                            maxlength="5000"
                        ></textarea>
                        <p data-order-note-error class="text-xs text-rose-500"></p>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                            data-order-note-modal-close
                        >
                            {{ gettext('Cancelar') }}
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-strong disabled:cursor-not-allowed disabled:opacity-70"
                            data-order-note-submit
                        >
                            <span data-order-note-submit-label>{{ gettext('Guardar') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Agregar/Editar Producto -->
    <div
        class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
        data-order-item-modal
        role="dialog"
        aria-modal="true"
    >
        <div class="mx-4 my-8 w-full max-w-2xl">
            <div class="relative w-full overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                    <h3 class="text-base font-medium text-gray-900 dark:text-white" data-order-item-modal-title>{{ gettext('Agregar producto') }}</h3>
                    <button
                        type="button"
                        class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                        data-order-item-modal-close
                        aria-label="{{ gettext('Cerrar') }}"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form data-order-item-form class="space-y-6 px-6 py-6" novalidate>
                    <input type="hidden" data-order-item-id>
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ gettext('Producto') }} <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative" data-select data-select-name="product_id" data-select-searchable="true" data-order-item-product-select>
                            <input type="hidden" data-order-item-product-id data-select-input required>
                            <button
                                type="button"
                                class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-surface dark:bg-slate-900 dark:text-white"
                                data-select-trigger
                                data-select-placeholder="{{ gettext('Selecciona un producto') }}"
                            >
                                <span data-select-value class="truncate">{{ gettext('Selecciona un producto') }}</span>
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
                                        data-order-item-product-search
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                        placeholder="{{ gettext('Buscar producto...') }}"
                                        autocomplete="off"
                                    >
                                </div>
                                <div class="max-h-60 overflow-y-auto py-2" data-order-item-product-options></div>
                            </div>
                        </div>
                        <p data-order-item-error="product_id" class="text-xs text-rose-500"></p>
                    </div>

                    <div class="grid gap-6 lg:grid-cols-2">
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                {{ gettext('Lista de precios') }}
                            </label>
                            <div class="relative" data-select data-select-name="price_list_id" data-order-item-price-list-select>
                                <input type="hidden" data-order-item-price-list-id data-select-input>
                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-surface dark:bg-slate-900 dark:text-white"
                                    data-select-trigger
                                    data-select-placeholder="{{ gettext('Selecciona una lista de precios') }}"
                                >
                                    <span data-select-value class="truncate">{{ gettext('Selecciona una lista de precios') }}</span>
                                    <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
                                </button>
                                <div
                                    class="invisible absolute left-0 right-0 z-50 mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                    data-select-dropdown
                                    hidden
                                >
                                    <div class="max-h-60 overflow-y-auto py-2" data-order-item-price-list-options></div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                {{ gettext('Cantidad') }} <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="number"
                                data-order-item-quantity
                                min="1"
                                max="9999"
                                step="1"
                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                required
                            >
                            <p data-order-item-error="quantity" class="text-xs text-rose-500"></p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ gettext('Precio unitario') }} <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="number"
                            data-order-item-unit-price
                            min="0"
                            step="0.01"
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                            required
                        >
                        <p data-order-item-error="unit_price" class="text-xs text-rose-500"></p>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ gettext('Notas') }}
                        </label>
                        <textarea
                            data-order-item-notes
                            rows="3"
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                            placeholder="{{ gettext('Notas adicionales sobre el producto...') }}"
                            maxlength="1000"
                        ></textarea>
                        <p data-order-item-error="notes" class="text-xs text-rose-500"></p>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                            data-order-item-modal-close
                        >
                            {{ gettext('Cancelar') }}
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-strong disabled:cursor-not-allowed disabled:opacity-70"
                            data-order-item-submit
                        >
                            <span data-order-item-submit-label>{{ gettext('Guardar') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Agregar/Editar Servicio -->
    <div
        class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
        data-order-service-modal
        role="dialog"
        aria-modal="true"
    >
        <div class="mx-4 my-8 w-full max-w-2xl">
            <div class="relative w-full overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-slate-900">
                <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                    <h3 class="text-base font-medium text-gray-900 dark:text-white" data-order-service-modal-title>{{ gettext('Agregar servicio') }}</h3>
                    <button
                        type="button"
                        class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                        data-order-service-modal-close
                        aria-label="{{ gettext('Cerrar') }}"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form data-order-service-form class="space-y-6 px-6 py-6" novalidate>
                    <input type="hidden" data-order-service-id>
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ gettext('Categoría') }} <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative" data-select data-select-manual="true" data-select-name="category_id" data-order-service-category-select>
                            <input type="hidden" data-order-service-category-id data-select-input required>
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
                                <div class="max-h-60 overflow-y-auto py-2" data-order-service-category-options></div>
                            </div>
                        </div>
                        <p data-order-service-error="category_id" class="text-xs text-rose-500"></p>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ gettext('Servicio') }} <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative" data-select data-select-manual="true" data-select-name="service_id" data-select-searchable="true" data-order-service-service-select>
                            <input type="hidden" data-order-service-service-id data-select-input disabled>
                            <button
                                type="button"
                                class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15 opacity-50 cursor-not-allowed dark:border-surface dark:bg-slate-900 dark:text-white"
                                data-select-trigger
                                data-select-placeholder="{{ gettext('Selecciona un servicio') }}"
                                disabled
                            >
                                <span data-select-value class="truncate">{{ gettext('Selecciona un servicio') }}</span>
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
                                        data-order-service-service-search
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                        placeholder="{{ gettext('Buscar servicio...') }}"
                                        autocomplete="off"
                                    >
                                </div>
                                <div class="max-h-60 overflow-y-auto py-2" data-order-service-service-options></div>
                            </div>
                        </div>
                        <p data-order-service-error="service_id" class="text-xs text-rose-500"></p>
                    </div>

                    <div class="grid gap-6 lg:grid-cols-2">
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                {{ gettext('Cantidad') }} <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="number"
                                data-order-service-quantity
                                min="1"
                                max="9999"
                                step="1"
                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                required
                            >
                            <p data-order-service-error="quantity" class="text-xs text-rose-500"></p>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                {{ gettext('Precio unitario') }} <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="number"
                                data-order-service-unit-price
                                min="0"
                                step="0.01"
                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                required
                            >
                            <p data-order-service-error="unit_price" class="text-xs text-rose-500"></p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ gettext('Notas') }}
                        </label>
                        <textarea
                            data-order-service-notes
                            rows="3"
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                            placeholder="{{ gettext('Notas adicionales sobre el servicio...') }}"
                            maxlength="1000"
                        ></textarea>
                        <p data-order-service-error="notes" class="text-xs text-rose-500"></p>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
                            data-order-service-modal-close
                        >
                            {{ gettext('Cancelar') }}
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-strong disabled:cursor-not-allowed disabled:opacity-70"
                            data-order-service-submit
                        >
                            <span data-order-service-submit-label>{{ gettext('Guardar') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.dashboard-layout>

