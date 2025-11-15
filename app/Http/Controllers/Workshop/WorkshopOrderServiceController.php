<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\WorkshopOrder;
use App\Models\WorkshopOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkshopOrderServiceController extends Controller
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

        $services = $workshopOrder->services()
            ->with('service:id,name,description')
            ->where('status', 'A')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => gettext('Servicios obtenidos correctamente.'),
            'data' => [
                'items' => $services->map(fn (WorkshopOrderService $service) => [
                    'id' => $service->id,
                    'service_id' => $service->service_id,
                    'service_name' => $service->service?->name ?? '',
                    'quantity' => $service->quantity,
                    'unit_price' => (string) $service->unit_price,
                    'subtotal' => (string) $service->subtotal,
                    'notes' => $service->notes,
                    'created_at' => $service->created_at?->toIso8601String(),
                ]),
            ],
        ]);
    }

    public function searchServices(Request $request): JsonResponse
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
                'message' => gettext('Servicios obtenidos correctamente.'),
                'data' => ['items' => []],
            ]);
        }

        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();
        $categoryId = $request->string('category_id')->toString();
        $limit = (int) $request->integer('limit', 20);
        $limit = $limit > 0 ? min($limit, 50) : 20;

        $query = Service::query()
            ->where('company_id', $companyId)
            ->where('status', 'A')
            ->when($categoryId !== '', fn ($builder) => $builder->where('category_id', $categoryId))
            ->orderBy('name');

        if ($search->isNotEmpty()) {
            $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
            $grammar = DB::query()->getGrammar();

            $tokens = collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();

            $searchableColumns = ['name', 'description'];

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

        $services = $query->limit($limit)->get(['id', 'name', 'description', 'price', 'currency']);

        $items = $services->map(function (Service $service) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'price' => (string) ($service->price ?? 0),
                'currency' => $service->currency ?? 'USD',
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => gettext('Servicios obtenidos correctamente.'),
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
            'service_id' => ['required', 'uuid', 'exists:services,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $service = new WorkshopOrderService($validated);
        $service->order_id = $workshopOrder->id;
        $service->company_id = $workshopOrder->company_id;
        $service->status = 'A';
        // subtotal is a generated column, so we don't set it manually
        $service->save();

        // Recalcular costos totales
        $this->recalculateOrderCosts($workshopOrder);

        $service->load('service:id,name,description');

        return response()->json([
            'status' => 'success',
            'message' => gettext('El servicio se agregó correctamente.'),
            'data' => [
                'item' => [
                    'id' => $service->id,
                    'service_id' => $service->service_id,
                    'service_name' => $service->service?->name ?? '',
                    'quantity' => $service->quantity,
                    'unit_price' => (string) $service->unit_price,
                    'subtotal' => (string) $service->subtotal,
                    'notes' => $service->notes,
                    'created_at' => $service->created_at?->toIso8601String(),
                ],
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(Request $request, WorkshopOrder $workshopOrder, WorkshopOrderService $service): JsonResponse
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

        if ($service->order_id !== $workshopOrder->id) {
            abort(404);
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $service->fill($validated);
        // subtotal is a generated column, so we don't set it manually
        $service->save();

        // Recalcular costos totales
        $this->recalculateOrderCosts($workshopOrder);

        $service->load('service:id,name,description');

        return response()->json([
            'status' => 'success',
            'message' => gettext('El servicio se actualizó correctamente.'),
            'data' => [
                'item' => [
                    'id' => $service->id,
                    'service_id' => $service->service_id,
                    'service_name' => $service->service?->name ?? '',
                    'quantity' => $service->quantity,
                    'unit_price' => (string) $service->unit_price,
                    'subtotal' => (string) $service->subtotal,
                    'notes' => $service->notes,
                    'created_at' => $service->created_at?->toIso8601String(),
                ],
            ],
        ]);
    }

    public function destroy(WorkshopOrder $workshopOrder, WorkshopOrderService $service): JsonResponse
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

        if ($service->order_id !== $workshopOrder->id) {
            abort(404);
        }

        $service->status = 'T';
        $service->save();

        // Recalcular costos totales
        $this->recalculateOrderCosts($workshopOrder);

        return response()->json([
            'status' => 'success',
            'message' => gettext('El servicio se eliminó correctamente.'),
            'data' => [
                'deleted_id' => $service->id,
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

    private function resolveAccentInsensitiveCollation(): string
    {
        $defaultConnection = config('database.default');
        $connectionConfig = config("database.connections.{$defaultConnection}", []);

        return $connectionConfig['search_collation']
            ?? $connectionConfig['collation']
            ?? 'utf8mb4_0900_ai_ci';
    }
}
