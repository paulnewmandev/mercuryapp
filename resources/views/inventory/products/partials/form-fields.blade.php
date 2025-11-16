@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $initial = $productPayload ?? [];
    $oldPrices = collect(old('prices', []))->keyBy(fn ($item) => $item['price_list_id'] ?? Str::uuid()->toString());
    $existingPrices = collect($initial['prices'] ?? [])->keyBy('price_list_id');

    $selectedLine = old('product_line_id', $initial['product_line_id'] ?? $product?->product_line_id);
    $selectedCategory = old('category_id', $initial['category_id'] ?? $product?->category_id);
    $selectedSubcategory = old('subcategory_id', $initial['subcategory_id'] ?? $product?->subcategory_id);
    $selectedWarehouse = old('warehouse_id', $initial['warehouse_id'] ?? $product?->warehouse_id ?? $warehouses->first()?->id);

    $posPriceList = old('price_list_pos_id', $initial['price_list_pos_id'] ?? $product?->price_list_pos_id);
    $b2cPriceList = old('price_list_b2c_id', $initial['price_list_b2c_id'] ?? $product?->price_list_b2c_id);
    $b2bPriceList = old('price_list_b2b_id', $initial['price_list_b2b_id'] ?? $product?->price_list_b2b_id);

    $stockQuantity = old('stock_quantity', $initial['stock_quantity'] ?? $stock?->quantity ?? 0);
    $minimumStock = old('minimum_stock', $initial['minimum_stock'] ?? $stock?->minimum_stock ?? 0);

    $initialDescription = old('description', $initial['description'] ?? $product?->description);
    $existingGallery = collect($initial['gallery'] ?? [])
        ->map(fn ($item) => is_array($item) ? $item : ['path' => $item, 'url' => asset($item)]);

    if ($existingGallery->isEmpty() && $product?->gallery_images) {
        $existingGallery = collect($product->gallery_images)
            ->map(fn ($path) => [
                'path' => $path,
                'url' => asset($path),
            ]);
    }

    $linePlaceholder = gettext('Selecciona una línea');
    $categoryPlaceholder = gettext('Selecciona una categoría');
    $subcategoryPlaceholder = gettext('Selecciona una subcategoría');
    $warehousePlaceholder = gettext('Selecciona una bodega');
    $priceListPlaceholder = gettext('Selecciona una lista de precios');

    $selectedLineLabel = optional($lines->firstWhere('id', $selectedLine))->name;
    $selectedCategoryLabel = optional($categories->firstWhere('id', $selectedCategory))->name;
    $selectedSubcategoryLabel = optional($subcategories->firstWhere('id', $selectedSubcategory))->name;
    $selectedWarehouseLabel = optional($warehouses->firstWhere('id', $selectedWarehouse))->name;
    $selectedPosPriceListLabel = optional($priceLists->firstWhere('id', $posPriceList))->name;
    $selectedB2cPriceListLabel = optional($priceLists->firstWhere('id', $b2cPriceList))->name;
    $selectedB2bPriceListLabel = optional($priceLists->firstWhere('id', $b2bPriceList))->name;

    $catalogPayload = [
        'lines' => $lines,
        'categories' => $categories,
        'subcategories' => $subcategories,
        'price_lists' => $priceLists,
    ];
@endphp

<div
    class="grid gap-6"
    data-product-form-root
    data-product-form-mode="{{ $mode }}"
    data-product-form-base-url="{{ url('inventory/products') }}"
    data-product-form-initial='@json($initial)'
    data-product-form-catalog='@json($catalogPayload)'
