@props([
    'tableId',
    'apiUrl',
    'columns' => [],
    'title' => null,
    'description' => null,
    'searchPlaceholder' => gettext('Buscar...'),
    'emptyTitle' => gettext('Sin registros'),
    'emptyDescription' => gettext('No encontramos resultados con los criterios actuales.'),
    'perPageOptions' => [5, 10, 20, 50, 100],
    'perPageDefault' => null,
    'strings' => [],
    'perPageSelectWidthClass' => 'w-[100px]',
    'searchFieldWidthClass' => 'w-[200px]',
    'cardClass' => 'w-full rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-slate-900/40',
    'headerActions' => null,
])

@php
    $columnsConfig = collect($columns)
        ->map(fn ($column) => [
            'key' => $column['key'] ?? '',
            'label' => $column['label'] ?? '',
            'sortable' => $column['sortable'] ?? true,
            'align' => $column['align'] ?? 'left',
            'render' => $column['render'] ?? null,
            'text_class' => $column['text_class'] ?? ($column['textClass'] ?? null),
            'badge_class' => $column['badge_class'] ?? ($column['badgeClass'] ?? null),
            'booleanField' => $column['booleanField'] ?? ($column['boolean_field'] ?? null),
            'trueLabel' => $column['trueLabel'] ?? ($column['true_label'] ?? null),
            'falseLabel' => $column['falseLabel'] ?? ($column['false_label'] ?? null),
            'trueClasses' => $column['trueClasses'] ?? ($column['true_classes'] ?? null),
            'falseClasses' => $column['falseClasses'] ?? ($column['false_classes'] ?? null),
        ])
        ->values()
        ->all();

    $strings = array_replace([
        'loading' => gettext('Cargando datos...'),
        'showing' => gettext('Mostrando %from% - %to% de %total% registros'),
        'empty' => gettext('Sin resultados que mostrar'),
    ], $strings);

    $initialPerPage = $perPageDefault ?? ($perPageOptions[0] ?? null);
@endphp

<div
    id="{{ $tableId }}"
    x-data="createDataTableComponent({
        tableId: '{{ $tableId }}',
        apiUrl: '{{ $apiUrl }}',
        columns: @js($columnsConfig),
        searchPlaceholder: '{{ $searchPlaceholder }}',
        perPageOptions: @js($perPageOptions),
        perPageDefault: {{ $initialPerPage !== null ? (int) $initialPerPage : 'null' }},
        strings: @js($strings),
    })"
    x-on:datatable-refresh.window="if(!$event.detail || !$event.detail.tableId || $event.detail.tableId === tableId){ const filters = $event.detail?.filters; fetchData(filters !== undefined ? (filters === null ? null : (filters || {})) : undefined); }"
    class="{{ $cardClass }} relative overflow-hidden"
