<?php

namespace App\Http\Controllers\Workshop;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workshop\Advances\StoreWorkshopOrderAdvanceRequest;
use App\Http\Requests\Workshop\Advances\UpdateWorkshopOrderAdvanceRequest;
use App\Models\WorkshopOrder;
use App\Models\WorkshopOrderAdvance;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkshopOrderAdvanceController extends Controller
{
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Abonos de órdenes',
            'description' => 'Gestiona los abonos recibidos de las órdenes de trabajo del taller.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Taller')],
            ['label' => gettext('Abonos')],
        ];

        return view('Workshop.Advances', [
            'meta' => $meta,
            'breadcrumbItems' => $breadcrumbItems,
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
                    'message' => gettext('Abonos obtenidos correctamente.'),
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

        $sortBy = $request->string('sort_by', 'payment_date')->toString();
        $sortDirection = Str::lower($request->string('sort_direction', 'desc')->toString()) === 'asc' ? 'asc' : 'desc';
        $perPage = (int) $request->integer('per_page', 10);
        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();

        $allowedSorts = [
            'payment_date',
            'amount',
            'order_number',
            'customer_name',
        ];

        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'payment_date';
        }

        $allowedPerPage = [5, 10, 20, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
        $grammar = DB::query()->getGrammar();

        $query = WorkshopOrderAdvance::query()
            ->select('workshop_order_advances.*')
            ->with(['order.customer'])
            ->join('workshop_orders', 'workshop_order_advances.order_id', '=', 'workshop_orders.id')
            ->leftJoin('customers', 'workshop_orders.customer_id', '=', 'customers.id')
            ->where('workshop_order_advances.company_id', $companyId)
            ->where('workshop_order_advances.status', 'A');

        if ($search->isNotEmpty()) {
            $tokens = collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();

            $query->where(function ($builder) use ($tokens, $accentInsensitiveCollation, $grammar): void {
                    $tokens->each(function (string $token) use ($builder, $accentInsensitiveCollation, $grammar): void {
                        $builder->where(function ($inner) use ($token, $accentInsensitiveCollation, $grammar): void {
                            $customerNameWrapped = $grammar->wrap('customers.first_name');
                            $customerLastNameWrapped = $grammar->wrap('customers.last_name');
                            $orderNumberWrapped = $grammar->wrap('workshop_orders.order_number');

                            $inner->orWhereRaw(
                                "LOWER(CONVERT({$customerNameWrapped} USING utf8mb4) COLLATE {$accentInsensitiveCollation}) LIKE ?",
                                ["%{$token}%"]
                            )->orWhereRaw(
                                "LOWER(CONVERT({$customerLastNameWrapped} USING utf8mb4) COLLATE {$accentInsensitiveCollation}) LIKE ?",
                                ["%{$token}%"]
                            )->orWhereRaw(
                                "LOWER(CONVERT({$orderNumberWrapped} USING utf8mb4) COLLATE {$accentInsensitiveCollation}) LIKE ?",
                                ["%{$token}%"]
                            );
                        });
                    });
            });
        }

        $sortColumn = match ($sortBy) {
            'order_number' => 'workshop_orders.order_number',
            'customer_name' => 'customers.first_name',
            'amount' => 'workshop_order_advances.amount',
            default => 'workshop_order_advances.payment_date',
        };

        $query->orderBy($sortColumn, $sortDirection);

        $paginator = $query->paginate($perPage)->withQueryString()->through(fn (WorkshopOrderAdvance $advance) => $this->transformAdvance($advance));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Abonos obtenidos correctamente.'),
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

    public function show(WorkshopOrderAdvance $workshopOrderAdvance): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        // Si el usuario no tiene compañía (super admin), permitir acceso a todos los abonos
        // Si el usuario tiene compañía, verificar que el abono pertenezca a su compañía
        // Solo validamos si el usuario tiene company_id (no es super admin)
        if ($companyId !== null) {
            if ($workshopOrderAdvance->company_id !== $companyId) {
                return response()->json([
                    'status' => 'error',
                    'message' => gettext('El abono solicitado no existe.'),
                ], 404);
            }
        }

        $workshopOrderAdvance->load(['order.customer']);

        return response()->json([
            'status' => 'success',
            'message' => gettext('Abono obtenido correctamente.'),
            'data' => [
                'item' => $this->transformAdvance($workshopOrderAdvance),
            ],
        ]);
    }

    public function store(StoreWorkshopOrderAdvanceRequest $request): JsonResponse
    {
        $user = Auth::user();

        $advance = new WorkshopOrderAdvance($request->validated());
        $advance->company()->associate($user?->company_id);
        $advance->status = $request->input('status', 'A');
        $advance->save();

        $advance->load(['order.customer']);

        return response()->json([
            'status' => 'success',
            'message' => gettext('El abono se creó correctamente.'),
            'data' => [
                'item' => $this->transformAdvance($advance->refresh()),
            ],
        ]);
    }

    public function update(UpdateWorkshopOrderAdvanceRequest $request, WorkshopOrderAdvance $workshopOrderAdvance): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        // Si el usuario no tiene compañía (super admin), permitir acceso a todos los abonos
        // Si el usuario tiene compañía, verificar que el abono pertenezca a su compañía
        // Solo validamos si el usuario tiene company_id (no es super admin)
        if ($companyId !== null) {
            if ($workshopOrderAdvance->company_id !== $companyId) {
                return response()->json([
                    'status' => 'error',
                    'message' => gettext('El abono solicitado no existe.'),
                ], 404);
            }
        }

        $workshopOrderAdvance->fill($request->validated());
        if ($request->has('status')) {
            $workshopOrderAdvance->status = $request->input('status');
        }
        $workshopOrderAdvance->save();

        $workshopOrderAdvance->load(['order.customer']);

        return response()->json([
            'status' => 'success',
            'message' => gettext('El abono se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformAdvance($workshopOrderAdvance->refresh()),
            ],
        ]);
    }

    public function destroy(WorkshopOrderAdvance $workshopOrderAdvance): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        // Si el usuario no tiene compañía (super admin), permitir acceso a todos los abonos
        // Si el usuario tiene compañía, verificar que el abono pertenezca a su compañía
        // Solo validamos si el usuario tiene company_id (no es super admin)
        if ($companyId !== null) {
            if ($workshopOrderAdvance->company_id !== $companyId) {
                return response()->json([
                    'status' => 'error',
                    'message' => gettext('El abono solicitado no existe.'),
                ], 404);
            }
        }

        $workshopOrderAdvance->delete();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El abono se eliminó correctamente.'),
        ]);
    }

    public function toggleStatus(Request $request, WorkshopOrderAdvance $workshopOrderAdvance): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        // Si el usuario no tiene compañía (super admin), permitir acceso a todos los abonos
        // Si el usuario tiene compañía, verificar que el abono pertenezca a su compañía
        // Solo validamos si el usuario tiene company_id (no es super admin)
        if ($companyId !== null) {
            if ($workshopOrderAdvance->company_id !== $companyId) {
                return response()->json([
                    'status' => 'error',
                    'message' => gettext('El abono solicitado no existe.'),
                ], 404);
            }
        }

        $request->validate([
            'status' => ['required', 'string', 'in:A,I'],
        ]);

        $workshopOrderAdvance->status = $request->input('status');
        $workshopOrderAdvance->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El estado del abono se modificó correctamente.'),
            'data' => [
                'item' => $this->transformAdvance($workshopOrderAdvance->refresh()->load(['order.customer'])),
            ],
        ]);
    }

    public function options(): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;

        // Si el usuario no tiene compañía (super admin), obtener la primera compañía disponible
        if (! $companyId) {
            $company = \App\Models\Company::query()->first();
            $companyId = $company?->id;
        }

        $orders = WorkshopOrder::query()
            ->when($companyId, fn ($builder) => $builder->where('company_id', $companyId))
            ->where('status', 'A')
            ->with(['customer'])
            ->orderBy('order_number', 'desc')
            ->limit(100)
            ->get()
            ->map(fn (WorkshopOrder $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number ?? '',
                'customer_name' => $order->customer?->display_name ?? '',
            ]);

        // Métodos de pago estáticos (EFECTIVO, TRANSFERENCIA, CHEQUE)
        $paymentMethods = collect([
            ['id' => 'EFECTIVO', 'name' => gettext('EFECTIVO')],
            ['id' => 'TRANSFERENCIA', 'name' => gettext('TRANSFERENCIA')],
            ['id' => 'CHEQUE', 'name' => gettext('CHEQUE')],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => gettext('Opciones obtenidas correctamente.'),
            'data' => [
                'orders' => $orders,
                'payment_methods' => $paymentMethods,
            ],
        ]);
    }

    public function searchOrders(Request $request): JsonResponse
    {
        $user = Auth::user();
        $companyId = $user?->company_id;
        
        // Si el usuario no tiene compañía (super admin), obtener la primera compañía disponible
        if (! $companyId) {
            $company = \App\Models\Company::query()->first();
            $companyId = $company?->id;
        }
        
        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();

        if ($search->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => gettext('Órdenes obtenidas correctamente.'),
                'data' => [
                    'items' => [],
                ],
            ]);
        }

        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
        $grammar = DB::query()->getGrammar();

        $tokens = collect(explode(' ', Str::ascii(Str::lower($search))))
            ->filter()
            ->unique()
            ->values();

        $query = WorkshopOrder::query()
            ->select('workshop_orders.id', 'workshop_orders.order_number', 'workshop_orders.work_summary')
            ->join('customers', 'workshop_orders.customer_id', '=', 'customers.id')
            ->when($companyId, fn ($builder) => $builder->where('workshop_orders.company_id', $companyId))
            ->where('workshop_orders.status', 'A')
            ->when($tokens->isNotEmpty(), function ($builder) use ($tokens, $accentInsensitiveCollation, $grammar): void {
                $builder->where(function ($outer) use ($tokens, $accentInsensitiveCollation, $grammar): void {
                    $tokens->each(function (string $token) use ($outer, $accentInsensitiveCollation, $grammar): void {
                        $outer->where(function ($inner) use ($token, $accentInsensitiveCollation, $grammar): void {
                            $customerFirstNameWrapped = $grammar->wrap('customers.first_name');
                            $customerLastNameWrapped = $grammar->wrap('customers.last_name');
                            $orderNumberWrapped = $grammar->wrap('workshop_orders.order_number');

                            $inner->orWhereRaw(
                                "LOWER(CONVERT({$customerFirstNameWrapped} USING utf8mb4) COLLATE {$accentInsensitiveCollation}) LIKE ?",
                                ["%{$token}%"]
                            )->orWhereRaw(
                                "LOWER(CONVERT({$customerLastNameWrapped} USING utf8mb4) COLLATE {$accentInsensitiveCollation}) LIKE ?",
                                ["%{$token}%"]
                            )->orWhereRaw(
                                "LOWER(CONVERT({$orderNumberWrapped} USING utf8mb4) COLLATE {$accentInsensitiveCollation}) LIKE ?",
                                ["%{$token}%"]
                            );
                        });
                    });
                });
            })
            ->orderBy('workshop_orders.order_number', 'desc')
            ->limit(20);

        $items = $query->get()->map(function (WorkshopOrder $order) {
            $order->load(['customer']);

            return [
                'id' => $order->id,
                'order_number' => $order->order_number ?? '',
                'customer_name' => $order->customer?->display_name ?? '',
                'label' => ($order->order_number ?? 'N/A') . ' - ' . ($order->customer?->display_name ?? ''),
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => gettext('Órdenes obtenidas correctamente.'),
            'data' => [
                'items' => $items,
            ],
        ]);
    }

    private function transformAdvance(WorkshopOrderAdvance $advance): array
    {
        if (!$advance->relationLoaded('order')) {
            $advance->load(['order.customer']);
        }
        $order = $advance->order;
        $customer = $order?->customer;
        $customerName = trim(collect([$customer?->first_name, $customer?->last_name])->filter()->implode(' '));
        
        if ($customerName === '') {
            $customerName = $customer?->display_name ?? '';
        }

        $paymentDate = $advance->payment_date instanceof Carbon
            ? $advance->payment_date
            : ($advance->payment_date ? Carbon::parse($advance->payment_date) : null);

        $createdAt = $advance->created_at instanceof Carbon
            ? $advance->created_at
            : ($advance->created_at ? Carbon::parse($advance->created_at) : null);

        // Obtener el nombre del método de pago estático
        $paymentMethodName = $this->getPaymentMethodName($advance->payment_method_id);

        return [
            'id' => $advance->id,
            'company_id' => $advance->company_id,
            'order_id' => $advance->order_id,
            'order_number' => $order?->order_number ?? '',
            'customer_name' => $customerName,
            'currency' => $advance->currency,
            'amount' => $advance->amount !== null ? (string) $advance->amount : null,
            'amount_formatted' => number_format($advance->amount, 2, '.', ','),
            'payment_date' => $paymentDate?->toIso8601String(),
            'payment_date_formatted' => $paymentDate?->translatedFormat('d M Y'),
            'payment_method_id' => $advance->payment_method_id,
            'payment_method_name' => $paymentMethodName,
            'reference' => $advance->reference,
            'notes' => $advance->notes,
            'status' => $advance->status,
            'status_label' => $advance->status_label,
            'created_at' => $createdAt?->toIso8601String(),
            'created_at_formatted' => $createdAt?->translatedFormat('d M Y H:i'),
            'updated_at' => optional($advance->updated_at)->toIso8601String(),
        ];
    }

    /**
     * Obtiene el nombre del método de pago basado en el ID estático.
     *
     * @param string|null $paymentMethodId
     * @return string
     */
    private function getPaymentMethodName(?string $paymentMethodId): string
    {
        if (!$paymentMethodId) {
            return '';
        }

        return match ($paymentMethodId) {
            'EFECTIVO' => gettext('EFECTIVO'),
            'TRANSFERENCIA' => gettext('TRANSFERENCIA'),
            'CHEQUE' => gettext('CHEQUE'),
            default => $paymentMethodId,
        };
    }

    private function resolveAccentInsensitiveCollation(): string
    {
        $defaultConnection = config('database.default');
        $connectionConfig = config("database.connections.{$defaultConnection}", []);

        return $connectionConfig['search_collation']
            ?? $connectionConfig['collation']
            ?? 'utf8mb4_0900_ai_ci';
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
}

