<x-layouts.dashboard-layout :meta="$meta ?? []">
    <div class="w-full p-4 pb-20 md:p-6 md:pb-6">
        <!-- Breadcrumb Start -->
        <div x-data="{ pageName: `{{ gettext('Cotización') }}` }">
            <div class="flex flex-wrap items-center justify-between gap-3 pb-6">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName">{{ gettext('Cotización') }}</h2>
                <nav>
                    <ol class="flex items-center gap-1.5">
                        <li>
                            <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="{{ route('dashboard') }}">
                                {{ gettext('Panel principal') }}
                                <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                            </a>
                        </li>
                        <li>
                            <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="{{ route('sales.quotations.index') }}">
                                {{ gettext('Cotizaciones') }}
                                <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                            </a>
                        </li>
                        <li class="text-sm text-gray-800 dark:text-white/90" x-text="pageName">{{ gettext('Cotización') }}</li>
                    </ol>
                </nav>
            </div>
        </div>
        <!-- Breadcrumb End -->

        <div>
            <!-- Invoice Mainbox Start -->
            <div class="w-full rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                    <h3 class="text-xl font-medium text-gray-800 dark:text-white/90">
                        {{ gettext('Cotización') }}
                    </h3>
                    <h4 class="text-base font-medium text-gray-700 dark:text-gray-400">
                        {{ gettext('ID') }} : #{{ $quotation->invoice_number }}
                    </h4>
                </div>
                <div class="p-5 xl:p-8">
                    <div class="mb-9 flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                {{ gettext('De') }}
                            </span>
                            <h5 class="mb-2 text-base font-semibold text-gray-800 dark:text-white/90">
                                {{ $quotation->company->name ?? $quotation->company->legal_name }}
                            </h5>
                            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                                @if($quotation->company->address)
                                    {{ $quotation->company->address }}
                                @endif
                                @if($quotation->branch && $quotation->branch->address)
                                    <br>{{ $quotation->branch->address }}
                                @endif
                            </p>
                            <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                {{ gettext('Emitida el') }}:
                            </span>
                            <span class="block text-sm text-gray-500 dark:text-gray-400">
                                {{ $quotation->issue_date->translatedFormat('d F, Y') }}
                            </span>
                        </div>
                        <div class="h-px w-full bg-gray-200 sm:h-[158px] sm:w-px dark:bg-gray-800"></div>
                        <div class="sm:text-right">
                            <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                {{ gettext('Para') }}
                            </span>
                            <h5 class="mb-2 text-base font-semibold text-gray-800 dark:text-white/90">
                                {{ $quotation->customer->display_name }}
                            </h5>
                            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                                @if($quotation->customer->address)
                                    {{ $quotation->customer->address }}
                                @endif
                                @if($quotation->customer->document_number)
                                    <br>{{ gettext('Documento') }}: {{ $quotation->customer->document_number }}
                                @endif
                            </p>
                            <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                {{ gettext('Vence el') }}:
                            </span>
                            <span class="block text-sm text-gray-500 dark:text-gray-400">
                                {{ $quotation->due_date ? $quotation->due_date->translatedFormat('d F, Y') : '-' }}
                            </span>
                        </div>
                    </div>

                    <!-- Invoice Table Start -->
                    <div>
                        <div class="overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-800">
                            <table class="min-w-full text-left text-gray-700 dark:text-gray-400">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr class="border-b border-gray-100 dark:border-gray-800">
                                        <th class="px-5 py-3 text-sm font-medium whitespace-nowrap text-gray-700 dark:text-gray-400">
                                            {{ gettext('N.º') }}
                                        </th>
                                        <th class="px-5 py-3 text-xs font-medium whitespace-nowrap text-gray-500 dark:text-gray-400">
                                            {{ gettext('Productos') }}
                                        </th>
                                        <th class="px-5 py-3 text-center text-sm font-medium whitespace-nowrap text-gray-700 dark:text-gray-400">
                                            {{ gettext('Cantidad') }}
                                        </th>
                                        <th class="px-5 py-3 text-center text-sm font-medium whitespace-nowrap text-gray-700 dark:text-gray-400">
                                            {{ gettext('Precio Unit.') }}
                                        </th>
                                        <th class="px-5 py-3 text-center text-sm font-medium whitespace-nowrap text-gray-700 dark:text-gray-400">
                                            {{ gettext('Descuento') }}
                                        </th>
                                        <th class="px-5 py-3 text-right text-sm font-medium whitespace-nowrap text-gray-700 dark:text-gray-400">
                                            {{ gettext('Total') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach($quotation->items as $index => $item)
                                        @php
                                            $name = '';
                                            if ($item->item_type === 'product') {
                                                $name = $products->get($item->item_id)?->name ?? 'Producto';
                                            } elseif ($item->item_type === 'service') {
                                                $name = $services->get($item->item_id)?->name ?? 'Servicio';
                                            }
                                        @endphp
                                        <tr>
                                            <td class="px-5 py-3 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                                {{ $index + 1 }}
                                            </td>
                                            <td class="px-5 py-3 text-sm font-medium whitespace-nowrap text-gray-800 dark:text-white/90">
                                                {{ $name }}
                                            </td>
                                            <td class="px-5 py-3 text-center text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                                {{ $item->quantity }}
                                            </td>
                                            <td class="px-5 py-3 text-center text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                                USD {{ number_format($item->unit_price, 2, '.', ',') }}
                                            </td>
                                            <td class="px-5 py-3 text-center text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                                0%
                                            </td>
                                            <td class="px-5 py-3 text-right text-sm text-gray-500 dark:text-gray-400">
                                                USD {{ number_format($item->subtotal, 2, '.', ',') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- Invoice Table End -->

                    <div class="my-6 flex justify-end border-b border-gray-100 pb-6 text-right dark:border-gray-800">
                        <div class="w-[220px]">
                            <p class="mb-4 text-left text-sm font-medium text-gray-800 dark:text-white/90">
                                {{ gettext('Resumen de orden') }}
                            </p>
                            <ul class="space-y-2">
                                <li class="flex justify-between gap-5">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ gettext('Sub Total') }}
                                    </span>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-400">USD {{ number_format($quotation->subtotal, 2, '.', ',') }}</span>
                                </li>
                                <li class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ gettext('IVA (15%)') }}:
                                    </span>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-400">USD {{ number_format($quotation->tax_amount, 2, '.', ',') }}</span>
                                </li>
                                <li class="flex items-center justify-between">
                                    <span class="font-medium text-gray-700 dark:text-gray-400">
                                        {{ gettext('Total') }}
                                    </span>
                                    <span class="text-lg font-semibold text-gray-800 dark:text-white/90">USD {{ number_format($quotation->total_amount, 2, '.', ',') }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    @if($quotation->notes)
                        <div class="mb-6">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">{{ gettext('Notas') }}</p>
                            <p class="text-sm text-gray-900 dark:text-white">{{ $quotation->notes }}</p>
                        </div>
                    @endif

                    <div class="flex items-center justify-end gap-3">
                        <a
                            href="{{ route('sales.quotations.index') }}"
                            class="shadow-theme-xs flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200"
                        >
                            {{ gettext('Volver') }}
                        </a>
                        <a
                            href="{{ route('sales.quotations.edit', $quotation->id) }}"
                            class="shadow-theme-xs flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200"
                        >
                            {{ gettext('Editar') }}
                        </a>
                        <a
                            href="{{ route('sales.quotations.pdf', $quotation->id) }}"
                            target="_blank"
                            class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 flex items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white"
                        >
                            <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M6.99578 4.08398C6.58156 4.08398 6.24578 4.41977 6.24578 4.83398V6.36733H13.7542V5.62451C13.7542 5.42154 13.672 5.22724 13.5262 5.08598L12.7107 4.29545C12.5707 4.15983 12.3835 4.08398 12.1887 4.08398H6.99578ZM15.2542 6.36902V5.62451C15.2542 5.01561 15.0074 4.43271 14.5702 4.00891L13.7547 3.21839C13.3349 2.81151 12.7733 2.58398 12.1887 2.58398H6.99578C5.75314 2.58398 4.74578 3.59134 4.74578 4.83398V6.36902C3.54391 6.41522 2.58374 7.40415 2.58374 8.61733V11.3827C2.58374 12.5959 3.54382 13.5848 4.74561 13.631V15.1665C4.74561 16.4091 5.75297 17.4165 6.99561 17.4165H13.0041C14.2467 17.4165 15.2541 16.4091 15.2541 15.1665V13.6311C16.456 13.585 17.4163 12.596 17.4163 11.3827V8.61733C17.4163 7.40414 16.4561 6.41521 15.2542 6.36902ZM4.74561 11.6217V12.1276C4.37292 12.084 4.08374 11.7671 4.08374 11.3827V8.61733C4.08374 8.20312 4.41953 7.86733 4.83374 7.86733H15.1663C15.5805 7.86733 15.9163 8.20312 15.9163 8.61733V11.3827C15.9163 11.7673 15.6269 12.0842 15.2541 12.1277V11.6217C15.2541 11.2075 14.9183 10.8717 14.5041 10.8717H5.49561C5.08139 10.8717 4.74561 11.2075 4.74561 11.6217ZM6.24561 12.3717V15.1665C6.24561 15.5807 6.58139 15.9165 6.99561 15.9165H13.0041C13.4183 15.9165 13.7541 15.5807 13.7541 15.1665V12.3717H6.24561Z" fill=""></path>
                            </svg>
                            {{ gettext('Imprimir') }}
                        </a>
                    </div>
                </div>
            </div>
            <!-- Invoice Mainbox End -->
        </div>
    </div>
</x-layouts.dashboard-layout>
