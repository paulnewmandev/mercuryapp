<?php

namespace App\Http\Controllers\Inventory;

use App\Contracts\SeoMetaManagerContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\Providers\StoreProviderRequest;
use App\Http\Requests\Inventory\Providers\UpdateProviderRequest;
use App\Models\Provider;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProviderController extends Controller
{
    public function __construct(private readonly SeoMetaManagerContract $seoMetaManager)
    {
    }

    public function index(): View
    {
        $meta = $this->seoMetaManager->compose([
            'title' => 'MercuryApp · Proveedores',
            'description' => 'Gestiona el directorio de proveedores de tu empresa.',
        ])->toArray();

        $breadcrumbItems = [
            ['label' => gettext('Panel principal'), 'url' => route('dashboard')],
            ['label' => gettext('Inventario'), 'url' => route('configuration.warehouses')],
            ['label' => gettext('Proveedores')],
        ];

        return view('Inventory.Providers', compact('meta', 'breadcrumbItems'));
    }

    public function list(Request $request): JsonResponse
    {
        $sortBy = $request->string('sort_by', 'created_at')->toString();
        $sortDirection = Str::lower($request->string('sort_direction', 'desc')->toString()) === 'asc' ? 'asc' : 'desc';
        $perPage = (int) $request->integer('per_page', 10);
        $search = Str::of($request->string('search')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();
        $providerTypeFilter = Str::lower($request->string('provider_type')->toString());
        $providerTypeFilter = in_array($providerTypeFilter, ['individual', 'business'], true) ? $providerTypeFilter : null;

        $allowedSorts = ['display_name', 'provider_type', 'identification_number', 'email', 'phone_number', 'status', 'created_at'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        $allowedPerPage = [5, 10, 20, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
        $searchableColumns = [
            'providers.first_name',
            'providers.last_name',
            'providers.business_name',
            'providers.identification_number',
            'providers.email',
            'providers.phone_number',
        ];
        $grammar = DB::query()->getGrammar();

        $tokens = $search->isEmpty()
            ? collect()
            : collect(explode(' ', Str::ascii(Str::lower($search))))
                ->filter()
                ->unique()
                ->values();

        $query = Provider::query()
            ->when($providerTypeFilter, fn ($builder) => $builder->where('provider_type', $providerTypeFilter))
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

                            $statusMatches = collect([
                                'A' => Str::ascii(Str::lower(gettext('Activo'))),
                                'I' => Str::ascii(Str::lower(gettext('Inactivo'))),
                                'T' => Str::ascii(Str::lower(gettext('En papelera'))),
                            ])->filter(fn ($label) => str_contains($label, $token));

                            if ($statusMatches->isNotEmpty()) {
                                $inner->orWhere(function ($statusQuery) use ($statusMatches): void {
                                    foreach ($statusMatches as $statusCode => $label) {
                                        $statusQuery->orWhere('status', $statusCode);
                                    }
                                });
                            }

                            $typeMatches = collect([
                                'individual' => Str::ascii(Str::lower(gettext('Persona natural'))),
                                'business' => Str::ascii(Str::lower(gettext('Empresa'))),
                            ])->filter(fn ($label) => str_contains($label, $token));

                            if ($typeMatches->isNotEmpty()) {
                                $inner->orWhere(function ($typeQuery) use ($typeMatches): void {
                                    foreach ($typeMatches as $type => $label) {
                                        $typeQuery->orWhere('provider_type', $type);
                                    }
                                });
                            }
                        });
                    });
                });
            })
            ->when($sortBy === 'display_name', function ($builder) use ($sortDirection): void {
                $builder
                    ->orderByRaw("CASE WHEN provider_type = 'business' THEN business_name ELSE CONCAT_WS(' ', first_name, last_name) END {$sortDirection}");
            }, fn ($builder) => $builder->orderBy($sortBy, $sortDirection));

        $paginator = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Provider $provider) => $this->transformProvider($provider));

        return response()->json([
            'status' => 'success',
            'message' => gettext('Proveedores obtenidos correctamente.'),
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

    public function show(Provider $provider): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => gettext('Proveedor obtenido correctamente.'),
            'data' => [
                'item' => $this->transformProvider($provider),
            ],
        ]);
    }

    public function store(StoreProviderRequest $request): JsonResponse
    {
        $user = Auth::user();

        $provider = new Provider($request->validated());
        $provider->company()->associate($user->company_id);
        $provider->status = $provider->status ?? 'A';
        $provider->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El proveedor se creó correctamente.'),
            'data' => [
                'item' => $this->transformProvider($provider->refresh()),
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateProviderRequest $request, Provider $provider): JsonResponse
    {
        $provider->fill($request->validated());
        $provider->save();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El proveedor se actualizó correctamente.'),
            'data' => [
                'item' => $this->transformProvider($provider->refresh()),
            ],
        ]);
    }

    public function destroy(Provider $provider): JsonResponse
    {
        $deletedId = $provider->id;
        $provider->delete();

        return response()->json([
            'status' => 'success',
            'message' => gettext('El proveedor se eliminó correctamente.'),
            'data' => [
                'deleted_id' => $deletedId,
            ],
        ]);
    }

    public function toggleStatus(Request $request, Provider $provider): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['A', 'I', 'T'])],
        ]);

        $provider->status = $validated['status'];
        $provider->save();

        $message = match ($validated['status']) {
            'A' => gettext('El proveedor se activó correctamente.'),
            'I' => gettext('El proveedor se desactivó correctamente.'),
            'T' => gettext('El proveedor se movió a la papelera.'),
            default => gettext('Estado actualizado correctamente.'),
        };

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => [
                'item' => $this->transformProvider($provider->refresh()),
            ],
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $search = Str::of($request->string('q')->toString())
            ->replaceMatches('/\s+/', ' ')
            ->trim();

        $accentInsensitiveCollation = $this->resolveAccentInsensitiveCollation();
        $grammar = DB::query()->getGrammar();

        $query = Provider::query()
            ->where('status', 'A')
            ->when($search->isNotEmpty(), function ($builder) use ($search, $accentInsensitiveCollation, $grammar): void {
                $token = Str::ascii(Str::lower($search));
                $builder->where(function ($inner) use ($token, $accentInsensitiveCollation, $grammar): void {
                    foreach (['business_name', 'first_name', 'last_name', 'identification_number'] as $column) {
                        $wrapped = $grammar->wrap("providers.{$column}");
                        $inner->orWhereRaw(
                            "LOWER(CONVERT({$wrapped} USING utf8mb4) COLLATE {$accentInsensitiveCollation}) LIKE ?",
                            ["%{$token}%"]
                        );
                    }
                });
            })
            ->orderBy('provider_type')
            ->orderBy('business_name')
            ->orderBy('first_name')
            ->limit(50);

        $items = $query->get()
            ->map(fn (Provider $provider) => [
                'id' => $provider->id,
                'label' => $provider->display_name,
                'identification' => $provider->identification_number,
            ])
            ->values();

        return response()->json([
            'status' => 'success',
            'message' => gettext('Opciones de proveedores obtenidas correctamente.'),
            'data' => [
                'items' => $items,
            ],
        ]);
    }

    private function transformProvider(Provider $provider): array
    {
        $createdAt = $provider->created_at instanceof Carbon
            ? $provider->created_at->timezone(config('app.timezone', 'UTC'))
            : null;

        $typeLabel = $provider->provider_type === 'business'
            ? gettext('Empresa')
            : gettext('Persona natural');

        $identificationTypeLabel = match ($provider->identification_type) {
            'RUC' => gettext('RUC'),
            'CEDULA' => gettext('Cédula'),
            'PASAPORTE' => gettext('Pasaporte'),
            default => $provider->identification_type,
        };

        return [
            'id' => $provider->id,
            'provider_type' => $provider->provider_type,
            'provider_type_label' => $typeLabel,
            'identification_type' => $provider->identification_type,
            'identification_type_label' => $identificationTypeLabel,
            'identification_number' => $provider->identification_number,
            'first_name' => $provider->first_name,
            'last_name' => $provider->last_name,
            'business_name' => $provider->business_name,
            'display_name' => $provider->display_name,
            'email' => $provider->email,
            'phone_number' => $provider->phone_number,
            'status' => $provider->status,
            'status_label' => $provider->status_label,
            'created_at' => optional($provider->created_at)->toIso8601String(),
            'created_at_label' => $createdAt ? $createdAt->format('d/m/Y H:i') : gettext('No especifica'),
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
}

