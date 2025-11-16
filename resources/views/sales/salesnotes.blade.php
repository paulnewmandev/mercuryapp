<x-layouts.dashboard-layout :meta="$meta ?? []">
    <x-ui.breadcrumb :title="gettext('Notas de Venta')" :items="$breadcrumbItems" />

    @php
        $columns = [
            [
                'key' => 'invoice_number',
                'label' => gettext('Número'),
                'render' => 'text',
                'text_class' => 'text-sm font-semibold text-gray-900 dark:text-white',
            ],
            [
                'key' => 'customer_name',
                'label' => gettext('Cliente'),
                'render' => 'text',
            ],
            [
                'key' => 'issue_date_formatted',
                'label' => gettext('Fecha'),
                'render' => 'text',
            ],
            [
                'key' => 'total_amount_formatted',
                'label' => gettext('Total'),
                'render' => 'text',
                'text_class' => 'text-sm font-semibold text-gray-900 dark:text-white',
            ],
            [
                'key' => 'workflow_status_label',
                'label' => gettext('Estado'),
                'render' => 'text',
            ],
            [
                'key' => 'actions',
                'label' => gettext('Acciones'),
                'render' => 'actions_view_only',
                'sortable' => false,
                'align' => 'center',
            ],
        ];
    @endphp

    <section class="w-full space-y-6 pb-12" data-sales-notes-root>
        <x-ui.data-table
            tableId="sales-notes-table"
            apiUrl="{{ route('sales.sales_notes.data') }}"
            :columns="$columns"
            :perPageOptions="[5, 10, 20, 50, 100]"
            :perPageDefault="10"
            searchPlaceholder="{{ gettext('Buscar por número de nota o cliente...') }}"
            emptyTitle="{{ gettext('Sin notas de venta registradas') }}"
            emptyDescription="{{ gettext('Las notas de venta aparecerán aquí cuando se generen.') }}"
            :strings="[
                'loading' => gettext('Cargando notas de venta...'),
                'showing' => gettext('Mostrando %from% - %to% de %total% notas de venta'),
                'empty' => gettext('No se encontraron notas de venta con los filtros actuales'),
            ]"
        >
        </x-ui.data-table>
    </section>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const root = document.querySelector('[data-sales-notes-root]');
                if (!root) return;

                // Handle view action - redirect to show page
                root.addEventListener('click', (e) => {
                    const viewBtn = e.target.closest('[data-action="view"]');
                    if (!viewBtn) return;

                    const noteId = viewBtn.dataset.id;
                    if (!noteId) return;

                    window.location.href = `{{ route('sales.sales_notes.show', ':id') }}`.replace(':id', noteId);
                });
            });
        </script>
    @endpush
</x-layouts.dashboard-layout>

