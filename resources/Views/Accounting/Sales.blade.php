<x-layouts.dashboard-layout :meta="$meta ?? []">
    <x-ui.breadcrumb :title="gettext('Ventas')" :items="$breadcrumbItems" />

    @php
        $columns = [
            [
                'key' => 'invoice_number',
                'label' => gettext('Número'),
                'render' => 'text',
                'text_class' => 'text-sm font-semibold text-gray-900 dark:text-white',
            ],
            [
                'key' => 'document_type_label',
                'label' => gettext('Tipo'),
                'render' => 'text',
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

    <section class="w-full space-y-6 pb-12" data-accounting-sales-root>
        <x-ui.data-table
            tableId="accounting-sales-table"
            apiUrl="{{ route('accounting.sales.data') }}"
            :columns="$columns"
            :perPageOptions="[5, 10, 20, 50, 100]"
            :perPageDefault="10"
            searchPlaceholder="{{ gettext('Buscar por número, cliente...') }}"
            emptyTitle="{{ gettext('Sin ventas registradas') }}"
            emptyDescription="{{ gettext('Las facturas y notas de venta aparecerán aquí.') }}"
            :strings="[
                'loading' => gettext('Cargando ventas...'),
                'showing' => gettext('Mostrando %from% - %to% de %total% registros'),
                'empty' => gettext('No se encontraron registros con los filtros actuales'),
            ]"
        >
        </x-ui.data-table>
    </section>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const root = document.querySelector('[data-accounting-sales-root]');
                if (!root) return;

                // Handle view action - redirect to show page based on document type
                root.addEventListener('click', (e) => {
                    const viewBtn = e.target.closest('[data-action="view"]');
                    if (!viewBtn) return;

                    const invoiceId = viewBtn.dataset.id;
                    const documentType = viewBtn.dataset.documentType;
                    
                    if (!invoiceId) return;

                    // Redirect based on document type
                    if (documentType === 'FACTURA') {
                        window.location.href = `{{ route('sales.invoices.show', ':id') }}`.replace(':id', invoiceId);
                    } else if (documentType === 'NOTA_DE_VENTA') {
                        window.location.href = `{{ route('sales.sales_notes.show', ':id') }}`.replace(':id', invoiceId);
                    } else {
                        // Fallback: use the accounting sales show route which will redirect
                        window.location.href = `{{ route('accounting.sales.show', ':id') }}`.replace(':id', invoiceId);
                    }
                });
            });
        </script>
    @endpush
</x-layouts.dashboard-layout>

