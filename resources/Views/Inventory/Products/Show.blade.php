@php
    use Illuminate\Support\Facades\Storage;
@endphp

<x-layouts.dashboard-layout :meta="$meta ?? []">
    <x-ui.breadcrumb
        :title="$product->name"
        :items="[
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Inventario'), 'url' => route('inventory.product_transfers.index')],
            ['label' => gettext('Productos'), 'url' => route('inventory.products.index')],
            ['label' => $product->name],
        ]"
    />

    <section class="grid gap-6 pb-12">
        <article class="rounded-3xl border border-gray-200 bg-white/90 p-6 shadow-sm backdrop-blur dark:border-gray-800 dark:bg-slate-900/80">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex-1 space-y-5">
                    <div class="flex flex-wrap items-start gap-3">
                        <span class="rounded-full bg-primary-soft px-4 py-1 text-xs font-semibold uppercase tracking-widest text-primary">{{ $product->sku }}</span>
                        <span class="rounded-full bg-sky-100 px-4 py-1 text-xs font-semibold uppercase tracking-widest text-sky-600 dark:bg-sky-500/20 dark:text-sky-300">{{ $productPayload['stock_status'] ?? gettext('Inventario') }}</span>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $product->name }}</h1>
                    <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500 dark:text-gray-300">
                        @if ($product->line?->name)
                            <span class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-1.5 dark:border-gray-700 dark:bg-slate-900">
                                <i class="fa-solid fa-layer-group text-primary"></i>
                                {{ $product->line->name }}
                            </span>
                        @endif
                        @if ($product->category?->name)
                            <span class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-1.5 dark:border-gray-700 dark:bg-slate-900">
                                <i class="fa-solid fa-tags text-emerald-500"></i>
                                {{ $product->category->name }}
                            </span>
                        @endif
                        @if ($product->subcategory?->name)
                            <span class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-1.5 dark:border-gray-700 dark:bg-slate-900">
                                <i class="fa-solid fa-tag text-rose-500"></i>
                                {{ $product->subcategory->name }}
                            </span>
                        @endif
                        <span class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-1.5 dark:border-gray-700 dark:bg-slate-900">
                            <i class="fa-solid fa-warehouse text-indigo-500"></i>
                            {{ $product->warehouse?->name ?? gettext('Sin bodega') }}
                        </span>
                    </div>
                    <div class="prose prose-sm max-w-none text-gray-600 dark:prose-invert dark:text-gray-200">
                        {!! $product->description ? new \Illuminate\Support\HtmlString($product->description) : '<p>'.gettext('Este producto no cuenta con una descripción detallada.').'</p>' !!}
                    </div>
                </div>
                <figure class="mx-auto flex h-64 w-64 items-center justify-center overflow-hidden rounded-3xl border border-gray-200 bg-gray-50 shadow-inner dark:border-gray-700 dark:bg-slate-900/60">
                    @if (!empty($productPayload['featured_image_url'] ?? null) || $product->featured_image_path)
                        <img
                            src="{{ $productPayload['featured_image_url'] ?? ($product->featured_image_path ? asset($product->featured_image_path) : '') }}"
                            alt="{{ $product->name }}"
                            class="h-full w-full object-cover"
                        >
                    @else
                        <i class="fa-solid fa-image text-4xl text-gray-300"></i>
                    @endif
                </figure>
            </div>
        </article>

        <div class="grid gap-6 lg:grid-cols-[2fr_3fr]">
            <article class="rounded-3xl border border-gray-200 bg-white/90 p-6 shadow-sm backdrop-blur dark:border-gray-800 dark:bg-slate-900/70">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Detalle logístico') }}</h2>
                <dl class="mt-5 space-y-4 text-sm text-gray-600 dark:text-gray-300">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="font-semibold text-gray-500 dark:text-gray-400">{{ gettext('Código de barras') }}</dt>
                        <dd>{{ $product->barcode ?: '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="font-semibold text-gray-500 dark:text-gray-400">{{ gettext('Stock disponible') }}</dt>
                        <dd>{{ number_format($productPayload['stock_quantity'] ?? 0) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="font-semibold text-gray-500 dark:text-gray-400">{{ gettext('Stock mínimo') }}</dt>
                        <dd>{{ number_format($productPayload['minimum_stock'] ?? 0) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="font-semibold text-gray-500 dark:text-gray-400">{{ gettext('Visibilidad') }}</dt>
                        <dd>{{ $productPayload['visibility_label'] ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="font-semibold text-gray-500 dark:text-gray-400">{{ gettext('Estado') }}</dt>
                        <dd>{{ $productPayload['status_label'] ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="font-semibold text-gray-500 dark:text-gray-400">{{ gettext('Creado el') }}</dt>
                        <dd>{{ optional($product->created_at)->translatedFormat('d M, Y H:i') }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="font-semibold text-gray-500 dark:text-gray-400">{{ gettext('Actualizado el') }}</dt>
                        <dd>{{ optional($product->updated_at)->translatedFormat('d M, Y H:i') }}</dd>
                    </div>
                </dl>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('inventory.products.edit', $product) }}" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-inverted shadow-sm transition hover:bg-primary-strong">
                        <i class="fa-regular fa-pen-to-square"></i>
                        {{ gettext('Editar') }}
                    </a>
                    <a href="{{ route('inventory.products.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300">
                        <i class="fa-solid fa-arrow-left"></i>
                        {{ gettext('Volver al listado') }}
                    </a>
                </div>
            </article>

            <article class="rounded-3xl border border-gray-200 bg-white/90 p-6 shadow-sm backdrop-blur dark:border-gray-800 dark:bg-slate-900/70">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Listas de precios') }}</h2>
                <div class="mt-5 overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 text-sm text-gray-600 dark:divide-gray-800 dark:text-gray-200">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-slate-900">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">{{ gettext('Lista') }}</th>
                                <th class="px-4 py-3 text-left font-semibold">{{ gettext('Valor') }}</th>
                                <th class="px-4 py-3 text-left font-semibold">{{ gettext('Canal') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach (($productPayload['prices'] ?? []) as $price)
                                <tr>
                                    <td class="px-4 py-3">{{ $priceLists->firstWhere('id', $price['price_list_id'])?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">${{ number_format($price['value'], 2) }}</td>
                                    <td class="px-4 py-3">
                                        @php
                                            $channels = [];
                                            if ($product->price_list_pos_id === $price['price_list_id']) {
                                                $channels[] = 'POS';
                                            }
                                            if ($product->price_list_b2c_id === $price['price_list_id']) {
                                                $channels[] = 'B2C';
                                            }
                                            if ($product->price_list_b2b_id === $price['price_list_id']) {
                                                $channels[] = 'B2B';
                                            }
                                        @endphp
                                        {{ empty($channels) ? '—' : implode(' · ', $channels) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if (!empty($productPayload['gallery']))
                    <h3 class="mt-8 text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Galería') }}</h3>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($productPayload['gallery'] as $item)
                            <figure class="overflow-hidden rounded-2xl border border-gray-200 bg-gray-50 shadow-sm dark:border-gray-700 dark:bg-slate-900/60">
                                <img src="{{ $item['url'] }}" alt="{{ $product->name }}" class="h-48 w-full object-cover">
                            </figure>
                        @endforeach
                    </div>
                @endif
            </article>
        </div>
    </section>
</x-layouts.dashboard-layout>

