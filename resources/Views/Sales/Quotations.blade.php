<x-layouts.dashboard-layout :meta="$meta ?? []">
    <x-ui.breadcrumb :title="gettext('Cotizaciones')" :items="$breadcrumbItems" />

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
                'render' => 'actions_quotation',
                'sortable' => false,
                'align' => 'center',
            ],
        ];
    @endphp

    <section class="w-full space-y-6 pb-12" data-quotations-root>
        <x-ui.data-table
            tableId="quotations-table"
            apiUrl="{{ route('sales.quotations.data') }}"
            :columns="$columns"
            :perPageOptions="[5, 10, 20, 50, 100]"
            :perPageDefault="10"
            searchPlaceholder="{{ gettext('Buscar por número de cotización o cliente...') }}"
            emptyTitle="{{ gettext('Sin cotizaciones registradas') }}"
            emptyDescription="{{ gettext('Crea una nueva cotización para comenzar.') }}"
            :strings="[
                'loading' => gettext('Cargando cotizaciones...'),
                'showing' => gettext('Mostrando %from% - %to% de %total% cotizaciones'),
                'empty' => gettext('No se encontraron cotizaciones con los filtros actuales'),
            ]"
        >
            <x-slot:headerActions>
                <a
                    href="{{ route('sales.quotations.create') }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-inverted shadow-sm transition hover:bg-primary-strong"
                >
                    <i class="fa-solid fa-plus text-sm"></i>
                    {{ gettext('Nueva') }}
                </a>
            </x-slot:headerActions>
        </x-ui.data-table>

        <!-- View Modal -->
        <div
            class="fixed inset-0 z-[70] hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
            data-quotation-modal="view"
            role="dialog"
            aria-modal="true"
        >
            <div class="mx-4 my-6 w-full max-w-4xl">
                <div class="relative max-h-[90vh] overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-slate-900">
                    <div class="relative border-b border-gray-200 px-6 py-4 text-center dark:border-gray-800">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white">{{ gettext('Ver Cotización') }}</h3>
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-slate-800"
                            data-quotation-modal-close
                            aria-label="{{ gettext('Cerrar') }}"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="max-h-[90vh] overflow-y-auto px-6 py-6" data-quotation-view-content>
                        <!-- Content loaded via JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const root = document.querySelector('[data-quotations-root]');
                if (!root) return;

                const viewModal = root.querySelector('[data-quotation-modal="view"]');
                const viewContent = root.querySelector('[data-quotation-view-content]');
                const closeBtn = root.querySelector('[data-quotation-modal-close]');

                // Handle view action
                root.addEventListener('click', (e) => {
                    const viewBtn = e.target.closest('[data-action="view"]');
                    if (!viewBtn) return;

                    const quotationId = viewBtn.dataset.id;
                    if (!quotationId) return;

                    // Redirect to show page instead of modal
                    window.location.href = `{{ route('sales.quotations.show', ':id') }}`.replace(':id', quotationId);
                });

                // Handle edit action
                root.addEventListener('click', (e) => {
                    const editBtn = e.target.closest('[data-action="edit"]');
                    if (!editBtn) return;

                    const quotationId = editBtn.dataset.id;
                    if (!quotationId) return;

                    // Redirect to edit page
                    window.location.href = `{{ route('sales.quotations.edit', ':id') }}`.replace(':id', quotationId);
                });

                // Close modal
                if (closeBtn) {
                    closeBtn.addEventListener('click', () => {
                        viewModal.classList.add('hidden');
                    });
                }

                // Close on outside click
                if (viewModal) {
                    viewModal.addEventListener('click', (e) => {
                        if (e.target === viewModal) {
                            viewModal.classList.add('hidden');
                        }
                    });
                }
            });
        </script>
    @endpush
</x-layouts.dashboard-layout>

