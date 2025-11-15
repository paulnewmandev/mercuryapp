<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use App\Models\ItemPrice;
use App\Models\Product;
use App\Models\WorkshopOrder;
use App\Models\WorkshopOrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkshopOrderItemController extends Controller
{
    public function index(WorkshopOrder $workshopOrder): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        if (! $companyId) {
            $company = \App\Models\Company::query()->first();
            $companyId = $company?->id;
        }

        if ($companyId && $workshopOrder->company_id !== $companyId) {
            abort(404);
        }

        $items = $workshopOrder->items()
            ->with('product:id,name,sku')
            ->where('status', 'A')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => gettext('Productos obtenidos correctamente.'),
            'data' => [
                'items' => $items->map(fn (WorkshopOrderItem $item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name ?? '',
                    'product_sku' => $item->product?->sku ?? '',
                    'quantity' => $item->quantity,
                    'unit_price' => (string) $item->unit_price,
                    'subtotal' => (string) $item->subtotal,
                    'notes' => $item->notes,
                    'created_at' => $item->created_at?->toIso8601String(),
                ]),
            ],
        ]);
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        if (! $companyId) {
            $company = \App\Models\Company::query()->first();
            $companyId = $company?->id;
        }

        if (! $companyId) {
            return response()->json([
                'status' => 'success',
                'message' => gettext('Productos obtenidos correctamente.'),
                'data' => ['items' => []],
            ]);
        }

        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();
        $limit = (int) $request->integer('limit', 20);
        $limit = $limit > 0 ? min($limit, 50) : 20;

        $query = Product::query()
            ->where('company_id', $companyId)
            ->where('status', 'A')
            ->orderBy('name');

        if ($search->isNotEmpty()) {
            $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
            $grammar = DB::query()->getGrammar();

            $tokens = collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();

            $searchableColumns = ['name', 'sku', 'barcode', 'description'];

            $query->where(function ($outer) use ($tokens, $searchableColumns, $accentInsensitiveCollation, $grammar): void {
                $tokens->each(function (string $token) use ($outer, $searchableColumns, $accentInsensitiveCollation, $grammar): void {
                    $outer->where(function ($inner) use ($token, $searchableColumns, $accentInsensitiveCollation, $grammar): void {
                        foreach ($searchableColumns as $column) {
                            $wrapped = $grammar->wrap($column);
                            $inner->orWhereRaw(
                                "LOWER(CONVERT({$wrapped} USING utf8mb4) COLLATE {$accentInsensitiveCollation}) LIKE ?",
                                ["%{$token}%"]
                            );
                        }
                    });
                });
            });
        }

        $products = $query->limit($limit)->get(['id', 'name', 'sku', 'barcode']);

        $items = $products->map(function (Product $product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => gettext('Productos obtenidos correctamente.'),
            'data' => ['items' => $items],
        ]);
    }

    public function store(Request $request, WorkshopOrder $workshopOrder): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        if (! $companyId) {
            $company = \App\Models\Company::query()->first();
            $companyId = $company?->id;
        }

        if ($companyId && $workshopOrder->company_id !== $companyId) {
            abort(404);
        }

        $validated = $request->validate([
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $item = new WorkshopOrderItem($validated);
        $item->order_id = $workshopOrder->id;
        $item->company_id = $workshopOrder->company_id;
        $item->status = 'A';
        // subtotal is a generated column, so we don't set it manually
        $item->save();

        // Recalcular costos totales
        $this->recalculateOrderCosts($workshopOrder);

        $item->load('product:id,name,sku');

        return response()->json([
            'status' => 'success',
            'message' => gettext('El producto se agregó correctamente.'),
            'data' => [
                'item' => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name ?? '',
                    'product_sku' => $item->product?->sku ?? '',
                    'quantity' => $item->quantity,
                    'unit_price' => (string) $item->unit_price,
                    'subtotal' => (string) $item->subtotal,
                    'notes' => $item->notes,
                    'created_at' => $item->created_at?->toIso8601String(),
                ],
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(Request $request, WorkshopOrder $workshopOrder, WorkshopOrderItem $item): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        if (! $companyId) {
            $company = \App\Models\Company::query()->first();
            $companyId = $company?->id;
        }

        if ($companyId && $workshopOrder->company_id !== $companyId) {
            abort(404);
        }

        if ($item->order_id !== $workshopOrder->id) {
            abort(404);
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $item->fill($validated);
        // subtotal is a generated column, so we don't set it manually
        $item->save();

        // Recalcular costos totales
        $this->recalculateOrderCosts($workshopOrder);

        $item->load('product:id,name,sku');

        return response()->json([
            'status' => 'success',
            'message' => gettext('El producto se actualizó correctamente.'),
            'data' => [
                'item' => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name ?? '',
                    'product_sku' => $item->product?->sku ?? '',
                    'quantity' => $item->quantity,
                    'unit_price' => (string) $item->unit_price,
                    'subtotal' => (string) $item->subtotal,
                    'notes' => $item->notes,
                    'created_at' => $item->created_at?->toIso8601String(),
                ],
            ],
        ]);
    }

    public function destroy(WorkshopOrder $workshopOrder, WorkshopOrderItem $item): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        if (! $companyId) {
            $company = \App\Models\Company::query()->first();
            $companyId = $company?->id;
        }

        if ($companyId && $workshopOrder->company_id !== $companyId) {
            abort(404);
        }

        if ($item->order_id !== $workshopOrder->id) {
            abort(404);
        }

        $item->status = 'T';
        $item->save();

        // Recalcular costos totales
        $this->recalculateOrderCosts($workshopOrder);

        return response()->json([
            'status' => 'success',
            'message' => gettext('El producto se eliminó correctamente.'),
            'data' => [
                'deleted_id' => $item->id,
            ],
        ]);
    }

    private function recalculateOrderCosts(WorkshopOrder $order): void
    {
        // Calcular total de productos
        $itemsTotal = $order->items()
            ->where('status', 'A')
            ->sum('subtotal');

        // Calcular total de servicios
        $servicesTotal = $order->services()
            ->where('status', 'A')
            ->sum('subtotal');

        // Calcular total pagado en abonos
        $advancesTotal = $order->advances()
            ->where('status', 'A')
            ->sum('amount');

        // Calcular costos totales
        $totalCost = $itemsTotal + $servicesTotal;
        $totalPaid = $advancesTotal;
        $balance = $totalCost - $totalPaid;

        // Actualizar la orden
        $order->total_cost = $totalCost;
        $order->total_paid = $totalPaid;
        $order->balance = $balance;
        $order->save();
    }

    public function getProductPrice(Request $request, Product $product): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        if (!$companyId) {
            $company = \App\Models\Company::query()->first();
            $companyId = $company?->id;
        }

        // Validar que el producto pertenezca a la compañía del usuario
        if ($companyId && $product->company_id !== $companyId) {
            return response()->json([
                'status' => 'error',
                'message' => gettext('El producto solicitado no existe.'),
            ], 404);
        }

        $priceListId = $request->string('price_list_id')->toString();

        $price = 0;
        if ($priceListId) {
            $itemPrice = ItemPrice::query()
                ->where('item_id', $product->id)
                ->where('item_type', 'product')
                ->where('price_list_id', $priceListId)
                ->where('status', 'A')
                ->first();

            if ($itemPrice) {
                $price = (float) $itemPrice->value;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => gettext('Precio obtenido correctamente.'),
            'data' => [
                'price' => (string) $price,
            ],
        ]);
    }

    private function resolveAccentInsensitiveCollation(): string
    {
        $defaultConnection = config('database.default');
        $connectionConfig = config("database.connections.{$defaultConnection}", []);

        return $connectionConfig['search_collation']
            ?? $connectionConfig['collation']
            ?? 'utf8mb4_0900_ai_ci';
    }
}
