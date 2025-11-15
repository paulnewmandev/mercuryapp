<x-layouts.dashboard-layout :meta="$meta ?? []">
    <x-ui.breadcrumb :title="gettext('Facturas')" :items="$breadcrumbItems" />

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
                'render' => 'actions_invoices',
                'sortable' => false,
                'align' => 'center',
            ],
        ];
    @endphp

    <section class="w-full space-y-6 pb-12" data-invoices-root>
        <x-ui.data-table
            tableId="invoices-table"
            apiUrl="{{ route('sales.invoices.data') }}"
            :columns="$columns"
            :perPageOptions="[5, 10, 20, 50, 100]"
            :perPageDefault="10"
            searchPlaceholder="{{ gettext('Buscar por número de factura o cliente...') }}"
            emptyTitle="{{ gettext('Sin facturas registradas') }}"
            emptyDescription="{{ gettext('Las facturas aparecerán aquí cuando se generen desde el POS o manualmente.') }}"
            :strings="[
                'loading' => gettext('Cargando facturas...'),
                'showing' => gettext('Mostrando %from% - %to% de %total% facturas'),
                'empty' => gettext('No se encontraron facturas con los filtros actuales'),
            ]"
        >
        </x-ui.data-table>
    </section>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const root = document.querySelector('[data-invoices-root]');
                if (!root) return;

                // Handle view action - redirect to show page
                root.addEventListener('click', (e) => {
                    const viewBtn = e.target.closest('[data-action="view"]');
                    if (viewBtn) {
                        const invoiceId = viewBtn.dataset.id;
                        if (invoiceId) {
                            window.location.href = `{{ route('sales.invoices.show', ':id') }}`.replace(':id', invoiceId);
                        }
                        return;
                    }

                    // Handle PDF action
                    const pdfBtn = e.target.closest('[data-action="pdf"]');
                    if (pdfBtn) {
                        const pdfUrl = pdfBtn.dataset.pdfUrl;
                        if (pdfUrl) {
                            window.open(pdfUrl, '_blank');
                        }
                        return;
                    }

                    // Handle authorize action
                    const authorizeBtn = e.target.closest('[data-action="authorize"]');
                    if (authorizeBtn) {
                        const invoiceId = authorizeBtn.dataset.id;
                        if (!invoiceId) return;

                        Swal.fire({
                            title: '¿Autorizar factura?',
                            text: '¿Estás seguro de que deseas autorizar esta factura en el SRI?',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#10b981',
                            cancelButtonColor: '#6b7280',
                            confirmButtonText: 'Sí, autorizar',
                            cancelButtonText: 'Cancelar',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Deshabilitar botón
                                authorizeBtn.disabled = true;
                                authorizeBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-sm"></i>';

                                // Llamar a API
                                fetch(`{{ route('sales.invoices.authorize', ':id') }}`.replace(':id', invoiceId), {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                                    },
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.status === 'success') {
                                        Swal.fire({
                                            title: '¡Autorizada!',
                                            text: 'La factura ha sido autorizada correctamente en el SRI.',
                                            icon: 'success',
                                            confirmButtonText: 'Aceptar'
                                        }).then(() => {
                                            window.location.reload();
                                        });
                                    } else {
                                        Swal.fire({
                                            title: 'Error',
                                            text: data.message || 'Error desconocido',
                                            icon: 'error',
                                            confirmButtonText: 'Aceptar'
                                        });
                                        authorizeBtn.disabled = false;
                                        authorizeBtn.innerHTML = '<i class="fa-solid fa-check-circle text-sm"></i>';
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    Swal.fire({
                                        title: 'Error',
                                        text: 'Error al autorizar la factura.',
                                        icon: 'error',
                                        confirmButtonText: 'Aceptar'
                                    });
                                    authorizeBtn.disabled = false;
                                    authorizeBtn.innerHTML = '<i class="fa-solid fa-check-circle text-sm"></i>';
                                });
                            }
                        });
                    }
                });
            });
        </script>
    @endpush
</x-layouts.dashboard-layout>

