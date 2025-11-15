<?php

namespace App\Http\Controllers\Inventory;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\Products\StoreProductRequest;
use App\Http\Requests\Inventory\Products\UpdateProductRequest;
use App\Models\Company;
use App\Models\ItemPrice;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductLine;
use App\Models\ProductStock;
use App\Models\Warehouse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Dompdf\Dompdf;
use Dompdf\Options;
use Picqer\Barcode\BarcodeGeneratorPNG;

class ProductController extends Controller
{
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Productos',
            'description' => 'Administra el catálogo de productos, disponibilidad y precios.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Inventario'), 'url' => route('inventory.product_transfers.index')],
            ['label' => gettext('Productos')],
        ];

        $user = Auth::user();
        $companyId = $user?->company_id;

        // Si el usuario no tiene compañía (super admin), obtener la primera compañía disponible
        if (!$companyId) {
            $company = Company::query()->first();
            $companyId = $company?->id;
        }

        if (!$companyId) {
            $cards = [
                [
                    'label' => gettext('Total de productos'),
                    'value' => '0',
                    'icon' => 'fa-solid fa-boxes-stacked',
                    'trend' => __('Activos: :count', ['count' => '0']),
                    'color' => 'from-sky-500/80 to-indigo-600/90',
                ],
                [
                    'label' => gettext('Inventario disponible'),
                    'value' => '0',
                    'icon' => 'fa-solid fa-warehouse',
                    'trend' => __('Bajo stock: :count', ['count' => '0']),
                    'color' => 'from-sky-500/80 to-indigo-600/90',
                ],
                [
                    'label' => gettext('Visible en B2B'),
                    'value' => '0',
                    'icon' => 'fa-solid fa-briefcase',
                    'trend' => __('B2C visibles: :count', ['count' => '0']),
                    'color' => 'from-sky-500/80 to-indigo-600/90',
                ],
            ];

            return view('Inventory.Products.Index', compact('meta', 'breadcrumbItems', 'cards'));
        }

        $baseQuery = Product::query()->where('company_id', $companyId);

        $totalProducts = (clone $baseQuery)->count();
        $activeProducts = (clone $baseQuery)->where('status', 'A')->count();
        $b2bVisible = (clone $baseQuery)->where('show_in_b2b', true)->count();
        $b2cVisible = (clone $baseQuery)->where('show_in_b2c', true)->count();

        $inventoryStats = ProductStock::query()
            ->selectRaw('SUM(quantity) as total_quantity, SUM(CASE WHEN quantity <= minimum_stock THEN 1 ELSE 0 END) as low_stock_items')
            ->whereIn('product_id', function ($subQuery) use ($companyId): void {
                $subQuery->select('id')
                    ->from((new Product())->getTable())
                    ->where('company_id', $companyId);
            })
            ->first();

        $cards = [
            [
                'label' => gettext('Total de productos'),
                'value' => number_format($totalProducts),
                'icon' => 'fa-solid fa-boxes-stacked',
                'trend' => __('Activos: :count', ['count' => number_format($activeProducts)]),
                'color' => 'from-sky-500/80 to-indigo-600/90',
            ],
            [
                'label' => gettext('Inventario disponible'),
                'value' => number_format((int) $inventoryStats?->total_quantity ?? 0),
                'icon' => 'fa-solid fa-warehouse',
                'trend' => __('Bajo stock: :count', ['count' => number_format((int) $inventoryStats?->low_stock_items ?? 0)]),
                'color' => 'from-sky-500/80 to-indigo-600/90',
            ],
            [
                'label' => gettext('Visible en B2B'),
                'value' => number_format($b2bVisible),
                'icon' => 'fa-solid fa-briefcase',
                'trend' => __('B2C visibles: :count', ['count' => number_format($b2cVisible)]),
                'color' => 'from-sky-500/80 to-indigo-600/90',
            ],
        ];

        return view('Inventory.Products.Index', compact('meta', 'breadcrumbItems', 'cards'));
    }

    public function list(Request $request): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        // Si el usuario no tiene compañía (super admin), obtener la primera compañía disponible
        if (!$companyId) {
            $company = Company::query()->first();
            $companyId = $company?->id;
        }

        if (!$companyId) {
            return response()->json([
                'status' => 'success',
                'message' => gettext('Productos obtenidos correctamente.'),
                'data' => [
                    'items' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'per_page' => 10,
                        'last_page' => 1,
                        'total' => 0,
                        'from' => null,
                        'to' => null,
                    ],
                ],
            ]);
        }

        $sortBy = $request->string('sort_by', 'created_at')->toString();
        $sortDirection = Str::lower($request->string('sort_direction', 'desc')->toString()) === 'asc' ? 'asc' : 'desc';
        $perPage = (int) $request->integer('per_page', 10);
        $search = Str::of($request->string('search')->toString())->lower()->trim();

        $allowedSorts = ['sku', 'name', 'line_name', 'category_name', 'warehouse_name', 'stock_quantity', 'status', 'created_at'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        $allowedPerPage = [5, 10, 20, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $query = Product::query()
            ->select([
                'products.*',
                'lines.name as line_name',
                'categories.name as category_name',
                'subcategories.name as subcategory_name',
                'warehouses.name as warehouse_name',
                DB::raw('(SELECT quantity FROM product_stock WHERE product_stock.product_id = products.id LIMIT 1) as stock_quantity'),
                DB::raw('(SELECT minimum_stock FROM product_stock WHERE product_stock.product_id = products.id LIMIT 1) as minimum_stock'),
            ])
            ->leftJoin('product_lines as lines', 'lines.id', '=', 'products.product_line_id')
            ->leftJoin('product_categories as categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('product_categories as subcategories', 'subcategories.id', '=', 'products.subcategory_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'products.warehouse_id')
            ->where('products.company_id', $companyId);

        if ($search->isNotEmpty()) {
            $tokens = collect(explode(' ', Str::ascii($search->toString())))
                ->filter()
                ->unique();

            $query->where(function ($outer) use ($tokens): void {
                foreach ($tokens as $token) {
                    $outer->where(function ($inner) use ($token): void {
                        $like = "%{$token}%";
                        $inner->orWhereRaw('LOWER(products.sku) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(products.name) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(categories.name) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(subcategories.name) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(warehouses.name) LIKE ?', [$like]);
                    });
                }
            });
        }

		$orderColumn = match ($sortBy) {
            'line_name' => 'lines.name',
            'category_name' => 'categories.name',
            'warehouse_name' => 'warehouses.name',
            'stock_quantity' => 'stock_quantity',
            default => "products.{$sortBy}",
        };

        $query->orderBy($orderColumn, $sortDirection);

        $paginator = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Product $product) => $this->transformProduct($product));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Productos obtenidos correctamente.'),
            'data' => [
                'items' => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'last_page' => $paginator->lastPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ],
        ]);
    }

    public function options(): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        // Si el usuario no tiene compañía (super admin), obtener la primera compañía disponible
        if (!$companyId) {
            $company = Company::query()->first();
            $companyId = $company?->id;
        }

        if (!$companyId) {
            return response()->json([
                'status' => 'success',
                'message' => gettext('Opciones obtenidas correctamente.'),
                'data' => [
                    'lines' => [],
                    'categories' => [],
                    'subcategories' => [],
                    'warehouses' => [],
                    'priceLists' => [],
                ],
            ]);
        }

        $lines = ProductLine::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $categories = ProductCategory::query()
            ->where('company_id', $companyId)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get(['id', 'name', 'product_line_id']);

        $subcategories = ProductCategory::query()
            ->where('company_id', $companyId)
            ->whereNotNull('parent_id')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id', 'product_line_id']);

        $warehouses = Warehouse::query()
            ->where('company_id', $companyId)
            ->where('status', 'A')
            ->orderBy('name')
            ->get(['id', 'name']);

        $priceLists = PriceList::query()
            ->where(function ($query) use ($companyId): void {
                $query->whereNull('company_id')
                    ->orWhere('company_id', $companyId);
            })
            ->where('status', 'A')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'status' => 'success',
            'message' => gettext('Opciones obtenidas correctamente.'),
            'data' => compact('lines', 'categories', 'subcategories', 'warehouses', 'priceLists'),
        ]);
    }

    public function create(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Nuevo producto',
            'description' => 'Registra un nuevo producto con precios, stock y visibilidad.',
        ])->toArray();

        return view('Inventory.Products.Create', array_merge($this->formSharedData(), [
            'meta' => $meta,
            'mode' => 'create',
            'product' => null,
        ]));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = Auth::user();
        $companyId = $user?->company_id;

        DB::beginTransaction();

        try {
            $product = new Product();
            $product->fill([
                'product_line_id' => $validated['product_line_id'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
                'subcategory_id' => $validated['subcategory_id'] ?? null,
                'warehouse_id' => $validated['warehouse_id'],
                'sku' => $validated['sku'],
                'barcode' => $validated['barcode'] ?? null,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'show_in_pos' => $validated['show_in_pos'],
                'show_in_b2b' => $validated['show_in_b2b'],
                'show_in_b2c' => $validated['show_in_b2c'],
                'price_list_pos_id' => $validated['price_list_pos_id'] ?? null,
                'price_list_b2c_id' => $validated['price_list_b2c_id'] ?? null,
                'price_list_b2b_id' => $validated['price_list_b2b_id'] ?? null,
                'status' => 'A',
            ]);
            $product->company_id = $companyId;
            $product->gallery_images = [];
            $product->save();

            Log::debug('[Products] Datos recibidos en store', [
                'product_id' => $product->id,
                'company_id' => $companyId,
                'has_featured_image' => $request->hasFile('featured_image'),
                'featured_error' => $request->file('featured_image')?->getError(),
                'gallery_count' => count(array_filter($request->file('gallery_images', []) ?? [])),
            ]);

            Log::debug('[Products] Producto creado preliminarmente', [
                'product_id' => $product->id,
                'company_id' => $companyId,
            ]);

            $removeFeaturedImage = $request->boolean('remove_featured_image');

            if ($request->hasFile('featured_image')) {
                $product->featured_image_path = $this->storeFeaturedImage($request->file('featured_image'), $product);
            } elseif ($removeFeaturedImage && $product->featured_image_path) {
                $this->deletePublicFile($product->featured_image_path);
                $product->featured_image_path = null;
            }

            $newGalleryPaths = $this->storeGalleryImages($product, $request->file('gallery_images', []));
            if (! empty($newGalleryPaths)) {
                $product->gallery_images = $newGalleryPaths;
            }

            Log::debug('[Products] Galería procesada', [
                'product_id' => $product->id,
                'new_gallery' => $newGalleryPaths,
            ]);

            $product->save();

            ProductStock::query()->updateOrCreate(
                ['product_id' => $product->id, 'warehouse_id' => $validated['warehouse_id']],
                [
                    'quantity' => $validated['stock_quantity'],
                    'minimum_stock' => $validated['minimum_stock'] ?? 0,
                ]
            );

            $this->syncPrices($product, $validated['prices']);

            DB::commit();

            return redirect()
                ->route('inventory.products.index')
                ->with('status', 'success')
                ->with('message', gettext('El producto se creó correctamente.'));
        } catch (\Throwable $exception) {
            DB::rollBack();
            report($exception);

            throw ValidationException::withMessages([
                'message' => gettext('No fue posible guardar el producto. Inténtalo nuevamente.'),
            ]);
        }
    }

    public function show(Product $product): View
    {
        $product->load(['line', 'category', 'subcategory', 'warehouse', 'priceListPos', 'priceListB2c', 'priceListB2b']);

        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Detalle del producto',
            'description' => 'Consulta la información detallada del producto seleccionado.',
        ])->toArray();

        return view('Inventory.Products.Show', array_merge($this->formSharedData(), [
            'meta' => $meta,
            'mode' => 'view',
            'product' => $product,
            'productPayload' => $this->transformProduct($product, true),
        ]));
    }

    public function edit(Product $product): View
    {
        $product->load(['line', 'category', 'subcategory', 'warehouse', 'priceListPos', 'priceListB2c', 'priceListB2b']);

        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Editar producto',
            'description' => 'Actualiza la información, precios y visibilidad del producto.',
        ])->toArray();

        return view('Inventory.Products.Edit', array_merge($this->formSharedData($product), [
            'meta' => $meta,
            'mode' => 'edit',
            'product' => $product,
            'productPayload' => $this->transformProduct($product, true),
        ]));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            $originalWarehouseId = $product->warehouse_id;

            Log::debug('[Products] Datos recibidos en update', [
                'product_id' => $product->id,
                'company_id' => $product->company_id,
                'has_featured_image' => $request->hasFile('featured_image'),
                'featured_error' => $request->file('featured_image')?->getError(),
                'gallery_count' => count(array_filter($request->file('gallery_images', []) ?? [])),
                'remove_featured_image' => $request->boolean('remove_featured_image'),
            ]);

            $product->fill([
                'product_line_id' => $validated['product_line_id'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
                'subcategory_id' => $validated['subcategory_id'] ?? null,
                'warehouse_id' => $validated['warehouse_id'],
                'sku' => $validated['sku'],
                'barcode' => $validated['barcode'] ?? null,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'show_in_pos' => $validated['show_in_pos'],
                'show_in_b2b' => $validated['show_in_b2b'],
                'show_in_b2c' => $validated['show_in_b2c'],
                'price_list_pos_id' => $validated['price_list_pos_id'] ?? null,
                'price_list_b2c_id' => $validated['price_list_b2c_id'] ?? null,
                'price_list_b2b_id' => $validated['price_list_b2b_id'] ?? null,
            ]);

            $product->save();

            $removeFeaturedImage = $request->boolean('remove_featured_image');
            if ($request->hasFile('featured_image')) {
                Log::debug('[Products] Actualizando imagen destacada', ['product_id' => $product->id]);
                $product->featured_image_path = $this->storeFeaturedImage($request->file('featured_image'), $product);
            } elseif ($removeFeaturedImage && $product->featured_image_path) {
                Log::debug('[Products] Eliminando imagen destacada existente', ['product_id' => $product->id]);
                $this->deletePublicFile($product->featured_image_path);
                $product->featured_image_path = null;
            }

            $retainGallery = collect($validated['retain_gallery'] ?? []);
            $removedGallery = collect($validated['removed_gallery'] ?? []);
            $currentGallery = collect($product->gallery_images ?? []);
            $galleryToKeep = $currentGallery
                ->filter(function ($path) use ($retainGallery, $removedGallery) {
                    if ($removedGallery->contains($path)) {
                        return false;
                    }

                    if ($retainGallery->isNotEmpty()) {
                        return $retainGallery->contains($path);
                    }

                    return true;
                })
                ->values();
            $galleryToDelete = $currentGallery->diff($galleryToKeep);

            $galleryToDelete->each(fn ($path) => $this->deletePublicFile($path));

            $newGallery = $this->storeGalleryImages(
                $product,
                $request->file('gallery_images', []),
                max(0, 5 - $galleryToKeep->count())
            );
            Log::debug('[Products] Galería procesada en actualización', [
                'product_id' => $product->id,
                'requested_retained' => $retainGallery->values()->all(),
                'requested_removed' => $removedGallery->values()->all(),
                'retained' => $galleryToKeep->values()->all(),
                'new_gallery' => $newGallery,
                'removed' => $galleryToDelete->values()->all(),
            ]);

            $product->gallery_images = $galleryToKeep->merge($newGallery)->values()->all();

            $product->save();

            ProductStock::query()->updateOrCreate(
                ['product_id' => $product->id, 'warehouse_id' => $validated['warehouse_id']],
                [
                    'quantity' => $validated['stock_quantity'],
                    'minimum_stock' => $validated['minimum_stock'] ?? 0,
                ]
            );

            if ($originalWarehouseId && $originalWarehouseId !== $validated['warehouse_id']) {
                ProductStock::query()
                    ->where('product_id', $product->id)
                    ->where('warehouse_id', $originalWarehouseId)
                    ->delete();
            }

            $this->syncPrices($product, $validated['prices']);

            DB::commit();

            return redirect()
                ->route('inventory.products.index')
                ->with('status', 'success')
                ->with('message', gettext('El producto se actualizó correctamente.'));
        } catch (\Throwable $exception) {
            DB::rollBack();
            report($exception);

            throw ValidationException::withMessages([
                'message' => gettext('No fue posible actualizar el producto. Inténtalo nuevamente.'),
            ]);
        }
    }

    public function destroy(Product $product): JsonResponse
    {
        DB::beginTransaction();

        try {
            if ($product->featured_image_path) {
                $this->deletePublicFile($product->featured_image_path);
            }

            foreach ($product->gallery_images ?? [] as $path) {
                $this->deletePublicFile($path);
            }

            ItemPrice::query()
                ->where('item_type', 'product')
                ->where('item_id', $product->id)
                ->delete();

            ProductStock::query()
                ->where('product_id', $product->id)
                ->delete();

            $mediaDirectory = $this->productMediaDirectory($product);
            $deletedId = $product->id;
            $product->delete();

            $this->deletePublicDirectory($mediaDirectory);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => gettext('El producto se eliminó correctamente.'),
                'data' => ['deleted_id' => $deletedId],
            ]);
        } catch (\Throwable $exception) {
            DB::rollBack();
            report($exception);

            return response()->json([
                'status' => 'error',
                'message' => gettext('No fue posible eliminar el producto. Inténtalo nuevamente.'),
                'data' => null,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function toggleStatus(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['A', 'I', 'T'])],
        ]);

        $product->status = $validated['status'];
        $product->save();

        return response()->json([
            'status' => 'success',
            'message' => __('El estado se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformProduct($product->refresh()),
            ],
        ]);
    }

    private function transformProduct(Product $product, bool $detailed = false): array
    {
        $product->loadMissing(['line', 'category', 'subcategory', 'warehouse', 'priceListPos', 'priceListB2c', 'priceListB2b']);

        $stock = ProductStock::query()
            ->where('product_id', $product->id)
            ->first();

        $prices = ItemPrice::query()
            ->where('item_type', 'product')
            ->where('item_id', $product->id)
            ->get()
            ->map(fn (ItemPrice $price) => [
                'id' => $price->id,
                'price_list_id' => $price->price_list_id,
                'value' => (float) $price->value,
            ])
            ->values();

        $rawSku = strtoupper((string) $product->sku);
        $normalizedSku = preg_match('/^\d+$/', $rawSku)
            ? str_pad($rawSku, 8, '0', STR_PAD_LEFT)
            : $rawSku;

        $base = [
            'id' => $product->id,
            'sku' => $normalizedSku,
            'name' => $product->name,
            'line_name' => $product->line?->name,
            'category_name' => $product->category?->name,
            'subcategory_name' => $product->subcategory?->name,
            'warehouse_name' => $product->warehouse?->name,
            'barcode_label_url' => route('inventory.products.barcode', $product),
            'status' => $product->status,
            'status_label' => match ($product->status) {
                'A' => gettext('Activo'),
                'I' => gettext('Inactivo'),
                'T' => gettext('En papelera'),
                default => $product->status,
            },
            'visibility' => [
                'pos' => $product->show_in_pos,
                'b2b' => $product->show_in_b2b,
                'b2c' => $product->show_in_b2c,
            ],
            'visibility_label' => collect([
                $product->show_in_pos ? gettext('POS') : null,
                $product->show_in_b2b ? gettext('B2B') : null,
                $product->show_in_b2c ? gettext('B2C') : null,
            ])->filter()->implode(' · '),
            'stock_quantity' => $stock?->quantity ?? 0,
            'minimum_stock' => $stock?->minimum_stock ?? 0,
            'stock_status' => ($stock?->quantity ?? 0) <= ($stock?->minimum_stock ?? 0)
                ? gettext('Bajo stock')
                : gettext('En stock'),
            'featured_image_url' => $this->publicAsset($product->featured_image_path),
            'created_at' => $product->created_at?->toIso8601String(),
            'updated_at' => $product->updated_at?->toIso8601String(),
        ];

        if (! $detailed) {
            return $base;
        }

        return array_merge($base, [
            'barcode' => $product->barcode,
            'description' => $product->description,
            'product_line_id' => $product->product_line_id,
            'category_id' => $product->category_id,
            'subcategory_id' => $product->subcategory_id,
            'warehouse_id' => $product->warehouse_id,
            'price_list_pos_id' => $product->price_list_pos_id,
            'price_list_b2c_id' => $product->price_list_b2c_id,
            'price_list_b2b_id' => $product->price_list_b2b_id,
            'gallery' => collect($product->gallery_images ?? [])
                ->map(fn ($path) => [
                    'path' => $path,
                    'url' => $this->publicAsset($path),
                ])->values()->all(),
            'prices' => $prices,
        ]);
    }

    private function syncPrices(Product $product, array $prices): void
    {
        $priceListIds = collect($prices)->pluck('price_list_id')->all();

        ItemPrice::query()
            ->where('item_type', 'product')
            ->where('item_id', $product->id)
            ->whereNotIn('price_list_id', $priceListIds)
            ->delete();

        foreach ($prices as $price) {
            ItemPrice::query()->updateOrCreate(
                [
                    'item_type' => 'product',
                    'item_id' => $product->id,
                    'price_list_id' => $price['price_list_id'],
                ],
                [
                    'value' => $price['value'],
                    'status' => 'A',
                ]
            );
        }
    }

    private function productMediaDirectory(Product $product): string
    {
        return "products/{$product->company_id}/{$product->id}";
    }

    private function ensurePublicDirectory(string $relativeDirectory): void
    {
        $fullPath = public_path($relativeDirectory);
        if (! File::exists($fullPath)) {
            File::makeDirectory($fullPath, 0755, true);
        }

        $storagePath = storage_path("app/public/{$relativeDirectory}");
        if (! File::exists($storagePath)) {
            File::makeDirectory($storagePath, 0755, true);
        }
    }

    private function deletePublicFile(?string $relativePath): void
    {
        if (! $relativePath) {
            return;
        }

        $fullPath = public_path($relativePath);
        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }

        $storagePath = storage_path("app/public/{$relativePath}");
        if (File::exists($storagePath)) {
            File::delete($storagePath);
        }
    }

    private function deletePublicDirectory(string $relativeDirectory): void
    {
        $fullPath = public_path($relativeDirectory);
        if (File::exists($fullPath)) {
            File::deleteDirectory($fullPath);
        }

        $storagePath = storage_path("app/public/{$relativeDirectory}");
        if (File::exists($storagePath)) {
            File::deleteDirectory($storagePath);
        }
    }

    private function publicAsset(?string $relativePath): ?string
    {
        if (! $relativePath) {
            return null;
        }

        $publicPath = public_path($relativePath);
        if (! File::exists($publicPath)) {
            $storagePath = storage_path("app/public/{$relativePath}");
            if (File::exists($storagePath)) {
                $directory = dirname($relativePath);
                if ($directory !== '.' && $directory !== DIRECTORY_SEPARATOR) {
                    $this->ensurePublicDirectory($directory);
                }
                File::copy($storagePath, $publicPath);
            }
        }

        return asset($relativePath);
    }

    private function storeFeaturedImage(?UploadedFile $file, Product $product): ?string
    {
        if (! $file) {
            return $product->featured_image_path;
        }

        $directory = $this->productMediaDirectory($product);
        $this->ensurePublicDirectory($directory);
        Log::debug('[Products] Asegurando directorio de imagen destacada', [
            'product_id' => $product->id,
            'directory' => $directory,
            'created' => File::exists(public_path($directory)),
        ]);

        if ($product->featured_image_path) {
            $this->deletePublicFile($product->featured_image_path);
        }

        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $filename = 'featured-'.Str::uuid().'.'.$extension;

        $file->move(public_path($directory), $filename);
        $storedPath = "{$directory}/{$filename}";
        Log::debug('[Products] Imagen destacada almacenada', [
            'product_id' => $product->id,
            'path' => $storedPath,
        ]);

        return $storedPath;
    }

    private function storeGalleryImages(Product $product, array $files, int $maxSlots = 5): array
    {
        if ($maxSlots <= 0) {
            return [];
        }

        $directory = $this->productMediaDirectory($product).'/gallery';
        $this->ensurePublicDirectory($directory);
        Log::debug('[Products] Asegurando directorio de galería', [
            'product_id' => $product->id,
            'directory' => $directory,
            'created' => File::exists(public_path($directory)),
        ]);

        $stored = collect($files)
            ->filter()
            ->take($maxSlots)
            ->map(function (UploadedFile $file) use ($directory) {
                $extension = $file->getClientOriginalExtension() ?: 'jpg';
                $filename = Str::uuid().'.'.$extension;

                $file->move(public_path($directory), $filename);

                return "{$directory}/{$filename}";
            })
            ->values()
            ->all();

        Log::debug('[Products] Imágenes de galería almacenadas', [
            'product_id' => $product->id,
            'stored' => $stored,
        ]);

        return $stored;
    }

    public function barcodeLabel(Product $product): Response
    {
        $barcodeValue = strtoupper((string) $product->sku);

        if ($barcodeValue === '') {
            abort(404, gettext('El producto no tiene SKU configurado.'));
        }

        $generator = new BarcodeGeneratorPNG();
        $barcodeImage = base64_encode(
            $generator->getBarcode($barcodeValue, BarcodeGeneratorPNG::TYPE_CODE_128)
        );

        $html = view('Inventory.Products.BarcodeLabel', [
            'product' => $product,
            'barcodeValue' => $barcodeValue,
            'barcodeImage' => $barcodeImage,
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->setDefaultFont('Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper([0, 0, 175.68, 62.64]); // 2.44 in x 0.87 in (Landscape)
        $dompdf->render();

        $output = $dompdf->output();
        if (! empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
            header_remove('Transfer-Encoding');
        }

        return response($output, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="barcode-'.$barcodeValue.'.pdf"',
            'Content-Length' => strlen($output),
        ]);
    }

    private function formSharedData(?Product $product = null): array
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        // Si el usuario no tiene compañía (super admin), obtener la primera compañía disponible
        if (!$companyId) {
            $company = Company::query()->first();
            $companyId = $company?->id;
        }

        // Si aún no hay company_id, retornar colecciones vacías
        if (!$companyId) {
            return [
                'lines' => collect([]),
                'categories' => collect([]),
                'subcategories' => collect([]),
                'warehouses' => collect([]),
                'priceLists' => collect([]),
                'stock' => null,
            ];
        }

        $lines = ProductLine::query()
            ->where('company_id', $companyId)
            ->where('status', 'A')
            ->orderBy('name')
            ->get(['id', 'name']);

        $categories = ProductCategory::query()
            ->where('company_id', $companyId)
            ->whereNull('parent_id')
            ->where('status', 'A')
            ->orderBy('name')
            ->get(['id', 'name', 'product_line_id']);

        $subcategories = ProductCategory::query()
            ->where('company_id', $companyId)
            ->whereNotNull('parent_id')
            ->where('status', 'A')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id', 'product_line_id']);

        $warehouses = Warehouse::query()
            ->where('company_id', $companyId)
            ->where('status', 'A')
            ->orderBy('name')
            ->get(['id', 'name']);

        $priceLists = PriceList::query()
            ->where(function ($query) use ($companyId): void {
                $query->whereNull('company_id')
                    ->orWhere('company_id', $companyId);
            })
            ->where('status', 'A')
            ->orderBy('name')
            ->get(['id', 'name']);

        $stock = null;
        if ($product) {
            $stock = ProductStock::query()
                ->where('product_id', $product->id)
                ->where('warehouse_id', $product->warehouse_id)
                ->first();
        }

        return compact('lines', 'categories', 'subcategories', 'warehouses', 'priceLists', 'stock');
    }
}

