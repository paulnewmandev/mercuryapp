<x-layouts.dashboard-layout :meta="$meta ?? []">
    <x-ui.breadcrumb
        :title="gettext('Editar producto')"
        :items="[
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Inventario'), 'url' => route('inventory.product_transfers.index')],
            ['label' => gettext('Productos'), 'url' => route('inventory.products.index')],
            ['label' => gettext('Editar')],
        ]"
    />

    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css">
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
    @endpush

    @?, [
        'mode' => 'edit',
        'product' => $product,
        'productPayload' => $productPayload ?? null,
    ])
</x-layouts.dashboard-layout>

