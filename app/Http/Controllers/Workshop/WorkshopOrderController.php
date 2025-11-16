<?php

namespace App\Http\Controllers\Workshop;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workshop\Orders\StoreWorkshopOrderRequest;
use App\Http\Requests\Workshop\Orders\UpdateWorkshopOrderRequest;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use App\Models\WorkshopAccessory;
use App\Models\WorkshopEquipment;
use App\Models\WorkshopOrder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Dompdf\Dompdf;
use Dompdf\Options;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WorkshopOrderController extends Controller
{
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Órdenes de trabajo',
            'description' => 'Gestiona las órdenes de trabajo del taller, asigna responsables y controla los accesorios entregados.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Taller')],
            ['label' => gettext('Órdenes de trabajo')],
        ];

        $user = Auth::user();
        $companyId = $user?->company_id;

        // Si el usuario no tiene compañía (super admin), obtener la primera compañía disponible
        if (! $companyId) {
            $company = \App\Models\Company::query()->first();
            $companyId = $company?->id;
        }

        if (!$companyId) {
            $cards = [
                [
                    'label' => gettext('Total de órdenes'),
                    'value' => '0',
                    'icon' => 'fa-solid fa-clipboard-list',
                    'trend' => __('Activas: :count', ['count' => '0']),
                ],
                [
                    'label' => gettext('En proceso'),
                    'value' => '0',
                    'icon' => 'fa-solid fa-gear',
                    'trend' => __('Pendientes: :count', ['count' => '0']),
                ],
                [
                    'label' => gettext('Completadas'),
                    'value' => '0',
                    'icon' => 'fa-solid fa-check-circle',
                    'trend' => __('Urgentes: :count', ['count' => '0']),
                ],
            ];

            return view('Workshop.Orders', [
                'meta' => $meta,
                'breadcrumbItems' => $breadcrumbItems,
                'cards' => $cards,
            ]);
        }

        $baseQuery = WorkshopOrder::query()->where('company_id', $companyId);

        $totalOrders = (clone $baseQuery)->count();
        $activeOrders = (clone $baseQuery)->where('status', 'A')->count();

        // Órdenes en proceso (con estados que no sean "Completada" o "Cancelada")
        $inProcessOrders = (clone $baseQuery)
            ->where('status', 'A')
            ->whereHas('state', function ($q) {
                $q->whereNotIn('name', ['Completada', 'Cancelada', 'Finalizada']);
            })
            ->count();

        // Órdenes completadas
        $completedOrders = (clone $baseQuery)
            ->where('status', 'A')
            ->whereHas('state', function ($q) {
                $q->whereIn('name', ['Completada', 'Finalizada']);
            })
            ->count();

        // Órdenes urgentes (prioridad Urgente)
        $urgentOrders = (clone $baseQuery)
            ->where('status', 'A')
            ->where('priority', 'Urgente')
            ->count();

        // Órdenes pendientes (prioridad Normal o Alta)
        $pendingOrders = (clone $baseQuery)
            ->where('status', 'A')
            ->where('priority', '!=', 'Urgente')
            ->whereHas('state', function ($q) {
                $q->whereNotIn('name', ['Completada', 'Cancelada', 'Finalizada']);
            })
            ->count();

        $cards = [
            [
                'label' => gettext('Total de órdenes'),
                'value' => number_format($totalOrders),
                'icon' => 'fa-solid fa-clipboard-list',
                'trend' => __('Activas: :count', ['count' => number_format($activeOrders)]),
            ],
            [
                'label' => gettext('En proceso'),
                'value' => number_format($inProcessOrders),
                'icon' => 'fa-solid fa-gear',
                'trend' => __('Pendientes: :count', ['count' => number_format($pendingOrders)]),
            ],
            [
                'label' => gettext('Completadas'),
                'value' => number_format($completedOrders),
                'icon' => 'fa-solid fa-check-circle',
                'trend' => __('Urgentes: :count', ['count' => number_format($urgentOrders)]),
            ],
        ];

        // Cargar categorías con sus estados para los tabs
        $categories = \App\Models\WorkshopCategory::query()
            ->where('company_id', $companyId)
            ->where('status', 'A')
            ->with(['states' => function ($q) {
                $q->where('status', 'A')->orderBy('name');
            }])
            ->orderBy('name')
            ->get();

        return view('Workshop.Orders', [
            'meta' => $meta,
            'breadcrumbItems' => $breadcrumbItems,
            'cards' => $cards,
            'categories' => $categories,
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        // Si el usuario no tiene compañía (super admin), obtener la primera compañía disponible
        if (! $companyId) {
            $company = \App\Models\Company::query()->first();
            $companyId = $company?->id;
            
            // Si no hay compañía, retornar vacío
            if (! $companyId) {
                return response()->json([
                    'status' => 'success',
                    'message' => gettext('Órdenes obtenidas correctamente.'),
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
        }

        $sortBy = $request->string('sort_by', 'created_at')->toString();
        $sortDirection = Str::lower($request->string('sort_direction', 'desc')->toString()) === 'asc' ? 'asc' : 'desc';
        $perPage = (int) $request->integer('per_page', 10);
        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();

        $allowedSorts = [
            'created_at',
            'order_number',
            'customer_name',
            'equipment_label',
            'priority',
            'state_name',
        ];

        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        $allowedPerPage = [5, 10, 20, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        // Filtros por categoría y estado
        $categoryId = $request->string('category_id')->isNotEmpty() ? $request->string('category_id')->toString() : null;
        $stateId = $request->string('state_id')->isNotEmpty() ? $request->string('state_id')->toString() : null;

        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
        $grammar = DB::query()->getGrammar();

        $tokens = $search->isEmpty()
            ? collect()
            : collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();

        $searchableColumns = [
            'workshop_orders.order_number',
            'workshop_orders.note',
            'customers.first_name',
            'customers.last_name',
            'customers.business_name',
            'customers.document_number',
            'workshop_equipments.identifier',
            'workshop_categories.name',
            'workshop_states.name',
            'users.first_name',
            'users.last_name',
            'workshop_accessories.name',
        ];

        $query = WorkshopOrder::query()
            ->select('workshop_orders.*')
            ->with([
                'customer',
                'equipment.brand',
                'equipment.model',
                'category',
                'state',
                'responsible',
                'accessories',
            ])
            ->leftJoin('customers', 'workshop_orders.customer_id', '=', 'customers.id')
            ->leftJoin('workshop_equipments', 'workshop_orders.equipment_id', '=', 'workshop_equipments.id')
            ->leftJoin('workshop_categories', 'workshop_orders.category_id', '=', 'workshop_categories.id')
            ->leftJoin('workshop_states', 'workshop_orders.state_id', '=', 'workshop_states.id')
            ->leftJoin('users', 'workshop_orders.responsible_user_id', '=', 'users.id')
            ->leftJoin('workshop_order_accessory', 'workshop_orders.id', '=', 'workshop_order_accessory.order_id')
            ->leftJoin('workshop_accessories', 'workshop_order_accessory.accessory_id', '=', 'workshop_accessories.id')
            ->where('workshop_orders.company_id', $companyId)
            ->when($categoryId, function ($builder) use ($categoryId): void {
                $builder->where('workshop_orders.category_id', $categoryId);
            })
            ->when($stateId, function ($builder) use ($stateId): void {
                $builder->where('workshop_orders.state_id', $stateId);
            })
            ->when($tokens->isNotEmpty(), function ($builder) use ($tokens, $searchableColumns, $accentInsensitiveCollation, $grammar): void {
                $builder->where(function ($outer) use ($tokens, $searchableColumns, $accentInsensitiveCollation, $grammar): void {
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
            })
            ->distinct('workshop_orders.id');

        $sortColumn = match ($sortBy) {
            'order_number' => 'workshop_orders.order_number',
            'customer_name' => 'customers.first_name',
            'equipment_label' => 'workshop_equipments.identifier',
            'priority' => 'workshop_orders.priority',
            'state_name' => 'workshop_states.name',
            default => 'workshop_orders.created_at',
        };

        $paginator = $query
            ->orderBy($sortColumn, $sortDirection)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (WorkshopOrder $order) => $this->transformOrder($order));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Órdenes obtenidas correctamente.'),
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

    public function create(): View
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        // Si el usuario no tiene compañía (super admin), obtener la primera compañía disponible
        if (! $companyId) {
            $company = \App\Models\Company::query()->first();
            $companyId = $company?->id;
        }

        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Crear orden de trabajo',
            'description' => 'Crea una nueva orden de trabajo para el taller.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Taller')],
            ['label' => gettext('Órdenes de trabajo'), 'url' => route('taller.ordenes_de_trabajo')],
            ['label' => gettext('Crear orden')],
        ];

        return view('Workshop.Orders.Create', [
            'meta' => $meta,
            'breadcrumbItems' => $breadcrumbItems,
        ]);
    }

    public function edit(WorkshopOrder $workshopOrder): View
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        // Si el usuario no tiene compañía (super admin), obtener la primera compañía disponible
        if (! $companyId) {
            $company = \App\Models\Company::query()->first();
            $companyId = $company?->id;
        }

        // Verificar que la orden pertenece a la compañía
        if ($companyId && $workshopOrder->company_id !== $companyId) {
            abort(404);
        }

        $workshopOrder->loadMissing([
            'customer',
            'equipment.brand',
            'equipment.model',
            'category',
            'state',
            'responsible',
            'accessories',
            'notes.user',
            'items.product',
            'services.service',
            'advances',
        ]);

        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Editar orden de trabajo',
            'description' => 'Edita la orden de trabajo del taller.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Taller')],
            ['label' => gettext('Órdenes de trabajo'), 'url' => route('taller.ordenes_de_trabajo')],
            ['label' => $workshopOrder->order_number ?? gettext('Editar orden')],
        ];

        return view('Workshop.Orders.Edit', [
            'meta' => $meta,
            'breadcrumbItems' => $breadcrumbItems,
            'order' => $workshopOrder,
        ]);
    }

    public function show(WorkshopOrder $workshopOrder): View
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        // Si el usuario no tiene compañía (super admin), obtener la primera compañía disponible
        if (! $companyId) {
            $company = \App\Models\Company::query()->first();
            $companyId = $company?->id;
        }

        // Verificar que la orden pertenece a la compañía
        if ($companyId && $workshopOrder->company_id !== $companyId) {
            abort(404);
        }

        $workshopOrder->loadMissing([
            'customer',
            'equipment.brand',
            'equipment.model',
            'category',
            'state',
            'responsible',
            'accessories',
            'notes.user',
            'items.product',
            'services.service',
            'advances',
        ]);

        // Recalcular costos para asegurar que los totales estén actualizados
        $this->recalculateOrderCosts($workshopOrder);
        $workshopOrder->refresh();

        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Ver orden de trabajo',
            'description' => 'Consulta los detalles de la orden de trabajo.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Taller')],
            ['label' => gettext('Órdenes de trabajo'), 'url' => route('taller.ordenes_de_trabajo')],
            ['label' => $workshopOrder->order_number ?? gettext('Ver orden')],
        ];

        return view('Workshop.Orders.Show', [
            'meta' => $meta,
            'breadcrumbItems' => $breadcrumbItems,
            'order' => $workshopOrder,
        ]);
    }

    public function showJson(WorkshopOrder $workshopOrder): JsonResponse
    {
        $workshopOrder->loadMissing([
            'customer',
            'equipment.brand',
            'equipment.model',
            'category',
            'state',
            'responsible',
            'accessories',
            'notes.user',
            'items.product',
            'services.service',
            'advances',
        ]);

        $transformed = $this->transformOrder($workshopOrder);
        
        // Agregar items, services y costos calculados
        $transformed['items'] = $workshopOrder->items()
            ->where('status', 'A')
            ->with('product:id,name,sku')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? '',
                'product_sku' => $item->product?->sku ?? '',
                'quantity' => $item->quantity,
                'unit_price' => (string) $item->unit_price,
                'subtotal' => (string) $item->subtotal,
                'notes' => $item->notes,
            ])
            ->values()
            ->all();

        $transformed['services'] = $workshopOrder->services()
            ->where('status', 'A')
            ->with('service:id,name,description')
            ->get()
            ->map(fn ($service) => [
                'id' => $service->id,
                'service_id' => $service->service_id,
                'service_name' => $service->service?->name ?? '',
                'quantity' => $service->quantity,
                'unit_price' => (string) $service->unit_price,
                'subtotal' => (string) $service->subtotal,
                'notes' => $service->notes,
            ])
            ->values()
            ->all();

        $transformed['total_cost'] = (string) ($workshopOrder->total_cost ?? 0);
        $transformed['total_paid'] = (string) ($workshopOrder->total_paid ?? 0);
        $transformed['balance'] = (string) ($workshopOrder->balance ?? 0);

        return response()->json([
            'status' => 'success',
            'message' => gettext('Orden obtenida correctamente.'),
            'data' => [
                'item' => $transformed,
            ],
        ]);
    }

    public function store(StoreWorkshopOrderRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        // Si el usuario no tiene compañía (super admin), obtener la primera compañía disponible
        if (! $companyId) {
            $company = \App\Models\Company::query()->first();
            $companyId = $company?->id;
            
            if (! $companyId) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', gettext('No hay empresas registradas en el sistema.'));
            }
        }

        $validated = $request->validated();
        $accessoryIds = collect($validated['accessories'] ?? [])
            ->filter()
            ->unique()
            ->values()
            ->all();
        unset($validated['accessories']);

        // Obtener branch_id si no se proporciona (primera sucursal activa de la compañía)
        if (empty($validated['branch_id'])) {
            $branch = Branch::query()
                ->where('company_id', $companyId)
                ->where('status', 'A')
                ->first();
            if ($branch) {
                $validated['branch_id'] = $branch->id;
            }
        }

        $order = new WorkshopOrder($validated);
        $order->company_id = $companyId;
        $order->status = $validated['status'] ?? 'A';
        $order->diagnosis = (bool) ($validated['diagnosis'] ?? false);
        $order->warranty = (bool) ($validated['warranty'] ?? false);
        $order->save();

        $order->accessories()->sync($accessoryIds);

        // Recalcular costos totales iniciales
        $this->recalculateOrderCosts($order);

        return redirect()
            ->route('taller.ordenes.show', $order)
            ->with('success', gettext('La orden se creó correctamente.'));
    }

    public function update(UpdateWorkshopOrderRequest $request, WorkshopOrder $workshopOrder): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        // Si el usuario no tiene compañía (super admin), obtener la primera compañía disponible
        if (! $companyId) {
            $company = \App\Models\Company::query()->first();
            $companyId = $company?->id;
        }

        // Verificar que la orden pertenece a la compañía
        if ($companyId && $workshopOrder->company_id !== $companyId) {
            abort(404);
        }

        $validated = $request->validated();
        
        // Si solo se actualiza el estado (drag and drop), actualizar solo ese campo
        if ($request->has('state_id') && count($request->only(['state_id'])) === 1) {
            $workshopOrder->state_id = $validated['state_id'];
            $workshopOrder->save();
        } else {
            // Actualización completa
            $accessoryIds = collect($validated['accessories'] ?? [])
                ->filter()
                ->unique()
                ->values()
                ->all();
            unset($validated['accessories']);

            $workshopOrder->fill($validated);
            if (array_key_exists('diagnosis', $validated)) {
                $workshopOrder->diagnosis = (bool) $validated['diagnosis'];
            }
            if (array_key_exists('warranty', $validated)) {
                $workshopOrder->warranty = (bool) $validated['warranty'];
            }
            $workshopOrder->save();

            $workshopOrder->accessories()->sync($accessoryIds);
        }

        // Recalcular costos totales
        $this->recalculateOrderCosts($workshopOrder);

        // Si es una petición AJAX, devolver JSON
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => gettext('La orden se actualizó correctamente.'),
                'data' => [
                    'item' => $this->transformOrder($workshopOrder),
                ],
            ]);
        }

        return redirect()
            ->route('taller.ordenes.show', $workshopOrder)
            ->with('success', gettext('La orden se actualizó correctamente.'));
    }

    public function destroy(WorkshopOrder $workshopOrder): JsonResponse
    {
        $deletedId = $workshopOrder->id;
        $workshopOrder->delete();

        return response()->json([
            'status' => 'success',
            'message' => gettext('La orden se eliminó correctamente.'),
            'data' => [
                'deleted_id' => $deletedId,
            ],
        ]);
    }

    public function toggleStatus(Request $request, WorkshopOrder $workshopOrder): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['A', 'I', 'T'])],
        ]);

        $workshopOrder->status = $validated['status'];
        $workshopOrder->save();

        $workshopOrder->loadMissing([
            'customer',
            'equipment.brand',
            'equipment.model',
            'category',
            'state',
            'responsible',
            'accessories',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => gettext('El estado se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformOrder($workshopOrder),
            ],
        ]);
    }

    public function options(): JsonResponse
    {
        $priorities = [
            ['value' => 'Baja', 'label' => gettext('Baja')],
            ['value' => 'Normal', 'label' => gettext('Normal')],
            ['value' => 'Alta', 'label' => gettext('Alta')],
            ['value' => 'Urgente', 'label' => gettext('Urgente')],
        ];


        $booleanOptions = [
            ['value' => 1, 'label' => gettext('Sí')],
            ['value' => 0, 'label' => gettext('No')],
        ];

        $currencyOptions = [
            ['value' => 'USD', 'label' => 'US$'],
            ['value' => 'EUR', 'label' => '€'],
            ['value' => 'MXN', 'label' => 'MX$'],
            ['value' => 'COP', 'label' => 'COP$'],
            ['value' => 'CLP', 'label' => 'CLP$'],
        ];

        return response()->json([
            'status' => 'success',
            'message' => gettext('Opciones obtenidas correctamente.'),
            'data' => [
                'priorities' => $priorities,
                'boolean_options' => $booleanOptions,
                'currency_options' => $currencyOptions,
            ],
        ]);
    }

    public function searchCustomers(Request $request): JsonResponse
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
                'status' => 'success',
                'message' => gettext('Clientes obtenidos correctamente.'),
                'data' => [
                    'items' => [],
                ],
            ]);
        }

        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();
        $limit = (int) $request->integer('limit', 10);
        $limit = $limit > 0 ? min($limit, 30) : 10;

        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
        $grammar = DB::query()->getGrammar();

        $query = Customer::query()
            ->where('company_id', $companyId)
            ->where('status', 'A')
            ->orderBy('first_name');

        if ($search->isNotEmpty()) {
            $tokens = collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();

            $searchableColumns = [
                'first_name',
                'last_name',
                'business_name',
                'document_number',
                'email',
            ];

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

        $customers = $query->limit($limit)->get();

        $items = $customers->map(fn (Customer $customer) => [
            'id' => $customer->id,
            'name' => $customer->display_name,
            'display_name' => $customer->display_name,
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'business_name' => $customer->business_name,
            'document_number' => $customer->document_number,
            'email' => $customer->email,
            'type' => $customer->customer_type,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => gettext('Clientes obtenidos correctamente.'),
            'data' => [
                'items' => $items,
            ],
        ]);
    }

    public function searchEquipments(Request $request): JsonResponse
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
                'status' => 'success',
                'message' => gettext('Equipos obtenidos correctamente.'),
                'data' => [
                    'items' => [],
                ],
            ]);
        }

        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();
        $limit = (int) $request->integer('limit', 10);
        $limit = $limit > 0 ? min($limit, 30) : 10;

        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
        $grammar = DB::query()->getGrammar();

        $query = WorkshopEquipment::query()
            ->where('workshop_equipments.company_id', $companyId)
            ->where('workshop_equipments.status', 'A')
            ->with(['brand', 'model'])
            ->orderBy('identifier');

        if ($search->isNotEmpty()) {
            $tokens = collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();

            $searchableColumns = [
                'workshop_equipments.identifier',
                'workshop_equipments.note',
                'workshop_brands.name',
                'workshop_models.name',
            ];

            $query
                ->leftJoin('workshop_brands', 'workshop_equipments.brand_id', '=', 'workshop_brands.id')
                ->leftJoin('workshop_models', 'workshop_equipments.model_id', '=', 'workshop_models.id')
                ->where(function ($outer) use ($tokens, $searchableColumns, $accentInsensitiveCollation, $grammar): void {
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

        $equipments = $query->limit($limit)->get();

        $items = $equipments->map(function (WorkshopEquipment $equipment) {
            $parts = collect([
                $equipment->brand?->name,
                $equipment->model?->name,
            ])->filter()->implode(' · ');

            $label = trim(implode(' · ', Arr::whereNotNull([
                $parts !== '' ? $parts : null,
                $equipment->identifier,
            ])));

            if ($label === '') {
                $label = $equipment->identifier ?? gettext('Equipo sin identificador');
            }

            return [
                'id' => $equipment->id,
                'label' => $label,
                'note' => $equipment->note,
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => gettext('Equipos obtenidos correctamente.'),
            'data' => [
                'items' => $items,
            ],
        ]);
    }

    public function searchResponsibles(Request $request): JsonResponse
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
                'status' => 'success',
                'message' => gettext('Usuarios obtenidos correctamente.'),
                'data' => [
                    'items' => [],
                ],
            ]);
        }

        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();
        $limit = (int) $request->integer('limit', 10);
        $limit = $limit > 0 ? min($limit, 30) : 10;

        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
        $grammar = DB::query()->getGrammar();

        $query = User::query()
            ->where('company_id', $companyId)
            ->where('status', 'A')
            ->with('role')
            ->orderBy('first_name');

        if ($search->isNotEmpty()) {
            $tokens = collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();

            $searchableColumns = [
                'first_name',
                'last_name',
                'email',
                'document_number',
            ];

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

        $responsibles = $query->limit($limit)->get();

        $items = $responsibles->map(fn (User $responsible) => [
            'id' => $responsible->id,
            'name' => $responsible->display_name,
            'first_name' => $responsible->first_name,
            'last_name' => $responsible->last_name,
            'full_name' => $responsible->display_name,
            'document_number' => $responsible->document_number,
            'email' => $responsible->email,
            'role' => $responsible->role?->display_name,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => gettext('Usuarios obtenidos correctamente.'),
            'data' => [
                'items' => $items,
            ],
        ]);
    }

    private function transformOrder(WorkshopOrder $order): array
    {
        $order->loadMissing([
            'customer',
            'equipment.brand',
            'equipment.model',
            'category',
            'state',
            'responsible',
            'accessories',
        ]);

        $customer = $order->customer;
        $responsible = $order->responsible;
        $equipment = $order->equipment;

        $customerName = $customer?->display_name ?? '';
        $customerDocument = $customer?->document_number ?? null;

        $equipmentParts = collect([
            $equipment?->brand?->name,
            $equipment?->model?->name,
        ])->filter()->implode(' · ');
        $equipmentLabel = trim(implode(' · ', Arr::whereNotNull([
            $equipmentParts !== '' ? $equipmentParts : null,
            $equipment?->identifier,
        ])));

        if ($equipmentLabel === '') {
            $equipmentLabel = $equipment?->identifier ?? '';
        }

        $promisedAt = $order->promised_at instanceof Carbon
            ? $order->promised_at
            : ($order->promised_at ? Carbon::parse($order->promised_at) : null);

        $createdAt = $order->created_at instanceof Carbon
            ? $order->created_at
            : ($order->created_at ? Carbon::parse($order->created_at) : null);

        $accessories = $order->accessories
            ->map(fn (WorkshopAccessory $accessory) => [
                'id' => $accessory->id,
                'name' => $accessory->name,
            ])
            ->values();

        return [
            'id' => $order->id,
            'company_id' => $order->company_id,
            'branch_id' => $order->branch_id,
            'order_number' => $order->order_number ?? '',
            'category_id' => $order->category_id,
            'category_name' => $order->category?->name,
            'state_id' => $order->state_id,
            'state_name' => $order->state?->name,
            'customer_id' => $order->customer_id,
            'customer_name' => $customerName,
            'customer_document' => $customerDocument,
            'equipment_id' => $order->equipment_id,
            'equipment_label' => $equipmentLabel,
            'responsible_user_id' => $order->responsible_user_id,
            'responsible_name' => $responsible?->display_name,
            'priority' => $order->priority,
            'status' => $order->status,
            'status_label' => $order->status_label,
            'note' => $order->note,
            'diagnosis' => (bool) $order->diagnosis,
            'diagnosis_label' => $order->diagnosis ? gettext('Sí') : gettext('No'),
            'warranty' => (bool) $order->warranty,
            'warranty_label' => $order->warranty ? gettext('Sí') : gettext('No'),
            'equipment_password' => $order->equipment_password,
            'promised_at' => $promisedAt?->toIso8601String(),
            'promised_at_formatted' => $promisedAt?->translatedFormat('d M Y H:i'),
            'budget_currency' => $order->budget_currency,
            'budget_amount' => $order->budget_amount !== null ? (string) $order->budget_amount : null,
            'budget_amount_formatted' => $this->formatCurrency($order->budget_currency, $order->budget_amount),
            'advance_currency' => $order->advance_currency,
            'advance_amount' => $order->advance_amount !== null ? (string) $order->advance_amount : null,
            'advance_amount_formatted' => $this->formatCurrency($order->advance_currency, $order->advance_amount),
            'accessories' => $accessories,
            'label_url' => route('workshop.orders.label', $order),
            'ticket_url' => route('workshop.orders.ticket', $order),
            'created_at' => $createdAt?->toIso8601String(),
            'created_at_formatted' => $createdAt?->translatedFormat('d M Y H:i'),
            'updated_at' => optional($order->updated_at)->toIso8601String(),
        ];
    }

    private function resolveAccentInsensitiveCollation(): string
    {
        $defaultConnection = config('database.default');
        $connectionConfig = config("database.connections.{$defaultConnection}", []);

        return $connectionConfig['search_collation']
            ?? $connectionConfig['collation']
            ?? 'utf8mb4_unicode_ci';
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

    private function formatCurrency(?string $currency, $amount): ?string
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        $numeric = is_numeric($amount) ? (float) $amount : null;

        if ($numeric === null) {
            return null;
        }

        $symbol = match (Str::upper($currency ?? '')) {
            'USD' => 'US$',
            'EUR' => '€',
            'MXN' => 'MX$',
            'COP' => 'COP$',
            'CLP' => 'CLP$',
            default => $currency ?? '',
        };

        $formatted = number_format($numeric, 2, '.', ',');

        return trim(sprintf('%s %s', $symbol, $formatted));
    }

    public function label(WorkshopOrder $workshopOrder): Response
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        // Verificar que la orden pertenece a la compañía
        if ($companyId && $workshopOrder->company_id !== $companyId) {
            abort(404);
        }

        $workshopOrder->loadMissing(['customer']);

        $barcodeValue = strtoupper((string) $workshopOrder->order_number);

        if ($barcodeValue === '') {
            abort(404, gettext('La orden no tiene número configurado.'));
        }

        $generator = new BarcodeGeneratorPNG();
        $barcodeImage = base64_encode(
            $generator->getBarcode($barcodeValue, BarcodeGeneratorPNG::TYPE_CODE_128)
        );

        $html = view('Workshop.Orders.Label', [
            'order' => $workshopOrder,
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
            'Content-Disposition' => 'inline; filename="label-'.$barcodeValue.'.pdf"',
            'Content-Length' => strlen($output),
        ]);
    }

    public function ticket(WorkshopOrder $workshopOrder): Response
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        // Verificar que la orden pertenece a la compañía
        if ($companyId && $workshopOrder->company_id !== $companyId) {
            abort(404);
        }

        // Recalcular costos antes de generar el ticket
        $this->recalculateOrderCosts($workshopOrder);
        $workshopOrder->refresh();

        $workshopOrder->loadMissing([
            'customer',
            'equipment.brand',
            'equipment.model',
            'category',
            'state',
            'responsible',
            'branch',
            'items.product',
            'services.service',
            'advances.paymentMethod',
        ]);

        // Obtener información de la compañía
        $company = \App\Models\Company::find($workshopOrder->company_id);

        $html = view('Workshop.Orders.Ticket', [
            'order' => $workshopOrder,
            'company' => $company,
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->setDefaultFont('Helvetica');
        $options->set('isPhpEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('marginTop', 0);
        $options->set('marginRight', 0);
        $options->set('marginBottom', 0);
        $options->set('marginLeft', 0);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        // Tamaño del ticket: 3.15 × 12.15 pulgadas
        // 1 pulgada = 72 puntos
        // Ancho: 3.15 × 72 = 226.8 puntos
        // Alto: 12.15 × 72 = 874.8 puntos
        $dompdf->setPaper([0, 0, 226.8, 874.8], 'portrait');
        $dompdf->render();

        $output = $dompdf->output();
        if (! empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
            header_remove('Transfer-Encoding');
        }

        return response($output, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="ticket-'.$workshopOrder->order_number.'.pdf"',
            'Content-Length' => strlen($output),
        ]);
    }
}