>
    <form
        @if ($mode === 'create')
            action="{{ route('inventory.products.store') }}"
        @elseif ($mode === 'edit' && isset($product))
            action="{{ route('inventory.products.update', $product) }}"
        @endif
        method="POST"
        enctype="multipart/form-data"
        class="grid gap-10"
        data-product-form
    >
        @csrf
        @if ($mode === 'edit')
            @method('PATCH')
        @endif

        <input type="hidden" name="description" value="{{ $initialDescription }}" data-product-description-input>

        <div class="grid gap-10 xl:grid-cols-[1.75fr_1fr]">
            <div class="grid gap-8">
        <section class="relative z-[200] grid gap-6 rounded-3xl border border-gray-200 bg-white/95 p-6 shadow-sm backdrop-blur dark:border-gray-800 dark:bg-slate-900/80 overflow-visible">
            <header class="flex flex-col gap-2">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Información general') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ gettext('Define la identificación y la estructura del catálogo del producto.') }}</p>
            </header>

                    <div class="grid gap-6 lg:grid-cols-2">
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('SKU') }}</label>
                    <input
                        type="text"
                        name="sku"
                        value="{{ old('sku', $initial['sku'] ?? $product?->sku) }}"
                                class="w-full rounded-2xl border border-gray-300 bg-white/95 px-4 py-3 text-sm font-medium text-gray-800 shadow-sm ring-1 ring-transparent transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                        required
                        maxlength="100"
                    >
                    @error('sku')
                                <p class="text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                        <div class="space-y-2 hidden">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Código de barras') }}</label>
                    <input
                        type="text"
                        name="barcode"
                        value="{{ old('barcode', $initial['barcode'] ?? $product?->barcode) }}"
                                class="w-full rounded-2xl border border-gray-200 bg-white/95 px-4 py-3 text-sm font-medium text-gray-800 shadow-sm ring-1 ring-transparent transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                        maxlength="255"
                    >
                    @error('barcode')
                                <p class="text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                        <div class="space-y-2 lg:col-span-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Nombre del producto') }}</label>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $initial['name'] ?? $product?->name) }}"
                                class="w-full rounded-2xl border border-gray-200 bg-white/95 px-4 py-3 text-base font-semibold text-gray-900 shadow-sm ring-1 ring-transparent transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                        maxlength="255"
                        required
                    >
                    @error('name')
                                <p class="text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

                    <div class="grid gap-6 lg:grid-cols-3">
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Línea de producto') }}</label>
                            <div
                                class="relative z-[160]"
                                data-select
                                data-select-manual
                        data-product-line-select
                                data-select-name="product_line_id"
                                data-select-invalid="{{ $errors->has('product_line_id') ? 'true' : 'false' }}"
                            >
                                <input type="hidden" name="product_line_id" value="{{ $selectedLine }}">
                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between rounded-2xl border {{ $errors->has('product_line_id') ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-300/40' : 'border-gray-300 focus:border-primary focus:ring-primary/20' }} bg-white px-4 py-3 text-sm font-medium text-gray-700 outline-none transition focus:ring-2 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-200"
                                    data-select-trigger
                                    data-select-placeholder="{{ $linePlaceholder }}"
                                    aria-haspopup="listbox"
                                    aria-expanded="false"
                    >
                                    <span data-select-value class="truncate">{{ $selectedLine ? $selectedLineLabel : $linePlaceholder }}</span>
                                    <i class="fa-solid fa-chevron-down text-xs text-gray-400 transition dark:text-gray-500" data-select-icon></i>
                                </button>
                                <div
                                class="invisible absolute left-0 right-0 z-[170] mt-2 w-full origin-top scale-95 transform overflow-hidden rounded-2xl border border-gray-200 bg-white/95 shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                    data-select-dropdown
                                    role="listbox"
                                    hidden
                                >
                                    <div class="max-h-60 overflow-y-auto py-2">
                        @foreach ($lines as $line)
                                            <button
                                                type="button"
                                                class="group flex w-full items-center justify-between gap-3 px-4 py-2 text-left text-sm text-gray-700 transition hover:bg-primary/10 data-[selected=true]:text-primary dark:text-gray-200"
                                                data-select-option
                                                data-value="{{ $line->id }}"
                                                data-label="{{ $line->name }}"
                                                role="option"
                                            >
                                                <span class="truncate">{{ $line->name }}</span>
                                                <i class="fa-solid fa-check text-xs opacity-0 transition group-data-[selected=true]:opacity-100" data-select-check></i>
                                            </button>
                        @endforeach
                                    </div>
                                </div>
                            </div>
                    @error('product_line_id')
                                <p class="text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Categoría') }}</label>
                            <div
                                class="relative z-[160]"
                                data-select
                                data-select-manual
                        data-product-category-select
                                data-select-name="category_id"
                                data-select-invalid="{{ $errors->has('category_id') ? 'true' : 'false' }}"
                            >
                                <input type="hidden" name="category_id" value="{{ $selectedCategory }}">
                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between rounded-2xl border {{ $errors->has('category_id') ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-300/40' : 'border-gray-300 focus:border-primary focus:ring-primary/20' }} bg-white px-4 py-3 text-sm font-medium text-gray-700 outline-none transition focus:ring-2 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-200"
                                    data-select-trigger
                                    data-select-placeholder="{{ $categoryPlaceholder }}"
                                    aria-haspopup="listbox"
                                    aria-expanded="false"
                                >
                                    <span data-select-value class="truncate">{{ $selectedCategory ? $selectedCategoryLabel : $categoryPlaceholder }}</span>
                                    <i class="fa-solid fa-chevron-down text-xs text-gray-400 transition dark:text-gray-500" data-select-icon></i>
                                </button>
                                <div
                                class="invisible absolute left-0 right-0 z-[170] mt-2 w-full origin-top scale-95 transform overflow-hidden rounded-2xl border border-gray-200 bg-white/95 shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                    data-select-dropdown
                                    role="listbox"
                                    hidden
                    >
                                    <div class="max-h-60 overflow-y-auto py-2" data-product-category-options>
                        @foreach ($categories as $category)
                                            <button
                                                type="button"
                                                class="group flex w-full items-center justify-between gap-3 px-4 py-2 text-left text-sm text-gray-700 transition hover:bg-primary/10 data-[selected=true]:text-primary dark:text-gray-200"
                                                data-select-option
                                                data-product-category-option
                                                data-value="{{ $category->id }}"
                                                data-label="{{ $category->name }}"
                                                data-line="{{ $category->product_line_id }}"
                                                role="option"
                                            >
                                                <span class="truncate">{{ $category->name }}</span>
                                                <i class="fa-solid fa-check text-xs opacity-0 transition group-data-[selected=true]:opacity-100" data-select-check></i>
                                            </button>
                        @endforeach
                                    </div>
                                </div>
                            </div>
                    @error('category_id')
                                <p class="text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Subcategoría') }}</label>
                            <div
                                class="relative z-[160]"
                                data-select
                                data-select-manual
                        data-product-subcategory-select
                                data-select-name="subcategory_id"
                                data-select-invalid="{{ $errors->has('subcategory_id') ? 'true' : 'false' }}"
                            >
                                <input type="hidden" name="subcategory_id" value="{{ $selectedSubcategory }}">
                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between rounded-2xl border {{ $errors->has('subcategory_id') ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-300/40' : 'border-gray-300 focus:border-primary focus:ring-primary/20' }} bg-white px-4 py-3 text-sm font-medium text-gray-700 outline-none transition focus:ring-2 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-200"
                                    data-select-trigger
                                    data-select-placeholder="{{ $subcategoryPlaceholder }}"
                                    aria-haspopup="listbox"
                                    aria-expanded="false"
                                >
                                    <span data-select-value class="truncate">{{ $selectedSubcategory ? $selectedSubcategoryLabel : $subcategoryPlaceholder }}</span>
                                    <i class="fa-solid fa-chevron-down text-xs text-gray-400 transition dark:text-gray-500" data-select-icon></i>
                                </button>
                                <div
                                class="invisible absolute left-0 right-0 z-[170] mt-2 w-full origin-top scale-95 transform overflow-hidden rounded-2xl border border-gray-200 bg-white/95 shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                    data-select-dropdown
                                    role="listbox"
                                    hidden
                    >
                                    <div class="max-h-60 overflow-y-auto py-2" data-product-subcategory-options>
                        @foreach ($subcategories as $subcategory)
                                            <button
                                                type="button"
                                                class="group flex w-full items-center justify-between gap-3 px-4 py-2 text-left text-sm text-gray-700 transition hover:bg-primary/10 data-[selected=true]:text-primary dark:text-gray-200"
                                                data-select-option
                                                data-product-subcategory-option
                                                data-value="{{ $subcategory->id }}"
                                                data-label="{{ $subcategory->name }}"
                                                data-line="{{ $subcategory->product_line_id }}"
                                                data-category="{{ $subcategory->parent_id }}"
                                                role="option"
                                            >
                                                <span class="truncate">{{ $subcategory->name }}</span>
                                                <i class="fa-solid fa-check text-xs opacity-0 transition group-data-[selected=true]:opacity-100" data-select-check></i>
                                            </button>
                        @endforeach
                </div>
            </div>
                </div>
                            @error('subcategory_id')
                                <p class="text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </section>

        <section class="relative grid gap-6 rounded-3xl border border-gray-200 bg-white/95 p-6 shadow-sm backdrop-blur dark:border-gray-800 dark:bg-slate-900/80 overflow-visible">
            <header class="flex flex-col gap-2">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Contenido') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ gettext('Comparte la historia del producto con un editor enriquecido para destacar sus beneficios.') }}</p>
            </header>

                    <div class="space-y-4">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Descripción detallada') }}</label>
                        <div class="rounded-3xl bg-transparent" data-product-description-surface>
                            <div class="min-h-[280px] p-0" data-product-description-editor>{{ $initialDescription }}</div>
                        </div>
                        @error('description')
                            <p class="text-xs text-rose-500">{{ $message }}</p>
                        @enderror
            </div>
        </section>

        <section class="relative grid gap-6 rounded-3xl border border-gray-200 bg-white/95 p-6 shadow-sm backdrop-blur dark:border-gray-800 dark:bg-slate-900/80 overflow-visible">
            <header class="flex flex-col gap-2">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Gestión de precios') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ gettext('Selecciona las listas predeterminadas y personaliza los precios por canal.') }}</p>
            </header>

                    <div class="grid gap-5 lg:grid-cols-3">
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Precio POS por defecto') }}</label>
                            <div
                                class="relative z-[160]"
                                data-select
                                data-select-manual
                                data-select-name="price_list_pos_id"
                                data-select-invalid="{{ $errors->has('price_list_pos_id') ? 'true' : 'false' }}"
                            >
                                <input type="hidden" name="price_list_pos_id" value="{{ $posPriceList }}">
                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between rounded-2xl border {{ $errors->has('price_list_pos_id') ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-300/40' : 'border-gray-300 focus:border-primary focus:ring-primary/20' }} bg-white px-4 py-3 text-sm font-medium text-gray-700 outline-none transition focus:ring-2 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-200"
                                    data-select-trigger
                                    data-select-placeholder="{{ $priceListPlaceholder }}"
                                    aria-haspopup="listbox"
                                    aria-expanded="false"
                    >
                                    <span data-select-value class="truncate">{{ $posPriceList ? $selectedPosPriceListLabel : $priceListPlaceholder }}</span>
                                    <i class="fa-solid fa-chevron-down text-xs text-gray-400 transition dark:text-gray-500" data-select-icon></i>
                                </button>
                                <div
                                    class="invisible absolute left-0 right-0 z-[170] mt-2 w-full origin-top scale-95 transform overflow-hidden rounded-2xl border border-gray-200 bg-white/95 shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                    data-select-dropdown
                                    role="listbox"
                                    hidden
                                >
                                    <div class="max-h-60 overflow-y-auto py-2">
                        @foreach ($priceLists as $priceList)
                                            <button
                                                type="button"
                                                class="group flex w-full items-center justify-between gap-3 px-4 py-2 text-left text-sm text-gray-700 transition hover:bg-primary/10 data-[selected=true]:text-primary dark:text-gray-200"
                                                data-select-option
                                                data-value="{{ $priceList->id }}"
                                                data-label="{{ $priceList->name }}"
                                                role="option"
                                            >
                                                <span class="truncate">{{ $priceList->name }}</span>
                                                <i class="fa-solid fa-check text-xs opacity-0 transition group-data-[selected=true]:opacity-100" data-select-check></i>
                                            </button>
                        @endforeach
                                    </div>
                                </div>
                            </div>
                    @error('price_list_pos_id')
                                <p class="text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Precio B2C por defecto') }}</label>
                            <div
                                class="relative z-[160]"
                                data-select
                                data-select-manual
                                data-select-name="price_list_b2c_id"
                                data-select-invalid="{{ $errors->has('price_list_b2c_id') ? 'true' : 'false' }}"
                            >
                                <input type="hidden" name="price_list_b2c_id" value="{{ $b2cPriceList }}">
                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between rounded-2xl border {{ $errors->has('price_list_b2c_id') ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-300/40' : 'border-gray-300 focus:border-primary focus:ring-primary/20' }} bg-white px-4 py-3 text-sm font-medium text-gray-700 outline-none transition focus:ring-2 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-200"
                                    data-select-trigger
                                    data-select-placeholder="{{ $priceListPlaceholder }}"
                                    aria-haspopup="listbox"
                                    aria-expanded="false"
                    >
                                    <span data-select-value class="truncate">{{ $b2cPriceList ? $selectedB2cPriceListLabel : $priceListPlaceholder }}</span>
                                    <i class="fa-solid fa-chevron-down text-xs text-gray-400 transition dark:text-gray-500" data-select-icon></i>
                                </button>
                                <div
                                    class="invisible absolute left-0 right-0 z-[170] mt-2 w-full origin-top scale-95 transform overflow-hidden rounded-2xl border border-gray-200 bg-white/95 shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                    data-select-dropdown
                                    role="listbox"
                                    hidden
                                >
                                    <div class="max-h-60 overflow-y-auto py-2">
                        @foreach ($priceLists as $priceList)
                                            <button
                                                type="button"
                                                class="group flex w-full items-center justify-between gap-3 px-4 py-2 text-left text-sm text-gray-700 transition hover:bg-primary/10 data-[selected=true]:text-primary dark:text-gray-200"
                                                data-select-option
                                                data-value="{{ $priceList->id }}"
                                                data-label="{{ $priceList->name }}"
                                                role="option"
                                            >
                                                <span class="truncate">{{ $priceList->name }}</span>
                                                <i class="fa-solid fa-check text-xs opacity-0 transition group-data-[selected=true]:opacity-100" data-select-check></i>
                                            </button>
                        @endforeach
                                    </div>
                                </div>
                            </div>
                    @error('price_list_b2c_id')
                                <p class="text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Precio B2B por defecto') }}</label>
                            <div
                                class="relative z-[160]"
                                data-select
                                data-select-manual
                                data-select-name="price_list_b2b_id"
                                data-select-invalid="{{ $errors->has('price_list_b2b_id') ? 'true' : 'false' }}"
                            >
                                <input type="hidden" name="price_list_b2b_id" value="{{ $b2bPriceList }}">
                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between rounded-2xl border {{ $errors->has('price_list_b2b_id') ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-300/40' : 'border-gray-300 focus:border-primary focus:ring-primary/20' }} bg-white px-4 py-3 text-sm font-medium text-gray-700 outline-none transition focus:ring-2 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-200"
                                    data-select-trigger
                                    data-select-placeholder="{{ $priceListPlaceholder }}"
                                    aria-haspopup="listbox"
                                    aria-expanded="false"
                    >
                                    <span data-select-value class="truncate">{{ $b2bPriceList ? $selectedB2bPriceListLabel : $priceListPlaceholder }}</span>
                                    <i class="fa-solid fa-chevron-down text-xs text-gray-400 transition dark:text-gray-500" data-select-icon></i>
                                </button>
                                <div
                                    class="invisible absolute left-0 right-0 z-[170] mt-2 w-full origin-top scale-95 transform overflow-hidden rounded-2xl border border-gray-200 bg-white/95 shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                    data-select-dropdown
                                    role="listbox"
                                    hidden
                                >
                                    <div class="max-h-60 overflow-y-auto py-2">
                        @foreach ($priceLists as $priceList)
                                            <button
                                                type="button"
                                                class="group flex w-full items-center justify-between gap-3 px-4 py-2 text-left text-sm text-gray-700 transition hover:bg-primary/10 data-[selected=true]:text-primary dark:text-gray-200"
                                                data-select-option
                                                data-value="{{ $priceList->id }}"
                                                data-label="{{ $priceList->name }}"
                                                role="option"
                                            >
                                                <span class="truncate">{{ $priceList->name }}</span>
                                                <i class="fa-solid fa-check text-xs opacity-0 transition group-data-[selected=true]:opacity-100" data-select-check></i>
                                            </button>
                        @endforeach
                                    </div>
                                </div>
                            </div>
                    @error('price_list_b2b_id')
                                <p class="text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($priceLists as $priceIndex => $priceList)
                    @php
                        $priceValue = optional($oldPrices->get($priceList->id))['value'] ?? optional($existingPrices->get($priceList->id))['value'] ?? null;
                    @endphp
                            <div class="rounded-2xl border border-gray-200 bg-white/95 p-4 shadow-sm transition hover:border-primary/50 dark:border-gray-700 dark:bg-slate-900">
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Lista de precios') }}</p>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ $priceList->name }}</h3>
                            </div>
                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-primary/10 text-primary dark:bg-primary/20">
                                <i class="fa-solid fa-tag"></i>
                            </span>
                        </div>
                                <div class="mt-4 space-y-2">
                            <input
                                type="hidden"
                                name="prices[{{ $priceIndex }}][price_list_id]"
                                value="{{ $priceList->id }}"
                            >
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Valor') }}</label>
                            <input
                                type="number"
                                name="prices[{{ $priceIndex }}][value]"
                                value="{{ $priceValue !== null ? $priceValue : '0' }}"
                                class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 shadow-sm transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                step="0.01"
                                min="0"
                                inputmode="decimal"
                                data-product-price-input
                            >
                        </div>
                    </div>
                @endforeach
            </div>
            @error('prices')
                <p class="text-xs text-rose-500">{{ $message }}</p>
            @enderror
        </section>
            </div>

            <div class="grid gap-8">
        <section class="relative grid gap-6 rounded-3xl border border-gray-200 bg-white/95 p-6 shadow-sm backdrop-blur dark:border-gray-800 dark:bg-slate-900/80 overflow-visible">
                    <header class="flex flex-col gap-2">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Inventario y visibilidad') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ gettext('Controla la bodega principal, el stock y los canales donde se mostrará.') }}</p>
                    </header>

                    <div class="space-y-6">
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Bodega principal') }}</label>
                            <div
                                class="relative z-50"
                                data-select
                                data-select-manual
                                data-select-name="warehouse_id"
                                data-select-invalid="{{ $errors->has('warehouse_id') ? 'true' : 'false' }}"
                            >
                                <input type="hidden" name="warehouse_id" value="{{ $selectedWarehouse }}">
                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between rounded-2xl border {{ $errors->has('warehouse_id') ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-300/40' : 'border-gray-300 focus:border-primary focus:ring-primary/20' }} bg-white px-4 py-3 text-sm font-medium text-gray-700 outline-none transition focus:ring-2 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-200"
                                    data-select-trigger
                                    data-select-placeholder="{{ $warehousePlaceholder }}"
                                    aria-haspopup="listbox"
                                    aria-expanded="false"
                                >
                                    <span data-select-value class="truncate">{{ $selectedWarehouse ? $selectedWarehouseLabel : $warehousePlaceholder }}</span>
                                    <i class="fa-solid fa-chevron-down text-xs text-gray-400 transition dark:text-gray-500" data-select-icon></i>
                                </button>
                                <div
                                    class="invisible absolute left-0 right-0 z-60 mt-2 w-full origin-top scale-95 transform overflow-hidden rounded-2xl border border-gray-200 bg-white/95 shadow-xl opacity-0 transition will-change-transform data-[open='true']:visible data-[open='true']:scale-100 data-[open='true']:opacity-100 dark:border-gray-700 dark:bg-slate-900"
                                    data-select-dropdown
                                    role="listbox"
                                    hidden
                                >
                                    <div class="max-h-60 overflow-y-auto py-2">
                                        @foreach ($warehouses as $warehouse)
                                            <button
                                                type="button"
                                                class="group flex w-full items-center justify-between gap-3 px-4 py-2 text-left text-sm text-gray-700 transition hover:bg-primary/10 data-[selected=true]:text-primary dark:text-gray-200"
                                                data-select-option
                                                data-value="{{ $warehouse->id }}"
                                                data-label="{{ $warehouse->name }}"
                                                role="option"
                                            >
                                                <span class="truncate">{{ $warehouse->name }}</span>
                                                <i class="fa-solid fa-check text-xs opacity-0 transition group-data-[selected=true]:opacity-100" data-select-check></i>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @error('warehouse_id')
                                <p class="text-xs text-rose-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Stock disponible') }}</label>
                                <input
                                    type="number"
                                    min="0"
                                    name="stock_quantity"
                                    value="{{ $stockQuantity }}"
                                    class="w-full rounded-2xl border border-gray-200 bg-white/95 px-4 py-3 text-sm font-medium text-gray-800 shadow-sm ring-1 ring-transparent transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                    required
                                >
                                @error('stock_quantity')
                                    <p class="text-xs text-rose-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Stock mínimo') }}</label>
                                <input
                                    type="number"
                                    min="0"
                                    name="minimum_stock"
                                    value="{{ $minimumStock }}"
                                    class="w-full rounded-2xl border border-gray-200 bg-white/95 px-4 py-3 text-sm font-medium text-gray-800 shadow-sm ring-1 ring-transparent transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-slate-900 dark:text-white"
                                >
                                @error('minimum_stock')
                                    <p class="text-xs text-rose-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="space-y-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Visibilidad por canal') }}</p>
                            <div class="space-y-3">
                            <label class="flex cursor-pointer items-center justify-between gap-3 rounded-2xl border border-gray-200 bg-white/95 px-4 py-3 text-sm font-medium text-gray-700 shadow-sm transition hover:border-primary/50 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-200">
                                    <span class="flex items-center gap-2">
                                        <i class="fa-solid fa-store text-primary"></i>
                                        {{ __('Mostrar en POS') }}
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <input type="hidden" name="show_in_pos" value="0">
                                        <input type="checkbox" name="show_in_pos" value="1" class="peer sr-only" @checked(old('show_in_pos', $initial['visibility']['pos'] ?? $product?->show_in_pos ?? true))>
                                        <span class="relative inline-flex h-6 w-11 items-center rounded-full bg-gray-300 transition peer-checked:bg-primary" data-visibility-track>
                                            <span class="absolute left-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5" data-visibility-thumb></span>
                                        </span>
                                    </div>
                                </label>
                            <label class="flex cursor-pointer items-center justify-between gap-3 rounded-2xl border border-gray-200 bg-white/95 px-4 py-3 text-sm font-medium text-gray-700 shadow-sm transition hover:border-primary/50 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-200">
                                    <span class="flex items-center gap-2">
                                        <i class="fa-solid fa-briefcase text-primary"></i>
                                        {{ __('Mostrar en B2B') }}
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <input type="hidden" name="show_in_b2b" value="0">
                                        <input type="checkbox" name="show_in_b2b" value="1" class="peer sr-only" @checked(old('show_in_b2b', $initial['visibility']['b2b'] ?? $product?->show_in_b2b ?? true))>
                                        <span class="relative inline-flex h-6 w-11 items-center rounded-full bg-gray-300 transition peer-checked:bg-primary" data-visibility-track>
                                            <span class="absolute left-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5" data-visibility-thumb></span>
                                        </span>
                                    </div>
                                </label>
                            <label class="flex cursor-pointer items-center justify-between gap-3 rounded-2xl border border-gray-200 bg-white/95 px-4 py-3 text-sm font-medium text-gray-700 shadow-sm transition hover:border-primary/50 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-200">
                                    <span class="flex items-center gap-2">
                                        <i class="fa-solid fa-cart-shopping text-primary"></i>
                                        {{ __('Mostrar en B2C') }}
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <input type="hidden" name="show_in_b2c" value="0">
                                        <input type="checkbox" name="show_in_b2c" value="1" class="peer sr-only" @checked(old('show_in_b2c', $initial['visibility']['b2c'] ?? $product?->show_in_b2c ?? true))>
                                        <span class="relative inline-flex h-6 w-11 items-center rounded-full bg-gray-300 transition peer-checked:bg-primary" data-visibility-track>
                                            <span class="absolute left-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5" data-visibility-thumb></span>
                                        </span>
                                    </div>
                                </label>
                            </div>
                            @foreach (['show_in_pos', 'show_in_b2b', 'show_in_b2c'] as $visibilityField)
                                @error($visibilityField)
                                    <p class="text-xs text-rose-500">{{ $message }}</p>
                                @enderror
                            @endforeach
                        </div>
                    </div>
        </section>

                <section class="grid gap-6 rounded-3xl border border-gray-200 bg-white/95 p-6 shadow-sm backdrop-blur dark:border-gray-800 dark:bg-slate-900/80">
            <header class="flex flex-col gap-2">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ gettext('Imágenes del producto') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ gettext('Carga una fotografía destacada y administra hasta cinco imágenes adicionales en la galería.') }}</p>
            </header>

                    <div class="space-y-6">
                        <div class="space-y-3">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Imagen destacada') }}</label>
                            <div
                                class="relative flex flex-col items-center justify-center gap-3 rounded-3xl border border-dashed border-gray-300 bg-white/95 px-6 py-8 text-center shadow-sm transition hover:border-primary/60 dark:border-gray-700 dark:bg-slate-900"
                                data-product-featured-wrapper
                                data-product-featured-initial="{{ !empty($initial['featured_image_url'] ?? $product?->featured_image_path) ? 'true' : 'false' }}"
                            >
                                <button
                                    type="button"
                                    class="absolute right-3 top-3 inline-flex h-9 w-9 items-center justify-center rounded-full bg-slate-900/80 text-white opacity-0 transition hover:bg-slate-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary pointer-events-none data-[visible='true']:pointer-events-auto data-[visible='true']:opacity-100"
                                    data-product-featured-remove
                                    data-visible="{{ !empty($initial['featured_image_url'] ?? $product?->featured_image_path) ? 'true' : 'false' }}"
                                    aria-label="{{ gettext('Eliminar imagen destacada') }}"
                                >
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                                <figure class="relative flex h-36 w-36 items-center justify-center overflow-hidden rounded-2xl bg-gray-100 shadow-inner ring-1 ring-gray-200/70 dark:bg-slate-800 dark:ring-gray-700/60" data-product-featured-preview>
                            @if (!empty($initial['featured_image_url'] ?? $product?->featured_image_path))
                                <img src="{{ $initial['featured_image_url'] ?? ($product?->featured_image_path ? asset($product->featured_image_path) : '') }}" alt="{{ gettext('Vista previa') }}" class="h-full w-full object-cover">
                            @else
                                <i class="fa-solid fa-image text-2xl text-gray-400"></i>
                            @endif
                        </figure>
                                <input type="hidden" name="remove_featured_image" value="0" data-product-featured-remove-input>
                                <label class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-primary bg-primary/5 px-5 py-2.5 text-sm font-semibold text-primary transition hover:bg-primary hover:text-white">
                            <i class="fa-solid fa-arrow-up-from-bracket"></i>
                            <span>{{ gettext('Seleccionar imagen') }}</span>
                            <input type="file" name="featured_image" accept="image/*" class="hidden" data-product-featured-input>
                        </label>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ gettext('Formatos admitidos: JPG, PNG o WebP · Peso máximo: 2MB.') }}</p>
                    </div>
                    @error('featured_image')
                        <p class="text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                        <div class="space-y-3">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gettext('Galería (máximo 5 imágenes)') }}</label>
                            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-2" data-product-gallery-container>
                        @foreach ($existingGallery as $asset)
                                    <div class="group relative flex flex-col gap-2 rounded-2xl border border-gray-200 bg-white/95 p-3 shadow-sm transition hover:border-primary/60 dark:border-gray-700 dark:bg-slate-900" data-product-gallery-existing data-product-gallery-card data-gallery-type="existing" data-gallery-path="{{ $asset['path'] }}">
                                        <button
                                            type="button"
                                            class="absolute right-2 top-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-900/80 text-white transition hover:bg-slate-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary focus-visible:outline-white cursor-pointer z-10"
                                            data-product-gallery-remove
                                            aria-label="{{ gettext('Eliminar imagen de la galería') }}"
                                        >
                                            <span class="sr-only">{{ gettext('Eliminar imagen de la galería') }}</span>
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                                                <path d="M3.5 3.5L10.5 10.5M10.5 3.5L3.5 10.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                            </svg>
                                        </button>
                                        <figure class="aspect-square overflow-hidden rounded-xl bg-gray-100 shadow-inner ring-1 ring-gray-200/70 dark:bg-slate-800 dark:ring-gray-700/60">
                                            <img src="{{ $asset['url'] }}" alt="{{ gettext('Imagen de galería') }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                </figure>
                                        <input type="checkbox" name="retain_gallery[]" value="{{ $asset['path'] }}" class="sr-only" checked>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ gettext('Imagen existente · se conservará si no la eliminas.') }}</p>
                                </div>
                        @endforeach

                                <label class="flex min-h-[180px] cursor-pointer flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-gray-300 bg-gray-50 text-sm font-medium text-gray-500 transition hover:border-primary/50 hover:bg-primary/5 hover:text-primary dark:border-gray-700 dark:bg-slate-900 dark:text-gray-300" data-product-gallery-upload>
                            <i class="fa-solid fa-plus text-xl"></i>
                            <span>{{ gettext('Agregar imágenes') }}</span>
                                    <small class="text-xs text-gray-400 dark:text-gray-500">{{ gettext('Puedes seleccionar varias a la vez') }}</small>
                            <input type="file" name="gallery_images[]" accept="image/*" multiple class="hidden" data-product-gallery-input>
                        </label>
                                <div data-product-gallery-removed class="hidden"></div>
                    </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ gettext('Las imágenes nuevas reemplazarán a las no retenidas. Tamaño máximo por archivo: 2MB.') }}</p>
                    @error('gallery_images')
                                <p class="text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                    @error('gallery_images.*')
                                <p class="text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </section>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3">
            <a
                href="{{ route('inventory.products.index') }}"
                class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-600 transition hover:border-primary hover:text-primary dark:border-gray-700 dark:text-gray-300"
            >
                {{ gettext('Cancelar') }}
            </a>
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-inverted shadow-sm transition hover:bg-primary-strong disabled:cursor-not-allowed disabled:opacity-70"
            >
                {{ $mode === 'create' ? gettext('Guardar') : gettext('Actualizar') }}
            </button>
        </div>
    </form>
</div>