>
    <div
        x-show="loading"
        x-cloak
        class="absolute inset-0 z-40 flex items-center justify-center bg-white/80 backdrop-blur-sm dark:bg-slate-900/70"
    >
        <div class="flex flex-col items-center gap-3 text-secondary">
            <span class="h-10 w-10 animate-spin rounded-full border-2 border-primary border-t-transparent"></span>
            <p class="text-sm font-medium" x-text="strings.loading"></p>
        </div>
    </div>

    @if ($title || $description || isset($headerActions))
        <div class="border-b border-gray-100 px-5 py-4 sm:px-6 dark:border-gray-800">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    @if ($title)
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>
                    @endif
                    @if ($description)
                        <p class="text-sm text-gray-500 dark:text-gray-300">{{ $description }}</p>
                    @endif
                </div>
                @isset($headerActions)
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        {{ $headerActions }}
                    </div>
                @endisset
            </div>
        </div>
    @endif

    <div class="space-y-4 px-5 pb-6 pt-5 sm:px-6">
        <div class="flex flex-wrap items-center justify-between gap-3 md:gap-4">
            <div
                class="relative {{ $perPageSelectWidthClass }}"
                data-select
                data-select-name="per_page"
                data-select-invalid="false"
            >
                <input
                    type="hidden"
                    name="per_page"
                    value="{{ $initialPerPage }}"
                    x-ref="perPageInput"
                    @change="changePerPage"
                >
                <button
                    type="button"
                    class="flex w-full items-center justify-between rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-surface dark:bg-slate-900 dark:text-white"
                    data-select-trigger
                    data-select-placeholder="{{ gettext('Selecciona una cantidad') }}"
                    aria-haspopup="listbox"
                    aria-expanded="false"
                >
                    <span data-select-value class="truncate">{{ $initialPerPage ?? gettext('Selecciona una cantidad') }}</span>
                    <i class="fa-solid fa-chevron-down text-xs text-secondary transition" data-select-icon></i>
                </button>
                <div
                    class="invisible absolute left-0 right-0 z-40 mt-2 w-full origin-top scale-95 transform rounded-xl border border-gray-200 bg-white shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-surface dark:bg-slate-900"
                    data-select-dropdown
                    role="listbox"
                    hidden
                >
                    <div class="max-h-60 overflow-y-auto py-2">
                        @foreach ($perPageOptions as $option)
                            <button
                                type="button"
                                class="group flex w-full items-center justify-between px-4 py-2 text-left text-sm text-heading transition hover:bg-primary-soft/20 data-[selected=true]:bg-primary-soft/30 data-[selected=true]:text-primary"
                                data-select-option
                                data-value="{{ $option }}"
                                data-label="{{ $option }}"
                                role="option"
                                data-selected="{{ $option === $initialPerPage ? 'true' : 'false' }}"
                            >
                                <span class="truncate">{{ $option }}</span>
                                <i class="fa-solid fa-circle-check text-primary opacity-0 transition group-data-[selected=true]:opacity-100" data-select-check></i>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="relative {{ $searchFieldWidthClass }} md:ml-auto md:text-right">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400 dark:text-gray-500">
                    <i class="fa-solid fa-magnifying-glass text-sm"></i>
                </span>
                <input
                    type="text"
                    x-model="search"
                    @input.debounce.400ms="handleSearch"
                    :placeholder="searchPlaceholder"
                    class="w-full rounded-xl border border-gray-300 bg-white py-2 pl-11 pr-4 text-sm text-gray-800 placeholder:text-gray-400 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30 dark:border-gray-700 dark:bg-slate-800 dark:text-white"
                >
                <button
                    type="button"
                    x-show="search.length > 0"
                    @click="clearSearch"
                    class="absolute inset-y-0 right-3 flex items-center text-gray-400 transition hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <div class="space-y-3 md:hidden">
            <template x-for="(row, rowIndex) in rows" :key="row.id ?? rowIndex">
                <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-primary/40 dark:border-gray-800 dark:bg-slate-900">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-400 dark:text-gray-500" x-text="primaryColumn?.label ?? ''"></p>
                            <div class="text-base font-semibold text-gray-900 dark:text-white" x-html="primaryColumn ? renderCell(row, primaryColumn) : ''"></div>
                        </div>
                        <div class="shrink-0" x-show="actionColumn" x-html="actionColumn ? renderCell(row, actionColumn) : ''"></div>
                    </div>
                    <dl class="mt-4 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                        <template x-for="column in detailColumns" :key="column.key">
                            <div class="flex items-start justify-between gap-2">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500" x-text="column.label"></dt>
                                <dd class="max-w-[60%] text-right" x-html="renderCell(row, column)"></dd>
                            </div>
                        </template>
                    </dl>
                </article>
            </template>

            <template x-if="!loading && rows.length === 0">
                <div class="flex flex-col items-center justify-center gap-2 rounded-2xl border border-dashed border-gray-200 px-6 py-12 text-center dark:border-gray-700">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-soft text-primary">
                        <i class="fa-regular fa-folder-open text-xl"></i>
                    </div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $emptyTitle }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $emptyDescription }}</p>
                </div>
            </template>
        </div>

        <div class="relative hidden overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800 md:block">
            <div class="overflow-x-auto overflow-y-visible">
                <table class="min-w-full divide-y divide-gray-200 bg-white text-left text-sm text-gray-700 dark:divide-gray-800 dark:bg-slate-900 dark:text-gray-200">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-slate-800 dark:text-gray-400">
                        <tr>
                            <template x-for="column in columns" :key="column.key">
                                <th
                                    scope="col"
                                    class="border-x border-gray-200 px-5 py-3 font-semibold first:border-l-0 last:border-r-0 dark:border-gray-700"
                                    :class="column.align === 'center' ? 'text-center' : column.align === 'right' ? 'text-right' : 'text-left'"
                                >
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500 transition hover:text-primary dark:text-gray-400 dark:hover:text-primary"
                                        :class="column.sortable === false ? 'pointer-events-none opacity-60' : ''"
                                        @click="column.sortable === false ? null : toggleSort(column.key)"
                                    >
                                        <span x-text="column.label"></span>
                                        <span x-show="column.sortable !== false" class="flex items-center text-xs">
                                            <i class="fa-solid fa-sort" :class="sortColumn === column.key ? 'text-primary' : 'text-gray-400 dark:text-gray-500'"></i>
                                        </span>
                                    </button>
                                </th>
                            </template>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <template x-if="!loading && rows.length === 0">
                            <tr>
                                <td :colspan="columns.length" class="px-8 py-14 text-center text-sm text-gray-500 dark:text-gray-400">
                                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-primary-soft text-primary">
                                        <i class="fa-regular fa-folder-open text-xl"></i>
                                    </div>
                                    <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $emptyTitle }}</p>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $emptyDescription }}</p>
                                </td>
                            </tr>
                        </template>

                        <template x-for="(row, rowIndex) in rows" :key="row.id ?? rowIndex">
                            <tr class="transition hover:bg-primary/10 dark:hover:bg-slate-800">
                                <template x-for="column in columns" :key="column.key">
                                    <td
                                        class="border-x border-gray-200 px-5 py-4 text-sm first:border-l-0 last:border-r-0 dark:border-gray-700"
                                        :class="column.align === 'center' ? 'text-center' : column.align === 'right' ? 'text-right' : 'text-left'"
                                    >
                                        <span class="block" x-html="renderCell(row, column)"></span>
                                    </td>
                                </template>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex flex-col items-center justify-center gap-3 border-t border-gray-100 pt-4 text-sm text-gray-600 dark:border-gray-800 dark:text-gray-300">
            <nav class="flex items-center justify-center gap-2">
                <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-600 transition hover:text-primary disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-700 dark:bg-slate-800 dark:text-gray-300"
                    :disabled="currentPage === 1"
                    @click="goToPage(currentPage - 1)"
                >
                    <span class="sr-only">{{ gettext('Página anterior') }}</span>
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M12.78 4.22a.75.75 0 010 1.06L8.06 10l4.72 4.72a.75.75 0 11-1.06 1.06l-5.25-5.25a.75.75 0 010-1.06l5.25-5.25a.75.75 0 011.06 0z" clip-rule="evenodd" />
                    </svg>
                </button>
                <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-600 transition hover:text-primary disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-700 dark:bg-slate-800 dark:text-gray-300"
                    :disabled="currentPage === totalPages || totalPages === 0"
                    @click="goToPage(currentPage + 1)"
                >
                    <span class="sr-only">{{ gettext('Página siguiente') }}</span>
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M7.22 4.22a.75.75 0 011.06 0l5.25 5.25a.75.75 0 010 1.06l-5.25 5.25a.75.75 0 11-1.06-1.06L11.94 10 7.22 5.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
            </nav>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            window.createDataTableComponent = window.createDataTableComponent ?? function (config) {
                const defaultRenderers = {
                    status(row, column) {
                        const raw = row.status ?? '';
                        const isActive = raw === 'A';
                        const isTrash = raw === 'T';
                        const checkedAttribute = isActive ? 'checked="checked"' : '';
                        const trackClass = isTrash
                            ? 'bg-gray-100 dark:bg-gray-800'
                            : isActive
                                ? 'bg-primary dark:bg-primary'
                                : 'bg-gray-200 dark:bg-white/10';
                        const thumbInitial = isActive ? 'translate-x-full' : 'translate-x-0';
                        const disabledAttr = isTrash ? 'disabled' : '';
                        const ariaChecked = isActive ? 'true' : 'false';
                        return `
                            <label class="flex cursor-pointer select-none items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-300" data-status-container>
                                <span class="sr-only">Cambiar estado</span>
                                <div class="relative">
                                    <input type="checkbox" class="peer sr-only" data-status-toggle data-id="${row.id}" ${checkedAttribute} ${disabledAttr} aria-checked="${ariaChecked}">
                                    <div class="block h-6 w-11 rounded-full transition duration-300 ${trackClass} peer-focus:ring-2 peer-focus:ring-primary/40"></div>
                                    <div class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition duration-300 ease-linear transform ${thumbInitial} peer-checked:translate-x-full"></div>
                                </div>
                            </label>
                        `;
                    },
                    actions(row) {
                        const displayName = row.display_name ?? row.name ?? row.bank_name ?? row.title ?? row.code ?? row.alias ?? '';
                        const safeName = String(displayName).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                        const rowId = String(row.id ?? '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                        const hasBarcode = row.has_barcode ?? false;
                        const barcodeUrl = (hasBarcode && row.barcode_label_url) ? row.barcode_label_url : '';
                        const barcodeButton = barcodeUrl
                            ? `
                                <button
                                    type="button"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 text-gray-600 transition hover:border-primary hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/30 dark:border-gray-700 dark:text-gray-300 dark:hover:border-primary"
                                    data-action="barcode"
                                    data-id="${rowId}"
                                    data-url="${barcodeUrl.replace(/"/g, '&quot;').replace(/'/g, '&#39;')}"
                                >
                                    <i class="fa-solid fa-barcode text-sm"></i>
                                </button>
                            `
                            : '';
                        const labelUrl = row.label_url ?? '';
                        const labelButton = labelUrl
                            ? `
                                <a
                                    href="${labelUrl.replace(/"/g, '&quot;').replace(/'/g, '&#39;')}"
                                    target="_blank"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 text-gray-600 transition hover:border-primary hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/30 dark:border-gray-700 dark:text-gray-300 dark:hover:border-primary"
                                    title="Etiqueta"
                                >
                                    <i class="fa-solid fa-barcode text-sm"></i>
                                </a>
                            `
                            : '';
                        const ticketUrl = row.ticket_url ?? '';
                        const ticketButton = ticketUrl
                            ? `
                                <a
                                    href="${ticketUrl.replace(/"/g, '&quot;').replace(/'/g, '&#39;')}"
                                    target="_blank"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 text-gray-600 transition hover:border-primary hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/30 dark:border-gray-700 dark:text-gray-300 dark:hover:border-primary"
                                    title="Ticket"
                                >
                                    <i class="fa-solid fa-receipt text-sm"></i>
                                </a>
                            `
                            : '';
                        return `
                             <div class="flex items-center justify-center gap-2">
                                 ${barcodeButton}
                                 ${labelButton}
                                 ${ticketButton}
                                 <button
                                     type="button"
                                     class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 text-gray-600 transition hover:border-primary hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/30 dark:border-gray-700 dark:text-gray-300 dark:hover:border-primary"
                                    data-action="view"
                                    data-row-action="view"
                                    data-id="${rowId}"
                                    data-row-id="${rowId}"
                                 >
                                     <i class="fa-regular fa-eye text-sm"></i>
                                 </button>
                                 <button
                                     type="button"
                                     class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 text-gray-600 transition hover:border-primary hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/30 dark:border-gray-700 dark:text-gray-300 dark:hover:border-primary"
                                    data-action="edit"
                                    data-row-action="edit"
                                    data-id="${rowId}"
                                    data-row-id="${rowId}"
                                 >
                                     <i class="fa-regular fa-pen-to-square text-sm"></i>
                                 </button>
                                 <button
                                     type="button"
                                     class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 text-gray-600 transition hover:border-rose-500 hover:text-rose-600 focus:outline-none focus:ring-2 focus:ring-rose-200 dark:border-gray-700 dark:text-gray-300 dark:hover:border-rose-400"
                                    data-action="delete"
                                    data-row-action="delete"
                                    data-id="${rowId}"
                                    data-row-id="${rowId}"
                                    data-name="${safeName}"
                                    data-row-name="${safeName}"
                                 >
                                     <i class="fa-regular fa-trash-can text-sm"></i>
                                 </button>
                             </div>
                         `;
                    },
                    actions_view_only(row) {
                        const rowId = String(row.id ?? '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                        const documentType = String(row.document_type ?? '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                        return `
                             <div class="flex items-center justify-center gap-2">
                                 <button
                                     type="button"
                                     class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 text-gray-600 transition hover:border-primary hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/30 dark:border-gray-700 dark:text-gray-300 dark:hover:border-primary"
                                    data-action="view"
                                    data-row-action="view"
                                    data-id="${rowId}"
                                    data-row-id="${rowId}"
                                    data-document-type="${documentType}"
                                 >
                                     <i class="fa-regular fa-eye text-sm"></i>
                                 </button>
                             </div>
                         `;
                    },
                    actions_invoices(row) {
                        const rowId = String(row.id ?? '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                        const canAuthorize = row.can_authorize ?? false;
                        const sriStatus = row.sri_status ?? '';
                        const authorizationNumber = row.authorization_number ?? '';
                        const pdfUrl = `${window.location.origin}/sales/invoices/${rowId}/pdf`;
                        
                        const viewButton = `
                            <button
                                type="button"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 text-gray-600 transition hover:border-primary hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/30 dark:border-gray-700 dark:text-gray-300 dark:hover:border-primary"
                                data-action="view"
                                data-row-action="view"
                                data-id="${rowId}"
                                data-row-id="${rowId}"
                                title="Ver"
                            >
                                <i class="fa-regular fa-eye text-sm"></i>
                            </button>
                        `;
                        
                        const pdfButton = `
                            <button
                                type="button"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-500 text-red-600 transition hover:border-red-600 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500/30 dark:border-red-400 dark:text-red-400"
                                data-action="pdf"
                                data-row-action="pdf"
                                data-id="${rowId}"
                                data-row-id="${rowId}"
                                data-pdf-url="${pdfUrl}"
                                title="Ver PDF"
                            >
                                <i class="fa-solid fa-file-pdf text-sm"></i>
                            </button>
                        `;
                        
                        const authorizeButton = canAuthorize
                            ? `
                                <button
                                    type="button"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-green-500 text-green-600 transition hover:border-green-600 hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-500/30 dark:border-green-400 dark:text-green-400"
                                    data-action="authorize"
                                    data-row-action="authorize"
                                    data-id="${rowId}"
                                    data-row-id="${rowId}"
                                    title="Autorizar en SRI"
                                >
                                    <i class="fa-solid fa-check-circle text-sm"></i>
                                </button>
                            `
                            : '';
                        
                        const authorizedBadge = (sriStatus === 'authorized' && authorizationNumber)
                            ? `
                                <span
                                    class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-400"
                                    title="Autorizada: ${authorizationNumber}"
                                >
                                    <i class="fa-solid fa-check-circle text-xs"></i>
                                    Autorizada
                                </span>
                            `
                            : '';
                        
                        return `
                            <div class="flex items-center justify-center gap-2">
                                ${viewButton}
                                ${pdfButton}
                                ${authorizeButton}
                                ${authorizedBadge}
                            </div>
                        `;
                    },
                    actions_quotation(row) {
                        const rowId = String(row.id ?? '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                        const pdfUrl = `${window.location.origin}/sales/quotations/${rowId}/pdf`;
                        return `
                             <div class="flex items-center justify-center gap-2">
                                 <button
                                     type="button"
                                     class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 text-gray-600 transition hover:border-primary hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/30 dark:border-gray-700 dark:text-gray-300 dark:hover:border-primary"
                                    data-action="view"
                                    data-row-action="view"
                                    data-id="${rowId}"
                                    data-row-id="${rowId}"
                                 >
                                     <i class="fa-regular fa-eye text-sm"></i>
                                 </button>
                                 <button
                                     type="button"
                                     class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 text-gray-600 transition hover:border-primary hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary/30 dark:border-gray-700 dark:text-gray-300 dark:hover:border-primary"
                                    data-action="edit"
                                    data-row-action="edit"
                                    data-id="${rowId}"
                                    data-row-id="${rowId}"
                                 >
                                     <i class="fa-regular fa-pen-to-square text-sm"></i>
                                 </button>
                                 <a
                                     href="${pdfUrl}"
                                     target="_blank"
                                     class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 text-gray-600 transition hover:border-rose-500 hover:text-rose-600 focus:outline-none focus:ring-2 focus:ring-rose-200 dark:border-gray-700 dark:text-gray-300 dark:hover:border-rose-400"
                                    title="PDF"
                                 >
                                     <i class="fa-solid fa-file-pdf text-sm"></i>
                                 </a>
                             </div>
                         `;
                    },
                    status_badge(row, column) {
                        const booleanField = column.booleanField ?? null;
                        
                        // Si hay un booleanField, generar un switch interactivo
                        if (booleanField) {
                            const isTrue = Boolean(row[booleanField] ?? false);
                            const checkedAttribute = isTrue ? 'checked="checked"' : '';
                            const trackClass = isTrue
                                ? 'bg-primary dark:bg-primary'
                                : 'bg-gray-200 dark:bg-white/10';
                            const thumbInitial = isTrue ? 'translate-x-full' : 'translate-x-0';
                            const ariaChecked = isTrue ? 'true' : 'false';
                            
                            return `
                                <label class="flex cursor-pointer select-none items-center justify-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-300" data-status-container>
                                    <span class="sr-only">Cambiar estado</span>
                                    <div class="relative">
                                        <input type="checkbox" class="peer sr-only" data-status-toggle data-id="${row.id}" ${checkedAttribute} aria-checked="${ariaChecked}">
                                        <div class="block h-6 w-11 rounded-full transition duration-300 ${trackClass} peer-focus:ring-2 peer-focus:ring-primary/40"></div>
                                        <div class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition duration-300 ease-linear transform ${thumbInitial} peer-checked:translate-x-full"></div>
                                    </div>
                                </label>
                            `;
                        }
                        
                        // Si no hay booleanField, mostrar badge estático
                        const isTrue = Boolean(row[column.key] ?? false);
                        const trueLabel = column.trueLabel ?? row[column.key] ?? gettext('Activo');
                        const falseLabel = column.falseLabel ?? row[column.key] ?? gettext('Inactivo');
                        const label = row[column.key] ?? (isTrue ? trueLabel : falseLabel);

                        const trueClasses = column.trueClasses ?? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300';
                        const falseClasses = column.falseClasses ?? 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-200';

                        const badgeClass = isTrue ? trueClasses : falseClasses;
                        const text = isTrue ? (column.trueLabel ?? label) : (column.falseLabel ?? label);

                        return `
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ${badgeClass}">
                                ${text}
                            </span>
                        `;
                    },
                    text(row, column) {
                        const value = row[column.key] ?? '';
                        const textClass = column.text_class ?? column.textClass ?? 'text-sm text-heading';
                        return `<span class="${textClass}">${value}</span>`;
                    },
                    badge(row, column) {
                        const value = row[column.key] ?? '';
                        const badgeClass = column.badge_class ?? column.badgeClass ?? 'bg-gray-100 text-gray-700';
                        return `
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ${badgeClass}">
                                ${value}
                            </span>
                        `;
                    },
                };

                return {
                    tableId: config.tableId,
                    apiUrl: config.apiUrl,
                    columns: config.columns ?? [],
                    perPageOptions: config.perPageOptions ?? [10, 25, 50],
                    searchPlaceholder: config.searchPlaceholder ?? '',
                    strings: Object.assign({
                        loading: 'Loading...',
                        showing: 'Showing %from% - %to% of %total% entries',
                        empty: 'No records found',
                    }, config.strings ?? {}),
                    rows: [],
                    search: '',
                    sortColumn: config.columns?.[0]?.key ?? 'id',
                    sortDirection: 'asc',
                    perPage: config.perPageDefault ?? config.perPageOptions?.[0] ?? 10,
                    currentPage: 1,
                    totalPages: 0,
                    totalItems: 0,
                    from: 0,
                    to: 0,
                    loading: false,
                    primaryColumn: null,
                    detailColumns: [],
                    actionColumn: null,
                    externalFilters: {},

                    init() {
                        this.primaryColumn = this.columns.find((column) => column.key !== 'actions' && column.render !== 'actions') ?? this.columns[0] ?? null;
                        this.actionColumn = this.columns.find((column) => column.key === 'actions' || column.render === 'actions') ?? null;
                        this.detailColumns = this.columns.filter((column) => {
                            const primaryKey = this.primaryColumn?.key ?? null;
                            const actionKey = this.actionColumn?.key ?? null;
                            return column.key !== primaryKey && column.key !== actionKey;
                        });
                        this.syncPerPageSelect();
                        this.fetchData(undefined);
                    },

                    async fetchData(newFilters = undefined) {
                        // Si se pasan filtros nuevos, actualizar los filtros externos
                        if (newFilters !== undefined) {
                            if (newFilters === null) {
                                // Si es null, limpiar los filtros
                                this.externalFilters = {};
                            } else if (typeof newFilters === 'object' && newFilters !== null) {
                                // Si es un objeto, actualizar los filtros
                                this.externalFilters = { ...newFilters };
                            }
                        }
                        // Si newFilters es undefined, mantener los filtros actuales
                        
                        if (!this.apiUrl) {
                            console.warn('[DataTable] No API URL configurada para', this.tableId);
                            return;
                        }
                        this.loading = true;
                        try {
                            const params = new URLSearchParams({
                                search: this.search || '',
                                sort_by: this.sortColumn || '',
                                sort_direction: this.sortDirection || 'asc',
                                page: this.currentPage,
                                per_page: this.perPage,
                            });

                            // Agregar filtros externos si existen
                            if (this.externalFilters && typeof this.externalFilters === 'object') {
                                Object.entries(this.externalFilters).forEach(([key, value]) => {
                                    if (value !== null && value !== undefined && value !== '') {
                                        params.append(key, value);
                                    }
                                });
                            }

                            const separator = this.apiUrl.includes('?') ? '&' : '?';
                            const requestUrl = `${this.apiUrl}${separator}${params.toString()}`;
                            console.debug('[DataTable] Fetch start', {
                                tableId: this.tableId,
                                url: requestUrl,
                                params: Object.fromEntries(params.entries()),
                            });

                            const response = await fetch(requestUrl, {
                                headers: {
                                    Accept: 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });

                            console.debug('[DataTable] Response status', {
                                tableId: this.tableId,
                                status: response.status,
                                ok: response.ok,
                            });

                            if (!response.ok) {
                                const text = await response.text();
                                console.error('[DataTable] Respuesta no OK', {
                                    tableId: this.tableId,
                                    status: response.status,
                                    body: text,
                                });
                                throw new Error(`Request failed with status ${response.status}`);
                            }

                            const payload = await response.json();
                            console.debug('[DataTable] Payload recibido', {
                                tableId: this.tableId,
                                payload,
                            });

                            if (Array.isArray(payload.data)) {
                                this.rows = payload.data ?? [];
                                this.currentPage = payload.current_page ?? 1;
                                this.totalPages = payload.last_page ?? 0;
                                this.totalItems = payload.total ?? 0;
                                this.from = payload.from ?? 0;
                                this.to = payload.to ?? 0;
                            } else {
                                const nestedData = payload?.data ?? {};
                                const items = Array.isArray(nestedData.items) ? nestedData.items : [];
                                const pagination = nestedData.pagination ?? {};

                                this.rows = items;
                                this.currentPage = pagination.current_page ?? payload.current_page ?? 1;
                                this.totalPages = pagination.last_page ?? payload.last_page ?? 0;
                                this.totalItems = pagination.total ?? payload.total ?? 0;
                                this.from = pagination.from ?? payload.from ?? 0;
                                this.to = pagination.to ?? payload.to ?? 0;
                                if (pagination.per_page) {
                                    this.perPage = Number(pagination.per_page);
                                }
                            }

                            this.syncPerPageSelect();
                        } catch (error) {
                            console.error('DataTable fetch error:', error);
                            this.rows = [];
                            this.totalPages = 0;
                            this.totalItems = 0;
                            this.from = 0;
                            this.to = 0;
                        } finally {
                            this.loading = false;
                        }
                    },

                    handleSearch() {
                        this.currentPage = 1;
                        this.fetchData(undefined);
                    },

                    clearSearch() {
                        this.search = '';
                        this.handleSearch();
                    },

                    changePerPage(event) {
                        const value = Number(event.target.value);
                        this.perPage = Number.isFinite(value) ? value : this.perPage;
                        this.currentPage = 1;
                        this.syncPerPageSelect();
                        this.fetchData(undefined);
                    },

                    toggleSort(columnKey) {
                        if (this.sortColumn === columnKey) {
                            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                        } else {
                            this.sortColumn = columnKey;
                            this.sortDirection = 'asc';
                        }
                        this.currentPage = 1;
                        this.fetchData(undefined);
                    },

                    goToPage(page) {
                        if (page < 1 || page > this.totalPages || page === this.currentPage) {
                            return;
                        }
                        this.currentPage = page;
                        this.fetchData(undefined);
                    },

                    get pageNumbers() {
                        if (this.totalPages <= 1) {
                            return [1];
                        }
                        const pages = [];
                        const maxVisible = 5;
                        let start = Math.max(1, this.currentPage - Math.floor(maxVisible / 2));
                        let end = Math.min(this.totalPages, start + maxVisible - 1);

                        if (end - start + 1 < maxVisible) {
                            start = Math.max(1, end - maxVisible + 1);
                        }

                        for (let page = start; page <= end; page++) {
                            pages.push(page);
                        }
                        return pages;
                    },

                    get paginationLabel() {
                        if (this.totalItems === 0) {
                            return this.strings.empty;
                        }
                        return (this.strings.showing ?? '')
                            .replace('%from%', this.from)
                            .replace('%to%', this.to)
                            .replace('%total%', this.totalItems);
                    },

                    renderCell(row, column) {
                        if (!column) {
                            return '';
                        }
                        const rendererKey = column.render ?? (column.key === 'actions' ? 'actions' : 'text');
                        const renderer = defaultRenderers[rendererKey] ?? defaultRenderers.text;
                        return renderer(row, column);
                    },

                    syncPerPageSelect() {
                        if (!this.tableId) {
                            return;
                        }
                        const root = document.getElementById(this.tableId);
                        if (!root) {
                            return;
                        }
                        const select = root.querySelector('[data-select-name="per_page"]');
                        if (!select) {
                            return;
                        }
                        const hiddenInput = select.querySelector('input[type="hidden"]');
                        const valueLabel = select.querySelector('[data-select-value]');
                        const trigger = select.querySelector('[data-select-trigger]');
                        const options = Array.from(select.querySelectorAll('[data-select-option]'));
                        const placeholder = trigger?.dataset.selectPlaceholder ?? '';
                        const normalizedValue = this.perPage != null ? String(this.perPage) : '';

                        if (hiddenInput) {
                            hiddenInput.value = normalizedValue;
                        }

                        let computedLabel = '';
                        options.forEach((option) => {
                            const optionValue = option.dataset.value ?? option.textContent.trim();
                            const isSelected = optionValue === normalizedValue || Number(optionValue) === Number(normalizedValue);
                            option.dataset.selected = isSelected ? 'true' : 'false';
                            if (isSelected) {
                                computedLabel = option.dataset.label ?? option.textContent.trim();
                            }
                        });

                        if (valueLabel) {
                            valueLabel.textContent = computedLabel || placeholder;
                        }

                        if (trigger) {
                            trigger.classList.toggle('is-empty', normalizedValue === '');
                        }
                    },
                };
            };
        </script>
    @endpush
@endonce
