<?php

namespace App\Http\Controllers\Inventory;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\ProductTransfers\StoreProductTransferRequest;
use App\Http\Requests\Inventory\ProductTransfers\UpdateProductTransferRequest;
use App\Models\Product;
use App\Models\ProductTransfer;
use App\Models\Warehouse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductTransferController extends Controller
{
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Movimientos de inventario',
            'description' => 'Gestiona los movimientos de productos entre bodegas.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Inventario')],
            ['label' => gettext('Movimientos')],
        ];

        $companyId = Auth::user()?->company_id;

        $warehouses = Warehouse::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->orderBy('name')
            ->get(['id', 'name']);

        $products = Product::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        return view('Inventory.ProductTransfers', [
            'meta' => $meta,
            'breadcrumbItems' => $breadcrumbItems,
            'warehouses' => $warehouses,
            'products' => $products,
        ]);
    }

    public function create(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Nuevo movimiento',
            'description' => 'Crear nuevo movimiento de inventario.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Inventario'), 'url' => route('inventory.product_transfers.index')],
            ['label' => gettext('Movimientos'), 'url' => route('inventory.product_transfers.index')],
            ['label' => gettext('Nuevo')],
        ];

        $companyId = Auth::user()?->company_id;

        $warehouses = Warehouse::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->orderBy('name')
            ->get(['id', 'name']);

        $products = Product::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        return view('Inventory.ProductTransfers.Create', [
            'meta' => $meta,
            'breadcrumbItems' => $breadcrumbItems,
            'warehouses' => $warehouses,
            'products' => $products,
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $sortBy = $request->string('sort_by', 'movement_date')->toString();
        $sortDirection = Str::lower($request->string('sort_direction', 'desc')->toString()) === 'asc' ? 'asc' : 'desc';
        $perPage = (int) $request->integer('per_page', 10);
        $search = Str::of($request->string('search')->toString())->replaceMatches('/\s+/', ' ')->trim();

        $allowedSorts = ['movement_date', 'reference', 'created_at'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'movement_date';
            $sortDirection = 'desc';
        }

        $allowedPerPage = [5, 10, 20, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $accentInsensitiveCollation = config('database.connections.mysql.search_collation', 'utf8mb4_0900_ai_ci');
        $tokens = $search->isEmpty()
            ? collect()
            : collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();

        $query = ProductTransfer::query()
            ->with(['originWarehouse', 'destinationWarehouse'])
            ->when($tokens->isNotEmpty(), function ($builder) use ($tokens, $accentInsensitiveCollation): void {
                $grammar = DB::query()->getGrammar();
                $builder->where(function ($outer) use ($tokens, $accentInsensitiveCollation, $grammar): void {
                    $tokens->each(function (string $token) use ($outer, $accentInsensitiveCollation, $grammar): void {
                        $outer->where(function ($inner) use ($token, $accentInsensitiveCollation, $grammar): void {
                            $inner->orWhereRaw(
                                "LOWER(CONVERT(reference USING utf8mb4) COLLATE {$accentInsensitiveCollation}) LIKE ?",
                                ["%{$token}%"]
                            )
                                ->orWhereRaw(
                                    "LOWER(CONVERT(notes USING utf8mb4) COLLATE {$accentInsensitiveCollation}) LIKE ?",
                                    ["%{$token}%"]
                                )
                                ->orWhereRaw(
                                    "LOWER(CONVERT((SELECT name FROM warehouses WHERE warehouses.id = product_transfers.origin_warehouse_id) USING utf8mb4) COLLATE {$accentInsensitiveCollation}) LIKE ?",
                                    ["%{$token}%"]
                                )
                                ->orWhereRaw(
                                    "LOWER(CONVERT((SELECT name FROM warehouses WHERE warehouses.id = product_transfers.destination_warehouse_id) USING utf8mb4) COLLATE {$accentInsensitiveCollation}) LIKE ?",
                                    ["%{$token}%"]
                                );
                        });
                    });
                });
            })
            ->orderBy($sortBy, $sortDirection);

        $paginator = $query
            ->select('product_transfers.*')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (ProductTransfer $transfer) => $this->transformTransfer($transfer));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Movimientos obtenidos correctamente.'),
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

    public function show(ProductTransfer $productTransfer): View|JsonResponse
    {
        $productTransfer->load([
            'originWarehouse',
            'destinationWarehouse',
            'items.product',
        ]);

        // Si es una petición AJAX, devolver JSON
        if (request()->expectsJson() || request()->wantsJson()) {
        return response()->json([
            'status' => 'success',
            'message' => gettext('Movimiento obtenido correctamente.'),
            'data' => [
                'item' => $this->transformTransfer($productTransfer, true),
            ],
        ]);
    }

        // Si es una petición normal, devolver vista
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Detalle del movimiento',
            'description' => 'Detalle del movimiento de inventario.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Inventario'), 'url' => route('inventory.product_transfers.index')],
            ['label' => gettext('Movimientos'), 'url' => route('inventory.product_transfers.index')],
            ['label' => gettext('Detalle')],
        ];

        $transferData = $this->transformTransfer($productTransfer, true);

        return view('Inventory.ProductTransfers.Show', [
            'meta' => $meta,
            'breadcrumbItems' => $breadcrumbItems,
            'transfer' => $productTransfer,
            'transferData' => $transferData,
        ]);
    }

    public function store(StoreProductTransferRequest $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        // Si el usuario no tiene compañía (super admin), obtener la primera compañía disponible
        if (! $companyId) {
            $company = \App\Models\Company::query()->first();
            $companyId = $company?->id;
        }

        if (! $companyId) {
            return response()->json([
                'status' => 'error',
                'message' => gettext('No se pudo determinar la compañía para el movimiento.'),
            ], 422);
        }

        $data = $request->validated();

        $transfer = null;

        DB::transaction(function () use (&$transfer, $companyId, $data): void {
            $transfer = ProductTransfer::query()->create([
                'company_id' => $companyId,
                'origin_warehouse_id' => $data['origin_warehouse_id'],
                'destination_warehouse_id' => $data['destination_warehouse_id'],
                'movement_date' => $data['movement_date'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'A',
            ]);

            foreach ($data['items'] as $item) {
                $transfer->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }
        });

        $transfer->load(['originWarehouse', 'destinationWarehouse', 'items.product']);

        // Si es una petición AJAX, devolver JSON
        if ($request->expectsJson() || $request->wantsJson()) {
        return response()->json([
            'status' => 'success',
            'message' => gettext('El movimiento se registró correctamente.'),
            'data' => [
                'item' => $this->transformTransfer($transfer, true),
            ],
        ], Response::HTTP_CREATED);
        }

        // Si es una petición normal, redirigir a la vista
        return redirect()
            ->route('inventory.product_transfers.show', $transfer)
            ->with('success', gettext('El movimiento se registró correctamente.'));
    }

    public function edit(ProductTransfer $productTransfer): View
    {
        $productTransfer->load([
            'originWarehouse',
            'destinationWarehouse',
            'items.product',
        ]);

        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Editar movimiento',
            'description' => 'Editar movimiento de inventario.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Inventario'), 'url' => route('inventory.product_transfers.index')],
            ['label' => gettext('Movimientos'), 'url' => route('inventory.product_transfers.index')],
            ['label' => gettext('Editar')],
        ];

        $companyId = Auth::user()?->company_id;

        $warehouses = Warehouse::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->orderBy('name')
            ->get(['id', 'name']);

        $products = Product::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $transferData = $this->transformTransfer($productTransfer, true);

        return view('Inventory.ProductTransfers.Edit', [
            'meta' => $meta,
            'breadcrumbItems' => $breadcrumbItems,
            'transfer' => $productTransfer,
            'transferData' => $transferData,
            'warehouses' => $warehouses,
            'products' => $products,
        ]);
    }

    public function update(
        UpdateProductTransferRequest $request,
        ProductTransfer $productTransfer
    ): JsonResponse|RedirectResponse {
        $data = $request->validated();

        DB::transaction(function () use ($productTransfer, $data): void {
            $productTransfer->fill([
                'origin_warehouse_id' => $data['origin_warehouse_id'],
                'destination_warehouse_id' => $data['destination_warehouse_id'],
                'movement_date' => $data['movement_date'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ])->save();

            $productTransfer->items()->delete();

            foreach ($data['items'] as $item) {
                $productTransfer->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }
        });

        $productTransfer->load(['originWarehouse', 'destinationWarehouse', 'items.product']);

        // Si es una petición AJAX, devolver JSON
        if ($request->expectsJson() || $request->wantsJson()) {
        return response()->json([
            'status' => 'success',
            'message' => gettext('El movimiento se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformTransfer($productTransfer, true),
            ],
        ]);
        }

        // Si es una petición normal, redirigir a la vista
        return redirect()
            ->route('inventory.product_transfers.show', $productTransfer)
            ->with('success', gettext('El movimiento se actualizó correctamente.'));
    }

    public function destroy(ProductTransfer $productTransfer): JsonResponse
    {
        $deletedId = $productTransfer->id;
        $productTransfer->delete();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El movimiento se eliminó correctamente.'),
            'data' => [
                'deleted_id' => $deletedId,
            ],
        ]);
    }

    private function transformTransfer(ProductTransfer $transfer, bool $withItems = false): array
    {
        $transfer->loadMissing(['originWarehouse', 'destinationWarehouse']);

        $payload = [
            'id' => $transfer->id,
            'movement_date' => optional($transfer->movement_date)?->toDateString(),
            'movement_date_formatted' => optional($transfer->movement_date)?->format('d/m/Y'),
            'reference' => $transfer->reference,
            'notes' => $transfer->notes,
            'status' => $transfer->status,
            'status_label' => $transfer->status_label,
            'is_active' => $transfer->status === 'A',
            'display_name' => $transfer->reference ?: $transfer->movement_date?->format('d/m/Y') ?: $transfer->id,
            'origin_warehouse_id' => $transfer->origin_warehouse_id,
            'origin_warehouse_name' => $transfer->originWarehouse?->name,
            'destination_warehouse_id' => $transfer->destination_warehouse_id,
            'destination_warehouse_name' => $transfer->destinationWarehouse?->name,
            'items_count' => (int) $transfer->items_count,
            'created_at' => optional($transfer->created_at)?->toIso8601String(),
        ];

        if ($withItems) {
            $transfer->loadMissing('items.product');
            $payload['items'] = $transfer->items->map(static function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name,
                    'product_sku' => $item->product?->sku,
                    'quantity' => $item->quantity,
                    'notes' => $item->notes,
                ];
            })->values();
        }

        return $payload;
    }
}

